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
        'description', 'discord_invite_url', 'discord_guild_id', 'website_url',
        'requires_discord_membership', 'status',
    ];

    protected function casts(): array
    {
        return [
            'requires_discord_membership' => 'boolean',
            'is_system'                   => 'boolean',
        ];
    }

    // League rows are keyed by their own id, not by a league_id column.
    public function getTenantKeyName(): string
    {
        return 'id';
    }

    // XCL's own permanent League row (Phase 2.5 — see docs/championships/PLAN.md),
    // replacing the old "league_id = NULL means XCL's own" convention. Bypasses the
    // tenant scope deliberately: this row must resolve for any caller — console
    // commands, unauthenticated visitors, a league manager outside XCL alike — the
    // row itself carries no credentials, same reasoning as PointsScheme::browse().
    public static function system(): self
    {
        return static::withoutTenantScope()->where('is_system', true)->firstOrFail();
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

    // Bot-invite (not user-OAuth) URL for this league's own Discord server — the
    // bot-per-league direction decided for Phase 4 (docs/championships/PLAN.md).
    // Inviting the bot only gets it into the guild; actually being able to read
    // membership also requires XCL's bot application to have the "Server Members
    // Intent" toggle enabled once, globally, in the Discord Developer Portal —
    // that's an application-wide setting, not something a per-guild invite can grant.
    public function discordBotInviteUrl(): ?string
    {
        $clientId = config('services.discord.client_id');
        if (! $this->discord_guild_id || ! $clientId) {
            return null;
        }

        return 'https://discord.com/oauth2/authorize?' . http_build_query([
            'client_id'            => $clientId,
            'scope'                => 'bot',
            'permissions'          => 0,
            'guild_id'             => $this->discord_guild_id,
            'disable_guild_select' => 'true',
        ]);
    }
}
