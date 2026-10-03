<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPayment extends Model
{
    protected $fillable = [
        'user_id', 'mollie_payment_id', 'sequence_type', 'plan', 'status', 'amount', 'currency',
        'mode', 'applied_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
