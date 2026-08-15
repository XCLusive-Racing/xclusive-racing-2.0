<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeamEvent extends Model
{
    protected $fillable = ['subject', 'title', 'subtitle', 'starts_at', 'duration_minutes', 'ends_at', 'watch_url', 'image'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function participatingDrivers(): BelongsToMany
    {
        return $this->belongsToMany(EsportsDriver::class, 'team_event_drivers')->orderBy('esports_drivers.sort_order');
    }

    /** Still shows an event once it has started, until its ends_at has passed. */
    public function scopeUpcoming($query)
    {
        return $query->where('ends_at', '>', now())->orderBy('starts_at');
    }

    public function isLive(): bool
    {
        return $this->ends_at && now()->between($this->starts_at, $this->ends_at);
    }

    public static function durationOptions(): array
    {
        return [
            30   => '30 min',
            60   => '1 hour',
            120  => '2 hours',
            180  => '3 hours',
            360  => '6 hours',
            720  => '12 hours',
            1440 => '24 hours',
        ];
    }

    public function getDurationBadgeAttribute(): string
    {
        $mins = (int) $this->duration_minutes;

        return $mins % 60 === 0 ? ($mins / 60) . 'H RACE' : $mins . 'M RACE';
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) return null;
        if (str_starts_with($this->image, 'http')) return $this->image;
        return \Illuminate\Support\Facades\Storage::disk('media')->url($this->image);
    }

    public function scopeForSubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }

    public static function subjects(): array
    {
        return [
            'dirk-schouten'    => 'Dirk Schouten',
            'mats-van-rooijen' => 'Mats van Rooijen',
            'jesse-aalbregt'   => 'Jesse Aalbregt',
            'acc-team'         => 'ACC Team',
            'lmu-team'         => 'LMU Team',
            'iracing-team'     => 'iRacing Team',
        ];
    }

    /** Maps an esports-team subject to its game key (used to filter the driver picker). */
    public static function teamSubjectGames(): array
    {
        return [
            'acc-team'     => 'acc',
            'lmu-team'     => 'lmu',
            'iracing-team' => 'iracing',
        ];
    }
}
