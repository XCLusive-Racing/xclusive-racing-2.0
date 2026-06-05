<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Race extends Model
{
    protected $fillable = ['title', 'game', 'track', 'scheduled_at', 'status', 'is_championship', 'event_tag', 'max_drivers', 'description', 'image', 'icon'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
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
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? Storage::disk('public')->url($this->icon) : null;
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