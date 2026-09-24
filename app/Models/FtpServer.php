<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FtpServer extends Model
{
    use Tenantable;

    protected $fillable = [
        'name', 'server_number', 'host', 'port', 'username', 'password', 'path', 'cfg_path', 'active',
        'server_type', 'reset_start_hour', 'reset_interval_minutes',
        'settings_defaults', 'eventrules_defaults', 'assistrules_defaults', 'event_defaults',
        'league_id', 'game', 'platform',
    ];

    protected $casts = [
        // Credentials are encrypted at rest and never rendered back to the browser.
        'username' => 'encrypted',
        'password' => 'encrypted',
        'active' => 'boolean',
        'port' => 'integer',
        'reset_start_hour' => 'integer',
        'reset_interval_minutes' => 'integer',
        'settings_defaults' => 'array',
        'eventrules_defaults' => 'array',
        'assistrules_defaults' => 'array',
        'event_defaults' => 'array',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function importedFiles(): HasMany
    {
        return $this->hasMany(FtpImportedFile::class);
    }

    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'ftp_server_id');
    }

    public function takenSlots(?int $excludeRaceId = null): array
    {
        return $this->races()
            ->whereNotNull('slot_time')
            ->when($excludeRaceId, fn ($q) => $q->where('id', '!=', $excludeRaceId))
            ->pluck('slot_time')
            ->map(fn ($t) => Carbon::parse($t)->utc()->format('Y-m-d H:i'))
            ->toArray();
    }

    public const ERR_NOT_FOR_RACE = 'This server isn\'t available for this event — it belongs to another league or runs a different ACC platform.';

    public const ERR_WRONG_PLATFORM = 'This server runs a different ACC platform than the event (ACC PC events need a PC server, ACC Console events a console server).';

    // ACC PC and ACC Console builds can't share a server (different car IDs, Steam vs
    // console player IDs), so an ACC PC event ('ac') needs a platform=pc server and an
    // ACC Console event ('acc') anything but. Other games aren't platform-split.
    public function supportsRaceGame(string $game): bool
    {
        return match ($game) {
            'ac' => $this->platform === 'pc',
            'acc' => $this->platform !== 'pc',
            default => true,
        };
    }

    // $allowHalfHour additionally accepts :30 starts, not just :00 — used by
    // championship round scheduling only; standalone race scheduling (RaceController)
    // stays hour-only.
    public function isValidSlot(Carbon $utcDateTime, bool $allowHalfHour = false): bool
    {
        if ($this->server_type === 'scheduled') {
            return true;
        }

        // reset_start_hour is configured against the wall-clock hour admins actually see
        // (Europe/London) — checking the raw UTC hour instead would flip even/odd parity
        // for the whole BST period (UTC+1 in summer), since it shifts every hour by one.
        $localDateTime = $utcDateTime->copy()->timezone('Europe/London');

        $validMinute = $allowHalfHour
            ? in_array($localDateTime->minute, [0, 30], true)
            : $localDateTime->minute === 0;

        if (! $validMinute || $localDateTime->second !== 0) {
            return false;
        }

        $intervalHours = $this->reset_interval_minutes / 60;
        $offset = ($localDateTime->hour - (int) $this->reset_start_hour) + ($localDateTime->minute / 60);

        return $offset >= 0 && fmod($offset, $intervalHours) === 0.0;
    }
}
