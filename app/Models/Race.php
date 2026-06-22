<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Race extends Model
{
    protected $fillable = ['title', 'game', 'track', 'scheduled_at', 'status', 'is_championship', 'event_tag', 'max_drivers', 'description', 'image', 'icon', 'duration_key', 'practice_duration', 'qualifying_duration', 'race_duration', 'car_class', 'sr_requirement', 'min_rating', 'weather', 'time_of_day', 'config_overrides', 'championship_id', 'round_number', 'is_multiclass'];

    protected function casts(): array
    {
        return [
            'scheduled_at'     => 'datetime',
            'config_overrides' => 'array',
            'is_multiclass'    => 'boolean',
        ];
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

    public function results(): HasMany
    {
        return $this->hasMany(RaceResult::class)->orderBy('position');
    }

    public function raceResults(): HasMany
    {
        return $this->hasMany(RaceResult::class)
            ->where('session_type', 'race')
            ->orderBy('position');
    }

    public function qualiResults(): HasMany
    {
        return $this->hasMany(RaceResult::class)
            ->where('session_type', 'quali')
            ->orderBy('position');
    }

    public function isRegistered(User $user): bool
    {
        return $this->registrations()->where('user_id', $user->id)->exists();
    }

    public function isPast(): bool
    {
        return $this->scheduled_at->isPast();
    }

    public function isFull(): bool
    {
        if ($this->max_drivers === null) {
            return false;
        }
        return $this->registrations()->count() >= $this->max_drivers;
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

    public function scheduledAtUk(): \Carbon\Carbon
    {
        return $this->scheduled_at->timezone('Europe/London');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('media')->url($this->image) : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? Storage::disk('media')->url($this->icon) : null;
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
}