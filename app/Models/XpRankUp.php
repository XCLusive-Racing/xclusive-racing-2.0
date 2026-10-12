<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A driver's rank going up, kept for the reveal.
class XpRankUp extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'from_rank', 'to_rank', 'race_id', 'seen_at'];

    protected function casts(): array
    {
        return ['seen_at' => 'datetime'];
    }
}
