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
        'fastest_lap', 'dnf', 'dns', 'dsq', 'dc',
        'rating_before', 'rating_after', 'elo_change', 'sof', 'sr_change',
    ];

    private const ACC_CARS = [
        0  => 'Porsche 991 GT3 R (2018)',
        1  => 'Mercedes-AMG GT3 (2015)',
        2  => 'Ferrari 488 GT3 (2018)',
        3  => 'Audi R8 LMS (2015)',
        4  => 'Lamborghini Huracan GT3 (2015)',
        5  => 'McLaren 650S GT3 (2015)',
        6  => 'Nissan GT-R Nismo GT3 (2018)',
        7  => 'BMW M6 GT3 (2017)',
        8  => 'Bentley Continental GT3 (2018)',
        9  => 'Porsche 991 II GT3 Cup (2017)',
        10 => 'Nissan GT-R Nismo GT3 (2015)',
        11 => 'Bentley Continental GT3 (2015)',
        12 => 'AMR V12 Vantage GT3 (2013)',
        13 => 'Reiter Engineering R-EX GT3 (2017)',
        14 => 'Emil Frey Jaguar G3 (2012)',
        15 => 'Lexus RC F GT3 (2016)',
        16 => 'Lamborghini Huracan GT3 Evo (2019)',
        17 => 'Honda NSX GT3 (2017)',
        18 => 'Lamborghini Huracán SuperTrofeo (2015)',
        19 => 'Audi R8 LMS Evo (2019)',
        20 => 'AMR V8 Vantage (2019)',
        21 => 'Honda NSX GT3 Evo (2019)',
        22 => 'McLaren 720S GT3 (2019)',
        23 => 'Porsche 911 II GT3 R (2019)',
        24 => 'Ferrari 488 GT3 Evo (2020)',
        25 => 'Mercedes-AMG GT3 (2020)',
        26 => 'BMW M4 GT3 (2022)',
        27 => 'Ferrari 488 Challenge Evo (2022)',
        28 => 'BMW M2 Club Sport Racing (2022)',
        29 => 'Porsche 992 GT3 Cup (2022)',
        30 => 'Lamborghini Huracán SuperTrofeo EVO2 (2022)',
        31 => 'Audi R8 LMS GT3 Evo 2 (2022)',
        32 => 'Ferrari 296 GT3 (2023)',
        33 => 'Lamborghini Huracan GT3 Evo 2 (2023)',
        34 => 'Porsche 992 GT3 R (2023)',
        35 => 'McLaren 720S GT3 Evo (2023)',
        36 => 'Ford Mustang GT3 (2024)',
        50 => 'Alpine A110 GT4 (2018)',
        51 => 'Aston Martin Vantage GT4 (2018)',
        52 => 'Audi R8 LMS GT4 (2018)',
        53 => 'BMW M4 GT4 (2018)',
        55 => 'Chevrolet Camaro GT4 (2017)',
        56 => 'Ginetta G55 GT4 (2012)',
        57 => 'KTM X-Bow GT4 (2016)',
        58 => 'Maserati MC GT4 (2016)',
        59 => 'McLaren 570S GT4 (2016)',
        60 => 'Mercedes AMG GT4 (2016)',
        61 => 'Porsche 718 Cayman GT4 Clubsport (2019)',
        80 => 'Audi R8 LMS GT2 (2021)',
        82 => 'KTM Xbow GT2 (2021)',
        83 => 'Maserati GT2 (2023)',
        84 => 'Mercedes AMG GT2 (2023)',
        85 => 'Porsche 991 II GT2 RS CS EVO (2023)',
        86 => 'Porsche 935 (2019)',
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
            'dns'           => 'boolean',
            'dsq'           => 'boolean',
            'dc'            => 'boolean',
            'rating_before' => 'decimal:4',
            'rating_after'  => 'decimal:4',
            'elo_change'    => 'decimal:4',
            'sof'           => 'decimal:2',
            'sr_change'     => 'decimal:4',
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

    /**
     * Collapses one result row per co-driver into one row per car for endurance/team
     * races (import creates a row per driver, but they share identical car-level stats).
     * Non-endurance races pass through unchanged. Preserves the input's existing order.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $results
     * @return \Illuminate\Support\Collection<int, object{result: self, label: string, sub: ?string}>
     */
    public static function groupedByCar(\Illuminate\Support\Collection $results, Race $race): \Illuminate\Support\Collection
    {
        if (!$race->is_endurance) {
            return $results->map(fn (self $r) => (object) [
                'result' => $r,
                'label'  => $r->displayName(),
                'sub'    => null,
            ]);
        }

        $teamByCarNumber = $race->teamEntries->keyBy('car_number');

        return $results->groupBy('car_number')->map(function ($group) use ($teamByCarNumber) {
            $primary = $group->first();
            $team    = $teamByCarNumber->get($primary->car_number);
            $names   = $group->map->displayName()->implode(' / ');

            return (object) [
                'result' => $primary,
                'label'  => $team?->team?->name ?? $names,
                'sub'    => $team ? $names : null,
            ];
        })->values();
    }

    /**
     * Renumbers classified results 1..N after excluding DNS/DSQ rows, so a DSQ near the
     * front doesn't leave the field stuck showing a gapped P2, P3... — everyone behind it
     * shifts up to fill the gap. Keyed by result id; DNS/DSQ rows are absent from the map.
     * DNF keeps its imported position since it still counts toward classification (see
     * User::raceStats()).
     *
     * @param  \Illuminate\Support\Collection<int, self>  $results
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function classifiedPositions(\Illuminate\Support\Collection $results): \Illuminate\Support\Collection
    {
        return $results->where('dns', false)->where('dsq', false)
            ->sortBy('position')
            ->values()
            ->mapWithKeys(fn (self $r, int $i) => [$r->id => $i + 1]);
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