<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventFormat extends Model
{
    protected $fillable = [
        'game', 'name', 'formation_type',
        'practice_mins', 'quali_mins', 'race1_mins', 'quali2_mins', 'race2_mins',
        'pitstop_type', 'pitstop_count', 'min_stop_secs',
        'xcl_r_multiplier', 'server_preference', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'xcl_r_multiplier' => 'float',
        ];
    }

    public function isDouble(): bool
    {
        return $this->race2_mins !== null;
    }

    public function pitstopLabel(): string
    {
        if ($this->pitstop_type === 'none' || $this->pitstop_count === 0) {
            return 'None';
        }
        $label = 'Fuel Only (' . $this->pitstop_count . 'x';
        if ($this->min_stop_secs) {
            $label .= ', min ' . $this->min_stop_secs . 's';
        }
        return $label . ')';
    }

    public function xclRLabel(): string
    {
        return '×' . number_format($this->xcl_r_multiplier, 1);
    }
}
