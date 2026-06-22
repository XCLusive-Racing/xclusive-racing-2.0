<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Championship extends Model
{
    protected $fillable = [
        'name', 'game', 'season', 'status', 'description', 'image', 'icon',
        'max_drivers', 'is_multiclass', 'points_system', 'bonus_fastest_lap',
        'bonus_pole', 'drop_rounds', 'max_missed_rounds', 'missed_rounds_action',
        'missed_rounds_penalty_points', 'registration_open', 'registration_deadline',
        'sr_requirement', 'min_rating', 'car_class', 'practice_duration',
        'qualifying_duration', 'race_duration', 'weather', 'time_of_day', 'duration_key',
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
        ];
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
            'ac'      => 'AC Rally',
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

    public function computeStandings(): array
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
                        'user'   => $result->user,
                        'rounds' => [],
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
