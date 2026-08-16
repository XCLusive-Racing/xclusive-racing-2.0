<?php

namespace App\Console\Commands\Import;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRatingsCommand extends Command
{
    protected $signature = 'xcl:import:ratings {file : Path to XCL RATING CSV} {--dry-run : Report what would change without writing anything}';
    protected $description = 'Import XCL ratings — updates elo_acc/sr_acc on matching Users, and upserts the drivers table';

    private const CHUNK = 200;

    public function handle(): int
    {
        $path   = $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        [$headers, $lines, $delimiter] = $this->parseFile($path);
        $col = $this->mapColumns($headers);

        if (!isset($col['gamertag']) || !isset($col['xuid_psid'])) {
            $this->error('Missing required column: DRIVER / XUID-PSID');
            return 1;
        }

        $this->info($dryRun ? 'DRY RUN — no changes will be written.' : 'Importing — this will update the database.');
        $this->info('Reading ' . count($lines) . ' rows…');

        $bar = $this->output->createProgressBar(count($lines));
        $bar->start();

        // Pass 1: parse every row, resolve placeholder XUIDs, keep the highest-rated
        // row per resolved XUID when the same one appears more than once.
        $byXuid = [];
        foreach ($lines as $line) {
            $raw  = array_pad(array_map('trim', str_getcsv($line, $delimiter)), count($headers), null);
            $data = array_combine($headers, $raw);

            $gamertag = $data[$col['gamertag']] ?? null;
            $bar->advance();
            if (!$gamertag) continue;

            $xuid = isset($col['xuid_psid']) ? trim($data[$col['xuid_psid']] ?? '') : '';
            // Placeholder ('x', empty, 1 char) → temp ID, same convention as xcl:import:drivers
            if (!$xuid || mb_strlen($xuid) <= 1 || strtolower($xuid) === 'x') {
                $xuid = 'T_' . strtolower($gamertag);
            }

            $row = [
                'gamertag'      => $gamertag,
                'number'        => isset($col['number'])      ? (($n = (int) ($data[$col['number']] ?? 0)) > 0 ? $n : null) : null,
                'xcl_rating'    => isset($col['xcl_rating'])   ? (float) ($data[$col['xcl_rating']] ?? 1500) : 1500,
                'xuid_psid'     => $xuid,
                'safety_rating' => isset($col['sr'])           ? (float) ($data[$col['sr']] ?? 4.00) : 4.00,
                'dns_count'     => isset($col['dns'])          ? (int) ($data[$col['dns']] ?? 0) : 0,
                'discord'       => isset($col['discord'])      ? ($data[$col['discord']] ?: null) : null,
                'abbreviation'  => isset($col['abbreviation']) ? (mb_substr($data[$col['abbreviation']] ?? '', 0, 3) ?: null) : null,
                'first_name'    => isset($col['first_name'])   ? ($data[$col['first_name']] ?: null) : null,
                'last_name'     => isset($col['last_name'])    ? ($data[$col['last_name']] ?: null) : null,
                'country_code'  => isset($col['country_code']) ? (mb_substr($data[$col['country_code']] ?? '', 0, 2) ?: null) : null,
                'car'           => isset($col['car'])          ? ($data[$col['car']] ?: null) : null,
                'car_id'        => isset($col['car_id'])       ? (($cid = (int) ($data[$col['car_id']] ?? 0)) > 0 ? $cid : null) : null,
                'team'          => isset($col['team'])         ? ($data[$col['team']] ?: null) : null,
                'date_joined'   => isset($col['date_joined'])  ? ($this->parseDate($data[$col['date_joined']] ?? '') ?: null) : null,
            ];

            if (!isset($byXuid[$xuid]) || $row['xcl_rating'] > $byXuid[$xuid]['xcl_rating']) {
                $byXuid[$xuid] = $row;
            }
        }
        $bar->finish();
        $this->newLine(2);

        $totalRows      = count($lines);
        $resolvedCount  = count($byXuid);
        $conflictsFound = $totalRows - $resolvedCount; // rows collapsed into an existing xuid (placeholder collisions counted too)

        // Pass 2: split into "matches an existing registered User" vs "driver-only" (not yet registered)
        $realXuids       = collect($byXuid)->reject(fn($r) => str_starts_with($r['xuid_psid'], 'T_'))->keys();
        $usersByXuid     = User::whereIn('platform_id', $realXuids)->get()->keyBy('platform_id');
        $existingDrivers = Driver::whereIn('xuid_psid', array_keys($byXuid))->pluck('xuid_psid')->flip();

        $usersToUpdate = [];
        $driverRows    = [];
        $newDrivers    = 0;
        $now           = now()->toDateTimeString();

        foreach ($byXuid as $xuid => $row) {
            $row['platform']   = $this->detectPlatform($xuid);
            $row['updated_at'] = $now;
            $row['created_at'] = $now;
            $driverRows[]      = $row;

            if (!$existingDrivers->has($xuid)) {
                $newDrivers++;
            }

            $user = $usersByXuid->get($xuid);
            if ($user) {
                $usersToUpdate[] = [
                    'id'      => $user->id,
                    'elo_acc' => (int) round($row['xcl_rating']),
                    'sr_acc'  => $row['safety_rating'],
                ];
            }
        }

        if (!$dryRun) {
            DB::transaction(function () use ($driverRows, $usersToUpdate) {
                foreach (array_chunk($driverRows, self::CHUNK) as $chunk) {
                    Driver::upsert($chunk, ['xuid_psid'], [
                        'gamertag', 'number', 'xcl_rating', 'safety_rating', 'dns_count', 'discord',
                        'abbreviation', 'first_name', 'last_name', 'country_code', 'car', 'car_id',
                        'team', 'date_joined', 'platform', 'updated_at',
                    ]);
                }
                foreach ($usersToUpdate as $u) {
                    User::where('id', $u['id'])->update(['elo_acc' => $u['elo_acc'], 'sr_acc' => $u['sr_acc']]);
                }
            });
        }

        $this->table(['Metric', 'Count'], [
            ['CSV rows read', $totalRows],
            ['Unique XUIDs after placeholder/duplicate resolution', $resolvedCount],
            ['Rows collapsed (placeholders + real conflicts, highest rating kept)', $conflictsFound],
            ['Matched existing registered Users (elo_acc/sr_acc ' . ($dryRun ? 'would be' : '') . ' updated)', count($usersToUpdate)],
            ['Driver rows ' . ($dryRun ? 'that would be' : '') . ' newly created', $newDrivers],
            ['Driver rows ' . ($dryRun ? 'that would be' : '') . ' updated (already existed)', $resolvedCount - $newDrivers],
            ['Not linked to any registered User (driver-only, incl. not-yet-registered)', $resolvedCount - count($usersToUpdate)],
        ]);

        $this->info($dryRun
            ? 'Dry run complete — nothing was written. Re-run without --dry-run to apply.'
            : 'Import complete.');

        return 0;
    }

