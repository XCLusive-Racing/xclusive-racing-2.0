<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class League extends Model
{
    use Tenantable, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'logo', 'banner', 'primary_color', 'accent_color',
        'description', 'discord_invite_url', 'website_url',
        'requires_discord_membership', 'status',
    ];

    protected function casts(): array
    {
        return [
            'requires_discord_membership' => 'boolean',
        ];
    }

    // League rows are keyed by their own id, not by a league_id column.
    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(LeagueUser::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'league_user')->withPivot('role')->withTimestamps();
    }

    public function managers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'manager');
    }

    public function stewards(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'steward');
    }

    public function ftpServers(): HasMany
    {
        return $this->hasMany(FtpServer::class);
    }

    public function championships(): HasMany
    {
        return $this->hasMany(Championship::class);
    }

    public function pointsSchemes(): HasMany
    {
        return $this->hasMany(PointsScheme::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('media')->url($this->logo) : null;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner ? Storage::disk('media')->url($this->banner) : null;
    }
}
