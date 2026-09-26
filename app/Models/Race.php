<?php

namespace App\Models;

use App\Enums\SafetyRatingGrade;
use App\Services\AccCarCatalog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Race extends Model
{
    // Public /events/{slug} URL per game, so each platform's event list has its own
    // address (and going back from an event lands on that game's list, not the picker).
    public const PLATFORM_SLUGS = [
        'acc' => 'acc-console',
        'ac' => 'acc-pc',
        'lmu' => 'le-mans-ultimate',
        'iracing' => 'iracing',
    ];

    protected $fillable = ['title', 'game', 'track', 'scheduled_at', 'status', 'is_championship', 'event_tag', 'max_drivers', 'description', 'image', 'icon', 'duration_key', 'xcl_r_multiplier', 'practice_duration', 'qualifying_duration', 'race_duration', 'pitstop_count', 'min_stop_secs', 'tyre_set_count', 'car_class', 'sr_requirement', 'min_rating', 'max_rating', 'weather', 'weather_randomness', 'rain_level', 'time_of_day', 'ambient_temp', 'practice_time_multiplier', 'qualifying_time_multiplier', 'race_time_multiplier', 'config_overrides', 'results_json_path', 'championship_id', 'round_number', 'is_multiclass', 'is_endurance', 'driver_stint_time_mins', 'max_total_driving_time_mins', 'mandatory_driver_swap', 'event_format_id', 'session_format_id', 'race_durations', 'ftp_server_id', 'slot_time', 'config_pushed_at', 'config_push_status', 'config_push_attempts', 'config_push_error', 'has_practice_server', 'practice_notes'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'config_overrides' => 'array',
            'is_multiclass' => 'boolean',
            'is_endurance' => 'boolean',
            'mandatory_driver_swap' => 'boolean',
            'slot_time' => 'datetime',
            'config_pushed_at' => 'datetime',
            'has_practice_server' => 'boolean',
            'race_durations' => 'array',
        ];
    }

    // Every race this event runs, as lengths in minutes: race_durations for a
    // multi-race round (a race weekend), otherwise the single race_duration.
    public function raceLengths(): array
    {
        return $this->race_durations ?: [(int) ($this->race_duration ?? 20)];
    }

    // Where a race session's raw results JSON is kept (for the results page's stats
    // tabs): race 1 at the long-standing results_json_path location, any further
    // race of a multi-race round next to it.
    public function resultsJsonPath(int $raceNumber = 1): string
    {
        return 'race-results/'.$this->id.($raceNumber > 1 ? '-race'.$raceNumber : '').'.json';
    }

    public function raceCount(): int
    {
        return count($this->raceLengths());
    }

    // The round form's "25, 25" Races field → [25, 25]; null for blank input.
    public static function parseRaceLengths(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return array_map(fn ($minutes) => (int) trim($minutes), explode(',', $value));
    }

    public function sessionFormat(): BelongsTo
    {
        return $this->belongsTo(SessionFormat::class);
    }

    public function practiceServerSession(): HasOne
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
        return $this->belongsTo(EventFormat::class);
    }

    public function ftpServer(): BelongsTo
    {
        return $this->belongsTo(FtpServer::class, 'ftp_server_id');
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    // The servers this race may be pushed to / import results from: a championship
    // round only its own league's servers, any other event only XCL's own (system
    // league) servers -- and either way only servers of the race's ACC platform
    // (FtpServer::supportsRaceGame()). Tenant scope is bypassed on purpose: this
    // picks by league explicitly, and an admin's scope would otherwise show every
    // league's servers.
    public function eligibleServers(): Collection
    {
        $leagueId = $this->championship_id
            ? Championship::withoutTenantScope()->whereKey($this->championship_id)->value('league_id')
            : null;

        return FtpServer::withoutTenantScope()
            ->where('active', true)
            ->where('league_id', $leagueId ?? League::system()->id)
            ->orderBy('name')
            ->get()
            ->filter(fn (FtpServer $server) => $server->supportsRaceGame($this->game))
            ->values();
    }

    // Car names a team can pick on sign-up, narrowed to the race's car class. ACC
    // (console and PC) use the per-platform AccCarCatalog — the cars table only
    // holds console cars — other games fall back to the cars table (often empty,
    // in which case the form shows a free-text field).
    public function carOptions(): Collection
    {
        if (AccCarCatalog::supports($this->game)) {
            return collect(AccCarCatalog::namesWithClass($this->game))
                ->when($this->car_class, fn ($cars) => $cars->filter(fn ($class) => $class === $this->car_class))
                ->keys();
        }

        return Car::where('game', $this->game)
            ->when($this->car_class, fn ($q) => $q->where('car_class', $this->car_class))
            ->orderBy('name')
            ->pluck('name');
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

    // ── Grid fillers ─────────────────────────────────────────────────────────
    // Filler drivers (users.is_filler, see config/fillers.php) are shown on an upcoming
    // standalone event's grid and sign-up counter: one per config('fillers.per_real_drivers')
    // real sign-ups (4 → 5, 8 → 10, 12 → 15), and never more than the free spots left —
    // so they drop out as the grid fills and a real driver never waits behind one.
    // They're display-only: never stored as registrations, so the server entry list,
    // results, ratings, capacity and the waiting list don't know they exist.

    public function usesGridFillers(): bool
    {
        return ! $this->is_endurance
            && ! $this->is_championship
            && $this->championship_id === null
            && $this->scheduled_at?->isFuture();
    }

    // How many fillers to show next to $real real drivers, within $freeSpots (null = no cap).
    public static function fillerCountFor(int $real, ?int $freeSpots): int
    {
        $count = intdiv($real, max(1, (int) config('fillers.per_real_drivers', 4)));

        return $freeSpots === null ? $count : max(0, min($count, $freeSpots));
    }

    // The public sign-up counter ("12 / 30"): real registrations plus fillers.
    public function displayedSignupCount(): int
    {
        $real = (int) ($this->registrations_count ?? $this->registrations()->count());
        if (! $this->usesGridFillers()) {
            return $real;
        }

        return $real + self::fillerCountFor($real, $this->max_drivers !== null ? $this->max_drivers - $real : null);
    }

    // Unsaved RaceRegistration stand-ins for the fillers in one grid box ($cls = null for
    // a single-class race). $offset skips fillers already used by an earlier class box,
    // so the same name never appears twice in one race. The pick is a stable per-race
    // shuffle, so the same names stay put on reload and new ones are only added.
    public function fillerRegistrations(int $real, ?int $freeSpots, ?RaceClass $cls = null, int $offset = 0): Collection
    {
        if (! $this->usesGridFillers()) {
            return collect();
        }

        $count = self::fillerCountFor($real, $freeSpots);
        if ($count === 0) {
            return collect();
        }

        $pool = once(fn () => User::where('is_filler', true)->get());

        return $pool
            ->sortBy(fn (User $u) => crc32($this->id.'-'.$u->id))
            ->slice($offset, $count)
            ->map(function (User $filler) use ($cls) {
                $reg = new RaceRegistration;
                $reg->setRelation('user', $filler);
                $reg->setRelation('raceClass', $cls);
                $reg->setRelation('teamEntry', null);

                return $reg;
            })
            ->values();
    }

    public function isFull(): bool
    {
        if ($this->is_multiclass && $this->raceClasses->isNotEmpty()) {
            if ($this->raceClasses->every(fn ($cls) => $cls->isFull())) {
                return true;
            }

            // A class with no cap of its own (max_drivers null) never reports full on
            // its own -- but the race-wide max_drivers, when set, is still a combined
            // ceiling across every class's registrations, not just a display number.
            return $this->max_drivers !== null && $this->registrations()->count() >= $this->max_drivers;
        }

        if ($this->max_drivers === null) {
            return false;
        }

        if ($this->is_endurance) {
            return $this->teamEntries()->count() >= $this->max_drivers;
        }

        return $this->registrations()->count() >= $this->max_drivers;
    }

    // Whether $registration is beyond capacity -- a live FIFO rank, not a stored flag.
    // Two independent caps can both apply to a multiclass entry: its own class's cap
    // (if the class has one) AND the race-wide max_drivers, which spans every class
    // combined -- a class with no cap of its own doesn't exempt it from the race's
    // overall ceiling. Either one being exceeded is enough to waitlist it.
    // Team/endurance entries have no waiting list (unchanged, hard-capped elsewhere) --
    // guarded here rather than trusting every caller to check team_entry_id first,
    // since a team registration's raw rank across ALL registrations (one row per
    // driver on the car) has nothing to do with the team-count cap registerTeam()
    // actually enforces, and would otherwise produce nonsense once enough team
    // drivers pushed the raw registration count past max_drivers.
    public function isRegistrationWaitlisted(RaceRegistration $registration): bool
    {
        if ($registration->team_entry_id) {
            return false;
        }

        if ($registration->race_class_id) {
            $raceClass = $registration->raceClass ?: $this->raceClasses->firstWhere('id', $registration->race_class_id);
            if ($raceClass && $raceClass->isRegistrationWaitlisted($registration)) {
                return true;
            }
        }

        if ($this->max_drivers === null) {
            return false;
        }

        return $this->registrationRank($registration) >= $this->max_drivers;
    }

    /** FIFO rank across every registration in the race, regardless of class. */
    public function registrationRank(RaceRegistration $registration): int
    {
        return $this->registrations()
            ->where(function ($q) use ($registration) {
                $q->where('created_at', '<', $registration->created_at)
                    ->orWhere(function ($q2) use ($registration) {
                        $q2->where('created_at', $registration->created_at)
                            ->where('id', '<', $registration->id);
                    });
            })
            ->count();
    }

    /** 1-indexed position on the waiting list (only meaningful when isRegistrationWaitlisted() is true). */
    public function waitlistPosition(RaceRegistration $registration): int
    {
        if ($registration->team_entry_id) {
            return 0;
        }

        if ($registration->race_class_id) {
            $raceClass = $registration->raceClass ?: $this->raceClasses->firstWhere('id', $registration->race_class_id);
            if ($raceClass && $raceClass->isRegistrationWaitlisted($registration)) {
                return $raceClass->waitlistPosition($registration);
            }
        }

        if ($this->max_drivers === null) {
            return 0;
        }

        return $this->registrationRank($registration) - $this->max_drivers + 1;
    }

    // Total waiting across the race. Computed per-registration (not derived from the
    // two caps' counts added together) so a driver waitlisted by both their class's
    // cap and the race-wide cap at once is still only counted once.
    public function waitlistCount(): int
    {
        if ($this->is_endurance) {
            return 0;
        }

        return $this->registrations
            ->filter(fn ($r) => ! $r->team_entry_id && $this->isRegistrationWaitlisted($r))
            ->count();
    }

    public function gameLabel(): string
    {
        return match ($this->game) {
            'acc' => 'ACC Console',
            'lmu' => 'Le Mans Ultimate',
            'iracing' => 'iRacing',
            'ac' => 'ACC PC',
            default => strtoupper($this->game),
        };
    }

    public function scheduledAtUk(): Carbon
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
            'europe' => 'Europe/London',
            'australia' => 'Australia/Sydney',
            'us' => 'America/New_York',
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
        $start = $this->scheduled_at->copy()->utc();
        $minutes = (int) $this->practice_duration + (int) $this->qualifying_duration + (int) $this->race_duration;
        $end = $start->copy()->addMinutes(max($minutes, 30));

        return [$start, $end];
    }

    public function googleCalendarUrl(): string
    {
        [$start, $end] = $this->calendarWindow();

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->title.' — '.$this->track,
            'dates' => $start->format('Ymd\THis\Z').'/'.$end->format('Ymd\THis\Z'),
            'location' => $this->track.' ('.$this->gameLabel().')',
            'details' => route('events.show', $this),
        ]);
    }

    public function outlookCalendarUrl(): string
    {
        [$start, $end] = $this->calendarWindow();

        return 'https://outlook.live.com/calendar/0/deeplink/compose?'.http_build_query([
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => $this->title.' — '.$this->track,
            'startdt' => $start->toIso8601String(),
            'enddt' => $end->toIso8601String(),
            'location' => $this->track.' ('.$this->gameLabel().')',
            'body' => route('events.show', $this),
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
        if ($mins === null) {
            return null;
        }
        if ($this->is_endurance && $mins % 60 === 0) {
            return ($mins / 60).'H';
        }

        return $mins.' MIN';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('media')->url($this->image) : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        $icon = $this->icon ?: $this->championshipIconPath() ?: $this->defaultCustomIconPath();

        return $icon ? Storage::disk('media')->url($icon) : null;
    }

    // Eager-loads what icon_url needs for a championship round — use on any list
    // that renders icon_url, or each round loads its championship + league on its own.
    public function scopeWithIconOwners(Builder $query): void
    {
        $query->with(self::iconOwnerRelations());
    }

    // withoutTenantScope(): public pages (and anonymous visitors) have no league
    // context, and TenantScope would otherwise hide every championship/league.
    private static function iconOwnerRelations(): array
    {
        return [
            'championship' => fn ($q) => $q->withoutTenantScope()->select(['id', 'league_id', 'icon']),
            'championship.league' => fn ($q) => $q->withoutTenantScope()->select(['id', 'logo']),
        ];
    }

    // A championship round without its own icon shows its championship's icon,
    // else its league's logo — not XCL's generic "special event" logo, which
    // made every league's rounds look like XCL events.
    private function championshipIconPath(): ?string
    {
        if (! $this->championship_id) {
            return null;
        }

        $this->loadMissing(self::iconOwnerRelations());

        return $this->championship?->icon ?: $this->championship?->league?->logo;
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

        if (! self::$specialEventIconResolved) {
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
            'acc' => '#7c3aed',
            'lmu' => '#db2877',
            'iracing' => '#2563eb',
            'ac' => '#16a34a',
            default => '#6b7280',
        };
    }

    /** Returns [letter, hex-color] for the SR tier badge. */
    public function srTier(): array
    {
        if (! $this->sr_requirement) {
            return ['', '#9ca3af'];
        }
        $grade = SafetyRatingGrade::fromRating((float) $this->sr_requirement);

        return [$grade->value, $grade->color()];
    }

    /** Returns [background-hex, text-hex] for the car class badge on event cards. */
    public function carClassStyle(): array
    {
        return match (strtoupper((string) $this->car_class)) {
            'GT3' => ['#DC2626', '#FFFFFF'],
            'GT4' => ['#2563EB', '#FFFFFF'],
            'GT2' => ['#16A34A', '#FFFFFF'],
            'GTC' => ['#F97316', '#FFFFFF'],
            'TCX' => ['#FFFFFF', '#0D0D0D'],
            default => ['#374151', '#FFFFFF'],
        };
    }

    /** Returns [display-name, hex-color] for a given XCL Rating tier slug. */
    public static function ratingTierInfo(?string $tier): array
    {
        return match ($tier) {
            'rookie' => ['Rookie',   '#ef4444'],
            'bronze' => ['Bronze',   '#cd7f32'],
            'silver' => ['Silver',   '#9ca3af'],
            'gold' => ['Gold',     '#f59e0b'],
            'platinum' => ['Platinum', '#7c3aed'],
            'alien' => ['Alien',    '#10b981'],
            default => ['',         '#6b7280'],
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
