<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'must_set_password', 'country', 'platform', 'platform_id', 'car_number', 'car_model', 'banner', 'game', 'team', 'role', 'flag', 'elo_acc', 'elo_lmu', 'elo_iracing', 'sr_acc', 'sr_lmu', 'sr_iracing'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'must_set_password' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('slug', $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->contains(fn($r) => in_array($r->slug, $roles));
    }

    public function isOwner(): bool        { return $this->hasRole('owner'); }
    public function isAdmin(): bool        { return $this->hasRole('admin'); }
    public function isModerator(): bool    { return $this->hasRole('moderator'); }
    public function isEventManager(): bool { return $this->hasRole('event_manager'); }
    public function isSteward(): bool      { return $this->hasRole('steward'); }
    public function isDriver(): bool       { return $this->hasRole('driver'); }
    public function isSuperAdmin(): bool   { return $this->isOwner(); }

    public function canManage(): bool
    {
        return $this->hasAnyRole(['owner', 'admin', 'moderator', 'event_manager', 'steward']);
    }

    public function canSeeUsers(): bool
    {
        return $this->hasAnyRole(['owner', 'admin', 'moderator']);
    }

    public function canManageEvents(): bool
    {
        return $this->hasAnyRole(['owner', 'admin', 'event_manager']);
    }

    public function raceRegistrations(): HasMany
    {
        return $this->hasMany(RaceRegistration::class);
    }

    public function raceResults(): HasMany
    {
        return $this->hasMany(RaceResult::class);
    }

    public function carAssignments(): HasMany
    {
        return $this->hasMany(CarAssignment::class);
    }

    public function carForGame(string $game): ?Car
    {
        return $this->carAssignments()
            ->whereNull('championship_id')
            ->whereHas('car', fn($q) => $q->where('game', $game))
            ->latest()
            ->first()
            ?->car;
    }

    // --- Rank based on XCL rating ---

    public static function ranks(): array
    {
        return [
            ['name' => 'Alien',    'slug' => 'alien',    'min' => 8000, 'color' => '#10b981'],
            ['name' => 'Platinum', 'slug' => 'platinum', 'min' => 6500, 'color' => '#7c3aed'],
            ['name' => 'Gold',     'slug' => 'gold',     'min' => 5000, 'color' => '#f59e0b'],
            ['name' => 'Silver',   'slug' => 'silver',   'min' => 3500, 'color' => '#9ca3af'],
            ['name' => 'Bronze',   'slug' => 'bronze',   'min' => 2000, 'color' => '#cd7f32'],
            ['name' => 'Rookie',   'slug' => 'rookie',   'min' => 0,    'color' => '#ef4444'],
        ];
    }

    public function rank(string $game = 'acc'): array
    {
        $elo = (int) ($this->{"elo_{$game}"} ?? 0);
        foreach (self::ranks() as $rank) {
            if ($elo >= $rank['min']) return $rank;
        }
        return end(self::ranks());
    }

    // --- Rating class (0–3) based on XCL rating score ---

    public static function ratingClasses(): array
    {
        return [
            ['min' => 0,    'max' => 2000,  'class' => 0],
            ['min' => 2001, 'max' => 4000,  'class' => 1],
            ['min' => 4001, 'max' => 5000,  'class' => 2],
            ['min' => 5001, 'max' => 10000, 'class' => 3],
        ];
    }

    public function ratingClass(string $game = 'acc'): int
    {
        $elo = $this->{"elo_{$game}"} ?? 0;
        foreach (self::ratingClasses() as $tier) {
            if ($elo >= $tier['min'] && $elo <= $tier['max']) {
                return $tier['class'];
            }
        }
        return 0;
    }

    // --- SR grade (D–Z) with hex color ---

    public static function srGrades(): array
    {
        return [
            ['grade' => 'D', 'color' => '#000000', 'min' => 0.01, 'max' => 3.00],
            ['grade' => 'C', 'color' => '#ff8000', 'min' => 3.00, 'max' => 5.00],
            ['grade' => 'B', 'color' => '#cc0000', 'min' => 5.00, 'max' => 6.00],
            ['grade' => 'A', 'color' => '#47b417', 'min' => 6.00, 'max' => 7.00],
            ['grade' => 'X', 'color' => '#3c81f3', 'min' => 7.00, 'max' => 8.00],
            ['grade' => 'Y', 'color' => '#ffc71d', 'min' => 8.00, 'max' => 9.00],
            ['grade' => 'Z', 'color' => '#a928ff', 'min' => 9.00, 'max' => 10.00],
        ];
    }

    public function srGrade(string $game = 'acc'): array
    {
        $sr     = (float) ($this->{"sr_{$game}"} ?? 0);
        $grades = self::srGrades();

        foreach ($grades as $grade) {
            if ($sr >= $grade['min'] && $sr < $grade['max']) {
                return $grade;
            }
        }

        return $sr >= 9.00 ? end($grades) : reset($grades);
    }
}
