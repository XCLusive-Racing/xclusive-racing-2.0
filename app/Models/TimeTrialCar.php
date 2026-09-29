<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'name', 'year', 'acc_car_model'])]
class TimeTrialCar extends Model
{
    public $incrementing = false;

    protected $keyType = 'int';

    public function label(): string
    {
        return $this->year ? "{$this->name} ({$this->year})" : $this->name;
    }
}
