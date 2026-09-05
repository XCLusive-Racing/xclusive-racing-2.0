<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Broadcast extends Model
{
    protected $fillable = [
        'author_id', 'title', 'subtitle', 'series', 'color',
        'watch_url', 'starts_at', 'duration_minutes', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Still shows a broadcast once it has started, until its ends_at has passed. */
    public function scopeUpcoming($query)
    {
        return $query->where('ends_at', '>', now())->orderBy('starts_at');
    }

    public function isLive(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
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
}
