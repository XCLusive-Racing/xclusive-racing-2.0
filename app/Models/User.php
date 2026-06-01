<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function canManage(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
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
