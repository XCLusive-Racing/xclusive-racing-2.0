<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeServerSession extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUSHING   = 'pushing';
    public const STATUS_LIVE      = 'live';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_TOO_LATE  = 'too_late';

    // Statuses that genuinely occupy the server for their window, for overlap checks.
    public const OCCUPYING_STATUSES = [
        self::STATUS_SCHEDULED, self::STATUS_PUSHING, self::STATUS_LIVE,
        self::STATUS_COMPLETED, self::STATUS_FAILED,
    ];

    protected $fillable = [
        'race_id', 'practice_server_id', 'window_start', 'upload_at', 'window_end',
        'status', 'pushed_at', 'entry_count', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'window_start' => 'datetime',
            'upload_at'    => 'datetime',
            'window_end'   => 'datetime',
            'pushed_at'    => 'datetime',
        ];
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function practiceServer(): BelongsTo
    {
        return $this->belongsTo(PracticeServer::class);
    }

    public function scopeOccupying($query)
    {
        return $query->whereIn('status', self::OCCUPYING_STATUSES);
    }

    public function isPushed(): bool
    {
        return $this->pushed_at !== null;
    }
}