    private function detectPlatform(?string $xuid): string
    {
        if (!$xuid || str_starts_with($xuid, 'T_')) return 'psn';
        return ctype_digit($xuid) && strlen($xuid) >= 15 ? 'xbox' : 'psn';
    }

    private function parseDate(string $value): ?string
    {
        if (!$value) return null;
        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseFile(string $path): array
    {
        $content   = str_replace(["\r\n", "\r"], "\n", file_get_contents($path));
        $lines     = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));
        $delimiter = $this->detectDelimiter($lines[0] ?? '');
        $headers   = array_map(fn($h) => strtolower(trim($h)), str_getcsv(array_shift($lines), $delimiter));
        return [$headers, $lines, $delimiter];
    }

    private function detectDelimiter(string $line): string
    {
        $counts = ["\t" => substr_count($line, "\t"), ';' => substr_count($line, ';'), ',' => substr_count($line, ',')];
        arsort($counts);
        return array_key_first($counts);
    }

    private function mapColumns(array $headers): array
    {
        $aliases = [
            'gamertag'     => ['driver', 'gamertag'],
            'number'       => ['numb', 'number'],
            'xcl_rating'   => ['xcl rating', 'xcl_rating', 'xclrating'],
            'xuid_psid'    => ['xuid / psid', 'xuid/psid', 'xuid_psid', 'xuid', 'psid'],
            'sr'           => ['sr'],
            'dns'          => ['dns'],
            'discord'      => ['discord'],
            'abbreviation' => ['abb', 'abbreviation'],
            'first_name'   => ['first name', 'first_name', 'firstname'],
            'last_name'    => ['last name', 'last_name', 'lastname'],
            'country_code' => ['co', 'country', 'country_code'],
            'car'          => ['car'],
            'car_id'       => ['car id', 'car_id'],
            'team'         => ['team/quote', 'team'],
            'date_joined'  => ['date', 'date_joined'],
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
