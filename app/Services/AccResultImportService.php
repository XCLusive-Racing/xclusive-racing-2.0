<?php

namespace App\Services;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;

class AccResultImportService
{
    /**
     * Normalizes raw file bytes (gPortal/ACC console exports are sometimes UTF-16) into
     * valid UTF-8 JSON. Returns [content, error] — error is null on success.
     */
    public function decodeContent(string $content, string $name): array
    {
        if (str_starts_with($content, "\xFF\xFE")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($content, "\xFE\xFF")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        } elseif (strlen($content) >= 2 && ord($content[1]) === 0) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
        } else {
            $content = ltrim($content, "\xEF\xBB\xBF");
        }

        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $content);

        if (json_decode($content, true) === null) {
            return ['', $name . ': ' . json_last_error_msg()];
        }

        return [$content, null];
    }

    /**
     * Parses a decoded results JSON payload (one or more sessions), saves RaceResult rows,
     * and marks the race finished + triggers rating processing once race-session rows land.
     * Returns [counts, errors] where counts = ['race' => int, 'quali' => int].
     */
    public function processSessions(string $content, Race $race, string $name): array
    {
        $data   = json_decode($content, true);
        $counts = ['race' => 0, 'quali' => 0];
        $errors = [];

        if (isset($data['sessions'])) {
            $sessions = $data['sessions'];
        } elseif (isset($data[0])) {
            $sessions = $data;
        } else {
            $sessions = [$data];
        }

        foreach ($sessions as $session) {
            if (!isset($session['sessionType'])) {
                continue;
            }

            $type          = $session['sessionType'] === 'Q' ? 'quali' : 'race';
            $counts[$type] += $this->parseSession($session, $race, $type);
        }

        if ($counts['race'] > 0) {
            $race->update(['status' => 'finished']);
            (new RatingService(new XclRating()))->processRace($race);
        }

        return [$counts, $errors];
    }

    private function parseSession(array $session, Race $race, string $sessionType): int
    {
        $lines     = $session['sessionResult']['leaderBoardLines'] ?? [];
        $bestLapMs = ($session['sessionResult']['bestlap'] ?? -1) > 0
            ? (int) $session['sessionResult']['bestlap']
            : null;

        $playerIds = collect($lines)
            ->map(fn($l) => $l['car']['drivers'][0]['playerId'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $usersByPlatformId = User::whereIn('platform_id', $playerIds)
            ->get()
            ->keyBy('platform_id');

        $saved = 0;

        foreach ($lines as $index => $line) {
            $drivers  = $line['car']['drivers'] ?? [];
            $driver   = $drivers[0] ?? null;
            $playerId = $driver['playerId'] ?? null;

            if (!$playerId) {
                continue;
            }

            $driverName = trim($driver['lastName'] ?? '');
            $carNumber  = $line['car']['raceNumber'] ?? null;
            $carModel   = $line['car']['carModel'] ?? null;
            $timing     = $line['timing'] ?? [];

            $rawBestLap = (int) ($timing['bestLap'] ?? -1);
            $bestLap   = ($rawBestLap > 0 && $rawBestLap < 2147483647) ? $rawBestLap : null;
            $lapCount  = isset($timing['lapCount'])        ? (int) $timing['lapCount']  : null;
            $rawTotal  = (int) ($timing['totalTime'] ?? -1);
            $totalTime = ($rawTotal > 0 && $rawTotal < 2147483647) ? $rawTotal : null;
            $lapsLed   = isset($line['lapsLed'])           ? (int) $line['lapsLed']     : null;

            $consistency = null;
            if ($bestLap && $lapCount > 0 && $totalTime) {
                $avgLap = $totalTime / $lapCount;
                $raw = ($bestLap / $avgLap) * 100;
                $consistency = ($raw >= 0 && $raw <= 999.99) ? round($raw, 2) : null;
            }

            $dnf        = ($line['missingMandatoryPitstop'] ?? -1) === 1;
            $fastestLap = $bestLapMs !== null && $bestLap !== null && $bestLap === $bestLapMs;

            $user = $usersByPlatformId->get($playerId);

            RaceResult::updateOrCreate(
                [
                    'race_id'      => $race->id,
                    'session_type' => $sessionType,
                    'player_id'    => $playerId,
                ],
                [
                    'race_title'        => $race->title,
                    'race_track'        => $race->track,
                    'race_game'         => $race->game,
                    'race_scheduled_at' => $race->scheduled_at,
                    'user_id'           => $user?->id,
                    'driver_name'       => $driverName ?: null,
                    'car_number'        => $carNumber,
                    'vehicle'           => RaceResult::accCarName($carModel),
                    'position'          => $index + 1,
                    'best_lap'          => $bestLap,
                    'lap_count'         => $lapCount,
                    'laps_led'          => $lapsLed,
                    'total_time'        => $totalTime,
                    'consistency'       => $consistency,
                    'fastest_lap'       => $fastestLap,
                    'dnf'               => $dnf,
                ]
            );

            $saved++;
        }

        return $saved;
    }
}