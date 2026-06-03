<?php

namespace App\Console\Commands\Import;

use App\Models\Driver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDriversCommand extends Command
{
    protected $signature = 'xcl:import:drivers {file : Path to XCL RATING CSV}';
    protected $description = 'Import drivers from XCL RATING CSV into the drivers table';

    private const CHUNK = 100;

    public function handle(): int
    {
        $path = $this->argument('file');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        [$headers, $lines, $delimiter] = $this->parseFile($path);
        $col = $this->mapColumns($headers);

        if (!isset($col['gamertag'])) {
            $this->error('Missing required column: DRIVER');
            return 1;
        }

        $total  = count($lines);
        $done   = 0;
        $errors = 0;
        $now    = now()->toDateTimeString();

        $this->info("Importing {$total} drivers…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_chunk($lines, self::CHUNK) as $chunk) {
            $rows = [];

            foreach ($chunk as $line) {
                $raw  = array_pad(array_map('trim', str_getcsv($line, $delimiter)), count($headers), null);
                $data = array_combine($headers, $raw);

                $gamertag = $data[$col['gamertag']] ?? null;
                if (!$gamertag) continue;

                $xuid = isset($col['xuid_psid']) ? trim($data[$col['xuid_psid']] ?? '') : '';
                // Placeholder ('x', leeg, 1 teken) → temp ID, wordt opgelost bij registratie
                if (!$xuid || strlen($xuid) <= 1 || strtolower($xuid) === 'x') {
                    $xuid = 'T_' . strtolower($gamertag);
                }

                $rows[] = [
                    'gamertag'      => $gamertag,
                    'number'        => isset($col['number'])      ? (($n = (int)($data[$col['number']] ?? 0)) > 0 ? $n : null) : null,
                    'xcl_rating'    => isset($col['xcl_rating'])  ? (float)($data[$col['xcl_rating']] ?? 1500) : 1500,
                    'xuid_psid'     => $xuid,
                    'safety_rating' => isset($col['sr'])          ? (float)($data[$col['sr']] ?? 4.00) : 4.00,
                    'dns_count'     => isset($col['dns'])         ? (int)($data[$col['dns']] ?? 0) : 0,
                    'discord'       => isset($col['discord'])     ? ($data[$col['discord']] ?? null) : null,
                    'abbreviation'  => isset($col['abbreviation'])? (substr($data[$col['abbreviation']] ?? '', 0, 3) ?: null) : null,
                    'first_name'    => isset($col['first_name'])  ? ($data[$col['first_name']] ?? null) : null,
                    'last_name'     => isset($col['last_name'])   ? ($data[$col['last_name']] ?? null) : null,
                    'country_code'  => isset($col['country_code'])? (substr($data[$col['country_code']] ?? '', 0, 2) ?: null) : null,
                    'car'           => isset($col['car'])         ? ($data[$col['car']] ?? null) : null,
                    'car_id'        => isset($col['car_id'])      ? (($cid = (int)($data[$col['car_id']] ?? 0)) > 0 ? $cid : null) : null,
                    'team'          => isset($col['team'])        ? ($data[$col['team']] ?? null) : null,
                    'date_joined'   => isset($col['date_joined']) ? ($this->parseDate($data[$col['date_joined']] ?? '') ?: null) : null,
                    'platform'      => $this->detectPlatform($xuid),
                    'updated_at'    => $now,
                    'created_at'    => $now,
                ];
            }

            if (empty($rows)) continue;

            try {
                DB::transaction(function () use ($rows) {
                    Driver::insertOrIgnore($rows);
                });
                $done += count($rows);
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("Chunk error: {$e->getMessage()}");
            }

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done — {$done} inserted (bestaande records overgeslagen), {$errors} chunk errors.");
        return 0;
    }

    private function detectPlatform(?string $xuid): string
    {
        if (!$xuid) return 'psn';
        // Xbox XUIDs are 16-digit numeric IDs; PSN IDs are alphanumeric strings
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
            'status'       => ['status'],
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