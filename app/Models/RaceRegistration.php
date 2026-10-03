<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RaceRegistration extends Model
{
    use SoftDeletes;

    protected $fillable = ['race_id', 'user_id', 'race_class_id', 'team_entry_id', 'stream_url'];

    // Hosts a supporter's stream link may point at (RaceController::updateStream()).
    public const STREAM_HOSTS = [
        'twitch' => ['twitch.tv', 'www.twitch.tv', 'm.twitch.tv'],
        'youtube' => ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'],
    ];

    // 'twitch' / 'youtube' for a stream URL on one of STREAM_HOSTS, null otherwise.
    public static function streamPlatformOf(?string $url): ?string
    {
        if (! $url || ! preg_match('#^https?://#i', $url)) {
            return null;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        foreach (self::STREAM_HOSTS as $platform => $hosts) {
            if (in_array($host, $hosts, true)) {
                return $platform;
            }
        }

        return null;
    }

    // The link shown in the event's streamers bar: the one the driver added to this race
    // (a copy of their profile link, RaceController::updateStream()) — only while they're
    // a supporter. A profile link alone doesn't put anyone in the bar.
    public function effectiveStreamUrl(): ?string
    {
        if (! $this->user?->isSupporter()) {
            return null;
        }

        return self::streamPlatformOf($this->stream_url) ? $this->stream_url : null;
    }

    public function streamPlatform(): ?string
    {
        return self::streamPlatformOf($this->effectiveStreamUrl());
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function raceClass(): BelongsTo
    {
        return $this->belongsTo(RaceClass::class);
    }

    public function teamEntry(): BelongsTo
    {
        return $this->belongsTo(RaceTeamEntry::class, 'team_entry_id');
    }
}
