<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// One achievement of the catalogue. A row, not code, so it can be added and edited
// without a deploy; the metric it counts is code (App\Xp\MetricRegistry). Never deleted:
// archiving takes it off the list of things still to earn, and drivers who hold it keep it.
class Achievement extends Model
{
    public const SCOPE_ALL = 'all';

    protected $fillable = [
        'code', 'name', 'description', 'category', 'family', 'metric_key', 'game_scope',
        'threshold', 'comparison', 'xp', 'sort_order', 'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'threshold' => 'float',
            'xp' => 'integer',
            'is_archived' => 'boolean',
        ];
    }

    public function awards(): HasMany
    {
        return $this->hasMany(AchievementAward::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function isMetBy(float $value): bool
    {
        return $this->comparison === 'lte' ? $value <= $this->threshold : $value >= $this->threshold;
    }
}
