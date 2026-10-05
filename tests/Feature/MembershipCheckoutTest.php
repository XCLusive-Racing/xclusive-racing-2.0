<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// The XCL Supporter membership (one plan: monthly 2.99 or yearly 29.99) through Mollie
// (MembershipService), with the Mollie API faked: first payment -> subscription ->
// recurring payments -> cancel -> runs out.
class MembershipCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, array> Mollie payments by id, as GET /payments/{id} returns them */
    private array $payments = [];

    private int $checkouts = 0;

    private int $subscriptions = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mollie.key' => 'test_fake', 'memberships.checkout_enabled' => true,
            'memberships.billing.monthly.interval' => '1 month', 'memberships.billing.yearly.interval' => '12 months',
        ]);
        Carbon::setTestNow('2026-10-03 12:00:00');

        Http::fake(function (Request $request) {
            $path = str_replace('https://api.mollie.com/v2/', '', $request->url());

            return match (true) {
                $request->method() === 'POST' && $path === 'customers' => Http::response(['id' => 'cst_1']),
                // First checkout tr_first, then tr_first2, tr_first3...
                $request->method() === 'POST' && $path === 'payments' => Http::response($this->payments[$id = 'tr_first'.($this->checkouts++ ? $this->checkouts : '')] = [
                    'id' => $id, 'status' => 'open', 'sequenceType' => 'first', 'customerId' => 'cst_1', 'mode' => 'test',
                    'amount' => $request['amount'],
                    '_links' => ['checkout' => ['href' => 'https://www.mollie.com/checkout/test']],
                ]),
                $request->method() === 'POST' && $path === 'customers/cst_1/subscriptions' => Http::response(['id' => 'sub_'.(++$this->subscriptions)]),
                $request->method() === 'DELETE' && str_starts_with($path, 'customers/cst_1/subscriptions/') => Http::response(['status' => 'canceled']),
                $request->method() === 'GET' && preg_match('#^customers/cst_1/subscriptions/(sub_\d+)/payments#', $path, $m) === 1 => Http::response(['_embedded' => ['payments' => array_values(array_filter($this->payments, fn ($p) => ($p['subscriptionId'] ?? null) === $m[1]))]]),
                $request->method() === 'GET' && str_starts_with($path, 'payments/') => Http::response($this->payments[substr($path, 9)]),
                default => Http::response(['detail' => 'unexpected '.$request->method().' '.$path], 500),
            };
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pay(string $id, string $status = 'paid'): void
    {
        $this->payments[$id]['status'] = $status;
        $this->payments[$id]['mandateId'] = 'mdt_1';
        $this->payments[$id]['paidAt'] = now()->toIso8601String();
    }

    private function recurring(string $id): void
    {
        $this->payments[$id] = [
            'id' => $id, 'status' => 'paid', 'sequenceType' => 'recurring', 'customerId' => 'cst_1', 'subscriptionId' => 'sub_1',
            'mode' => 'test', 'amount' => ['value' => '2.99', 'currency' => 'EUR'], 'paidAt' => now()->toIso8601String(),
        ];
    }

    private function startCheckout(User $user, string $billing = 'monthly'): void
    {
        $this->actingAs($user)->post(route('memberships.checkout'), ['plan' => $billing])
            ->assertRedirect('https://www.mollie.com/checkout/test');
    }

    public function test_the_full_membership_lifecycle(): void
    {
        $user = User::factory()->create(['is_supporter' => false]);
        $this->actingAs($user)->get(route('memberships'))->assertOk()
            ->assertSee('Monthly — €2,99 / month')->assertSee('Yearly — €29,99 / year')->assertSee('Save 16%')
            ->assertSee('Your own in-game abbreviation')->assertSee('Event voting')->assertSee('Rivals');

        // Checkout started, not paid yet: no perks.
        $this->startCheckout($user);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.mollie.com/v2/payments'
            && $r['sequenceType'] === 'first' && $r['amount'] === ['currency' => 'EUR', 'value' => '2.99'] && $r['customerId'] === 'cst_1');
        $this->assertFalse($user->fresh()->isSupporter());

        // Paid -> back on the site: one month paid up, every perk on, subscription after it.
        $this->pay('tr_first');
        $this->actingAs($user)->get(route('memberships.return'))->assertRedirect(route('memberships'))->assertSessionHas('success');
        $membership = Membership::where('user_id', $user->id)->sole();
        $this->assertSame('active', $membership->status);
        $this->assertSame('monthly', $membership->plan);
        $this->assertSame('sub_1', $membership->mollie_subscription_id);
        $this->assertSame('2026-11-03 12:00:00', $membership->paid_until->toDateTimeString());
        $user = $user->fresh();
        $this->assertTrue($user->isSupporter());
        $this->assertTrue($user->canShareStream());
        $this->assertNull($user->teamSeatLimit());
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.mollie.com/v2/customers/cst_1/subscriptions'
            && $r['interval'] === '1 month' && $r['startDate'] === '2026-11-03' && $r['mandateId'] === 'mdt_1' && $r['amount']['value'] === '2.99');

        // The webhook for the same payment arriving too doesn't add a second month.
        $this->post(route('webhooks.mollie'), ['id' => 'tr_first'])->assertOk();
        $this->assertSame('2026-11-03 12:00:00', $membership->fresh()->paid_until->toDateTimeString());

        // A month later the first recurring payment comes in through the webhook.
        Carbon::setTestNow('2026-11-03 09:00:00');
        $this->recurring('tr_rec1');
        $this->post(route('webhooks.mollie'), ['id' => 'tr_rec1'])->assertOk();
        $this->assertSame('2026-12-03 12:00:00', $membership->fresh()->paid_until->toDateTimeString());
        $this->assertSame(2, MembershipPayment::whereNotNull('applied_at')->count());

        // Cancel: subscription stopped at Mollie, perks stay until the paid period ends.
        $this->actingAs($user)->post(route('memberships.cancel'))->assertSessionHas('success');
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE');
        $this->assertSame('canceled', $membership->fresh()->status);
        $this->assertTrue($user->fresh()->isSupporter());

        Carbon::setTestNow('2026-12-03 12:00:01');
        $this->assertFalse($user->fresh()->isSupporter());
    }

    public function test_yearly_charges_29_99_for_twelve_months(): void
    {
        $user = User::factory()->create();
        $this->startCheckout($user, 'yearly');
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.mollie.com/v2/payments' && $r['amount']['value'] === '29.99');

        $this->pay('tr_first');
        $this->actingAs($user)->get(route('memberships.return'));

        $membership = Membership::sole();
        $this->assertSame('yearly', $membership->plan);
        $this->assertSame('2027-10-03 12:00:00', $membership->paid_until->toDateTimeString());
        $this->assertTrue($user->fresh()->isSupporter());
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/subscriptions')
            && $r['interval'] === '12 months' && $r['amount']['value'] === '29.99' && $r['startDate'] === '2027-10-03');
    }

    // Monthly -> yearly: same perks, so the year follows on from the month that's already
    // paid, and the monthly subscription stops once the year is paid.
    public function test_switching_to_yearly_follows_on_from_the_paid_month(): void
    {
        $user = User::factory()->create();
        $this->startCheckout($user, 'monthly');
        $this->pay('tr_first');
        $this->actingAs($user)->get(route('memberships.return'));

        Carbon::setTestNow('2026-10-10 12:00:00');
        $this->actingAs($user)->get(route('memberships'))->assertSee('Switch to yearly')->assertDontSee('Switch to monthly');
        $this->startCheckout($user, 'yearly');
        Http::assertNotSent(fn (Request $r) => $r->method() === 'DELETE');

        $this->pay('tr_first2');
        $this->actingAs($user)->get(route('memberships.return'));

        $membership = Membership::sole();
        $this->assertSame('yearly', $membership->plan);
        $this->assertSame('sub_2', $membership->mollie_subscription_id);
        $this->assertSame('2027-11-03 12:00:00', $membership->paid_until->toDateTimeString());
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/subscriptions/sub_1'));

        // A late payment of the replaced monthly subscription doesn't extend the year.
        $this->payments['tr_old'] = ['id' => 'tr_old', 'status' => 'paid', 'sequenceType' => 'recurring', 'customerId' => 'cst_1',
            'subscriptionId' => 'sub_1', 'mode' => 'test', 'amount' => ['value' => '2.99', 'currency' => 'EUR']];
        $this->post(route('webhooks.mollie'), ['id' => 'tr_old'])->assertOk();
        $this->assertSame('2027-11-03 12:00:00', $membership->fresh()->paid_until->toDateTimeString());
    }

    public function test_a_missed_webhook_is_picked_up_by_the_sync_command(): void
    {
        $user = User::factory()->create();
        $this->startCheckout($user);
        $this->pay('tr_first');
        $this->actingAs($user)->get(route('memberships.return'));

        Carbon::setTestNow('2026-11-03 09:00:00');
        $this->recurring('tr_rec1');
        $this->artisan('memberships:sync')->assertSuccessful();

        $this->assertSame('2026-12-03 12:00:00', Membership::sole()->paid_until->toDateTimeString());
    }

    public function test_a_canceled_or_failed_checkout_gives_no_perks(): void
    {
        $user = User::factory()->create();
        $this->startCheckout($user);
        $this->pay('tr_first', 'canceled');

        $this->actingAs($user)->get(route('memberships.return'))->assertSessionHas('error');
        $this->assertFalse($user->fresh()->isSupporter());
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/subscriptions'));
    }

    public function test_an_unknown_billing_option_is_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('memberships.checkout'), ['plan' => 'vip'])->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_the_webhook_ignores_unknown_and_malformed_ids(): void
    {
        $this->post(route('webhooks.mollie'), ['id' => '../../etc'])->assertOk();
        Http::assertNothingSent();
    }

    public function test_guests_must_log_in_to_check_out(): void
    {
        $this->get(route('memberships'))->assertOk()->assertSee('Log in to become a supporter');
        $this->post(route('memberships.checkout'))->assertRedirect(route('login'));
    }

    public function test_before_launch_visitors_see_launching_soon_and_only_staff_can_check_out(): void
    {
        config(['memberships.checkout_enabled' => false]);
        $user = User::factory()->create();

        $this->get(route('memberships'))->assertOk()->assertSee('Launching soon')->assertSee('2,99');
        $this->actingAs($user)->get(route('memberships'))->assertSee('Launching soon')->assertDontSee('Monthly — €2,99');
        $this->actingAs($user)->post(route('memberships.checkout'))->assertSessionHas('error');
        Http::assertNothingSent();

        $staff = User::factory()->create();
        $staff->roles()->attach(Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin'])->id);
        $this->actingAs($staff)->get(route('memberships'))->assertSee('Staff preview')->assertSee('Test mode');
        $this->startCheckout($staff);
    }

    public function test_a_test_mode_membership_stops_counting_once_the_live_key_is_in(): void
    {
        $user = User::factory()->create();
        $this->startCheckout($user);
        $this->pay('tr_first');
        $this->actingAs($user)->get(route('memberships.return'));
        $this->assertTrue($user->fresh()->isSupporter());

        config(['services.mollie.key' => 'live_fake']);
        $this->assertFalse($user->fresh()->isSupporter());

        // Checking out again in live mode starts from a new Mollie customer.
        $this->startCheckout($user);
        $this->assertSame(2, collect(Http::recorded())->filter(fn ($pair) => $pair[0]->url() === 'https://api.mollie.com/v2/customers')->count());
        $this->assertSame('live', Membership::sole()->mode);
    }

    public function test_the_profile_shows_the_membership(): void
    {
        $user = User::factory()->create();
        $this->startCheckout($user, 'yearly');
        $this->pay('tr_first');
        $this->actingAs($user)->get(route('memberships.return'));

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee('XCL Supporter (yearly)')->assertSee('renews on 3 Oct 2027')->assertSee('Manage membership');
    }
}
