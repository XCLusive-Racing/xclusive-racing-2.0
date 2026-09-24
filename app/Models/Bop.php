<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bop extends Model
{
    protected $fillable = ['game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes', 'active'];

    protected $casts = ['active' => 'boolean'];

    public static function games(): array
    {
        return ['acc' => 'ACC', 'lmu' => 'LMU', 'iracing' => 'iRacing', 'ac' => 'ACC PC'];
    }

    public static function categories(): array
    {
        return ['gt3' => 'GT3', 'gt4' => 'GT4', 'gt2' => 'GT2', 'cup' => 'Cup / GTC'];
    }
}
