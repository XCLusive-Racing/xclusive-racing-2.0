<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Result extends Model
{
    public const TYPES = [
        'race' => 'Race result',
        'standings' => 'Championship live standings',
        'final' => 'Championship final result',
    ];

    protected $fillable = ['subject', 'category', 'type', 'year', 'title', 'round_label', 'standing', 'notes', 'sort_order'];

    protected $casts = ['year' => 'integer'];

    public function races(): HasMany
    {
        return $this->hasMany(ResultRace::class)->orderBy('sort_order');
    }

    public function scopeForSubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeForDriver($query, int $driverId)
    {
        return $query->whereHas('races.positions', fn ($q) => $q->where('esports_driver_id', $driverId));
    }

    // Reshapes this pro subject's DB rows back into the exact nested array shape
    // teams/pro/show.blade.php already expects (and ProDriverController::allDrivers()
    // used to hardcode) — year => [ ['championship'=>, 'races'=>[...], 'standing'=>] ]
    // — so that blade needs zero changes. Always includes the current and previous
    // calendar year as empty placeholders even with no DB rows for them, matching
    // the old hardcoded array's behaviour, newest year first.
    public static function legacyProArrayForSubject(string $subject): array
    {
        $rows = static::forSubject($subject)->category('pro')
            ->with(['races.positions'])
            ->orderBy('year')->orderBy('sort_order')
            ->get();

        $byYear = [];
        foreach ($rows as $row) {
            $byYear[$row->year][] = [
                'championship' => $row->title,
                'races' => $row->races->map(function (ResultRace $race) {
                    $shaped = ['track' => $race->track];
                    if ($race->car_class !== null) {
                        $shaped['class'] = $race->car_class;
                    }
                    $shaped['positions'] = $race->positions->pluck('position')->all();

                    return $shaped;
                })->all(),
                'standing' => $row->standing ?? '',
            ];
        }

        $currentYear = (int) now()->format('Y');
        foreach ([$currentYear, $currentYear - 1] as $y) {
            $byYear[$y] ??= [];
        }

        krsort($byYear);

        return $byYear;
    }
}
