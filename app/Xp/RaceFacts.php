<?php

namespace App\Xp;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\RaceSessionFile;
use App\Models\XpRaceFact;
use App\Services\AccResultImportService;

// Reads what the result rows don't hold from a race's stored raw results file: whether a
// car got an in-game penalty, and whether every one of its laps was valid. Kept per result
// row (xp_race_facts) so a driver's history can be counted without opening every file.
// A race without a stored file gets no facts: unknown, not "clean".
class RaceFacts
{
    public function __construct(private AccResultImportService $importer) {}

    public function store(Race $race, int $raceNumber): void
    {
        $json = RaceSessionFile::jsonFor($race, $raceNumber);
        $session = $json ? $this->raceSession(json_decode($json, true), $race, $raceNumber) : null;
        if (! $session) {
            return;
        }

        $penalised = [];
        foreach ([...($session['penalties'] ?? []), ...($session['post_race_penalties'] ?? [])] as $penalty) {
            if (($penalty['penalty'] ?? 'None') !== 'None') {
                $penalised[$penalty['carId'] ?? null] = true;
            }
        }

        $laps = [];
        foreach ($session['laps'] ?? [] as $lap) {
            $carId = $lap['carId'] ?? null;
            $laps[$carId] = ($laps[$carId] ?? true) && ($lap['isValidForBest'] ?? false);
        }

        // Every driver of a car shares the car's facts.
        $byPlayer = [];
        foreach ($session['sessionResult']['leaderBoardLines'] ?? [] as $line) {
            $carId = $line['car']['carId'] ?? null;
            foreach ($line['car']['drivers'] ?? [] as $driver) {
                if (! empty($driver['playerId'])) {
                    $byPlayer[$driver['playerId']] = [
                        'penalty_free' => ! isset($penalised[$carId]),
                        'all_laps_valid' => $laps[$carId] ?? null, // no laps recorded: unknown
                    ];
                }
            }
        }

        $results = RaceResult::where('race_id', $race->id)->where('session_type', 'race')
            ->where('race_number', $raceNumber)->get(['id', 'player_id']);

        foreach ($results as $result) {
            if (isset($byPlayer[$result->player_id])) {
                XpRaceFact::updateOrCreate(['race_result_id' => $result->id], $byPlayer[$result->player_id]);
            }
        }
    }

    // The race session for this race number in a results file (one session, a list of
    // them, or a {sessions: []} wrapper: the shapes AccResultImportService accepts).
    private function raceSession(?array $data, Race $race, int $raceNumber): ?array
    {
        if (! $data) {
            return null;
        }

        $sessions = $data['sessions'] ?? (isset($data[0]) ? $data : [$data]);

        foreach ($sessions as $session) {
            if (AccResultImportService::sessionKind($session['sessionType'] ?? null) === 'race'
                && $this->importer->raceNumber($session, $race) === $raceNumber) {
                return $session;
            }
        }

        return null;
    }
}
