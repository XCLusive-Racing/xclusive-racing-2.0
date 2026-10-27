<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventFormat extends Model
{
    protected $fillable = [
        'game', 'name', 'formation_type',
        'practice_mins', 'quali_mins', 'race1_mins', 'quali2_mins', 'race2_mins',
        'pitstop_type', 'pitstop_count', 'mandatory_refuelling', 'mandatory_tyre_change', 'min_stop_secs',
        'xcl_r_multiplier', 'server_preference', 'server_group', 'default_event_tag', 'sort_order',
    ];

    // The stop time is either free or this fixed ACC value, nothing in between.
    public const FIXED_STOP_SECS = 25;

    protected function casts(): array
    {
        return [
            'xcl_r_multiplier' => 'float',
            'mandatory_refuelling' => 'boolean',
            'mandatory_tyre_change' => 'boolean',
        ];
    }

    public function pitstopLabel(): string
    {
        if ($this->pitstop_type === 'none' || $this->pitstop_count === 0) {
            return 'None';
        }
        $label = $this->pitstop_count.'x';
        if ($this->mandatory_refuelling) {
            $label .= ', refuel required';
        }
        if ($this->mandatory_tyre_change) {
            $label .= ', tyre change required';
        }
        if ($this->min_stop_secs) {
            $label .= ', fixed '.$this->min_stop_secs.'s';
        }

        return $label;
    }

    public function xclRLabel(): string
    {
        return '×'.number_format($this->xcl_r_multiplier, 1);
    }
}
