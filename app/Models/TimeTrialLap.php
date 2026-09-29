<?php

namespace App\Models;

use App\Services\AccServerConfigService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// One Time Trials lap. Times are integer milliseconds; the format*() helpers are for display only.
#[Fillable([
    'user_id', 'platform_identifier', 'platform', 'driver_name', 'track', 'car_id', 'car_class',
    'lap_time_ms', 'sector1_ms', 'sector2_ms', 'sector3_ms', 'game_patch', 'source_event',
    'laps_driven', 'source', 'time_trial_event_id', 'is_personal_best', 'source_key', 'recorded_at',
])]
class TimeTrialLap extends Model
{
    public const PLATFORMS = ['xbox', 'playstation', 'pc'];

    // Platform identifier prefix => platform. Anything else is rejected, never guessed.
    public const IDENTIFIER_PREFIXES = ['M' => 'xbox', 'P' => 'playstation'];

    protected function casts(): array
    {
        return [
            'is_personal_best' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(TimeTrialCar::class, 'car_id');
    }

    public static function platformFromIdentifier(string $identifier): ?string
    {
        return self::IDENTIFIER_PREFIXES[substr($identifier, 0, 1)] ?? null;
    }

    /** Board key (config time_trials.boards) => its platforms, enabled boards only. */
    public static function boards(): array
    {
        return array_filter(config('time_trials.boards'), fn ($board) => $board['enabled']);
    }

    // Display name of an ACC track key ("nurburgring_24h" => "Nordschleife"), from the
    // site's own track list (Race::TRACK_IMAGE_MAP) and ACC's track folder naming.
    public static function trackName(string $key): string
    {
        return self::trackNames()[$key] ?? Str::headline($key);
    }

    /** ACC track key => display name for every track the site knows. */
    public static function trackNames(): array
    {
        return once(fn () => collect(array_keys(Race::TRACK_IMAGE_MAP))
            ->mapWithKeys(fn ($name) => [AccServerConfigService::accTrackSlug($name) => $name])
            ->all());
    }

    // "1:22.312", or "8:04.272" for a Nordschleife lap.
    public static function formatLap(?int $ms): string
    {
        if ($ms === null) {
            return '';
        }

        return intdiv($ms, 60000).':'.sprintf('%02d.%03d', intdiv($ms % 60000, 1000), $ms % 1000);
    }

    // Sectors read as plain seconds ("26.547") until they pass a minute.
    public static function formatSector(?int $ms): string
    {
        if ($ms === null) {
            return '';
        }

        return $ms >= 60000 ? self::formatLap($ms) : sprintf('%d.%03d', intdiv($ms, 1000), $ms % 1000);
    }

    public static function formatGap(int $ms): string
    {
        return match (true) {
            $ms === 0 => '',
            $ms < 0 => '-'.self::formatSector(-$ms),
            default => '+'.self::formatSector($ms),
        };
    }

    // Marks each driver's fastest lap per track and car (ties go to the earliest row) as
    // their personal best and clears the flag on every other row of the given tracks.
    public static function recomputePersonalBests(?array $tracks = null): int
    {
        $best = [];
        self::query()
            ->when($tracks, fn ($q) => $q->whereIn('track', $tracks))
            ->orderBy('lap_time_ms')->orderBy('id')
            ->select(['id', 'platform', 'platform_identifier', 'track', 'car_id'])
            ->lazy(2000)
            ->each(function ($lap) use (&$best) {
                $best[$lap->platform.'|'.$lap->platform_identifier.'|'.$lap->track.'|'.$lap->car_id] ??= $lap->id;
            });

        DB::transaction(function () use ($tracks, $best) {
            self::query()->when($tracks, fn ($q) => $q->whereIn('track', $tracks))
                ->where('is_personal_best', true)->update(['is_personal_best' => false]);

            foreach (array_chunk(array_values($best), 1000) as $ids) {
                self::whereIn('id', $ids)->update(['is_personal_best' => true]);
            }
        });

        return count($best);
    }
}
