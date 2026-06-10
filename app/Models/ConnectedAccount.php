<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'provider', 'provider_id', 'username', 'connected_at'])]
class ConnectedAccount extends Model
{
    protected $table = 'user_connected_accounts';

    protected function casts(): array
    {
        return ['connected_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerLabel(): string
    {
        return match($this->provider) {
            'discord' => 'Discord',
            'steam'   => 'Steam',
            'xbox'    => 'Xbox',
            'psn'     => 'PlayStation',
            default   => ucfirst($this->provider),
        };
    }

    public function providerColor(): string
    {
        return match($this->provider) {
            'discord' => '#5865F2',
            'steam'   => '#1b2838',
            'xbox'    => '#107c10',
            'psn'     => '#00439c',
            default   => '#6b7280',
        };
    }

    public function providerIcon(): string
    {
        return match($this->provider) {
            'discord' => '<i class="fa-brands fa-discord"></i>',
            'steam'   => '<i class="fa-brands fa-steam"></i>',
            'xbox'    => '<i class="fa-brands fa-xbox"></i>',
            'psn'     => '<i class="fa-brands fa-playstation"></i>',
            default   => '',
        };
    }
}