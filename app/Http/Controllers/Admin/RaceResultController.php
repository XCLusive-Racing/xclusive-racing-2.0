<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\FtpService;
use Illuminate\Http\Request;

class RaceResultController extends Controller
{
    public function create(Race $race)
    {
        $raceResults  = $race->results()->where('session_type', 'race')->with('user')->get();
        $qualiResults = $race->results()->where('session_type', 'quali')->with('user')->get();

        $ftpServers     = FtpServer::where('active', true)->orderBy('name')->get();
        $selectedServer = null;
        $ftpFiles       = [];
        $ftpAllFiles    = [];
        $ftpError       = null;
        $importedFiles  = [];

        if ($serverId = request('server')) {
            $selectedServer = $ftpServers->firstWhere('id', $serverId);

            if ($selectedServer) {
                $ftp = new FtpService();

                if ($ftp->connect($selectedServer)) {
                    $result      = $ftp->listFiles($selectedServer->path);
                    $ftpFiles    = $result['json'];
                    $ftpAllFiles = $result['all'];
                    $ftp->disconnect();
                } else {
                    $ftpError = 'Could not connect to ' . $selectedServer->host . '. Check credentials in server settings.';
                }

                $importedFiles = FtpImportedFile::where('race_id', $race->id)
                    ->pluck('filename')
                    ->toArray();
            }
        }

        return view('admin.races.results', compact(
            'race', 'raceResults', 'qualiResults',
            'ftpServers', 'selectedServer', 'ftpFiles', 'ftpAllFiles', 'ftpError', 'importedFiles'
        ));
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
            [$content, $error] = $this->decodeContent($content, $file->getClientOriginalName());

            if ($error) {
                $errors[] = $error;
                continue;
            }

            [$sessionCounts, $sessionErrors] = $this->processSessions($content, $race, $file->getClientOriginalName());
            $counts['race']  += $sessionCounts['race'];
            $counts['quali'] += $sessionCounts['quali'];
            $errors = array_merge($errors, $sessionErrors);
        }

        return $this->redirectWithCounts($counts, $errors);
    }

    public function ftpImport(Request $request, Race $race)
    {
        $request->validate([
            'server_id' => 'required|exists:ftp_servers,id',
            'filename'  => 'required|string|max:255',
        ]);

        $server   = FtpServer::findOrFail($request->server_id);
        $filename = basename($request->filename);

        $ftp = new FtpService();

        if (!$ftp->connect($server)) {
            return back()->with('error', 'Could not connect to ' . $server->host . '.');
        }

        $fullPath = rtrim($server->path, '/') . '/' . $filename;
        $content  = $ftp->getFileContent($fullPath);
        $ftp->disconnect();

        if ($content === false) {
            return back()->with('error', 'Could not download: ' . $filename);
        }

        [$content, $error] = $this->decodeContent($content, $filename);

        if ($error) {
            return back()->with('error', $error);
        }

        $counts = ['race' => 0, 'quali' => 0];
        $errors = [];

        [$sessionCounts, $sessionErrors] = $this->processSessions($content, $race, $filename);
        $counts['race']  += $sessionCounts['race'];
        $counts['quali'] += $sessionCounts['quali'];
        $errors = array_merge($errors, $sessionErrors);

        if ($counts['race'] > 0 || $counts['quali'] > 0) {
            FtpImportedFile::updateOrCreate(
                ['race_id' => $race->id, 'filename' => $filename],
                ['ftp_server_id' => $server->id]
            );
        }

        return $this->redirectWithCounts($counts, $errors);
    }

    private function decodeContent(string $content, string $name): array
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

    private function processSessions(string $content, Race $race, string $name): array
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
        }

        return [$counts, $errors];
    }

    private function redirectWithCounts(array $counts, array $errors): \Illuminate\Http\RedirectResponse
    {
        if ($errors) {
            return back()->with('error', 'Failed to parse: ' . implode('; ', $errors));
        }

        $parts = [];
        if ($counts['race'] > 0)  $parts[] = $counts['race']  . ' race entries imported';
        if ($counts['quali'] > 0) $parts[] = $counts['quali'] . ' qualifying entries imported';

        $message = $parts ? implode(', ', $parts) . '.' : 'No results found in file.';

        return back()->with('success', $message);
    }

    private function parseSession(array $session, Race $race, string $sessionType): int
    {
        $lines     = $session['sessionResult']['leaderBoardLines'] ?? [];
        $bestLapMs = ($session['sessionResult']['bestlap'] ?? -1) > 0
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
            $carModel   = $line['car']['carModel'] ?? null;
            $timing     = $line['timing'] ?? [];

            $bestLap   = ($timing['bestLap']   ?? -1) > 0 ? (int) $timing['bestLap']   : null;
            $lapCount  = isset($timing['lapCount'])        ? (int) $timing['lapCount']  : null;
            $totalTime = ($timing['totalTime'] ?? -1) > 0 ? (int) $timing['totalTime'] : null;
            $lapsLed   = isset($line['lapsLed'])           ? (int) $line['lapsLed']     : null;

            $consistency = null;
            if ($bestLap && $lapCount > 0 && $totalTime) {
                $avgLap = $totalTime / $lapCount;
                $consistency = round(($bestLap / $avgLap) * 100, 2);
            }

            $dnf        = ($line['missingMandatoryPitstop'] ?? -1) === 1;
            $fastestLap = $bestLapMs !== null && $bestLap !== null && $bestLap === $bestLapMs;

            $user = User::where('platform_id', $playerId)->first();

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