<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

// A weekly Time Trial event. See docs/time-trials/PLAN.md for the full flow.
#[Fillable([
    'title', 'track', 'car_class', 'starts_at', 'ends_at', 'ftp_server_id', 'is_published', 'forced_entry_list',
    'last_pushed_at', 'last_push_error', 'last_entry_count',
    'results_checked_at', 'results_error', 'finalized_at',
])]
class TimeTrialEvent extends Model
{
    // ACC server carGroup values an event can be limited to (null = every class).
    public const CAR_CLASSES = ['GT3', 'GT4', 'GT2', 'GTC', 'TCX'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
            'forced_entry_list' => 'boolean',
            'last_pushed_at' => 'datetime',
            'results_checked_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(FtpServer::class, 'ftp_server_id')->withoutTenantScope();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TimeTrialRegistration::class);
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'time_trial_registrations')->withTimestamps();
    }

    public function laps(): HasMany
    {
        return $this->hasMany(TimeTrialEventLap::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TimeTrialEventResult::class)->orderBy('position');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    // Published and not yet ended: the one to show on the public page (live first, else next).
    public function scopeCurrentOrNext(Builder $query): void
    {
        $query->published()->where('ends_at', '>', now())->orderBy('starts_at');
    }

    public function status(): string
    {
        return match (true) {
            ! $this->is_published => 'draft',
            $this->finalized_at !== null => 'finished',
            now()->lt($this->starts_at) => 'upcoming',
            now()->lt($this->ends_at) => 'live',
            default => 'closed',
        };
    }

    public function isOpenForSignup(): bool
    {
        return $this->is_published && $this->finalized_at === null && now()->lt($this->ends_at);
    }

    public function trackName(): string
    {
        return TimeTrialLap::trackName($this->track);
    }

    // The track's stock image from the media library, like championship rounds use.
    public function imageUrl(): ?string
    {
        $path = Race::trackImagePath($this->trackName());

        return $path ? Storage::disk('media')->url($path) : null;
    }

    public function displayTitle(): string
    {
        return $this->title ?: $this->trackName().' Time Trial';
    }

    public function classLabel(): string
    {
        return $this->car_class ?? 'All classes';
    }

    public function isRegistered(?User $user): bool
    {
        return $user !== null && $this->registrations()->where('user_id', $user->id)->exists();
    }
}
