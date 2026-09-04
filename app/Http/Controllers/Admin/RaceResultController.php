<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\AccResultImportService;
use App\Services\FtpService;
use App\Services\RatingService;
use App\Services\XclRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RaceResultController extends Controller
{
    public function __construct(private AccResultImportService $importService)
    {
    }

    public function create(Race $race)
    {
        $race->loadMissing(['raceClasses', 'teamEntries']);

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

        $resultUserIds   = $raceResults->pluck('user_id')->filter()->toArray();
        $resultPlayerIds = $raceResults->pluck('player_id')->filter()->toArray();

        $dnsCandidates = $race->registrations()->with('user')->get()
            ->filter(fn($r) => !in_array($r->user_id, $resultUserIds))
            ->values();

        // Entrylist-based DNS candidates (drivers in uploaded entrylist but not in results)
        $entrylistDnsCandidates = collect();
        $uploadedEntrylist = $race->configFile('entrylist.json');
        if ($uploadedEntrylist) {
            $parsed = json_decode($uploadedEntrylist, true);
            $registrationUserIds = $dnsCandidates->pluck('user_id')->filter()->toArray();

            $playerIds = collect($parsed['entries'] ?? [])
                ->map(fn($e) => $e['drivers'][0]['playerID'] ?? null)
                ->filter()->values()->all();
            $usersByPlatformId = User::whereIn('platform_id', $playerIds)->get()->keyBy('platform_id');

            foreach ($parsed['entries'] ?? [] as $entry) {
                $driver   = $entry['drivers'][0] ?? null;
                $playerId = $driver['playerID'] ?? null;
                if (!$playerId) continue;
                if (in_array($playerId, $resultPlayerIds)) continue;

                $user = $usersByPlatformId->get($playerId);
                // Skip if already covered by registrations-based DNS candidates
                if ($user && in_array($user->id, $registrationUserIds)) continue;
                if ($user && in_array($user->id, $resultUserIds)) continue;

                $name = trim(($driver['firstName'] ?? '') . ' ' . ($driver['lastName'] ?? ''));
                $entrylistDnsCandidates->push([
                    'player_id'  => $playerId,
                    'name'       => $name ?: 'Unknown',
                    'car_number' => $entry['raceNumber'] ?? null,
                    'user'       => $user,
                ]);
            }
        }

        $linkedFinishers  = $raceResults->where('dns', false)->where('dnf', false)->where('dsq', false)->where('dc', false)->whereNotNull('user_id')->count();
        $minRatingDrivers = (new XclRating())->MIN_DRIVERS;

        return view('admin.races.results', compact(
            'race', 'raceResults', 'qualiResults',
            'ftpServers', 'selectedServer', 'ftpFiles', 'ftpAllFiles', 'ftpError', 'importedFiles',
            'dnsCandidates', 'entrylistDnsCandidates', 'linkedFinishers', 'minRatingDrivers'
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
            [$content, $error] = $this->importService->decodeContent($content, $file->getClientOriginalName());

            if ($error) {
                $errors[] = $error;
                continue;
            }

            [$sessionCounts, $sessionErrors] = $this->importService->processSessions($content, $race, $file->getClientOriginalName());
            $counts['race']  += $sessionCounts['race'];
            $counts['quali'] += $sessionCounts['quali'];
            $errors = array_merge($errors, $sessionErrors);

            if ($sessionCounts['race'] > 0) {
                $this->storeResultsJson($race, $content);
            }
        }

        return $this->redirectWithCounts($counts, $errors);
    }

    // Keeps the full decoded race-session JSON (laps, sectors, penalties) around so the
    // public results page can build detailed stats — the aggregate RaceResult rows alone
    // don't carry that per-lap detail.
    private function storeResultsJson(Race $race, string $content): void
    {
        $path = 'race-results/' . $race->id . '.json';
        Storage::disk('local')->put($path, $content);
        $race->update(['results_json_path' => $path]);
    }

    public function ftpImport(Request $request, Race $race)
    {
        $request->validate([
            'server_id' => 'required|exists:ftp_servers,id',
            'filename'  => 'required|string|max:255',
        ]);

        $server   = FtpServer::findOrFail($request->server_id);
        $filename = basename($request->filename);

        \Log::info('FTP import started', ['race_id' => $race->id, 'file' => $filename, 'server' => $server->host]);

        $ftp = new FtpService();

        if (!$ftp->connect($server)) {
            \Log::error('FTP connect failed', ['host' => $server->host]);
            return back()->with('error', 'Could not connect to ' . $server->host . '.');
        }

        $fullPath = rtrim($server->path, '/') . '/' . $filename;
        \Log::info('FTP downloading', ['path' => $fullPath]);

        $content  = $ftp->getFileContent($fullPath);
        $ftp->disconnect();

        if ($content === false) {
            \Log::error('FTP download failed', ['path' => $fullPath]);
            return back()->with('error', 'Could not download: ' . $filename);
        }

        \Log::info('FTP file downloaded', ['bytes' => strlen($content)]);

        [$content, $error] = $this->importService->decodeContent($content, $filename);

        if ($error) {
            \Log::error('FTP decode failed', ['file' => $filename, 'error' => $error]);
            return back()->with('error', $error);
        }

        $counts = ['race' => 0, 'quali' => 0];
        $errors = [];

        try {
            [$sessionCounts, $sessionErrors] = $this->importService->processSessions($content, $race, $filename);
            $counts['race']  += $sessionCounts['race'];
            $counts['quali'] += $sessionCounts['quali'];
            $errors = array_merge($errors, $sessionErrors);

            if ($sessionCounts['race'] > 0) {
                $this->storeResultsJson($race, $content);
            }
        } catch (\Throwable $e) {
            \Log::error('FTP import exception', [
                'file'    => $filename,
                'race_id' => $race->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        \Log::info('FTP import done', ['race' => $counts['race'], 'quali' => $counts['quali'], 'errors' => $errors]);

        if ($counts['race'] > 0 || $counts['quali'] > 0) {
            FtpImportedFile::updateOrCreate(
                ['race_id' => $race->id, 'filename' => $filename],
                ['ftp_server_id' => $server->id]
            );
        }

        return $this->redirectWithCounts($counts, $errors);
    }

    public function ftpCancel(Request $request, Race $race)
    {
        $request->validate(['filename' => 'required|string|max:255']);

        $filename = basename($request->filename);
        $imported = FtpImportedFile::where('race_id', $race->id)->where('filename', $filename)->firstOrFail();

        // Determine session type from filename (Q = quali, R = race)
        $parts       = explode('_', pathinfo($filename, PATHINFO_FILENAME));
        $typeChar    = strtoupper($parts[2] ?? '');
        $sessionType = $typeChar === 'Q' ? 'quali' : 'race';

        $deleted = RaceResult::where('race_id', $race->id)->where('session_type', $sessionType)->delete();
        $imported->delete();

        return back()->with('success', "{$deleted} {$sessionType} results cleared — ready to re-import.");
    }

    public function addDns(Request $request, Race $race)
    {
        $request->validate([
            'user_ids'        => 'nullable|array',
            'user_ids.*'      => 'integer|exists:users,id',
            'player_entries'  => 'nullable|array',
            'player_entries.*'=> 'string|max:100',
        ]);

        $existingUserIds   = RaceResult::where('race_id', $race->id)->where('session_type', 'race')->whereNotNull('user_id')->pluck('user_id')->toArray();
        $existingPlayerIds = RaceResult::where('race_id', $race->id)->where('session_type', 'race')->pluck('player_id')->toArray();
        $maxPos            = RaceResult::where('race_id', $race->id)->where('session_type', 'race')->max('position') ?? 0;
        $added             = 0;

        foreach ($request->user_ids ?? [] as $userId) {
            if (in_array($userId, $existingUserIds)) continue;
            $user = User::find($userId);
            if (!$user) continue;
            RaceResult::create([
                'race_id'           => $race->id,
                'session_type'      => 'race',
                'user_id'           => $user->id,
                'player_id'         => $user->platform_id ?? 'DNS_' . $user->id,
                'driver_name'       => $user->name,
                'race_title'        => $race->title,
                'race_track'        => $race->track,
                'race_game'         => $race->game,
                'race_scheduled_at' => $race->scheduled_at,
                'position'          => ++$maxPos,
                'dns'               => true,
                'dnf'               => false,
                'fastest_lap'       => false,
            ]);
            $added++;
        }

        foreach ($request->player_entries ?? [] as $encoded) {
            $data     = json_decode(base64_decode($encoded), true);
            $playerId = $data['player_id'] ?? null;
            $name     = $data['name'] ?? 'Unknown';
            if (!$playerId || in_array($playerId, $existingPlayerIds)) continue;
            RaceResult::create([
                'race_id'           => $race->id,
                'session_type'      => 'race',
                'user_id'           => null,
                'player_id'         => $playerId,
                'driver_name'       => $name,
                'race_title'        => $race->title,
                'race_track'        => $race->track,
                'race_game'         => $race->game,
                'race_scheduled_at' => $race->scheduled_at,
                'position'          => ++$maxPos,
                'dns'               => true,
                'dnf'               => false,
                'fastest_lap'       => false,
            ]);
            $added++;
        }

        return back()->with('success', $added . ' DNS ' . Str::plural('entry', $added) . ' added.');
    }

    public function recalculate(Race $race)
    {
        $results = RaceResult::where('race_id', $race->id)
            ->where('session_type', 'race')
            ->whereNotNull('user_id')
            ->get();

        $finishers = $results->where('dns', false)->where('dnf', false)->where('dsq', false)->where('dc', false)->count();
        $linked    = $results->count();
        $minNeeded = (new \App\Services\XclRating())->MIN_DRIVERS;

        if ($finishers < $minNeeded) {
            return back()->with('error',
                "Cannot calculate ratings: need {$minNeeded} linked finishers, have {$finishers}. " .
                "Make sure drivers have accounts and are matched to their platform ID."
            );
        }

        try {
            (new RatingService(new XclRating()))->processRace($race);
            return back()->with('success', "Ratings recalculated for {$linked} linked drivers.");
        } catch (\Throwable $e) {
            \Log::error('Recalculate ratings failed', ['race_id' => $race->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Rating calculation failed: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, Race $race, RaceResult $result)
    {
        abort_unless($result->race_id === $race->id, 404);

        $request->validate([
            'dsq' => 'required|boolean',
            'dc'  => 'required|boolean',
        ]);

        $result->update([
            'dsq' => $request->boolean('dsq'),
            'dc'  => $request->boolean('dc'),
        ]);

        return back()->with('success', $result->displayName() . ' status updated. Click "Recalculate Ratings" to apply it.');
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
}