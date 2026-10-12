<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A driver's last computed value for one metric in one scope ('all' or a game key).
class UserMetric extends Model
{
    public const CREATED_AT = null;

    protected $fillable = ['user_id', 'metric_key', 'scope', 'value'];

    protected function casts(): array
    {
        return ['value' => 'float'];
    }
}
