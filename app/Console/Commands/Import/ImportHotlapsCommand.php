<?php

namespace App\Console\Commands\Import;

use App\Models\Driver;
use App\Models\Hotlap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHotlapsCommand extends Command
{
    protected $signature = 'xcl:import:hotlaps {file : Path to HOTLAP CSV}';
    protected $description = 'Import hotlaps from HOTLAP CSV';

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

        if (!isset($col['xuid_psid'])) {
            $this->error('Missing required column: DriverID');
            return 1;
        }

        $driverMap = Driver::whereNotNull('xuid_psid')->pluck('id', 'xuid_psid')->all();

        $total  = count($lines);
        $done   = 0;
        $errors = 0;
        $now    = now()->toDateTimeString();

        $this->info("Importing {$total} hotlaps…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_chunk($lines, self::CHUNK) as $chunk) {
            $rows = [];

            foreach ($chunk as $line) {
                $raw  = array_pad(array_map('trim', str_getcsv($line, $delimiter)), count($headers), null);
                $data = array_combine($headers, $raw);

                $xuid = $data[$col['xuid_psid']] ?? null;
                if (!$xuid) continue;

                $bestLap = isset($col['best_lap']) ? ($data[$col['best_lap']] ?? null) : null;
                if (!$bestLap) continue;

                $rows[] = [
                    'driver_id'          => $driverMap[$xuid] ?? null,
                    'xuid_psid'          => $xuid,
                    'driver_name'        => isset($col['driver_name']) ? ($data[$col['driver_name']] ?? '') : '',
                    'car_id'             => isset($col['car_id'])      ? (($cid = (int)($data[$col['car_id']] ?? 0)) > 0 ? $cid : null) : null,
                    'car_name'           => isset($col['car_name'])    ? ($data[$col['car_name']] ?? null) : null,
                    'car_model'          => isset($col['car_model'])   ? ($data[$col['car_model']] ?? null) : null,
                    'best_lap'           => $bestLap,
                    'laps_driven'        => isset($col['laps_driven']) ? (int)($data[$col['laps_driven']] ?? 0) : 0,
                    'xcl_rating_at_time' => isset($col['xcl_rating'])    ? (float)($data[$col['xcl_rating']]    ?? 0)    : 0,
                    'rating_change'      => isset($col['rating_change'])  ? (($v = trim($data[$col['rating_change']] ?? '')) !== '' ? (float)$v : null) : null,
                    'new_xcl_rating'     => isset($col['new_xcl_rating']) ? (($v = trim($data[$col['new_xcl_rating']] ?? '')) !== '' ? (float)$v : null) : null,
                    'updated_at'         => $now,
                    'created_at'         => $now,
                ];
            }

            if (empty($rows)) continue;

            try {
                DB::transaction(fn() => Hotlap::insert($rows));
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
        $this->info("Done — {$done} hotlaps inserted, {$errors} chunk errors.");
        return 0;
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
            'xuid_psid'      => ['driverid', 'xuid_psid', 'xuid', 'psid'],
            'driver_name'    => ['drivername', 'driver name', 'driver'],
            'best_lap'       => ['min  bestlap', 'min bestlap', 'bestlap', 'best lap', 'best_lap'],
            'car_id'         => ['carid', 'car id', 'car_id'],
            'car_logo'       => ['car'],
            'car_name'       => ['model', 'car model', 'car_name'],
            'laps_driven'    => ['laps driven', 'lapsdriven', 'laps_driven'],
            'xcl_rating'     => ['xcl ratings', 'xcl rating', 'xcl_rating', 'xclrating'],
            'rating_change'  => ['change', 'rating change', 'rating_change'],
            'new_xcl_rating' => ['new xcl ratings', 'new xcl rating', 'new_xcl_rating', 'new xclrating'],
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