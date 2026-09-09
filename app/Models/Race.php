<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\RaceTeamEntry;
use Illuminate\Support\Facades\Storage;

class Race extends Model
{
    protected $fillable = ['title', 'game', 'track', 'scheduled_at', 'status', 'is_championship', 'event_tag', 'max_drivers', 'description', 'image', 'icon', 'duration_key', 'xcl_r_multiplier', 'practice_duration', 'qualifying_duration', 'race_duration', 'pitstop_count', 'min_stop_secs', 'car_class', 'sr_requirement', 'min_rating', 'max_rating', 'weather', 'weather_randomness', 'rain_level', 'time_of_day', 'ambient_temp', 'practice_time_multiplier', 'qualifying_time_multiplier', 'race_time_multiplier', 'config_overrides', 'results_json_path', 'championship_id', 'round_number', 'is_multiclass', 'is_endurance', 'driver_stint_time_mins', 'max_total_driving_time_mins', 'mandatory_driver_swap', 'event_format_id', 'ftp_server_id', 'slot_time', 'config_pushed_at', 'config_push_status', 'config_push_attempts', 'config_push_error', 'has_practice_server', 'practice_notes'];

    protected function casts(): array
    {
        return [
            'scheduled_at'           => 'datetime',
            'config_overrides'       => 'array',
            'is_multiclass'          => 'boolean',
            'is_endurance'           => 'boolean',
            'mandatory_driver_swap'  => 'boolean',
            'slot_time'              => 'datetime',
            'config_pushed_at'       => 'datetime',
            'has_practice_server'    => 'boolean',
        ];
    }

