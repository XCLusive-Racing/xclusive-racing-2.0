<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Race extends Model
{
    protected $fillable = ['title', 'game', 'track', 'scheduled_at', 'status', 'is_championship', 'event_tag', 'max_drivers', 'description', 'image', 'icon', 'duration_key', 'practice_duration', 'qualifying_duration', 'race_duration', 'car_class', 'sr_requirement', 'min_rating', 'max_rating', 'weather', 'time_of_day', 'config_overrides', 'championship_id', 'round_number', 'is_multiclass', 'event_format_id', 'ftp_server_id', 'slot_time', 'config_pushed_at', 'config_push_status', 'config_push_attempts', 'config_push_error'];

    protected function casts(): array
    {
        return [
            'scheduled_at'       => 'datetime',
            'config_overrides'   => 'array',
            'is_multiclass'      => 'boolean',
            'slot_time'          => 'datetime',
            'config_pushed_at'   => 'datetime',
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

    public function eventFormat(): BelongsTo
    {
        return $this->belongsTo(\App\Models\EventFormat::class);
    }

    public function ftpServer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FtpServer::class, 'ftp_server_id');
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
        if ($this->is_multiclass && $this->raceClasses->isNotEmpty()) {
            return $this->raceClasses->every(fn($cls) => $cls->isFull());
        }

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

    /** Returns [letter, hex-color] for the SR tier badge. */
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