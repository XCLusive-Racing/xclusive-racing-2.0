<?php

namespace App\Services;

use App\Jobs\SyncDiscordRankRole;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\User;
use Carbon\CarbonInterval;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

// XCL memberships (config/memberships.php plans) through Mollie's recurring payments,
// talking to the Mollie REST API directly (config('services.mollie.key')):
//   1. checkout(): a Mollie customer + a "first" payment for a plan, which also sets up a
//      mandate.
//   2. Once that payment is paid (webhook, the return page or the sync command, all via
//      handlePayment()): the membership is on that plan, paid up for one interval, and a
//      Mollie subscription charges the same mandate every interval after. Switching plans
//      is the same checkout: the old plan's subscription is stopped once the new one is paid.
//   3. Every paid recurring payment of the running subscription extends paid_until.
//   4. cancel(): stops the subscription; the paid period still runs out.
// The webhook is only a "something changed" ping — every payment is fetched back from
// Mollie, never trusted from the request. Nothing here grants anything directly: the
// perks follow User::hasTier(), which reads Membership::isPaidUp().
class MembershipService
{
    private const API = 'https://api.mollie.com/v2/';

    public function checkout(User $user, string $plan = 'supporter'): string
    {
        if (! array_key_exists($plan, config('memberships.plans')) || ! Membership::isPurchasable($plan)) {
            throw new InvalidArgumentException("Plan [{$plan}] can't be bought.");
        }

        $membership = Membership::firstOrCreate(['user_id' => $user->id]);

        // Mollie test and live customers are separate: a test-mode membership (from before
        // launch) starts over with a new live customer instead of reusing test IDs.
        if ($membership->mode && $membership->mode !== Membership::currentMode()) {
            $membership->update([
                'mollie_customer_id' => null, 'mollie_mandate_id' => null, 'mollie_subscription_id' => null,
                'status' => 'pending', 'paid_until' => null, 'canceled_at' => null,
            ]);
        }
        $membership->update(['mode' => Membership::currentMode()]);

        if (! $membership->mollie_customer_id) {
            $customer = $this->api()->post('customers', [
                'name' => $user->name,
                'email' => $user->email,
                'metadata' => ['user_id' => $user->id],
            ])->throw()->json();
            $membership->update(['mollie_customer_id' => $customer['id']]);
        }

        $payment = $this->api()->post('payments', array_filter([
            'amount' => $this->amount($plan),
            'description' => config("memberships.plans.{$plan}.name").' — first '.config('memberships.interval'),
            'customerId' => $membership->mollie_customer_id,
            'sequenceType' => 'first',
            'redirectUrl' => route('memberships.return'),
            'webhookUrl' => $this->webhookUrl(),
            'metadata' => ['user_id' => $user->id, 'plan' => $plan],
        ]))->throw()->json();

        $this->record($user->id, $payment, $plan);

        return $payment['_links']['checkout']['href'];
    }

    // Fetches the payment from Mollie and applies it. Safe to call any number of times
    // for the same payment (a paid payment extends the membership exactly once).
    public function handlePayment(string $paymentId): ?MembershipPayment
    {
        $payment = $this->api()->get('payments/'.$paymentId)->throw()->json();

        $membership = Membership::where('mollie_customer_id', $payment['customerId'] ?? null)->first();
        if (! $membership) {
            return null; // not one of ours
        }

        $record = DB::transaction(function () use ($payment, $membership) {
            $record = $this->record($membership->user_id, $payment, $membership->plan);
            $record = MembershipPayment::whereKey($record->id)->lockForUpdate()->first();

            if ($record->status !== 'paid' || $record->applied_at) {
                return $record;
            }

            $membership = Membership::whereKey($membership->id)->lockForUpdate()->first();
            $interval = CarbonInterval::make(config('memberships.interval'));
            $stillPaid = $membership->paid_until && $membership->paid_until->isFuture();

            if ($record->sequence_type === 'first') {
                // Same plan again (restarting after a cancel): the new period follows on from
                // what's left. Another plan: it starts now and replaces the old subscription.
                $from = $stillPaid && $membership->plan === $record->plan ? $membership->paid_until : now();
                $oldSubscription = $membership->mollie_subscription_id;

                $membership->fill([
                    'plan' => $record->plan,
                    'status' => 'active',
                    'canceled_at' => null,
                    'mode' => $payment['mode'] ?? $membership->mode,
                    'mollie_mandate_id' => $payment['mandateId'] ?? $membership->mollie_mandate_id,
                    'paid_until' => $from->copy()->add($interval),
                ]);
                if ($oldSubscription) {
                    $this->stopSubscription($membership->mollie_customer_id, $oldSubscription);
                }
                $membership->mollie_subscription_id = $this->startSubscription($membership, $membership->paid_until);
            } elseif (($payment['subscriptionId'] ?? null) === $membership->mollie_subscription_id) {
                $from = $stillPaid ? $membership->paid_until : now();
                $membership->paid_until = $from->copy()->add($interval);
            } else {
                return $record; // a payment of an old, replaced subscription: nothing to extend
            }

            $membership->save();
            $record->update(['applied_at' => now()]);

            return $record;
        });

        // The plan's Discord roles follow right away instead of at the next sweep.
        if ($record->applied_at && $record->wasChanged('applied_at')) {
            SyncDiscordRankRole::dispatch($membership->user_id);
        }

        return $record;
    }

