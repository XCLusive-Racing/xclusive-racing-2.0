<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A title XP is earned in (docs/xp-achievements/PLAN.md). A race's `game` maps onto one
// of these through App\Xp\Titles.
class Game extends Model
{
    protected $fillable = ['key', 'name', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Game key => name, in display order. */
    public static function options(): array
    {
        return self::orderBy('sort_order')->orderBy('name')->pluck('name', 'key')->all();
    }
}
