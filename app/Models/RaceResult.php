<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceResult extends Model
{
    protected $fillable = [
        'race_id', 'session_type', 'user_id',
        'player_id', 'driver_name', 'car_number',
        'position', 'best_lap', 'lap_count', 'total_time',
        'fastest_lap', 'dnf',
    ];

    protected function casts(): array
    {
        return [
            'fastest_lap' => 'boolean',
            'dnf'         => 'boolean',
        ];
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayName(): string
    {
        return $this->user?->name ?? $this->driver_name ?? 'Unknown';
    }

    public static function formatMs(?int $ms): string
    {
        if ($ms === null || $ms <= 0) {
            return '—';
        }
        $minutes = intdiv($ms, 60000);
        $seconds = intdiv($ms % 60000, 1000);
        $millis  = $ms % 1000;

        return $minutes > 0
            ? sprintf('%d:%02d.%03d', $minutes, $seconds, $millis)
            : sprintf('%d.%03d', $seconds, $millis);
    }
}