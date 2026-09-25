<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A league's reusable race format — session lengths, how many races a round runs
// and their lengths, pit/tyre rules — picked per round on Add/Edit Round, where it
// prefills the round's own fields (the round keeps its own copy, so editing a
// format later never changes a round already set up with it).
class SessionFormat extends Model
{
    use Tenantable;

    protected $fillable = [
        'league_id', 'name', 'description', 'practice_duration', 'qualifying_duration',
        'race_durations', 'pitstop_count', 'fixed_stop_time', 'tyre_set_count',
    ];

    protected function casts(): array
    {
        return [
            'race_durations' => 'array',
            'fixed_stop_time' => 'boolean',
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    // "P 10 · Q 15 · R 25 + 25" — one-line summary for lists and pickers.
    public function summary(): string
    {
        $parts = [];
        if ($this->practice_duration) {
            $parts[] = 'P '.$this->practice_duration;
        }
        if ($this->qualifying_duration) {
            $parts[] = 'Q '.$this->qualifying_duration;
        }
        $parts[] = 'R '.implode(' + ', $this->race_durations ?? []);

        return implode(' · ', $parts).' min';
    }

    // The same "25, 25" string the round form's Races field takes.
    public function raceLengthsText(): string
    {
        return implode(', ', $this->race_durations ?? []);
    }
}
