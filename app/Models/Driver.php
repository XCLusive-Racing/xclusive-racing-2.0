<?php

namespace App\Models;

use App\Enums\SafetyRatingGrade;
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
            'date_joined' => 'date',
            'xcl_rating' => 'decimal:2',
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
            ['min' => 10000, 'max' => PHP_INT_MAX, 'class' => 3, 'label' => 'Legend',   'slug' => 'legend'],
            ['min' => 8000, 'max' => 9999,        'class' => 3, 'label' => 'Alien',    'slug' => 'alien'],
            ['min' => 6500, 'max' => 7999,        'class' => 3, 'label' => 'Platinum', 'slug' => 'platinum'],
            ['min' => 5000, 'max' => 6499,        'class' => 2, 'label' => 'Gold',     'slug' => 'gold'],
            ['min' => 3500, 'max' => 4999,        'class' => 2, 'label' => 'Silver',   'slug' => 'silver'],
            ['min' => 2000, 'max' => 3499,        'class' => 1, 'label' => 'Bronze',   'slug' => 'bronze'],
            ['min' => 0,    'max' => 1999,        'class' => 0, 'label' => 'Rookie',   'slug' => 'rookie'],
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
        return '/assets/badges/'.$this->class.'.svg';
    }

    public function getSrClassAttribute(): array
    {
        return SafetyRatingGrade::fromRating((float) $this->safety_rating)->toArray();
    }

    // Compares current rating class vs stored status — full discord integration comes later
    public function getStatusLabelAttribute(): string
    {
        if (! $this->status) {
            return 'Valid';
        }
        $currentClass = $this->class;
        $statusLower = strtolower($this->status);
        if ($statusLower !== $currentClass && in_array($statusLower, ['rookie', 'bronze', 'silver', 'gold', 'platinum', 'alien', 'legend'])) {
            return 'Promote';
        }

        return 'Valid';
    }
}
