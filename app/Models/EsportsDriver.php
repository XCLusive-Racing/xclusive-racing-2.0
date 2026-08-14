<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EsportsDriver extends Model
{
    protected $fillable = ['name', 'slug', 'game', 'flag', 'photo', 'socials', 'sort_order'];

    protected $casts = ['socials' => 'array'];

    public function teamEvents(): BelongsToMany
    {
        return $this->belongsToMany(TeamEvent::class, 'team_event_drivers');
    }

    public function scopeByGame($query, string $game)
    {
        return $query->where('game', $game);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) return null;
        if (str_starts_with($this->photo, 'http') || str_starts_with($this->photo, '/')) return $this->photo;
        return \Illuminate\Support\Facades\Storage::disk('media')->url($this->photo);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_filter($parts));
        return implode('', array_slice($letters, 0, 2));
    }

    public static function gameLabel(string $game): string
    {
        return match ($game) {
            'acc'     => 'ACC',
            'lmu'     => 'LMU',
            'iracing' => 'iRacing',
            default   => strtoupper($game),
        };
    }
}
