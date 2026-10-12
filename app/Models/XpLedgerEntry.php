<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

// One XP grant. The ledger is the single source of truth for a driver's XP and is
// append only: a row is never changed (apart from seen_at) and never removed, because a
// driver's total can never fall.
class XpLedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'xp_ledger';

    protected $fillable = [
        'user_id', 'xp', 'source', 'game', 'platform', 'reference_type', 'reference_id', 'reference_key',
        'reason', 'breakdown', 'seen_at',
    ];

    protected function casts(): array
    {
        return ['xp' => 'integer', 'breakdown' => 'array', 'seen_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (XpLedgerEntry $entry) {
            if (array_diff(array_keys($entry->getDirty()), ['seen_at'])) {
                throw new LogicException('The XP ledger is append only.');
            }
        });

        static::deleting(fn () => throw new LogicException('The XP ledger is append only.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
