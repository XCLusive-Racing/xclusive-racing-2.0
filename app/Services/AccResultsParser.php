<?php

namespace App\Services;

use App\Models\RaceResult;

// Turns a raw ACC session-result JSON export into the detailed per-lap/per-sector
// stats the aggregate `race_results` rows don't carry (used by the race results
// page's stats tabs — best laps, sectors, consistency, lap-by-lap, penalties).
class AccResultsParser
{
    private AccResultImportService $importService;

    public function __construct(?AccResultImportService $importService = null)
    {
        $this->importService = $importService ?? new AccResultImportService();
    }

    public function parse(string $path): array
    {
        if (!is_file($path)) {
            return $this->empty();
        }

        $content = file_get_contents($path);
        [$decoded, $error] = $this->importService->decodeContent($content, basename($path));

        if ($error) {
            return $this->empty();
        }

        $session = $this->extractRaceSession(json_decode($decoded, true));

        if (!$session) {
            return $this->empty();
        }

        $lines             = $session['sessionResult']['leaderBoardLines'] ?? [];
        $allLaps           = $session['laps'] ?? [];
        $penalties         = $session['penalties'] ?? [];
        $postRacePenalties = $session['post_race_penalties'] ?? [];

        $leaderboard = $this->buildLeaderboard($lines, $allLaps);
        $bestSectors = $this->overallBestSectors($leaderboard);
        $leaderboard = $this->markPurpleSectors($leaderboard, $bestSectors);

        $fastestLaps = array_filter(array_column($leaderboard, 'bestLap'));

        return [
            'sessionType'         => $session['sessionType'] ?? null,
            'trackName'           => $session['trackName'] ?? null,
            'serverName'          => $session['serverName'] ?? null,
            'leaderboard'         => $leaderboard,
            'laps'                => $allLaps,
            'penalties'           => $this->parsePenalties($penalties, $lines),
            'post_race_penalties' => $this->parsePenalties($postRacePenalties, $lines),
            'bestOverallSectors'  => $bestSectors,
            'fastestOverallLap'   => $fastestLaps ? min($fastestLaps) : null,
            'leaderLapCount'      => $leaderboard[0]['lapCount'] ?? 0,
        ];
    }

    private function empty(): array
    {
        return [
            'sessionType' => null, 'trackName' => null, 'serverName' => null,
            'leaderboard' => [], 'laps' => [], 'penalties' => [], 'post_race_penalties' => [],
            'bestOverallSectors' => [null, null, null], 'fastestOverallLap' => null, 'leaderLapCount' => 0,
        ];
    }

    // A results file can hold one session, a {"sessions": [...]} wrapper, or a raw
    // array of sessions (practice/quali/race combined) — same shapes AccResultImportService
    // handles when saving RaceResult rows. We only care about the race ('R') session.
    private function extractRaceSession(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        if (isset($data['sessions'])) {
            $sessions = $data['sessions'];
        } elseif (isset($data[0])) {
            $sessions = $data;
        } else {
            $sessions = [$data];
        }

        foreach ($sessions as $session) {
            if (($session['sessionType'] ?? null) === 'R') {
                return $session;
            }
        }

        return null;
    }

    private function buildLeaderboard(array $lines, array $allLaps): array
    {
        $leaderTotal = null;
        $result      = [];

        foreach ($lines as $index => $line) {
            $car     = $line['car'] ?? [];
            $timing  = $line['timing'] ?? [];
            $carId   = $car['carId'] ?? $index;
            $driver  = ($car['drivers'] ?? [])[0] ?? [];

            $totalTime = (int) ($timing['totalTime'] ?? 0);
            if ($index === 0) {
                $leaderTotal = $totalTime;
            }

            $driverLaps = array_values(array_filter(
                $allLaps,
                fn ($lap) => ($lap['carId'] ?? null) === $carId
            ));

            $validLaps = array_values(array_filter(
                $driverLaps,
                fn ($lap) => ($lap['isValidForBest'] ?? false) && (int) ($lap['laptime'] ?? PHP_INT_MAX) < 2000000
            ));

            $bestSplits = $timing['bestSplits'] ?? [null, null, null];
            $bestLap    = (int) ($timing['bestLap'] ?? 0) ?: null;

            $result[] = [
                'position'        => $index + 1,
                'carId'           => $carId,
                'firstName'       => $driver['firstName'] ?? '',
                'lastName'        => $driver['lastName'] ?? '',
                'shortName'       => $driver['shortName'] ?? '',
                'playerId'        => $driver['playerId'] ?? null,
                'carModel'        => $car['carModel'] ?? null,
                'carName'         => RaceResult::accCarName($car['carModel'] ?? null),
                'bestLap'         => $bestLap,
                'bestSplits'      => $bestSplits,
                'totalTime'       => $totalTime ?: null,
                'lapCount'        => (int) ($timing['lapCount'] ?? 0),
                'gap'             => $index === 0 ? 0 : max(0, $totalTime - $leaderTotal),
                'allLaps'         => $driverLaps,
                'consistency'     => $this->consistency($validLaps),
                'theoreticalBest' => $this->theoreticalBest($driverLaps),
            ];
        }

        return $result;
    }

