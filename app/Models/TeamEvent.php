<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamEvent extends Model
{
    protected $fillable = ['subject', 'title', 'subtitle', 'starts_at', 'watch_url', 'image'];

    protected $casts = ['starts_at' => 'datetime'];

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now())->orderBy('starts_at');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) return null;
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
}
