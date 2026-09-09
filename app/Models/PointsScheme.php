<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsScheme extends Model
{
    use Tenantable;

    protected $fillable = ['league_id', 'name', 'points_map', 'fastest_lap_points', 'pole_points', 'is_template'];

    protected function casts(): array
    {
        return [
            'points_map'         => 'array',
            'fastest_lap_points' => 'integer',
            'pole_points'        => 'integer',
            'is_template'        => 'boolean',
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    // League managers create their own scheme by copying a template, never by
    // editing a shared one — the template itself is untouched by this.
    public function copyFor(League $league, string $name): self
    {
        return self::create([
            'league_id'          => $league->id,
            'name'               => $name,
            'points_map'         => $this->points_map,
            'fastest_lap_points' => $this->fastest_lap_points,
            'pole_points'        => $this->pole_points,
            'is_template'        => false,
        ]);
    }
}
