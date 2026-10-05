<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A user's XCL Supporter membership (App\Services\MembershipService). It counts while it's
// paid up — a canceled one still runs out its last paid period.
class Membership extends Model
{
    protected $fillable = [
        'user_id', 'mollie_customer_id', 'mollie_mandate_id', 'mollie_subscription_id',
        'status', 'plan', 'mode', 'paid_until', 'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_until' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    // Only a membership paid in the Mollie mode the site runs in now counts, so test-mode
    // memberships from before launch don't hand out free perks once the live key is in.
    public function isPaidUp(): bool
    {
        return in_array($this->status, ['active', 'canceled'], true)
            && $this->paid_until !== null && $this->paid_until->isFuture()
            && ($this->mode === null || $this->mode === self::currentMode());
    }

    // 'plan' holds the billing choice (config memberships.billing: monthly / yearly). Rows
    // from the earlier three-plan test period hold a plan name instead and count as monthly.
    public function billing(): string
    {
        return array_key_exists((string) $this->plan, config('memberships.billing')) ? $this->plan : 'monthly';
    }

    public function interval(): string
    {
        return config('memberships.billing.'.$this->billing().'.interval');
    }

    // "XCL Supporter (yearly)"
    public function planName(): string
    {
        return config('memberships.name').' ('.strtolower(config('memberships.billing.'.$this->billing().'.label')).')';
    }

    public static function isBillingOption(string $billing): bool
    {
        return array_key_exists($billing, config('memberships.billing'))
            && filled(config("memberships.billing.{$billing}.price"));
    }

    // 'live' with a live_ MOLLIE_KEY, 'test' otherwise.
    public static function currentMode(): string
    {
        return str_starts_with((string) config('services.mollie.key'), 'live_') ? 'live' : 'test';
    }

    // The checkout is open to everyone once launched; before that only staff can use it
    // (to test it with the test key).
    public static function checkoutOpenFor(?User $user): bool
    {
        return config('memberships.checkout_enabled') || (bool) $user?->isStaffSupporter();
    }

    // Renews automatically (the Mollie subscription is still running).
    public function isRenewing(): bool
    {
        return $this->status === 'active' && $this->mollie_subscription_id !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
