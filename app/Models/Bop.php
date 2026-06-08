<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bop extends Model
{
    protected $fillable = ['game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'];

    public static function games(): array
    {
        return ['acc' => 'ACC', 'lmu' => 'LMU', 'iracing' => 'iRacing', 'ac' => 'AC Rally'];
    }
}