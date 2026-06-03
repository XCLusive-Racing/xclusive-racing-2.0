<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


#[Fillable([
    'gamertag', 'number', 'xcl_rating', 'xuid_psid', 'safety_rating', 'dns_count',
    'discord', 'abbreviation', 'first_name', 'last_name', 'country_code',
    'car', 'car_id', 'team', 'date_joined', 'platform', 'status',
])]
class Driver extends Model
{
    protected function casts(): array
    {
        return [
            'date_joined'   => 'date',
            'xcl_rating'    => 'decimal:2',
            'safety_rating' => 'decimal:2',
        ];
    }

    public function stats(): HasOne
    {
        return $this->hasOne(DriverStats::class);
    }

    public function trackTimes(): HasMany
    {
        return $this->hasMany(DriverTrackTime::class);
    }

    public function hotlaps(): HasMany
    {
        return $this->hasMany(Hotlap::class);
    }

    // --- XCL Rating brackets (matches User::ratingClasses) ---

    public static function brackets(): array
    {
        return [
            ['min' => 8000, 'max' => PHP_INT_MAX, 'class' => 3, 'label' => 'Alien',    'slug' => 'alien'],
            ['min' => 6500, 'max' => 7999,        'class' => 3, 'label' => 'Platinum', 'slug' => 'platinum'],
            ['min' => 5000, 'max' => 6499,        'class' => 2, 'label' => 'Gold',     'slug' => 'gold'],
            ['min' => 3500, 'max' => 4999,        'class' => 2, 'label' => 'Silver',   'slug' => 'silver'],
            ['min' => 2000, 'max' => 3499,        'class' => 1, 'label' => 'Bronze',   'slug' => 'bronze'],
            ['min' => 0,    'max' => 1999,        'class' => 0, 'label' => 'Rookie',   'slug' => 'rookie'],
        ];
    }

    public static function srGrades(): array
    {
        return [
            ['grade' => 'Z', 'color' => '#a928ff', 'min' => 9.00, 'max' => 10.00],
            ['grade' => 'Y', 'color' => '#ffc71d', 'min' => 8.00, 'max' => 9.00],
            ['grade' => 'X', 'color' => '#3c81f3', 'min' => 7.00, 'max' => 8.00],
            ['grade' => 'A', 'color' => '#47b417', 'min' => 6.00, 'max' => 7.00],
            ['grade' => 'B', 'color' => '#cc0000', 'min' => 5.00, 'max' => 6.00],
            ['grade' => 'C', 'color' => '#ff8000', 'min' => 3.00, 'max' => 5.00],
            ['grade' => 'D', 'color' => '#000000', 'min' => 0.00, 'max' => 3.00],
        ];
    }

    // --- Computed accessors (NOT stored in DB) ---

    public function getClassAttribute(): string
    {
        $rating = (float) $this->xcl_rating;
        foreach (self::brackets() as $bracket) {
            if ($rating >= $bracket['min'] && $rating <= $bracket['max']) {
                return $bracket['slug'];
            }
        }
        return 'rookie';
    }

    public function getBannerLevelAttribute(): int
    {
        $rating = (float) $this->xcl_rating;
        foreach (self::brackets() as $bracket) {
            if ($rating >= $bracket['min'] && $rating <= $bracket['max']) {
                return $bracket['class'];
            }
        }
        return 0;
    }

    public function getBadgeUrlAttribute(): string
    {
        return '/assets/badges/' . $this->class . '.svg';
    }

    public function getSrClassAttribute(): array
    {
        $sr = (float) $this->safety_rating;
        foreach (self::srGrades() as $grade) {
            if ($sr >= $grade['min'] && $sr < $grade['max']) {
                return $grade;
            }
        }
        return ['grade' => 'D', 'color' => '#000000'];
    }

    // Compares current rating class vs stored status — full discord integration comes later
    public function getStatusLabelAttribute(): string
    {
        if (!$this->status) {
            return 'Valid';
        }
        $currentClass = $this->class;
        $statusLower  = strtolower($this->status);
        if ($statusLower !== $currentClass && in_array($statusLower, ['rookie', 'bronze', 'silver', 'gold', 'platinum', 'alien'])) {
            return 'Promote';
        }
        return 'Valid';
    }
}