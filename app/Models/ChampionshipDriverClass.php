<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// A driver class (Pro / Pro-Am / Am …): entries are put in one by the league by
// hand, independent of the car they drive (car classes are ChampionshipClass).
class ChampionshipDriverClass extends Model
{
    // ACC entrylist driverCategory => the in-game number banner it gives.
    public const ACC_CATEGORIES = [
        2 => ['label' => 'White', 'color' => '#ffffff'],
        1 => ['label' => 'Grey', 'color' => '#9ca3af'],
        0 => ['label' => 'Red', 'color' => '#ef4444'],
    ];

    protected $fillable = ['championship_id', 'name', 'acc_category', 'max_entries', 'sort_order'];

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
