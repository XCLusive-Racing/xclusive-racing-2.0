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

    public function planName(): string
    {
        return config("memberships.plans.{$this->plan}.name", 'XCL Membership');
    }

    // A plan can be bought once it has a price (VIP has none until MEMBERSHIP_VIP_PRICE is set).
    public static function isPurchasable(string $plan): bool
    {
        return filled(config("memberships.plans.{$plan}.price"));
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
