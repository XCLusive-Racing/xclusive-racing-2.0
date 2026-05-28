<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Http\Request;

class RaceResultController extends Controller
{
    public function create(Race $race)
    {
        $raceResults  = $race->results()->where('session_type', 'race')->with('user')->get();
        $qualiResults = $race->results()->where('session_type', 'quali')->with('user')->get();

        return view('admin.races.results', compact('race', 'raceResults', 'qualiResults'));
    }

    public function store(Request $request, Race $race)
    {
        $request->validate([
            'result_json'   => 'required|array|min:1',
            'result_json.*' => 'file|max:10240',
        ]);

        $counts = ['race' => 0, 'quali' => 0];
        $errors = [];

        foreach ($request->file('result_json') as $file) {
            $content = file_get_contents($file->getRealPath());

            // UTF-16 LE with BOM
            if (str_starts_with($content, "\xFF\xFE")) {
                $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
            // UTF-16 BE with BOM
            } elseif (str_starts_with($content, "\xFE\xFF")) {
                $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
            // UTF-16 LE without BOM: JSON starts with { or [ — in UTF-16LE that's 7B 00 or 5B 00
            } elseif (strlen($content) >= 2 && ord($content[1]) === 0) {
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
            } else {
                // UTF-8: strip BOM if present
                $content = ltrim($content, "\xEF\xBB\xBF");
            }

            // Strip stray control characters that break JSON parsing
            $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $content);

            $data = json_decode($content, true);

            if ($data === null) {
                $errors[] = $file->getClientOriginalName() . ': ' . json_last_error_msg();
                continue;
            }

            // Support: single session object, array of sessions, or {sessions: [...]}
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
        }

        if ($counts['race'] > 0) {
            $race->update(['status' => 'finished']);
        }

        if ($errors) {
            return back()->with('error', 'Failed to parse: ' . implode('; ', $errors));
        }

        $parts = [];
        if ($counts['race'] > 0)  $parts[] = $counts['race']  . ' race entries imported';
        if ($counts['quali'] > 0) $parts[] = $counts['quali'] . ' qualifying entries imported';

        $message = $parts ? implode(', ', $parts) . '.' : 'No results found in files.';

        return back()->with('success', $message);
    }

    private function parseSession(array $session, Race $race, string $sessionType): int
    {
        $lines      = $session['sessionResult']['leaderBoardLines'] ?? [];
        $bestLapMs  = ($session['sessionResult']['bestlap'] ?? -1) > 0
            ? (int) $session['sessionResult']['bestlap']
            : null;

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
            $timing     = $line['timing'] ?? [];

            $bestLap   = ($timing['bestLap']   ?? -1) > 0 ? (int) $timing['bestLap']   : null;
            $lapCount  = isset($timing['lapCount'])        ? (int) $timing['lapCount']  : null;
            $totalTime = ($timing['totalTime'] ?? -1) > 0 ? (int) $timing['totalTime'] : null;

            $dnf         = ($line['missingMandatoryPitstop'] ?? -1) === 1;
            $fastestLap  = $bestLapMs !== null && $bestLap !== null && $bestLap === $bestLapMs;

            $user = User::where('platform_id', $playerId)->first();

            RaceResult::updateOrCreate(
                [
                    'race_id'      => $race->id,
                    'session_type' => $sessionType,
                    'player_id'    => $playerId,
                ],
                [
                    'user_id'     => $user?->id,
                    'driver_name' => $driverName ?: null,
                    'car_number'  => $carNumber,
                    'position'    => $index + 1,
                    'best_lap'    => $bestLap,
                    'lap_count'   => $lapCount,
                    'total_time'  => $totalTime,
                    'fastest_lap' => $fastestLap,
                    'dnf'         => $dnf,
                ]
            );

            $saved++;
        }

        return $saved;
    }
}