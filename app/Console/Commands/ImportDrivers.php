<?php

namespace App\Console\Commands;

use App\Models\Car;
use App\Models\CarAssignment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportDrivers extends Command
{
    protected $signature = 'import:drivers {file : Path to CSV/TSV file} {--game=acc : Game: acc, lmu, iracing}';
    protected $description = 'Import drivers from a CSV file';

    private const CHUNK_SIZE = 200;

    public function handle(): int
    {
        $path = $this->argument('file');
        $game = $this->option('game');

        if (!in_array($game, ['acc', 'lmu', 'iracing'])) {
            $this->error("Invalid game '{$game}'. Use: acc, lmu, iracing.");
            return 1;
        }

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $content = str_replace(["\r\n", "\r"], "\n", file_get_contents($path));
        $lines   = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));

        if (count($lines) < 2) {
            $this->error('File has no data rows.');
            return 1;
        }

        $delimiter = $this->detectDelimiter($lines[0]);
        $headers   = array_map(fn($h) => strtolower(trim($h)), str_getcsv(array_shift($lines), $delimiter));
        $col       = $this->mapColumns($headers);

        $missing = array_diff(['driver', 'platform_id'], array_keys($col));
        if ($missing) {
            $this->error('Missing required columns: ' . implode(', ', $missing));
            return 1;
        }

        $total     = count($lines);
        $imported  = 0;
        $updated   = 0;
        $eloField  = "elo_{$game}";
        $srField   = "sr_{$game}";
        $now       = now()->toDateTimeString();

        // Compute placeholder hash once — not per user
        $placeholderPassword = Hash::make('!import-placeholder!', ['rounds' => 4]);

        $this->info("Importing {$total} rows in chunks of " . self::CHUNK_SIZE . "...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_chunk($lines, self::CHUNK_SIZE) as $chunk) {
            $rows = [];
            foreach ($chunk as $line) {
                $row  = array_pad(array_map('trim', str_getcsv($line, $delimiter)), count($headers), null);
                $data = array_combine($headers, $row);

                $platformId = $data[$col['platform_id']] ?? null;
                if (!$platformId || strlen($platformId) < 3) continue;

                $rows[] = [
                    'platform_id' => $platformId,
                    'platform'    => str_starts_with($platformId, 'M') ? 'xbox' : 'ps5',
                    'name'        => $data[$col['driver']] ?? 'Unknown',
                    'team'        => isset($col['team']) ? ($data[$col['team']] ?? null) : null,
                    'flag'        => isset($col['flag']) ? ($data[$col['flag']] ?? null) : null,
                    $eloField     => isset($col['xcl_rating']) ? ((int) ($data[$col['xcl_rating']] ?? 1500)) : 1500,
                    $srField      => isset($col['sr']) ? ((float) ($data[$col['sr']] ?? 5.0)) : 5.0,
                    'car_id'      => isset($col['car_id']) ? ((int) ($data[$col['car_id']] ?? -1)) : -1,
                    'car_name'    => isset($col['car_name']) ? ($data[$col['car_name']] ?? null) : null,
                    'car_year'    => isset($col['car_year']) ? ((int) ($data[$col['car_year']] ?? 0) ?: null) : null,
                    'car_logo'    => isset($col['car_logo']) ? ($data[$col['car_logo']] ?? null) : null,
                ];
            }

            if (empty($rows)) continue;

            // Load existing users for this chunk in one query
            $platformIds  = array_column($rows, 'platform_id');
            $existingMap  = User::whereIn('platform_id', $platformIds)
                ->pluck('id', 'platform_id')
                ->all();

            $toInsert = [];
            $toUpdate = [];

            foreach ($rows as $row) {
                $pid = $row['platform_id'];
                if (isset($existingMap[$pid])) {
                    $toUpdate[] = ['id' => $existingMap[$pid], 'data' => $row];
                } else {
                    $toInsert[] = $row;
                }
            }

            DB::transaction(function () use (
                $toInsert, $toUpdate, $game, $placeholderPassword, $now,
                $eloField, $srField, &$imported, &$updated
            ) {
                // Bulk insert new users
                if ($toInsert) {
                    $insertRows = array_map(fn($r) => [
                        'name'              => $r['name'],
                        'email'             => $r['platform_id'] . '@import.local',
                        'password'          => $placeholderPassword,
                        'must_set_password' => true,
                        'platform'          => $r['platform'],
                        'platform_id'       => $r['platform_id'],
                        'team'              => $r['team'],
                        'flag'              => $r['flag'],
                        $eloField           => $r[$eloField],
                        $srField            => $r[$srField],
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ], $toInsert);

                    User::insertOrIgnore($insertRows);
                    $imported += count($insertRows);

                    // Reload IDs for car assignments
                    $newPids = array_column($toInsert, 'platform_id');
                    $newMap  = User::whereIn('platform_id', $newPids)->pluck('id', 'platform_id')->all();

                    foreach ($toInsert as $r) {
                        if (isset($newMap[$r['platform_id']])) {
                            $this->assignCar($r, $newMap[$r['platform_id']], $game, $now);
                        }
                    }
                }

                // Update existing users one by one (selective field update)
                foreach ($toUpdate as ['id' => $userId, 'data' => $r]) {
                    User::where('id', $userId)->update(array_filter([
                        'name'     => $r['name'],
                        'platform' => $r['platform'],
                        'team'     => $r['team'],
                        'flag'     => $r['flag'],
                        $eloField  => $r[$eloField],
                        $srField   => $r[$srField],
                    ], fn($v) => $v !== null));

                    $this->assignCar($r, $userId, $game, $now);
                    $updated++;
                }
            });

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done — {$imported} created, {$updated} updated.");
        return 0;
    }

    private function assignCar(array $row, int $userId, string $game, string $now): void
    {
        $carId   = $row['car_id'] ?? -1;
        $carName = $row['car_name'] ?? null;

        if ($carId < 0 || !$carName) return;

        Car::updateOrCreate(
            ['id' => $carId],
            ['game' => $game, 'name' => $carName, 'year' => $row['car_year'], 'logo' => $row['car_logo']]
        );

        CarAssignment::where('user_id', $userId)
            ->whereNull('championship_id')
            ->whereIn('car_id', Car::where('game', $game)->pluck('id'))
            ->delete();

        CarAssignment::insert(['user_id' => $userId, 'car_id' => $carId, 'created_at' => $now, 'updated_at' => $now]);
    }

    private function detectDelimiter(string $line): string
    {
        $counts = ["\t" => 0, ';' => 0, ',' => 0];
        foreach (array_keys($counts) as $d) {
            $counts[$d] = substr_count($line, $d);
        }
        arsort($counts);
        return array_key_first($counts);
    }

    private function mapColumns(array $headers): array
    {
        $aliases = [
            'driver'      => ['driver'],
            'xcl_rating'  => ['xcl rating', 'xcl_rating', 'rating'],
            'platform_id' => ['xuid / psid', 'xuid/psid', 'xuid', 'psid', 'platform_id'],
            'sr'          => ['sr'],
            'flag'        => ['flag'],
            'team'        => ['team/quote', 'team'],
            'car_id'      => ['carid', 'car_id', 'car id'],
            'car_name'    => ['carname', 'car_name', 'car name'],
            'car_year'    => ['year'],
            'car_logo'    => ['logo'],
        ];

        $resolved = [];
        foreach ($aliases as $key => $candidates) {
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $headers, true)) {
                    $resolved[$key] = $candidate;
                    break;
                }
            }
        }

        return $resolved;
    }
}