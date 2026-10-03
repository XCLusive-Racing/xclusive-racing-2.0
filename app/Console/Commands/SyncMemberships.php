<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Services\MembershipService;
use Illuminate\Console\Command;
use Throwable;

// Picks up recurring membership payments a webhook didn't deliver (and every renewal on
// a local install, which Mollie can't reach): checks each running subscription that is
// due — paid up for less than another day.
class SyncMemberships extends Command
{
    protected $signature = 'memberships:sync {--all : Check every running subscription, not just the ones due}';

    protected $description = 'Apply recurring XCL Supporter payments from Mollie';

    public function handle(MembershipService $memberships): int
    {
        // Only subscriptions of the Mollie mode in use (test ones can't be fetched with a live key).
        $query = Membership::where('status', 'active')->whereNotNull('mollie_subscription_id')
            ->where('mode', Membership::currentMode());
        if (! $this->option('all')) {
            $query->where(fn ($q) => $q->whereNull('paid_until')->orWhere('paid_until', '<', now()->addDay()));
        }

        foreach ($query->get() as $membership) {
            try {
                $count = $memberships->sync($membership);
                $this->line("User {$membership->user_id}: {$count} payment(s) checked, paid until ".($membership->fresh()->paid_until?->toDateTimeString() ?? '-'));
            } catch (Throwable $e) {
                $this->error("User {$membership->user_id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
