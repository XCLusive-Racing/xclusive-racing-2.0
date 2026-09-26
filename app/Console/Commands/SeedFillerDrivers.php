<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

// Creates the grid-filler drivers from config/fillers.php — one users row per gamertag
// that doesn't have one yet (safe to re-run; existing fillers keep their ratings).
// They can't log in: no platform ID to match a sign-up against, an unguessable
// password and an unroutable email address.
class SeedFillerDrivers extends Command
{
    protected $signature = 'fillers:seed {--dry-run : List what would be created without writing anything}';

    protected $description = 'Create the grid-filler drivers listed in config/fillers.php';

    public function handle(): int
    {
        $existing = User::where('is_filler', true)->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();
        $created = 0;

        foreach (config('fillers.gamertags', []) as $gamertag) {
            if (in_array(mb_strtolower($gamertag), $existing, true)) {
                continue;
            }

            $attributes = [
                'name' => $gamertag,
                'email' => 'filler-'.Str::slug($gamertag).'@fillers.invalid',
                'password' => Str::random(64),
                'must_set_password' => false,
                'platform' => Arr::random(['ps5', 'ps5', 'xbox']),
                'platform_id' => null,
                'country' => Arr::random(['NL', 'NL', 'BE', 'DE', 'GB', 'FR', 'ES', 'IT', 'DK', 'SE', 'PL', 'US']),
                'role' => 'driver',
                'elo_acc' => $this->rating(),
                'elo_lmu' => $this->rating(),
                'elo_iracing' => $this->rating(),
                'sr_acc' => $this->safetyRating(),
                'sr_lmu' => $this->safetyRating(),
                'sr_iracing' => $this->safetyRating(),
            ];

            $this->line(sprintf('%-20s ACC %4d  LMU %4d  iR %4d', $gamertag, $attributes['elo_acc'], $attributes['elo_lmu'], $attributes['elo_iracing']));

            if (! $this->option('dry-run')) {
                $user = new User($attributes);
                $user->is_filler = true;
                $user->save();
            }
            $created++;
        }

        $this->info(($this->option('dry-run') ? 'Would create ' : 'Created ').$created.' filler driver(s).');

        return self::SUCCESS;
    }

    // 1000-4000, mostly 1000-2000: 75% in 1000-1999, 20% in 2000-2999, 5% in 3000-4000.
    private function rating(): int
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 75 => random_int(1000, 1999),
            $roll <= 95 => random_int(2000, 2999),
            default => random_int(3000, 4000),
        };
    }

    private function safetyRating(): float
    {
        return random_int(250, 750) / 100;
    }
}
