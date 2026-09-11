<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use App\Settings\Casts\ChampionshipSettingsCast;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Championship extends Model
{
    use Tenantable, SoftDeletes;

    protected $fillable = [
        'name', 'game', 'season', 'status', 'description', 'image', 'icon',
        'max_drivers', 'is_multiclass', 'points_system', 'bonus_fastest_lap',
        'bonus_pole', 'drop_rounds', 'max_missed_rounds', 'missed_rounds_action',
        'missed_rounds_penalty_points', 'registration_open', 'registration_deadline',
        'sr_requirement', 'min_rating', 'car_class', 'practice_duration',
        'qualifying_duration', 'race_duration', 'weather', 'time_of_day', 'duration_key',
        // League-scoped championships (see 2026_09_08_000010_add_league_fields_to_championships_table.php)
        'league_id', 'slug', 'platform', 'visibility', 'starts_at', 'ends_at', 'settings',
        // Default server for new rounds, set on the Basics step — see
        // 2026_09_09_000001_add_ftp_server_id_to_championships_table.php.
        'ftp_server_id',
    ];

    protected function casts(): array
    {
        return [
            'is_multiclass'         => 'boolean',
            'registration_open'     => 'boolean',
            'registration_deadline' => 'datetime',
            'points_system'         => 'array',
            'bonus_fastest_lap'     => 'integer',
            'bonus_pole'            => 'integer',
            'drop_rounds'           => 'integer',
            'starts_at'             => 'datetime',
            'ends_at'               => 'datetime',
            'xcl_rating_enabled'    => 'boolean',
            'xcl_rating_approved_at' => 'datetime',
            'settings_version'      => 'integer',
            'settings'              => ChampionshipSettingsCast::class,
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function ftpServer(): BelongsTo
    {
        return $this->belongsTo(FtpServer::class);
    }

    // Suggests when round $roundNumber should run, from the Basics-step
    // recurrence settings — start_date, recurrence and (for weekly/biweekly)
    // day_of_week. Returns null when there's nothing to suggest from (no start
    // date set, or recurrence is "none"), in which case Add Round is left blank
    // for the manager to fill in by hand. Always just a starting suggestion —
    // Add Round never enforces it, so one round can still be moved freely.
    public function scheduledDateTimeForRound(int $roundNumber): ?\Carbon\Carbon
    {
        $schedule   = $this->settings->schedule;
        $startDate  = $schedule->start_date ?? null;
        $recurrence = $schedule->recurrence ?? 'none';
        $dayOfWeek  = $schedule->day_of_week ?? null;
        $timeOfDay  = $schedule->time_of_day ?? '14:00';

        if (!$startDate || $recurrence === 'none') {
            return null;
        }

        $first = \Carbon\Carbon::parse($startDate, 'Europe/London')->startOfDay();

        $daysOfWeek = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];

        if (in_array($recurrence, ['weekly', 'biweekly'], true) && isset($daysOfWeek[$dayOfWeek])) {
            while ($first->dayOfWeek !== $daysOfWeek[$dayOfWeek]) {
                $first->addDay();
            }
        }

        $date = match ($recurrence) {
            'daily'    => $first->copy()->addDays($roundNumber - 1),
            'weekly'   => $first->copy()->addWeeks($roundNumber - 1),
            'biweekly' => $first->copy()->addWeeks(($roundNumber - 1) * 2),
            'monthly'  => $first->copy()->addMonthsNoOverflow($roundNumber - 1),
            default    => null,
        };

        if (!$date) {
            return null;
        }

        [$hour, $minute] = array_pad(array_map('intval', explode(':', $timeOfDay)), 2, 0);

        return $date->setTime($hour, $minute);
    }

    public function ratingApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'xcl_rating_approved_by');
    }

    // Bypasses the tenant scope deliberately — standings must resolve
    // correctly for an unauthenticated public visitor (no league membership
    // at all) reading this championship's public page, and a points scheme
    // carries no credentials or infrastructure, so reading it isn't a leak
    // (same reasoning as PointsSchemeController::browse()).
    public function pointsScheme(): ?PointsScheme
    {
        $id = $this->settings->scoring->points_scheme_id ?? null;
        return $id ? PointsScheme::withoutTenantScope()->find($id) : null;
    }

    // League manager side: raises the request. Does not enable rating.
    public function requestXclRating(): void
    {
        $settings = $this->settings;
        $penalties = $settings->penalties->toArray();
        $penalties['xcl_rating_requested'] = true;
        $this->settings = array_replace($settings->toArray(), ['penalties' => $penalties]);
        $this->save();
    }

    // XCL admin side: the only place xcl_rating_enabled is ever set to true.
    // Callers must check ChampionshipPolicy::approveRating before calling this.
    // These three columns are deliberately absent from $fillable — set only here,
    // by direct attribute assignment, never through mass assignment — so no
    // update() call anywhere else in the app could ever touch them by accident.
    public function approveXclRating(User $admin): void
    {
        $this->xcl_rating_enabled     = true;
        $this->xcl_rating_approved_at = now();
        $this->xcl_rating_approved_by = $admin->id;
        $this->save();
    }

    public function revokeXclRating(): void
    {
        $this->xcl_rating_enabled     = false;
        $this->xcl_rating_approved_at = null;
        $this->xcl_rating_approved_by = null;
        $this->save();
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ChampionshipClass::class)->orderBy('sort_order');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ChampionshipRegistration::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Race::class)->orderBy('round_number');
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(ChampionshipPenalty::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('media')->url($this->image) : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? Storage::disk('media')->url($this->icon) : null;
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

    public function isFull(): bool
    {
        if ($this->max_drivers === null) {
            return false;
        }
        return $this->registrations()->where('is_spectator', false)->count() >= $this->max_drivers;
    }

    public function spectatorSlots(): int
    {
        return (int) ($this->settings->format->spectator_slots ?? 0);
    }

    public function isSpectatorFull(): bool
    {
        $slots = $this->spectatorSlots();
        if ($slots <= 0) {
            return true;
        }

        return $this->registrations()->where('is_spectator', true)->count() >= $slots;
    }

    // Which entry-requirement thresholds gate registration — a league-owned
    // championship (built through the wizard) reads its own settings.requirements;
    // XCL's own native championship (league_id = XCL's system league, Phase 2.5)
    // keeps reading the legacy flat columns exactly as before, since the wizard
    // never touches them and the native admin form still writes to them directly.
    public function requirementThresholds(): array
    {
        if ($this->league_id !== null && $this->league_id !== League::system()->id) {
            return [
                'sr'  => $this->settings->requirements->min_safety_rating ?? null,
                'min' => $this->settings->requirements->min_xcl_rating_tier ?? null,
                'max' => $this->settings->requirements->max_xcl_rating_tier ?? null,
            ];
        }

        // Native championships have no upper-rating-cap column — only the wizard-driven
        // settings schema (above) supports it.
        return [
            'sr'  => $this->sr_requirement,
            'min' => $this->min_rating,
            'max' => null,
        ];
    }

    // Registered directly, or a member of a team that's already registered
    // (driver-swaps-enabled championships register the team, not each driver).
    public function isRegistered(User $user): bool
    {
        return $this->registrations()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereIn('racing_team_id', $user->allRacingTeams()->pluck('id'));
            })
            ->exists();
    }

    public function waitlistEnabled(): bool
    {
        return (bool) ($this->settings->requirements->waitlist_enabled ?? false);
    }

    // There is no waitlist column — "waitlisted" is purely a rank: the first
    // max_drivers registrations (oldest first) are active, everyone after that
    // is waitlisted. A cancellation immediately promotes the next one in line,
    // just by virtue of their rank now falling inside max_drivers.
    public function isRegistrationWaitlisted(User $user): bool
    {
        if (!$this->waitlistEnabled() || $this->max_drivers === null) {
            return false;
        }

        $registration = $this->registrations()->where('user_id', $user->id)->where('is_spectator', false)->first();
        if (!$registration) {
            return false;
        }

        $rank = $this->registrations()->where('is_spectator', false)->where('created_at', '<', $registration->created_at)->count();

        return $rank >= $this->max_drivers;
    }

    public function waitlistCount(): int
    {
        if (!$this->waitlistEnabled() || $this->max_drivers === null) {
            return 0;
        }

        return max(0, $this->registrations()->where('is_spectator', false)->count() - $this->max_drivers);
    }

    // Governs the registration form itself — status/registration_open still gate
    // it first (see ChampionshipController::register()); this layers the wizard's
    // own Registration Closes setting (always open / at first round / a window)
    // on top of that.
    public function registrationIsOpen(): bool
    {
        $mode = $this->settings->requirements->registration_mode ?? 'always_open';

        if ($mode === 'closes_at_first_round') {
            $firstRound = $this->rounds()->orderBy('scheduled_at')->first();
            if ($firstRound && now()->gte($firstRound->scheduled_at)) {
                return false;
            }
        }

        if ($mode === 'specific_period') {
            $opensAt  = $this->settings->requirements->registration_opens_at ?? null;
            $closesAt = $this->settings->requirements->registration_closes_at ?? null;

            if ($opensAt && now()->lt(\Carbon\Carbon::parse($opensAt))) {
                return false;
            }
            if ($closesAt && now()->gte(\Carbon\Carbon::parse($closesAt))) {
                return false;
            }
        }

        return true;
    }

    public function computeStandings(): array
    {
        return $this->buildDriverStandings();
    }

    // Groups the same overall standings by championship class, preserving each
    // driver's position within their class. Drivers without a class assignment
    // (e.g. registered before a class existed) are omitted from every group.
    public function computeClassStandings(): array
    {
        if (!$this->is_multiclass) {
            return [];
        }

        $classByUser = $this->registrations->pluck('championship_class_id', 'user_id');

        $grouped = $this->classes->mapWithKeys(fn($class) => [$class->id => ['class' => $class, 'standings' => []]])->all();

        foreach ($this->buildDriverStandings() as $entry) {
            $classId = $classByUser[$entry['user_id']] ?? null;
            if ($classId !== null && isset($grouped[$classId])) {
                $grouped[$classId]['standings'][] = $entry;
            }
        }

        return $grouped;
    }

    // A separate team classification for a driver-swaps championship where teams
    // register as a unit (Championship::registerTeam() equivalent flow,
    // ChampionshipRegistration.racing_team_id) — settings.scoring.team_points_enabled
    // existed since the points-scheme rebuild but never computed anything.
    // A team's total is the combined points of every member who actually scored
    // (owner or driver-swap member), not just whichever one holds the
    // registration — round-by-round attribution to a specific member already
    // lives in RaceResult/RaceRegistration.team_entry_id, not here.
    public function computeTeamStandings(): array
    {
        if (!($this->settings->scoring->team_points_enabled ?? false)) {
            return [];
        }

        $teamRegistrations = $this->registrations()
            ->whereNotNull('racing_team_id')
            ->with('racingTeam.members', 'racingTeam.owner')
            ->get();

        if ($teamRegistrations->isEmpty()) {
            return [];
        }

        $driverStandings = collect($this->buildDriverStandings())->keyBy('user_id');

        $teams = [];
        foreach ($teamRegistrations as $registration) {
            $team = $registration->racingTeam;
            if (!$team || isset($teams[$team->id])) {
                continue;
            }

            $memberIds = $team->members->pluck('id')->push($team->owner_id)->unique();

            $teams[$team->id] = [
                'team'         => $team,
                'total_points' => $memberIds->sum(fn ($id) => $driverStandings->get($id)['total_points'] ?? 0),
            ];
        }

        $teams = array_values($teams);
        usort($teams, fn ($a, $b) => $b['total_points'] <=> $a['total_points']);

        return $teams;
    }

    // League-owned championships (settings.scoring.points_scheme_id set) score
    // from that PointsScheme's own resolved, stored points_table and bonus
    // values; XCL's own native championships (no scheme selected) keep using
    // the legacy flat columns exactly as before. Points already on the board
    // for a scored round never move because of this — the scheme's table is
    // itself locked the moment a round is scored (PointsScheme::isLockedByCompletedRounds()),
    // so only the *shape of future rounds* can ever be affected by an edit.
    private function buildDriverStandings(): array
    {
        $scheme = $this->pointsScheme();

        $pointsSystem = $this->points_system ?? [];
        $pointsTable  = $scheme?->points_table ?? [];
        $bonusFL      = $scheme?->fastest_lap_points ?? $this->bonus_fastest_lap;
        $bonusPole    = $scheme?->pole_points ?? $this->bonus_pole;
        $bonusLead    = $scheme?->leading_lap_points ?? 0;
        $dropRounds   = $scheme ? (int) ($this->settings->scoring->drop_rounds ?? 0) : $this->drop_rounds;

        // A league-owned championship reads its missed-rounds rule from settings;
        // XCL's own native championships keep the legacy flat columns. Neither was
        // ever actually wired into standings before now — the admin form (and, for
        // leagues, the schema field) existed, but nothing here read them.
        $isLeagueOwned = $this->league_id !== null && $this->league_id !== League::system()->id;
        if ($isLeagueOwned) {
            $maxMissedRounds    = $this->settings->scoring->max_missed_rounds ?? null;
            $missedRoundsAction = $this->settings->scoring->missed_rounds_action ?? 'none';
            $missedRoundsPoints = (int) ($this->settings->scoring->missed_rounds_penalty_points ?? 0);
        } else {
            $maxMissedRounds    = $this->max_missed_rounds;
            $missedRoundsAction = $this->missed_rounds_action ?? 'none';
            $missedRoundsPoints = (int) ($this->missed_rounds_penalty_points ?? 0);
        }

        $finishedRounds = $this->rounds()
            ->where('status', 'finished')
            ->with(['raceResults.user'])
            ->get();

        $penalties = $this->penalties()->get()->groupBy('user_id');

        $driverData = [];

        // Seed every registered (non-spectator) entrant, even one with zero
        // results so far — otherwise a driver who missed every round simply
        // never appears in standings, and max_missed_rounds would have nobody
        // to apply to. Matches how a real championship classification still
        // lists a no-show at the back on zero points, rather than omitting them.
        foreach ($this->registrations()->where('is_spectator', false)->with('user')->get() as $registration) {
            if (!isset($driverData[$registration->user_id])) {
                $driverData[$registration->user_id] = [
                    'user_id' => $registration->user_id,
                    'user'    => $registration->user,
                    'rounds'  => [],
                ];
            }
        }

        foreach ($finishedRounds as $race) {
            $qualiResults = $race->qualiResults()->get();
            $poleUserId   = $qualiResults->first()?->user_id;

            // Percentage-depth schemes resolve their scoring cutoff fresh per
            // round, against that round's own classified-finisher count —
            // never the starting grid, so a retirement elsewhere in the field
            // never changes what a classified finisher scores.
            $classifiedCount = $race->raceResults->where('dnf', false)->count();
            $cutoff          = $scheme ? $scheme->scoringCutoffFor($classifiedCount) : null;

            foreach ($race->raceResults as $result) {
                $userId = $result->user_id;
                if (!isset($driverData[$userId])) {
                    $driverData[$userId] = [
                        'user_id' => $userId,
                        'user'    => $result->user,
                        'rounds'  => [],
                    ];
                }

                $pos = $result->dnf ? null : ($result->position ?? null);
                $pts = 0;

                if ($pos !== null) {
                    if ($scheme) {
                        if ($pos <= $cutoff) {
                            $pts = (float) ($pointsTable[$pos] ?? 0);
                        }
                    } elseif (isset($pointsSystem[$pos - 1])) {
                        $pts = (int) $pointsSystem[$pos - 1];
                    }
                }
                if ($result->fastest_lap) {
                    $pts += $bonusFL;
                }
                if ($poleUserId && $poleUserId === $userId) {
                    $pts += $bonusPole;
                }
                if ($bonusLead > 0 && $result->laps_led > 0) {
                    $pts += $bonusLead;
                }

                $driverData[$userId]['rounds'][] = [
                    'race_id'  => $race->id,
                    'position' => $pos,
                    'points'   => $pts,
                    'dnf'      => $result->dnf,
                ];
            }
        }

        foreach ($driverData as $userId => &$data) {
            $roundPoints = collect($data['rounds'])->pluck('points');

            $dropped = [];
            if ($dropRounds > 0 && $roundPoints->count() > $dropRounds) {
                $sorted    = $roundPoints->sort()->values();
                $dropCount = min($dropRounds, $sorted->count());
                $droppedPts = $sorted->slice(0, $dropCount)->values();

                $tempRounds = collect($data['rounds']);
                foreach ($droppedPts as $dp) {
                    $idx = $tempRounds->search(fn($r) => $r['points'] === $dp && !in_array($r['race_id'], $dropped));
                    if ($idx !== false) {
                        $dropped[] = $tempRounds[$idx]['race_id'];
                    }
                }
            }

            $total = collect($data['rounds'])
                ->filter(fn($r) => !in_array($r['race_id'], $dropped))
                ->sum('points');

            $penaltyPts = isset($penalties[$userId])
                ? $penalties[$userId]->sum('points')
                : 0;

            // Missed rounds only ever count against a driver, never for one —
            // a round dropped as one of their worst scores above still counts
            // as "participated" here, it just didn't score.
            $missedPenalty = 0;
            if ($missedRoundsAction === 'penalise' && $maxMissedRounds !== null && $finishedRounds->isNotEmpty()) {
                $participated = collect($data['rounds'])->pluck('race_id')->unique()->count();
                $missed       = $finishedRounds->count() - $participated;
                if ($missed > $maxMissedRounds) {
                    $missedPenalty = ($missed - $maxMissedRounds) * $missedRoundsPoints;
                }
            }

            $data['dropped']               = $dropped;
            $data['missed_rounds_penalty'] = $missedPenalty;
            $data['total_points']          = $total - $penaltyPts - $missedPenalty;
        }
        unset($data);

        usort($driverData, fn($a, $b) => $b['total_points'] <=> $a['total_points']);

        return $driverData;
    }
}
