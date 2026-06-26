<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FtpServer extends Model
{
    protected $fillable = [
        'name', 'host', 'port', 'username', 'password', 'path', 'cfg_path', 'active',
        'server_type', 'reset_start_hour', 'reset_interval_minutes',
    ];

    protected $casts = [
        'password'                => 'encrypted',
        'active'                  => 'boolean',
        'port'                    => 'integer',
        'reset_start_hour'        => 'integer',
        'reset_interval_minutes'  => 'integer',
    ];

    public function importedFiles(): HasMany
    {
        return $this->hasMany(FtpImportedFile::class);
    }

    public function races(): HasMany
    {
        return $this->hasMany(\App\Models\Race::class, 'ftp_server_id');
    }

    public function takenSlots(): array
    {
        return $this->races()
            ->whereNotNull('slot_time')
            ->pluck('slot_time')
            ->map(fn($t) => \Carbon\Carbon::parse($t)->utc()->format('Y-m-d H:i'))
            ->toArray();
    }

    public function slotsForDays(int $days = 7): array
    {
        if ($this->server_type === 'scheduled') {
            return [];
        }

        $slots         = [];
        $intervalHours = $this->reset_interval_minutes / 60;

        for ($d = 0; $d < $days; $d++) {
            $date = now()->utc()->startOfDay()->addDays($d);
            $hour = (int) $this->reset_start_hour;

            while ($hour < 24) {
                $slot = $date->copy()->setHour($hour)->setMinute(0)->setSecond(0);
                if ($slot->isAfter(now()->utc()->addHour())) {
                    $slots[] = $slot->format('Y-m-d H:i');
                }
                $hour += $intervalHours;
            }
        }

        return $slots;
    }
}