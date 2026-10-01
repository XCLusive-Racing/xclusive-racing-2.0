<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// A driver class (Pro / Pro-Am / Am …), independent of the car an entry drives (car
// classes are ChampionshipClass). An entry lands in one automatically when the
// class has a rank range (min_rank/max_rank), otherwise the league puts it in by hand.
class ChampionshipDriverClass extends Model
{
    // ACC entrylist driverCategory => the in-game number banner it gives.
    public const ACC_CATEGORIES = [
        2 => ['label' => 'White', 'color' => '#ffffff'],
        1 => ['label' => 'Grey', 'color' => '#9ca3af'],
        0 => ['label' => 'Red', 'color' => '#ef4444'],
    ];

    protected $fillable = ['championship_id', 'name', 'acc_category', 'max_entries', 'min_rank', 'max_rank', 'sort_order'];

    // Whether a driver of this XCL rank (User::ranks() slug) belongs in this class.
    // Either bound may be open ("silver and up", "up to bronze"); with neither set
    // the class covers no rank at all, so it stays manual-only.
    public function coversRank(string $rank): bool
    {
        if ($this->min_rank === null && $this->max_rank === null) {
            return false;
        }

        // User::ranks() runs highest first, so a higher rank has a lower index.
        $order = array_column(User::ranks(), 'slug');
        $index = array_search($rank, $order, true);
        if ($index === false) {
            return false;
        }

        $minIndex = $this->min_rank !== null ? array_search($this->min_rank, $order, true) : false;
        $maxIndex = $this->max_rank !== null ? array_search($this->max_rank, $order, true) : false;

        return ($minIndex === false || $index <= $minIndex)
            && ($maxIndex === false || $index >= $maxIndex);
    }

    // "Silver+", "Rookie – Bronze", "Up to Bronze" — null when no range is set.
    public function rankRangeLabel(): ?string
    {
        $min = $this->min_rank ? ucfirst($this->min_rank) : null;
        $max = $this->max_rank ? ucfirst($this->max_rank) : null;

        return match (true) {
            $min && $max => $min === $max ? $min : $min.' – '.$max,
            (bool) $min => $min.'+',
            (bool) $max => 'Up to '.$max,
            default => null,
        };
    }

    protected function casts(): array
    {
        return ['acc_category' => 'integer', 'max_entries' => 'integer'];
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ChampionshipRegistration::class, 'driver_class_id');
    }

    // Colour for the class label on the site: its banner colour, or a neutral
    // purple when the banner follows each driver's XCL rating.
    public function color(): string
    {
        return self::ACC_CATEGORIES[$this->acc_category]['color'] ?? '#a78bfa';
    }

    public function isFull(?int $ignoreRegistrationId = null): bool
    {
        if ($this->max_entries === null) {
            return false;
        }

        return $this->registrations()
            ->when($ignoreRegistrationId, fn ($q) => $q->whereKeyNot($ignoreRegistrationId))
            ->count() >= $this->max_entries;
    }
}
