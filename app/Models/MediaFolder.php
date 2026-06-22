<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFolder extends Model
{
    protected $fillable = ['name', 'slug'];

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'category', 'slug');
    }

    public function cover(): ?Media
    {
        return $this->media()->whereIn('type', ['image', 'youtube'])->latest()->first();
    }
}
