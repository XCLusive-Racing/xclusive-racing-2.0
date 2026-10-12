<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One step of the rank curve: the XP a rank starts at (cumulative_xp) and what the next
// one costs. Read through App\Xp\RankCurve.
class RankLevel extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'rank';

    protected $fillable = ['rank', 'xp_to_next', 'cumulative_xp'];

    protected function casts(): array
    {
        return ['rank' => 'integer', 'xp_to_next' => 'integer', 'cumulative_xp' => 'integer'];
    }
}
