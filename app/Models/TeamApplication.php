<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamApplication extends Model
{
    // Single source of truth for role slug => label, shared by the public
    // apply form (dropdown + card pre-select), validation, and the admin
    // listing/detail views.
    public const ROLES = [
        'esports-drivers'       => 'Esports Drivers',
        'professional-drivers'  => 'Professional Drivers',
        'race-engineers'        => 'Race Engineers',
        'community-coaches'     => 'Community Coaches',
        'stewards'              => 'Stewards',
        'event-managers'        => 'Event Managers',
        'social-media-managers' => 'Social Media Managers',
        'news-broadcasters'     => 'News Broadcasters',
        'team-managers'         => 'Team Managers',
        'ambassadors'           => 'Ambassadors',
        'relationship-managers' => 'Relationship Managers',
        'scouts'                => 'Scouts',
    ];

    protected $fillable = [
        'name', 'email', 'discord', 'role', 'platforms', 'motivation',
    ];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
        ];
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
