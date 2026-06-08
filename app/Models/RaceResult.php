<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceResult extends Model
{
    protected $fillable = [
        'race_id', 'race_title', 'race_track', 'race_game', 'race_scheduled_at',
        'session_type', 'user_id',
        'player_id', 'driver_name', 'car_number', 'vehicle',
        'position', 'best_lap', 'lap_count', 'laps_led', 'total_time', 'consistency',
        'fastest_lap', 'dnf',
        'rating_before', 'rating_after', 'elo_change', 'sof',
    ];

    private const ACC_CARS = [
        0  => 'Porsche 991 GT3 R',
        1  => 'Mercedes-AMG GT3',
        2  => 'Ferrari 488 GT3',
        3  => 'Audi R8 LMS',
        4  => 'Lamborghini Huracán GT3',
        5  => 'McLaren 650S GT3',
        6  => 'Nissan GT-R Nismo GT3',
        7  => 'BMW M6 GT3',
        8  => 'Bentley Continental GT3',
        9  => 'Porsche 991 II GT3 Cup',
        11 => 'Bentley Continental GT3 (2016)',
        12 => 'AMR V12 Vantage GT3',
        14 => 'Emil Frey Jaguar G3',
        15 => 'Lexus RC F GT3',
        16 => 'Lamborghini Huracán GT3 Evo',
        17 => 'Honda NSX GT3',
        18 => 'Lamborghini Huracán ST',
        19 => 'Audi R8 LMS Evo',
        20 => 'AMR V8 Vantage GT3',
        21 => 'Honda NSX GT3 Evo',
        22 => 'McLaren 720S GT3',
        23 => 'Porsche 991 II GT3 R',
        24 => 'Ferrari 488 GT3 Evo',
        25 => 'Mercedes-AMG GT3 Evo',
        26 => 'Ferrari 488 Challenge Evo',
        27 => 'BMW M2 CS Racing',
        28 => 'Porsche 992 GT3 Cup',
        29 => 'Lamborghini Huracán ST Evo2',
        30 => 'BMW M4 GT3',
        31 => 'Audi R8 LMS Evo 2',
        32 => 'Ferrari 296 GT3',
        33 => 'Lamborghini Huracán GT3 Evo2',
        34 => 'Porsche 992 GT3 R',
        35 => 'McLaren 720S GT3 Evo',
        36 => 'Ford Mustang GT3',
    ];

    public static function accCarName(?int $modelId): ?string
    {
        if ($modelId === null) return null;
        return self::ACC_CARS[$modelId] ?? 'Car #' . $modelId;
    }

    protected function casts(): array
    {
        return [
            'fastest_lap'   => 'boolean',
            'dnf'           => 'boolean',
            'rating_before' => 'decimal:4',
            'rating_after'  => 'decimal:4',
            'elo_change'    => 'decimal:4',
            'sof'           => 'decimal:2',
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