    // The return page after checkout: the newest first payment of this user, if any.
    public function handleLatestCheckout(User $user): ?MembershipPayment
    {
        $latest = MembershipPayment::where('user_id', $user->id)
            ->where('sequence_type', 'first')
            ->latest('id')
            ->first();

        return $latest ? $this->handlePayment($latest->mollie_payment_id) : null;
    }

    public function cancel(Membership $membership): void
    {
        if ($membership->mollie_subscription_id) {
            $this->stopSubscription($membership->mollie_customer_id, $membership->mollie_subscription_id);
        }

        $membership->update(['status' => 'canceled', 'canceled_at' => now()]);
    }

    // Safety net for missed webhooks (and the only way renewals arrive on a local
    // install Mollie can't reach): applies every payment of the running subscription.
    public function sync(Membership $membership): int
    {
        if (! $membership->mollie_subscription_id) {
            return 0;
        }

        $payments = $this->api()
            ->get("customers/{$membership->mollie_customer_id}/subscriptions/{$membership->mollie_subscription_id}/payments", ['limit' => 250])
            ->throw()->json('_embedded.payments', []);

        foreach ($payments as $payment) {
            $this->handlePayment($payment['id']);
        }

        return count($payments);
    }

    private function startSubscription(Membership $membership, Carbon $firstChargeAt): string
    {
        $subscription = $this->api()->post("customers/{$membership->mollie_customer_id}/subscriptions", array_filter([
            'amount' => $this->amount($membership->plan),
            'interval' => config('memberships.interval'),
            'startDate' => $firstChargeAt->toDateString(),
            // Mollie wants a description that's unique per customer.
            'description' => $membership->planName().' (since '.now()->format('Y-m-d H:i:s').')',
            'mandateId' => $membership->mollie_mandate_id,
            'webhookUrl' => $this->webhookUrl(),
            'metadata' => ['user_id' => $membership->user_id, 'plan' => $membership->plan],
        ]))->throw()->json();

        return $subscription['id'];
    }

    private function stopSubscription(string $customerId, string $subscriptionId): void
    {
        $response = $this->api()->delete("customers/{$customerId}/subscriptions/{$subscriptionId}");
        // Already canceled/ended at Mollie's side is fine; anything else isn't.
        if ($response->failed() && ! in_array($response->status(), [404, 410, 422], true)) {
            $response->throw();
        }
    }

    // $plan only applies to a payment seen for the first time (a checkout's own plan, or
    // the membership's current plan for a recurring payment).
    private function record(int $userId, array $payment, string $plan): MembershipPayment
    {
        $record = MembershipPayment::firstOrNew(['mollie_payment_id' => $payment['id']]);
        if (! $record->exists) {
            $record->fill(['user_id' => $userId, 'plan' => $plan]);
        }

        $record->fill([
            'sequence_type' => $payment['sequenceType'] ?? 'oneoff',
            'status' => $payment['status'],
            'amount' => $payment['amount']['value'],
            'currency' => $payment['amount']['currency'],
            'mode' => $payment['mode'] ?? null,
            'paid_at' => isset($payment['paidAt']) ? Carbon::parse($payment['paidAt']) : null,
        ])->save();

        return $record;
    }

    private function amount(string $plan): array
    {
        return ['currency' => config('memberships.currency'), 'value' => config("memberships.plans.{$plan}.price")];
    }

    // Mollie refuses a webhook it can't reach, so a local install (localhost / 127.0.0.1)
    // goes without one and relies on the return page and memberships:sync instead.
    private function webhookUrl(): ?string
    {
        $url = route('webhooks.mollie');
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with((string) $host, '.test') ? null : $url;
    }

    private function api(): PendingRequest
    {
        $key = config('services.mollie.key');
        if (! $key) {
            throw new RuntimeException('MOLLIE_KEY is not set.');
        }

        return Http::baseUrl(self::API)->withToken($key)->acceptJson()->asJson()->timeout(20);
    }
}
