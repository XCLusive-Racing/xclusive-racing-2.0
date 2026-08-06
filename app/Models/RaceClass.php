<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaceClass extends Model
{
    protected $fillable = [
        'race_id', 'name', 'color', 'car_class',
        'max_drivers', 'sr_requirement', 'min_rating', 'sort_order',
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(RaceRegistration::class);
    }

    public function isFull(): bool
    {
        if ($this->max_drivers === null) {
            return false;
        }
        return $this->registrations()->count() >= $this->max_drivers;
    }

    /** Returns [grade-letter, hex-color] for the Min. SR badge. */
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

    /** Returns [display-name, hex-color] for the XCL Rating tier badge. */
    public function xclTierInfo(): array
    {
        return match ($this->min_rating) {
            'rookie'   => ['Rookie',   '#ef4444'],
            'bronze'   => ['Bronze',   '#cd7f32'],
            'silver'   => ['Silver',   '#9ca3af'],
            'gold'     => ['Gold',     '#f59e0b'],
            'platinum' => ['Platinum', '#7c3aed'],
            'alien'    => ['Alien',    '#10b981'],
            default    => ['',         '#6b7280'],
        };
    }
}
