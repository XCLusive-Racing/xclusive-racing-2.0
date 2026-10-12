<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A driver holding an achievement. xp_awarded is what it was worth at that moment and is
// never read back from the catalogue, so editing an achievement leaves history alone.
class AchievementAward extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'achievement_id', 'xp_awarded', 'game', 'race_id', 'source_type', 'source_id', 'awarded_at', 'seen_at',
    ];

    protected function casts(): array
    {
        return ['awarded_at' => 'datetime', 'seen_at' => 'datetime', 'xp_awarded' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
