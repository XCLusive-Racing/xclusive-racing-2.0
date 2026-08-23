<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['id', 'game', 'car_class', 'name', 'year', 'logo'];

    public function assignments(): HasMany
    {
        return $this->hasMany(CarAssignment::class);
    }
}