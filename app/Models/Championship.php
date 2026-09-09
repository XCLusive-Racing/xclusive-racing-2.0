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

    public function ratingApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'xcl_rating_approved_by');
    }

    public function pointsScheme(): ?PointsScheme
    {
        $id = $this->settings->scoring->points_scheme_id ?? null;
        return $id ? PointsScheme::find($id) : null;
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
        return $this->registrations()->count() >= $this->max_drivers;
    }

    public function isRegistered(User $user): bool
    {
        return $this->registrations()->where('user_id', $user->id)->exists();
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

        $registration = $this->registrations()->where('user_id', $user->id)->first();
        if (!$registration) {
            return false;
        }

        $rank = $this->registrations()->where('created_at', '<', $registration->created_at)->count();

        return $rank >= $this->max_drivers;
    }

    public function waitlistCount(): int
    {
        if (!$this->waitlistEnabled() || $this->max_drivers === null) {
            return 0;
        }

        return max(0, $this->registrations()->count() - $this->max_drivers);
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

    private function buildDriverStandings(): array
    {
        $pointsSystem = $this->points_system ?? [];
        $bonusFL      = $this->bonus_fastest_lap;
        $bonusPole    = $this->bonus_pole;
        $dropRounds   = $this->drop_rounds;

        $finishedRounds = $this->rounds()
            ->where('status', 'finished')
            ->with(['raceResults.user'])
            ->get();

        $penalties = $this->penalties()->get()->groupBy('user_id');

        $driverData = [];

        foreach ($finishedRounds as $race) {
            $qualiResults = $race->qualiResults()->get();
            $poleUserId   = $qualiResults->first()?->user_id;

            foreach ($race->raceResults as $result) {
                $userId = $result->user_id;
                if (!isset($driverData[$userId])) {
                    $driverData[$userId] = [
                        'user_id' => $userId,
                        'user'    => $result->user,
                        'rounds'  => [],
                    ];
                }

                $pos    = $result->dnf ? null : ($result->position ?? null);
                $pts    = 0;
                if ($pos !== null && isset($pointsSystem[$pos - 1])) {
                    $pts = (int) $pointsSystem[$pos - 1];
                }
                if ($result->fastest_lap) {
                    $pts += $bonusFL;
                }
                if ($poleUserId && $poleUserId === $userId) {
                    $pts += $bonusPole;
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

            $data['dropped']      = $dropped;
            $data['total_points'] = $total - $penaltyPts;
        }
        unset($data);

        usort($driverData, fn($a, $b) => $b['total_points'] <=> $a['total_points']);

        return $driverData;
    }
}
