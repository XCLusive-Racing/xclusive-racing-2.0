<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTag extends Model
{
    protected $fillable = ['name', 'slug', 'color'];
}