<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportVerdict extends Model
{
    protected $fillable = ['report_id', 'steward_id', 'penalty', 'multiplier', 'red_flag', 'notes'];

    protected function casts(): array
    {
        return [
            'multiplier' => 'decimal:1',
            'red_flag'   => 'boolean',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function steward(): BelongsTo
    {
        return $this->belongsTo(User::class, 'steward_id');
    }

    /** Same penalty code and same multiplier as another verdict. */
    public function matches(ReportVerdict $other): bool
    {
        return $this->penalty === $other->penalty
            && (float) $this->multiplier === (float) $other->multiplier;
    }
}
