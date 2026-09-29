<?php

namespace App\Console\Commands\Import;

use App\Models\ConnectedAccount;
use App\Models\TimeTrialCar;
use App\Models\TimeTrialLap;
use App\Models\User;
use App\Services\AccCarCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Imports the historical Time Trials lap times (the hotlap sheet's TTOutput export) and its
// car lookup. Always a dry run unless --confirm is passed. Idempotent: every lap has a
// source_key built from its own values, so a re-run skips rows already imported.
class ImportTimeTrialsCommand extends Command
{
    protected $signature = 'time-trials:import
        {laps : Path to the lap times CSV (XCL_Hotlap_-_TTOutput.csv)}
        {cars : Path to the car lookup CSV (XCL_Hotlap_-_Data.csv)}
        {--confirm : Write to the database. Without it the command only reports what it would do}';

    protected $description = 'Import historical Time Trials lap times and the car lookup (dry run unless --confirm)';

    private const LAP_COLUMNS = ['track', 'driverid', 'drivername', 'bestlap', 'car', 'carclass', 's1', 's2', 's3', 'patch', 'event', 'nooflaps'];

    private const CAR_COLUMNS = ['carid', 'carname', 'year'];

    private const SECTOR_TOLERANCE_MS = 100;

    // The source sheet writes 999 (seconds) for a sector it has no time for. Stored as null.
    private const SECTOR_PLACEHOLDER_MS = 999000;

    private const CHUNK = 500;

    public function handle(): int
    {
        $confirm = (bool) $this->option('confirm');

        foreach (['laps', 'cars'] as $arg) {
            if (! is_file($this->argument($arg))) {
                $this->error("File not found: {$this->argument($arg)}");

                return self::FAILURE;
            }
        }

        $cars = $this->readCars($this->argument('cars'));
        if ($cars === null) {
            return self::FAILURE;
        }

        $parsed = $this->readLaps($this->argument('laps'), $cars['rows']);
        if ($parsed === null) {
            return self::FAILURE;
        }

        $users = $this->matchUsers(array_keys($parsed['drivers']));
        $tablesExist = Schema::hasTable('time_trial_laps') && Schema::hasTable('time_trial_cars');
        $existingKeys = $tablesExist ? $this->existingKeys(array_keys($parsed['laps'])) : [];

        $this->report($cars, $parsed, $users, $existingKeys, $tablesExist);

        if (! $confirm) {
            $this->newLine();
            $this->warn('DRY RUN: nothing was written. Run again with --confirm to import.');

            return self::SUCCESS;
        }

        if (! $tablesExist) {
            $this->error('The time_trial_laps / time_trial_cars tables do not exist. Run the migrations first.');

            return self::FAILURE;
        }

        $this->write($cars['rows'], $parsed, $users, $existingKeys);

        return self::SUCCESS;
    }

    // ── Reading ─────────────────────────────────────────────────────────────────

    private function readCars(string $path): ?array
    {
        [$header, $records] = $this->readCsv($path);
        if ($missing = array_diff(self::CAR_COLUMNS, $header)) {
            $this->error('Car lookup is missing column(s): '.implode(', ', $missing));

            return null;
        }

        $rows = $unmapped = $rejected = [];
        foreach ($records as [$line, $data]) {
            $id = trim($data['carid'] ?? '');
            $name = trim($data['carname'] ?? '');
            $year = trim($data['year'] ?? '');

            if (! ctype_digit($id) || $name === '') {
                $rejected[] = "line {$line}: invalid car id or empty name";

                continue;
            }

            $id = (int) $id;
            $year = ctype_digit($year) ? (int) $year : null;
            $accModel = $this->accModelFor($id, $year);
            if ($accModel === null) {
                $unmapped[$id] = "{$name} ({$year})";
            }

            $rows[$id] = ['id' => $id, 'name' => $name, 'year' => $year, 'acc_car_model' => $accModel];
        }

        $sharedNames = collect($rows)->groupBy('name')->filter(fn ($g) => $g->count() > 1)
            ->map(fn ($g) => $g->pluck('id')->all())->all();

        return compact('rows', 'unmapped', 'rejected', 'sharedNames');
    }

    // The console catalogue shares the source's numbering; the ID only counts as a match
    // when the catalogue's model year agrees, so a renumbered car is reported, not mismapped.
    private function accModelFor(int $id, ?int $year): ?int
    {
        $name = AccCarCatalog::cars('acc')[$id] ?? null;
        if ($name === null) {
            return null;
        }

        return preg_match('/\((\d{4})\)$/', $name, $m) && (int) $m[1] === $year ? $id : null;
    }

    private function readLaps(string $path, array $cars): ?array
    {
        [$header, $records] = $this->readCsv($path);
        if ($missing = array_diff(self::LAP_COLUMNS, $header)) {
            $this->error('Lap file is missing column(s): '.implode(', ', $missing));

            return null;
        }

        $laps = $rejected = $sectorMismatch = $drivers = [];
        $duplicates = $missingSectors = $classMismatch = $placeholderSectors = 0;

        foreach ($records as [$line, $d]) {
            $identifier = trim($d['driverid']);
            $platform = TimeTrialLap::platformFromIdentifier($identifier);
            $track = strtolower(trim($d['track']));
            $carId = trim($d['car']);
            $lapMs = $this->parseLap(trim($d['bestlap']));
            $sectors = array_map(fn ($s) => $this->parseSeconds(trim($d[$s])), ['s1', 's2', 's3']);

            $reason = match (true) {
                $identifier === '' => 'empty driver identifier',
                $platform === null => 'identifier prefix is not M (xbox) or P (playstation)',
                $track === '' => 'empty track',
                ! ctype_digit($carId) || ! isset($cars[(int) $carId]) => 'car id not in the car lookup',
                $lapMs === null => 'lap time not in m:ss.mmm form',
                in_array(false, $sectors, true) => 'sector time is not a decimal number of seconds',
                default => null,
            };

            if ($reason) {
                $rejected[$reason][] = "line {$line} ({$identifier} {$track} {$d['bestlap']})";

                continue;
            }

            $carId = (int) $carId;
            if (in_array(self::SECTOR_PLACEHOLDER_MS, $sectors, true)) {
                $placeholderSectors++;
                $sectors = array_map(fn ($s) => $s === self::SECTOR_PLACEHOLDER_MS ? null : $s, $sectors);
            }
            [$s1, $s2, $s3] = $sectors;
            $carClass = $this->normalizeClass(trim($d['carclass'])) ?? AccCarCatalog::carClass($carId, 'acc') ?? 'GT3';
            if (($catalogClass = AccCarCatalog::carClass($carId, 'acc')) && $catalogClass !== $carClass) {
                $classMismatch++;
            }

            $row = [
                'platform_identifier' => $identifier,
                'platform' => $platform,
                'driver_name' => trim($d['drivername']) ?: $identifier,
                'track' => $track,
                'car_id' => $carId,
                'car_class' => $carClass,
                'lap_time_ms' => $lapMs,
                'sector1_ms' => $s1,
                'sector2_ms' => $s2,
                'sector3_ms' => $s3,
                'game_patch' => trim($d['patch']) ?: null,
                'source_event' => ctype_digit(trim($d['event'])) ? (int) trim($d['event']) : null,
                'laps_driven' => ctype_digit(trim($d['nooflaps'])) ? (int) trim($d['nooflaps']) : null,
            ];

            $key = sha1('import|'.implode('|', array_map(fn ($v) => (string) $v, [
                $identifier, $track, $carId, $lapMs, $s1, $s2, $s3, $row['game_patch'], $row['source_event'], $row['laps_driven'],
            ])));

            if (isset($laps[$key])) {
                $duplicates++;

                continue;
            }

            if ($s1 === null || $s2 === null || $s3 === null) {
                $missingSectors++;
            } elseif (abs($s1 + $s2 + $s3 - $lapMs) > self::SECTOR_TOLERANCE_MS) {
                $sectorMismatch[] = [$line, $row['driver_name'], $track, $d['bestlap'], $s1 + $s2 + $s3 - $lapMs];
            }

            $laps[$key] = $row;
            $drivers[$identifier] = $platform;
        }

        // Same driver, track and car more than once: all rows are kept, the fastest is flagged.
        $repeatGroups = collect($laps)
            ->groupBy(fn ($r) => $r['platform'].'|'.$r['platform_identifier'].'|'.$r['track'].'|'.$r['car_id'])
            ->filter(fn ($g) => $g->count() > 1);

        return [
            'total' => count($records),
            'laps' => $laps,
            'drivers' => $drivers,
            'rejected' => $rejected,
            'duplicates' => $duplicates,
            'sectorMismatch' => $sectorMismatch,
            'missingSectors' => $missingSectors,
            'placeholderSectors' => $placeholderSectors,
            'classMismatch' => $classMismatch,
            'repeatGroups' => $repeatGroups,
        ];
    }

    /** @return array{0: string[], 1: array<int, array{0: int, 1: array<string, string>}>} */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = null;
        $records = [];
        $line = 0;

        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            $line++;
            if ($cells === [null] || trim(implode('', $cells)) === '') {
                continue;
            }

            if ($header === null) {
                $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cells[0]);
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $cells);

                continue;
            }

            $cells = array_pad($cells, count($header), '');
            $data = [];
            foreach ($header as $i => $name) {
                if ($name !== '') {
                    $data[$name] = (string) $cells[$i];
                }
            }
            $records[] = [$line, $data];
        }

        fclose($handle);

        return [$header ?? [], $records];
    }

    // "1:22.312" / "8:04.272" (any number of minute digits) => milliseconds.
    private function parseLap(string $value): ?int
    {
        if (! preg_match('/^(\d+):([0-5]\d)\.(\d{1,3})$/', $value, $m)) {
            return null;
        }

        return (int) $m[1] * 60000 + (int) $m[2] * 1000 + (int) str_pad($m[3], 3, '0');
    }

    // Decimal seconds ("21.31", "167.66") => milliseconds without going through a float.
    // Blank is null (no sector recorded); anything unparseable is false.
    private function parseSeconds(string $value): int|false|null
    {
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^(\d+)(?:\.(\d{1,3}))?$/', $value, $m)) {
            return false;
        }

        return (int) $m[1] * 1000 + (int) str_pad($m[2] ?? '', 3, '0');
    }

    // "GTC (cup)" / "TCX (m2)" => the ACC class code in front.
    private function normalizeClass(string $value): ?string
    {
        return $value === '' ? null : strtoupper(strtok($value, ' '));
    }

    // ── Matching ────────────────────────────────────────────────────────────────

    // Identifier => user ID. A member's primary platform_id wins; a linked Xbox or
    // PlayStation account only fills identifiers nobody has as their primary ID.
    // Grid filler accounts are never matched.
    private function matchUsers(array $identifiers): array
    {
        $matched = [];

        foreach (array_chunk($identifiers, self::CHUNK) as $chunk) {
            User::whereIn('platform_id', $chunk)->where('is_filler', false)
                ->pluck('id', 'platform_id')
                ->each(function ($id, $identifier) use (&$matched) {
                    $matched[$identifier] = ['user_id' => $id, 'via' => 'profile platform ID'];
                });

            ConnectedAccount::whereIn('provider', ['xbox', 'psn'])->whereIn('provider_id', $chunk)
                ->whereHas('user', fn ($q) => $q->where('is_filler', false))
                ->get(['user_id', 'provider_id'])
                ->each(function ($account) use (&$matched) {
                    $matched[$account->provider_id] ??= ['user_id' => $account->user_id, 'via' => 'linked account'];
                });
        }

        return $matched;
    }

    private function existingKeys(array $keys): array
    {
        $existing = [];
        foreach (array_chunk($keys, self::CHUNK) as $chunk) {
            foreach (TimeTrialLap::whereIn('source_key', $chunk)->pluck('source_key') as $key) {
                $existing[$key] = true;
            }
        }

        return $existing;
    }

    // ── Report ──────────────────────────────────────────────────────────────────

    private function report(array $cars, array $parsed, array $users, array $existingKeys, bool $tablesExist): void
    {
        $laps = collect($parsed['laps']);
        $new = $laps->reject(fn ($r, $key) => isset($existingKeys[$key]));
        $rejectedCount = collect($parsed['rejected'])->sum(fn ($rows) => count($rows));

        $this->info('Cars');
        $this->line('  Car lookup rows:        '.count($cars['rows']));
        $this->line('  Mapped to an ACC model: '.(count($cars['rows']) - count($cars['unmapped'])));
        $this->line('  Not mapped:             '.(count($cars['unmapped']) ?: 'none'));
        foreach ($cars['unmapped'] as $id => $label) {
            $this->line("    #{$id} {$label}");
        }
        foreach ($cars['sharedNames'] as $name => $ids) {
            $this->line("  Name under several ids: {$name} => ".implode(', ', $ids));
        }
        foreach ($cars['rejected'] as $msg) {
            $this->line("  Rejected car row: {$msg}");
        }

        $this->newLine();
        $this->info('Lap rows');
        $this->line('  Rows in file:           '.$parsed['total']);
        $this->line('  Valid:                  '.$laps->count());
        $this->line('  Rejected:               '.$rejectedCount);
        foreach ($parsed['rejected'] as $reason => $rows) {
            $this->line('    '.count($rows)." x {$reason}");
            foreach (array_slice($rows, 0, 20) as $row) {
                $this->line("      {$row}");
            }
            if (count($rows) > 20) {
                $this->line('      and '.(count($rows) - 20).' more');
            }
        }
        $this->line('  Exact duplicates in file (skipped): '.$parsed['duplicates']);
        $this->line('  Already imported (skipped):         '.($tablesExist ? count($existingKeys) : 'n/a, tables not migrated yet'));
        $this->line('  Would be created:       '.$new->count());
        $this->line('  Rows with a sector missing:         '.$parsed['missingSectors']);
        $this->line('  Rows with 999 placeholder sectors (stored as empty): '.$parsed['placeholderSectors']);
        $this->line('  Class differs from the ACC catalogue: '.$parsed['classMismatch']);

        $this->newLine();
        $this->info('Drivers');
        $drivers = collect($parsed['drivers']);
        $matched = $drivers->keys()->filter(fn ($id) => isset($users[$id]));
        $this->line('  Unique drivers:         '.$drivers->count());
        $this->line('  Match a member profile: '.$matched->count()
            .' ('.$matched->filter(fn ($id) => $users[$id]['via'] === 'profile platform ID')->count().' by profile platform ID, '
            .$matched->filter(fn ($id) => $users[$id]['via'] === 'linked account')->count().' by linked account)');
        $this->line('  No member profile:      '.($drivers->count() - $matched->count()));
        $this->line('  Lap rows linked to a member: '.$laps->filter(fn ($r) => isset($users[$r['platform_identifier']]))->count());

        $this->newLine();
        $this->info('Per platform');
        $this->table(['Platform', 'Rows', 'Drivers', 'Matched drivers'], $laps->groupBy('platform')->map(fn ($rows, $p) => [
            $p,
            $rows->count(),
            $rows->pluck('platform_identifier')->unique()->count(),
            $rows->pluck('platform_identifier')->unique()->filter(fn ($id) => isset($users[$id]))->count(),
        ])->sortKeys()->values()->all());

        $this->info('Per track');
        $this->table(['Track', 'Name', 'Rows', 'Drivers', 'Xbox rows', 'PlayStation rows'], $laps->groupBy('track')->map(fn ($rows, $t) => [
            $t,
            TimeTrialLap::trackName($t),
            $rows->count(),
            $rows->pluck('platform_identifier')->unique()->count(),
            $rows->where('platform', 'xbox')->count(),
            $rows->where('platform', 'playstation')->count(),
        ])->sortKeys()->values()->all());

        $this->info('Same driver, track and car more than once ('.$parsed['repeatGroups']->count().' cases, all rows kept, fastest marked as personal best)');
        foreach ($parsed['repeatGroups'] as $rows) {
            $first = $rows->first();
            $this->line("  {$first['driver_name']} ({$first['platform_identifier']}) {$first['track']} car #{$first['car_id']}: "
                .$rows->sortBy('lap_time_ms')->map(fn ($r) => TimeTrialLap::formatLap($r['lap_time_ms']))->implode(', '));
        }

        $this->newLine();
        $mismatch = $parsed['sectorMismatch'];
        $this->info('Sectors not summing to the lap time within '.self::SECTOR_TOLERANCE_MS.' ms: '.count($mismatch).' (reported, still imported)');
        if ($mismatch) {
            $this->table(['Line', 'Driver', 'Track', 'Lap', 'Sectors minus lap (ms)'], $mismatch);
        }
    }

    // ── Write ───────────────────────────────────────────────────────────────────

    private function write(array $cars, array $parsed, array $users, array $existingKeys): void
    {
        $now = now();
        $created = 0;

        DB::transaction(function () use ($cars, $parsed, $users, $existingKeys, $now, &$created) {
            foreach ($cars as $car) {
                TimeTrialCar::updateOrCreate(['id' => $car['id']], $car);
            }

            $rows = [];
            foreach ($parsed['laps'] as $key => $row) {
                if (isset($existingKeys[$key])) {
                    continue;
                }
                $rows[] = $row + [
                    'user_id' => $users[$row['platform_identifier']]['user_id'] ?? null,
                    'source' => 'import',
                    'is_personal_best' => false,
                    'source_key' => $key,
                    'recorded_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, self::CHUNK) as $chunk) {
                TimeTrialLap::insert($chunk);
            }
            $created = count($rows);

            // Members who joined since a previous run get their earlier laps linked too.
            foreach ($users as $identifier => $match) {
                TimeTrialLap::where('platform_identifier', $identifier)->whereNull('user_id')
                    ->update(['user_id' => $match['user_id']]);
            }
        });

        $tracks = collect($parsed['laps'])->pluck('track')->unique()->values()->all();
        $bests = TimeTrialLap::recomputePersonalBests($tracks);

        $this->newLine();
        $this->info("Imported: {$created} lap rows created, ".count($cars)." cars saved, {$bests} personal bests marked.");
    }
}