    private function consistency(array $validLaps): ?array
    {
        if (!$validLaps) {
            return null;
        }

        $times = array_map(fn ($lap) => (int) $lap['laptime'], $validLaps);
        $best  = min($times);
        $avg   = array_sum($times) / count($times);

        return [
            'bestLap'       => $best,
            'avgLap'        => (int) round($avg),
            'worstLap'      => max($times),
            'delta'         => $best > 0 ? round((($avg - $best) / $best) * 100, 2) : null,
            'validLapCount' => count($times),
        ];
    }

    // Sum of this driver's best S1 + best S2 + best S3 across all their laps — can be
    // faster than any single actual lap.
    private function theoreticalBest(array $laps): ?int
    {
        $best = [null, null, null];

        foreach ($laps as $lap) {
            $splits = $lap['splits'] ?? [];
            foreach ([0, 1, 2] as $i) {
                $v = $splits[$i] ?? null;
                if ($v !== null && $v > 0 && ($best[$i] === null || $v < $best[$i])) {
                    $best[$i] = $v;
                }
            }
        }

        return in_array(null, $best, true) ? null : array_sum($best);
    }

    private function overallBestSectors(array $leaderboard): array
    {
        $best = [null, null, null];

        foreach ($leaderboard as $entry) {
            foreach ([0, 1, 2] as $i) {
                $v = $entry['bestSplits'][$i] ?? null;
                if ($v !== null && $v > 0 && ($best[$i] === null || $v < $best[$i])) {
                    $best[$i] = $v;
                }
            }
        }

        return $best;
    }

    private function markPurpleSectors(array $leaderboard, array $bestSectors): array
    {
        foreach ($leaderboard as &$entry) {
            $entry['purpleSectors'] = [
                $entry['bestSplits'][0] !== null && $entry['bestSplits'][0] === $bestSectors[0],
                $entry['bestSplits'][1] !== null && $entry['bestSplits'][1] === $bestSectors[1],
                $entry['bestSplits'][2] !== null && $entry['bestSplits'][2] === $bestSectors[2],
            ];
        }

        return $leaderboard;
    }

    private function parsePenalties(array $penalties, array $lines): array
    {
        $namesByCarId = [];
        foreach ($lines as $line) {
            $carId  = $line['car']['carId'] ?? null;
            $driver = ($line['car']['drivers'] ?? [])[0] ?? [];
            $namesByCarId[$carId] = trim(($driver['firstName'] ?? '') . ' ' . ($driver['lastName'] ?? ''));
        }

        return array_values(array_map(fn ($p) => [
            'carId'          => $p['carId'] ?? null,
            'driverName'     => $namesByCarId[$p['carId'] ?? null] ?? 'Unknown',
            'reason'         => $p['reason'] ?? null,
            'penalty'        => $p['penalty'] ?? null,
            'penaltyValue'   => $p['penaltyValue'] ?? null,
            'violationInLap' => $p['violationInLap'] ?? null,
            'clearedInLap'   => $p['clearedInLap'] ?? null,
        ], $penalties));
    }

    public static function formatLaptime(?int $ms): string
    {
        if ($ms === null || $ms <= 0 || $ms >= 2000000) {
            return 'NO TIME';
        }

        $minutes = intdiv($ms, 60000);
        $seconds = intdiv($ms % 60000, 1000);
        $millis  = $ms % 1000;

        return $minutes > 0
            ? sprintf('%d:%02d.%03d', $minutes, $seconds, $millis)
            : sprintf('%d.%03d', $seconds, $millis);
    }

    // Total race time legitimately runs past the 2,000,000ms "no time" sentinel that
    // marks an invalid/DNF single lap — that cutoff doesn't apply here.
    public static function formatDuration(?int $ms): string
    {
        if ($ms === null || $ms <= 0) {
            return '—';
        }

        $hours   = intdiv($ms, 3600000);
        $minutes = intdiv($ms % 3600000, 60000);
        $seconds = intdiv($ms % 60000, 1000);
        $millis  = $ms % 1000;

        return $hours > 0
            ? sprintf('%d:%02d:%02d.%03d', $hours, $minutes, $seconds, $millis)
            : sprintf('%d:%02d.%03d', $minutes, $seconds, $millis);
    }
}
