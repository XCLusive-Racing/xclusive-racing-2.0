<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatingConfig extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'float'];
    }

    public static function get(string $key, float $default = 0.0): float
    {
        return (float) (static::where('key', $key)->value('value') ?? $default);
    }
}