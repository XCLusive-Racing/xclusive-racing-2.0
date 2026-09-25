<?php

namespace App\Services;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

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
            return ['', $name.': '.json_last_error_msg()];
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
        $data = json_decode($content, true);
        $counts = ['race' => 0, 'quali' => 0];
        $errors = [];

        if (isset($data['sessions'])) {
            $sessions = $data['sessions'];
        } elseif (isset($data[0])) {
            $sessions = $data;
        } else {
            $sessions = [$data];
        }

        $raceNumbers = [];

        foreach ($sessions as $session) {
            if (! in_array($session['sessionType'] ?? null, ['Q', 'R'], true)) {
                continue;
            }

            $type = $session['sessionType'] === 'Q' ? 'quali' : 'race';
            $raceNumber = $type === 'race' ? $this->raceNumber($session, $race) : 1;
            $saved = $this->parseSession($session, $race, $type, $raceNumber);
            $counts[$type] += $saved;

            if ($type === 'race' && $saved > 0) {
                $raceNumbers[] = $raceNumber;
            }
        }

        if ($counts['race'] > 0) {
            foreach (array_unique($raceNumbers) as $raceNumber) {
                $this->storeResultsJson($race, $content, $raceNumber);
            }

            // A multi-race round only finishes once every race is in — until then
            // the scheduled importer keeps looking for the next race's file.
            $racesIn = $race->raceResults()->reorder()->distinct()->pluck('race_number')->count();
            if ($racesIn >= $race->raceCount()) {
                $race->update(['status' => 'finished']);
            }

            (new RatingService(new XclRating))->processRace($race);
        }

        return [$counts, $errors];
    }

    // Which race of the round an R session is. ACC's sessionIndex is the session's
    // position in the event's sessions list, which AccServerConfigService builds as
    // [P?] [Q?] R R… — so it's the index past practice/quali, 1-based. A results
    // file without a sessionIndex (older/hand-built uploads) counts as race 1.
    public function raceNumber(array $session, Race $race): int
    {
        if (! isset($session['sessionIndex']) || $race->raceCount() === 1) {
            return 1;
        }

        $firstRaceIndex = ($race->practice_duration ? 1 : 0) + ($race->qualifying_duration ? 1 : 0);

        return max(1, min($race->raceCount(), (int) $session['sessionIndex'] - $firstRaceIndex + 1));
    }

    // Keeps the full decoded race-session JSON (laps, sectors, penalties) around so the
    // public results page can build its detailed stats tabs — the aggregate RaceResult
    // rows alone don't carry that per-lap detail.
    //
    // Lives here, inside processSessions()'s own race-rows branch, rather than at each
    // call site: it used to be a private method on RaceResultController, so only the two
    // *manual* import paths (upload + the admin's FTP import button) ever saved it.
    // ImportGportalResults — the scheduled every-minute importer that brings in
    // practically every real race — calls processSessions() directly and never knew to,
    // so those races silently got no stats panel at all.
    private function storeResultsJson(Race $race, string $content, int $raceNumber = 1): void
    {
        $path = $race->resultsJsonPath($raceNumber);
        Storage::disk('local')->put($path, $content);

        if ($raceNumber === 1) {
            $race->update(['results_json_path' => $path]);
        }
    }

    // A driver who parks in the pits (or never gets going) still shows up in ACC's
    // leaderboard with whatever position they last held — there's no "retired"/"finished"
    // flag in the export, so the dnf flag has to be inferred from how far they got
    // relative to the session leader. This flag drives the DNF badge/status and freezes
    // Safety Rating — it does NOT by itself decide the flat DNF rating penalty, see
    // RatingService (only a lap-0/1 retirement gets that).
    private const DNF_LAP_THRESHOLD = 0.70;

    private function parseSession(array $session, Race $race, string $sessionType, int $raceNumber = 1): int
    {
        $lines = $session['sessionResult']['leaderBoardLines'] ?? [];
        $bestLapMs = ($session['sessionResult']['bestlap'] ?? -1) > 0
            ? (int) $session['sessionResult']['bestlap']
            : null;

        $leaderLaps = $sessionType === 'race'
            ? collect($lines)->max(fn ($l) => (int) ($l['timing']['lapCount'] ?? 0))
            : 0;

        // Only save results for drivers who are actually registered — keyed for O(1) lookup.
        // Keyed by the same per-game player ID the entrylist sent (User::playerIdFor()), so
        // an ACC PC result's Steam ID matches a console-registered driver's linked Steam.
        $registeredIds = $race->registrations()
            ->with('user.connectedAccounts')
            ->get()
            ->map(fn ($reg) => $reg->user?->playerIdFor($race->game))
            ->filter()
            ->flip()
            ->all();

        // Collect all driver IDs across all cars (team entries have multiple drivers per car)
        $playerIds = collect($lines)
            ->flatMap(fn ($l) => collect($l['car']['drivers'] ?? [])->pluck('playerId'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $usersByPlatformId = User::keyedByPlayerIds($playerIds);

        $saved = 0;

        foreach ($lines as $index => $line) {
            $drivers = $line['car']['drivers'] ?? [];
            $carNumber = $line['car']['raceNumber'] ?? null;
            $carModel = $line['car']['carModel'] ?? null;
            $timing = $line['timing'] ?? [];

            $rawBestLap = (int) ($timing['bestLap'] ?? -1);
            $bestLap = ($rawBestLap > 0 && $rawBestLap < 2147483647) ? $rawBestLap : null;
            $lapCount = isset($timing['lapCount']) ? (int) $timing['lapCount'] : null;
            $rawTotal = (int) ($timing['totalTime'] ?? -1);
            $totalTime = ($rawTotal > 0 && $rawTotal < 2147483647) ? $rawTotal : null;
            $lapsLed = isset($line['lapsLed']) ? (int) $line['lapsLed'] : null;

            $consistency = null;
            if ($bestLap && $lapCount > 0 && $totalTime) {
                $avgLap = $totalTime / $lapCount;
                $raw = ($bestLap / $avgLap) * 100;
                $consistency = ($raw >= 0 && $raw <= 999.99) ? round($raw, 2) : null;
            }

            $dns = $sessionType === 'race' && $totalTime === null;
            $dnf = $sessionType === 'race' && ! $dns && $leaderLaps > 0
                && ($lapCount ?? 0) < $leaderLaps * self::DNF_LAP_THRESHOLD;
            $fastestLap = $bestLapMs !== null && $bestLap !== null && $bestLap === $bestLapMs;

            // For team entries all drivers share the same car-level stats; each registered
            // driver gets their own RaceResult row so the rating system credits them all.
            foreach ($drivers as $driver) {
                $playerId = $driver['playerId'] ?? null;
                if (! $playerId || ! isset($registeredIds[$playerId])) {
                    continue;
                }

                $driverName = AccServerConfigService::driverDisplayName($driver);
                $user = $usersByPlatformId->get($playerId);

                RaceResult::updateOrCreate(
                    [
                        'race_id' => $race->id,
                        'session_type' => $sessionType,
                        'race_number' => $raceNumber,
                        'player_id' => $playerId,
                    ],
                    [
                        'race_title' => $race->title,
                        'race_track' => $race->track,
                        'race_game' => $race->game,
                        'race_scheduled_at' => $race->scheduled_at,
                        'user_id' => $user?->id,
                        'driver_name' => $driverName ?: null,
                        'car_number' => $carNumber,
                        'vehicle' => AccCarCatalog::name($carModel, $race->game),
                        'car_class' => AccCarCatalog::carClass($carModel, $race->game),
                        'position' => $index + 1,
                        'best_lap' => $bestLap,
                        'lap_count' => $lapCount,
                        'laps_led' => $lapsLed,
                        'total_time' => $totalTime,
                        'consistency' => $consistency,
                        'fastest_lap' => $fastestLap,
                        'dnf' => $dnf,
                        'dns' => $dns,
                    ]
                );

                $saved++;
            }
        }

        return $saved;
    }
}
