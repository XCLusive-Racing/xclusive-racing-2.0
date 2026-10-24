<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EsportsDriver extends Model
{
    protected $fillable = ['name', 'slug', 'game', 'extra_games', 'flag', 'photo', 'socials', 'sort_order'];

    protected $casts = ['socials' => 'array', 'extra_games' => 'array'];

    /** Every team the driver races for: their own `game` first, then any `extra_games`. */
    public function games(): array
    {
        return array_values(array_unique([$this->game, ...($this->extra_games ?? [])]));
    }

    /**
     * Drivers keyed by game, a driver listed under every team they race for. `sort_order`
     * only ranks drivers within their own game, so a driver joining from another game is
     * slotted in by surname (the order the rosters are kept in).
     */
    public static function groupedByGame(): Collection
    {
        $drivers = static::orderBy('sort_order')->get();

        return $drivers->flatMap(fn (self $driver) => $driver->games())->unique()->values()
            ->mapWithKeys(function (string $game) use ($drivers) {
                $roster = $drivers->where('game', $game)->values();

                foreach ($drivers->filter(fn (self $d) => $d->game !== $game && in_array($game, $d->games(), true)) as $guest) {
                    $at = $roster->search(fn (self $d) => strcasecmp($d->surname(), $guest->surname()) > 0);
                    $roster->splice($at === false ? $roster->count() : $at, 0, [$guest]);
                }

                return [$game => $roster];
            });
    }

    private function surname(): string
    {
        return Str::ascii(Str::afterLast(trim($this->name), ' '));
    }

    public function teamEvents(): BelongsToMany
    {
        return $this->belongsToMany(TeamEvent::class, 'team_event_drivers');
    }

    public function resultRacePositions(): HasMany
    {
        return $this->hasMany(ResultRacePosition::class, 'esports_driver_id');
    }

    public function scopeByGame($query, string $game)
    {
        return $query->where('game', $game);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }
        if (str_starts_with($this->photo, 'http') || str_starts_with($this->photo, '/')) {
            return $this->photo;
        }

        return Storage::disk('media')->url($this->photo);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $letters = array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_filter($parts));

        return implode('', array_slice($letters, 0, 2));
    }

    public static function gameLabel(string $game): string
    {
        return match ($game) {
            'acc' => 'ACC',
            'lmu' => 'LMU',
            'iracing' => 'iRacing',
            default => strtoupper($game),
        };
    }
}