    public function practiceServerSession(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PracticeServerSession::class);
    }

    public function configFile(string $filename): ?string
    {
        return $this->config_overrides[$filename] ?? null;
    }

    public function hasConfigOverride(string $filename): bool
    {
        return isset($this->config_overrides[$filename]);
    }

    public function hasAnyConfigOverride(): bool
    {
        return ! empty($this->config_overrides);
    }

    public function eventFormat(): BelongsTo
    {
        return $this->belongsTo(\App\Models\EventFormat::class);
    }

    public function ftpServer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FtpServer::class, 'ftp_server_id');
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function raceClasses(): HasMany
    {
        return $this->hasMany(RaceClass::class)->orderBy('sort_order');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(RaceRegistration::class);
    }

    public function teamEntries(): HasMany
    {
        return $this->hasMany(RaceTeamEntry::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(RaceResult::class)->orderBy('dns')->orderBy('dsq')->orderBy('dnf')->orderBy('position');
    }

    public function raceResults(): HasMany
    {
        return $this->hasMany(RaceResult::class)
            ->where('session_type', 'race')
            ->orderBy('dns')->orderBy('dsq')->orderBy('dnf')->orderBy('position');
    }

    public function qualiResults(): HasMany
    {
        return $this->hasMany(RaceResult::class)
            ->where('session_type', 'quali')
            ->orderBy('dns')->orderBy('dsq')->orderBy('dnf')->orderBy('position');
    }

    public function isRegistered(User $user): bool
    {
        return $this->registrations()->where('user_id', $user->id)->exists();
    }

    public function isPast(): bool
    {
        return $this->scheduled_at->isPast();
    }

    public function registrationOpen(): bool
    {
        return $this->status === 'open' && $this->scheduled_at->gt(now()->addMinutes(5));
    }

    public function isFull(): bool
    {
        if ($this->is_multiclass && $this->raceClasses->isNotEmpty()) {
            return $this->raceClasses->every(fn($cls) => $cls->isFull());
        }

        if ($this->max_drivers === null) {
            return false;
        }

        if ($this->is_endurance) {
            return $this->teamEntries()->count() >= $this->max_drivers;
        }

        return $this->registrations()->count() >= $this->max_drivers;
    }

    public function gameLabel(): string
    {
        return match ($this->game) {
            'acc'     => 'ACC Console',
            'lmu'     => 'Le Mans Ultimate',
            'iracing' => 'iRacing',
            'ac'      => 'ACC PC',
            default   => strtoupper($this->game),
        };
    }

    public function scheduledAtUk(): \Carbon\Carbon
    {
        return $this->scheduled_at->timezone('Europe/London');
    }

    // Which region(s) this race's start time falls in "prime time" evening
    // hours for — used only by the public Events page's Timezone filter, so
    // people browsing for their own region's evening slot aren't shown every
    // race we run. A race can match more than one region at once (the windows
    // are independent local-hour checks), so this returns every match rather
    // than a single label. 17:00-23:59 local is a reasonable default "evening"
    // window; adjust here if the actual prime-time slots end up different.
    public function eveningRegions(): array
    {
        $regions = [
            'europe'    => 'Europe/London',
            'australia' => 'Australia/Sydney',
            'us'        => 'America/New_York',
        ];

        $matches = [];
        foreach ($regions as $region => $timezone) {
            $hour = $this->scheduled_at->copy()->timezone($timezone)->hour;
            if ($hour >= 17 && $hour <= 23) {
                $matches[] = $region;
            }
        }

        return $matches;
    }

    // [start, end] for calendar exports — practice+qualifying+race summed from the
    // effective session durations (already resolved from the format if any), with a
    // 30-minute floor for races that have no duration data at all (e.g. a bare stub).
    public function calendarWindow(): array
    {
        $start   = $this->scheduled_at->copy()->utc();
        $minutes = (int) $this->practice_duration + (int) $this->qualifying_duration + (int) $this->race_duration;
        $end     = $start->copy()->addMinutes(max($minutes, 30));

        return [$start, $end];
    }

    public function googleCalendarUrl(): string
    {
        [$start, $end] = $this->calendarWindow();

        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action'   => 'TEMPLATE',
            'text'     => $this->title . ' — ' . $this->track,
            'dates'    => $start->format('Ymd\THis\Z') . '/' . $end->format('Ymd\THis\Z'),
            'location' => $this->track . ' (' . $this->gameLabel() . ')',
            'details'  => route('events.show', $this),
        ]);
    }

    public function outlookCalendarUrl(): string
    {
        [$start, $end] = $this->calendarWindow();

        return 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query([
            'path'     => '/calendar/action/compose',
            'rru'      => 'addevent',
            'subject'  => $this->title . ' — ' . $this->track,
            'startdt'  => $start->toIso8601String(),
            'enddt'    => $end->toIso8601String(),
            'location' => $this->track . ' (' . $this->gameLabel() . ')',
            'body'     => route('events.show', $this),
        ]);
    }

    /** Total on-track race time in minutes, from the event format (race1 + race2 for double races). */
    public function raceDurationMinutes(): ?int
    {
        if ($this->is_endurance && $this->race_duration) {
            return (int) $this->race_duration;
        }
        if (! $this->eventFormat) {
            return null;
        }
        return $this->eventFormat->race1_mins + ($this->eventFormat->race2_mins ?? 0);
    }

    /** Human-readable duration label: "4H" for endurance, "240 MIN" for others. */
    public function durationLabel(): ?string
    {
        $mins = $this->raceDurationMinutes();
        if ($mins === null) return null;
        if ($this->is_endurance && $mins % 60 === 0) {
            return ($mins / 60) . 'H';
        }
        return $mins . ' MIN';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('media')->url($this->image) : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        $icon = $this->icon ?: $this->defaultCustomIconPath();

        return $icon ? Storage::disk('media')->url($icon) : null;
    }

    // Custom races (no event_format_id) have no format-derived icon, so without an
    // admin-chosen one they'd show the plain text badge instead of a logo. Fall back
    // to the shared "special event" logo in that case.
    private static ?string $specialEventIconPath = null;
    private static bool $specialEventIconResolved = false;

    private function defaultCustomIconPath(): ?string
    {
        if ($this->event_format_id) {
            return null;
        }

        if (!self::$specialEventIconResolved) {
            self::$specialEventIconResolved = true;
            self::$specialEventIconPath = Media::where('title', 'special_event')
                ->orWhere('original_name', 'like', 'special_event%')
                ->value('path');
        }

        return self::$specialEventIconPath;
    }

    public function gameColor(): string
    {
        return match ($this->game) {
            'acc'     => '#7c3aed',
            'lmu'     => '#db2877',
            'iracing' => '#2563eb',
            'ac'      => '#16a34a',
            default   => '#6b7280',
        };
    }

    /** Returns [letter, hex-color] for the SR tier badge. */
    public function srTier(): array
    {
        if (!$this->sr_requirement) return ['', '#9ca3af'];
        $val = (float) $this->sr_requirement;
        if ($val >= 9.0) return ['Z', '#7c3aed'];
        if ($val >= 8.0) return ['Y', '#eab308'];
        if ($val >= 7.0) return ['X', '#2563eb'];
        if ($val >= 5.0) return ['A', '#16a34a'];
        if ($val >= 3.0) return ['B', '#dc2626'];
        return ['D', '#6b7280'];
    }

    /** Returns [background-hex, text-hex] for the car class badge on event cards. */
    public function carClassStyle(): array
    {
        return match (strtoupper((string) $this->car_class)) {
            'GT3'  => ['#DC2626', '#FFFFFF'],
            'GT4'  => ['#2563EB', '#FFFFFF'],
            'GT2'  => ['#16A34A', '#FFFFFF'],
            'GTC'  => ['#F97316', '#FFFFFF'],
            'TCX'  => ['#FFFFFF', '#0D0D0D'],
            default => ['#374151', '#FFFFFF'],
        };
    }

    /** Returns [display-name, hex-color] for a given XCL Rating tier slug. */
    public static function ratingTierInfo(?string $tier): array
    {
        return match ($tier) {
            'rookie'   => ['Rookie',   '#ef4444'],
            'bronze'   => ['Bronze',   '#cd7f32'],
            'silver'   => ['Silver',   '#9ca3af'],
            'gold'     => ['Gold',     '#f59e0b'],
            'platinum' => ['Platinum', '#7c3aed'],
            'alien'    => ['Alien',    '#10b981'],
            default    => ['',         '#6b7280'],
        };
    }

    /** Returns [display-name, hex-color] for this race's minimum XCL Rating tier badge. */
    public function xclTierInfo(): array
    {
        return static::ratingTierInfo($this->min_rating);
    }

    /** Returns [display-name, hex-color] for this race's maximum XCL Rating tier badge. */
    public function xclMaxTierInfo(): array
    {
        return static::ratingTierInfo($this->max_rating);
    }
}