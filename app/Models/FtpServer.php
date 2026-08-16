<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FtpServer extends Model
{
    protected $fillable = [
        'name', 'server_number', 'host', 'port', 'username', 'password', 'path', 'cfg_path', 'active',
        'server_type', 'reset_start_hour', 'reset_interval_minutes',
        'settings_defaults', 'eventrules_defaults', 'assistrules_defaults', 'event_defaults',
    ];

    protected $casts = [
        'password'                => 'encrypted',
        'active'                  => 'boolean',
        'port'                    => 'integer',
        'reset_start_hour'        => 'integer',
        'reset_interval_minutes'  => 'integer',
        'settings_defaults'       => 'array',
        'eventrules_defaults'     => 'array',
        'assistrules_defaults'    => 'array',
        'event_defaults'          => 'array',
    ];

    public function importedFiles(): HasMany
    {
        return $this->hasMany(FtpImportedFile::class);
    }

    public function races(): HasMany
    {
        return $this->hasMany(\App\Models\Race::class, 'ftp_server_id');
    }

    public function takenSlots(?int $excludeRaceId = null): array
    {
        return $this->races()
            ->whereNotNull('slot_time')
            ->when($excludeRaceId, fn($q) => $q->where('id', '!=', $excludeRaceId))
            ->pluck('slot_time')
            ->map(fn($t) => \Carbon\Carbon::parse($t)->utc()->format('Y-m-d H:i'))
            ->toArray();
    }

    public function isValidSlot(\Carbon\Carbon $utcDateTime): bool
    {
        if ($this->server_type === 'scheduled') {
            return true;
        }

        // reset_start_hour is configured against the wall-clock hour admins actually see
        // (Europe/London) — checking the raw UTC hour instead would flip even/odd parity
        // for the whole BST period (UTC+1 in summer), since it shifts every hour by one.
        $localDateTime = $utcDateTime->copy()->timezone('Europe/London');

        if ($localDateTime->minute !== 0 || $localDateTime->second !== 0) {
            return false;
        }

        $intervalHours = $this->reset_interval_minutes / 60;
        $offset        = $localDateTime->hour - (int) $this->reset_start_hour;

        return $offset >= 0 && fmod($offset, $intervalHours) === 0.0;
    }
}