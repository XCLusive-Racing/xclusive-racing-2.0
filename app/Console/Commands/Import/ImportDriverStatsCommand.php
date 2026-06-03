<?php

namespace App\Console\Commands\Import;

use App\Models\Driver;
use App\Models\DriverStats;
use App\Models\DriverTrackTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDriverStatsCommand extends Command
{
    protected $signature = 'xcl:import:driverstats {file : Path to DRIVERSTATS CSV}';
    protected $description = 'Import driver stats and track laptimes from DRIVERSTATS CSV';

    private const CHUNK = 100;

    // Non-track columns (lowercased) — numeric race-position cols (4–52) skipped via is_numeric() check
    private const KNOWN_COLS = [
        'driverid', 'drivername', 'wins', '2nds', 'seconds', '3rds', 'thirds',
        'totalraces', 'podiums', 'top5s', 'top10s', 'fastestrlaps', 'fastestqualifyinglaps',
        'totalspectated', 'totalracepositions', 'totalqualypositions',
        'positionsgained', 'totalracesin2024',
    ];

    // CSV header (lowercased) → driver_stats field
    private const PENALTY_MAP = [
        'reasons_pitspeeding'   => 'penalty_pit_speeding',
        'pitspeeding'           => 'penalty_pit_speeding',
        'pit speeding'          => 'penalty_pit_speeding',
        'wrongway'              => 'penalty_wrong_way',
        'wrong way'             => 'penalty_wrong_way',
        'cutting'               => 'penalty_cutting',
        'trolling'              => 'penalty_trolling',
        'none'                  => 'penalty_other',
        'startspeeding'         => 'penalty_start_speeding',
        'start speeding'        => 'penalty_start_speeding',
        'outofstartpos'         => 'penalty_out_of_start_pos',
        'out of start pos'      => 'penalty_out_of_start_pos',
        'penalties_stopandgo30' => 'penalty_stop_go_30',
        'stopandgo30'           => 'penalty_stop_go_30',
        'stopgo30'              => 'penalty_stop_go_30',
        'stop go 30'            => 'penalty_stop_go_30',
        'stop&go30'             => 'penalty_stop_go_30',
        'disqualified'          => 'penalty_disqualified',
        'dq'                    => 'penalty_disqualified',
        'drivethrough'          => 'penalty_drive_through',
        'drive through'         => 'penalty_drive_through',
        'postracetime'          => 'penalty_post_race_time',
        'post race time'        => 'penalty_post_race_time',
    ];

    public function handle(): int
    {
        $path = $this->argument('file');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        [$headers, $lines, $delimiter] = $this->parseFile($path);
        $col            = $this->mapColumns($headers);
        $penaltyCols    = $this->detectPenaltyColumns($headers);
        $raceTrackCols  = $this->detectRaceTrackColumns($headers, $col, $penaltyCols);
        $qualyTrackCols = $this->detectQualyTrackColumns($headers);

        if (!isset($col['xuid_psid'])) {
            $this->error('Missing required column: DriverID');
            return 1;
        }

        $this->info(sprintf(
            'Found %d race track columns, %d qualifying columns, %d penalty columns.',
            count($raceTrackCols), count($qualyTrackCols), count($penaltyCols)
        ));

        $driverMap = Driver::whereNotNull('xuid_psid')->pluck('id', 'xuid_psid')->all();

        $total  = count($lines);
        $done   = 0;
        $errors = 0;
        $now    = now()->toDateTimeString();

        $this->info("Importing stats for {$total} drivers…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_chunk($lines, self::CHUNK) as $chunk) {
            $statsRows        = [];
            $trackAccumulator = []; // [xuid => [track => ['race' => ..., 'qualy' => ..., 'driver_id' => ...]]]

            foreach ($chunk as $line) {
                $raw  = array_pad(array_map('trim', str_getcsv($line, $delimiter)), count($headers), null);
                $data = array_combine($headers, $raw);

                $xuid = $data[$col['xuid_psid']] ?? null;
                if (!$xuid) continue;

                $driverId = $driverMap[$xuid] ?? null;

                $statsRow = [
                    'driver_id'               => $driverId,
                    'xuid_psid'               => $xuid,
                    'wins'                    => (int) ($data[$col['wins']            ?? ''] ?? 0),
                    'seconds'                 => (int) ($data[$col['seconds']         ?? ''] ?? 0),
                    'thirds'                  => (int) ($data[$col['thirds']          ?? ''] ?? 0),
                    'total_races'             => (int) ($data[$col['total_races']     ?? ''] ?? 0),
                    'podiums'                 => (int) ($data[$col['podiums']         ?? ''] ?? 0),
                    'top5s'                   => (int) ($data[$col['top5s']           ?? ''] ?? 0),
                    'top10s'                  => (int) ($data[$col['top10s']          ?? ''] ?? 0),
                    'fastest_race_laps'       => (int) ($data[$col['fastest_race_laps']      ?? ''] ?? 0),
                    'fastest_qualifying_laps' => (int) ($data[$col['fastest_qualifying_laps'] ?? ''] ?? 0),
                    'total_spectated'         => (int) ($data[$col['total_spectated']        ?? ''] ?? 0),
                    'total_race_positions'    => (int) ($data[$col['total_race_positions']   ?? ''] ?? 0),
                    'total_qualy_positions'   => (int) ($data[$col['total_qualy_positions']  ?? ''] ?? 0),
                    'positions_gained'        => (int) ($data[$col['positions_gained']       ?? ''] ?? 0),
                    'total_races_2024'        => (int) ($data[$col['total_races_2024']       ?? ''] ?? 0),
                    'updated_at'              => $now,
                    'created_at'              => $now,
                ];

                foreach ($penaltyCols as $header => $field) {
                    $statsRow[$field] = (int) ($data[$header] ?? 0);
                }

                $statsRows[] = $statsRow;

                // Accumulate track times
                foreach ($raceTrackCols as $header => $trackName) {
                    $lap = trim($data[$header] ?? '');
                    if ($lap && $lap !== '-' && $lap !== '0') {
                        $trackAccumulator[$xuid][$trackName]['race']      = $lap;
                        $trackAccumulator[$xuid][$trackName]['driver_id'] = $driverId;
                    }
                }
                foreach ($qualyTrackCols as $header => $trackName) {
                    $lap = trim($data[$header] ?? '');
                    if ($lap && $lap !== '-' && $lap !== '0') {
                        $trackAccumulator[$xuid][$trackName]['qualy']     = $lap;
                        $trackAccumulator[$xuid][$trackName]['driver_id'] = $driverId;
                    }
                }
            }

            // Flatten accumulator into upsert rows
            $trackRows = [];
            foreach ($trackAccumulator as $xuid => $tracks) {
                foreach ($tracks as $trackName => $times) {
                    $trackRows[] = [
                        'driver_id'           => $times['driver_id'] ?? null,
                        'xuid_psid'           => $xuid,
                        'track'               => $trackName,
                        'best_race_lap'       => $times['race']  ?? null,
                        'best_qualifying_lap' => $times['qualy'] ?? null,
                        'updated_at'          => $now,
                        'created_at'          => $now,
                    ];
                }
            }

            try {
                DB::transaction(function () use ($statsRows, $trackRows) {
                    if ($statsRows) {
                        DriverStats::upsert($statsRows, ['xuid_psid'], [
                            'driver_id', 'wins', 'seconds', 'thirds', 'total_races', 'podiums',
                            'top5s', 'top10s', 'fastest_race_laps', 'fastest_qualifying_laps',
                            'total_spectated', 'total_race_positions', 'total_qualy_positions',
                            'positions_gained', 'total_races_2024',
                            'penalty_pit_speeding', 'penalty_wrong_way', 'penalty_cutting',
                            'penalty_trolling', 'penalty_start_speeding', 'penalty_out_of_start_pos',
                            'penalty_stop_go_30', 'penalty_disqualified', 'penalty_drive_through',
                            'penalty_post_race_time', 'penalty_other', 'updated_at',
                        ]);
                    }
                    foreach (array_chunk($trackRows, 200) as $batch) {
                        DriverTrackTime::upsert($batch, ['xuid_psid', 'track'], [
                            'driver_id', 'best_race_lap', 'best_qualifying_lap', 'updated_at',
                        ]);
                    }
                });
                $done += count($statsRows);
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("Chunk error: {$e->getMessage()}");
            }

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done — {$done} stats upserted, {$errors} chunk errors.");
        return 0;
    }

    private function detectRaceTrackColumns(array $headers, array $col, array $penaltyCols): array
    {
        $skipLower = array_merge(
            self::KNOWN_COLS,
            array_map('strtolower', array_values($col)),
            array_map('strtolower', array_keys($penaltyCols)),
            array_keys(self::PENALTY_MAP)
        );

        $tracks = [];
        foreach ($headers as $header) {
            $lower = strtolower(trim($header));
            if (!$lower || is_numeric($lower)) continue;
            if (in_array($lower, $skipLower, true)) continue;
            if (str_ends_with($lower, '(q)')) continue; // qualifying — handled separately

            $tracks[$header] = trim($header);
        }
        return $tracks;
    }

    private function detectQualyTrackColumns(array $headers): array
    {
        $tracks = [];
        foreach ($headers as $header) {
            if (preg_match('/\(Q\)\s*$/i', $header)) {
                // Strip the (Q) suffix to get the base track name
                $trackName        = trim(preg_replace('/\s*\(Q\)\s*$/i', '', $header));
                $tracks[$header]  = $trackName;
            }
        }
        return $tracks;
    }

    private function detectPenaltyColumns(array $headers): array
    {
        $result = [];
        foreach ($headers as $header) {
            $lower = strtolower(trim($header));
            if (isset(self::PENALTY_MAP[$lower])) {
                $result[$header] = self::PENALTY_MAP[$lower];
            }
        }
        return $result;
    }

    private function parseFile(string $path): array
    {
        $content   = str_replace(["\r\n", "\r"], "\n", file_get_contents($path));
        $lines     = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));
        $delimiter = $this->detectDelimiter($lines[0] ?? '');
        $headers   = array_map(fn($h) => trim($h), str_getcsv(array_shift($lines), $delimiter));
        return [$headers, $lines, $delimiter];
    }

    private function detectDelimiter(string $line): string
    {
        $counts = ["\t" => substr_count($line, "\t"), ';' => substr_count($line, ';'), ',' => substr_count($line, ',')];
        arsort($counts);
        return array_key_first($counts);
    }

    private function mapColumns(array $rawHeaders): array
    {
        $lower   = array_map('strtolower', $rawHeaders);
        $aliases = [
            'xuid_psid'               => ['driverid', 'xuid_psid', 'xuid', 'psid'],
            'driver_name'             => ['drivername', 'driver name'],
            'wins'                    => ['wins'],
            'seconds'                 => ['2nds', 'seconds'],
            'thirds'                  => ['3rds', 'thirds'],
            'total_races'             => ['totalraces', 'total races'],
            'podiums'                 => ['podiums'],
            'top5s'                   => ['top5s', 'top 5s'],
            'top10s'                  => ['top10s', 'top 10s'],
            'fastest_race_laps'       => ['fastestrlaps', 'fastest race laps'],
            'fastest_qualifying_laps' => ['fastestqualifyinglaps', 'fastest qualifying laps'],
            'total_spectated'         => ['totalspectated', 'total spectated'],
            'total_race_positions'    => ['totalracepositions', 'total race positions'],
            'total_qualy_positions'   => ['totalqualypositions', 'total qualy positions'],
            'positions_gained'        => ['positionsgained', 'positions gained'],
            'total_races_2024'        => ['totalracesin2024', 'total races 2024', 'total races in 2024'],
        ];

        $resolved = [];
        foreach ($aliases as $key => $candidates) {
            foreach ($candidates as $candidate) {
                $idx = array_search($candidate, $lower, true);
                if ($idx !== false) {
                    $resolved[$key] = $rawHeaders[$idx];
                    break;
                }
            }
        }
        return $resolved;
    }
}