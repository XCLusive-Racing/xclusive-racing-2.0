<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeServer extends Model
{
    protected $fillable = [
        'ftp_server_id', 'max_car_slots', 'max_connections',
        'restart_cadence_minutes', 'restart_offset_minutes',
        'platform', 'join_password', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function ftpServer(): BelongsTo
    {
        return $this->belongsTo(FtpServer::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PracticeServerSession::class);
    }

    // Entries beyond this gap can never actually join once max_car_slots is full and
    // spectator/connection headroom runs out — used to warn admins at config-build time.
    public function admissionGap(): int
    {
        return max(0, $this->max_connections - $this->max_car_slots);
    }
}
