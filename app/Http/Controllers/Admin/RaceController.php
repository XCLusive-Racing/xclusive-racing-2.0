<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventFormat;
use App\Models\EventTag;
use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\Media;
use App\Models\Race;
use App\Models\RaceClass;
use App\Services\AccServerConfigService;
use App\Services\FtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RaceController extends Controller
{
    public const ERR_SLOT_WRONG_SERVER = 'This time doesn\'t match a restart slot on the selected server — pick a different server or adjust the time.';
    public const ERR_SLOT_TAKEN        = 'This time slot is already taken on the selected server.';

    public function index()
    {
        // Auto-close races whose start time has passed but are still open
        Race::where('status', 'open')
            ->where('scheduled_at', '<', now())
            ->update(['status' => 'closed']);

        $races = Race::select(['id','title','game','track','scheduled_at','status','is_championship','event_tag','max_drivers','duration_key','is_endurance','event_format_id'])
            ->where('is_endurance', false)
            ->whereNotNull('event_format_id')
            ->orderBy('scheduled_at', 'asc')
            ->get();
        $races->loadCount(['registrations', 'teamEntries']);

        return view('admin.races.index', compact('races'));
    }

    public function specialIndex()
    {
        $races = Race::select(['id','title','game','track','scheduled_at','status','is_championship','event_tag','max_drivers','duration_key','event_format_id','is_endurance'])
            ->where(fn($q) => $q->where('is_endurance', true)->orWhereNull('event_format_id'))
            ->orderBy('scheduled_at', 'desc')
            ->get();
        $races->loadCount(['registrations', 'teamEntries']);

        return view('admin.races.special', compact('races'));
    }

    public function show(Race $race, AccServerConfigService $config)
    {
        $raceResults   = $race->results()->where('session_type', 'race')->with('user')->get();
        $qualiResults  = $race->results()->where('session_type', 'quali')->with('user')->get();
        $registrations = $race->registrations()->with('user')->orderBy('created_at')->get();
        $teamEntries   = $race->is_endurance ? $race->teamEntries()->count() : null;

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
                    $ftpAllFiles = $result['all'];
                    $ftpFiles    = $result['json'];
                    $ftp->disconnect();
                } else {
                    $ftpError = 'Could not connect to ' . $selectedServer->host . '.';
                }
                $importedFiles = FtpImportedFile::where('race_id', $race->id)->pluck('filename')->toArray();
            }
        }

        $entrylistDrivers = [];
        $uploadedEntrylist = $race->configFile('entrylist.json');
        if ($uploadedEntrylist) {
            $parsed = json_decode($uploadedEntrylist, true);
            $playerIds = collect($parsed['entries'] ?? [])
                ->map(fn($e) => $e['drivers'][0]['playerID'] ?? null)
                ->filter()->values()->all();

            $usersByPlatformId = \App\Models\User::whereIn('platform_id', $playerIds)
                ->get()->keyBy('platform_id');

            foreach ($parsed['entries'] ?? [] as $entry) {
                $driver = $entry['drivers'][0] ?? null;
                if (!$driver) continue;
                $playerId = $driver['playerID'] ?? null;
                $name = trim(($driver['firstName'] ?? '') . ' ' . ($driver['lastName'] ?? ''));
                $entrylistDrivers[] = [
                    'name'       => $name ?: 'Unknown',
                    'player_id'  => $playerId,
                    'car_number' => $entry['raceNumber'] ?? null,
                    'user'       => $playerId ? $usersByPlatformId->get($playerId) : null,
                ];
            }
        }

        return view('admin.races.show', compact(
            'race', 'raceResults', 'qualiResults', 'registrations', 'teamEntries',
            'ftpServers', 'selectedServer', 'ftpFiles', 'ftpAllFiles', 'ftpError', 'importedFiles',
            'entrylistDrivers'
        ))->with('configData', $config);
    }

    public function downloadEntryList(Race $race)
    {
        $registrations = $race->registrations()->with('user')->orderBy('created_at')->get();

        $entries = $registrations->map(function ($reg) use ($race) {
            $user      = $reg->user;
            $lastName  = $user->team ? $user->name . "\n" . $user->team : ($user->name ?? '');
            $shortName = mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $user->name ?? ''), 0, 3));

            return [
                'drivers' => [
                    [
                        'firstName'      => '',
                        'lastName'       => $lastName,
                        'shortName'      => $shortName,
                        'playerID'       => $user->platform_id ?? '',
                        'driverCategory' => $user->ratingClass($race->game),
                    ],
                ],
                'raceNumber'          => is_numeric($user->car_number) ? (int) $user->car_number : '',
                'defaultGridPosition' => -1,
                'ballastKg'           => 0,
                'forcedCarModel'      => -1,
                'overrideDriverInfo'  => 1,
            ];
        });

        $data = [
            'entries'        => $entries,
            'configVersion'  => 1,
            'forceEntryList' => 0,
        ];

        $filename = Str::slug($race->title) . '-entry-list.json';

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], JSON_PRETTY_PRINT);
    }

    public function bulkCreate()
    {
        return redirect()->route('admin.races.create', ['tab' => 'bulk']);
    }

    public function customCreate()
    {
        $tags    = EventTag::orderBy('name')->get();
        $servers = FtpServer::where('active', true)->orderBy('name')->get();
        $accTracks = array_keys(self::TRACK_IMAGE_MAP);

        $trackFilenames   = array_values(self::TRACK_IMAGE_MAP);
        $trackMediaByName = Media::whereIn('original_name', $trackFilenames)->get()->keyBy('original_name');
        $trackPreviewUrls = collect(self::TRACK_IMAGE_MAP)
            ->map(fn($fname) => $trackMediaByName->get($fname)?->url)
            ->all();

        return view('admin.races.custom-create', compact('tags', 'servers', 'accTracks', 'trackPreviewUrls'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'game'                 => 'required|in:acc,lmu,iracing,ac',
            'event_tag'            => 'nullable|exists:event_tags,slug',
            'event_format_id'      => 'nullable|exists:event_formats,id',
            'duration_key'         => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'xcl_r_multiplier'     => 'nullable|numeric|min:0.1|max:10',
            'practice_duration'    => 'nullable|integer|min:1|max:1440',
            'qualifying_duration'  => 'nullable|integer|min:1|max:1440',
            'race_duration'        => 'nullable|integer|min:1|max:1440',
            'car_class'            => 'nullable|string|max:50',
            'weather'              => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness'   => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level'           => 'nullable|numeric|min:0|max:1',
            'time_of_day'          => 'nullable|date_format:H:i',
            'ambient_temp'         => 'nullable|integer|min:-30|max:50',
            'sr_requirement'       => 'nullable|numeric|in:3,4,5,6,7,8,9',
            'min_rating'           => 'nullable|string|in:rookie,bronze,silver,gold,platinum,alien',
            'max_rating'           => 'nullable|string|in:rookie,bronze,silver,gold,platinum,alien',
            'max_drivers'          => 'nullable|integer|min:1',
            'description'          => 'nullable|string',
            'is_multiclass'        => 'nullable|boolean',
            'classes_json'         => 'nullable|string',
            'ftp_server_id'        => 'nullable|exists:ftp_servers,id',
            'events'               => 'required|array|min:1|max:20',
            'events.*.title'           => 'required|string|max:255',
            'events.*.track'           => 'required|string|max:255',
            'events.*.scheduled_at'    => 'required|date',
            'events.*.event_tag'       => 'nullable|exists:event_tags,slug',
            'events.*.event_format_id' => 'nullable|exists:event_formats,id',
            'events.*.ftp_server_id'   => 'nullable|exists:ftp_servers,id',
            'events.*.weather'         => 'nullable|in:dry,wet,mixed,random',
            'events.*.time_of_day'     => 'nullable|date_format:H:i',
            'events.*.ambient_temp'    => 'nullable|integer|min:-30|max:50',
            'events.*.max_drivers'     => 'nullable|integer|min:1',
        ]);

        $shared = [
            'game'                 => $request->game,
            'event_tag'            => $request->event_tag ?: null,
            'event_format_id'      => $request->event_format_id ?: null,
            'duration_key'         => $request->duration_key ?: null,
            'practice_duration'    => $request->practice_duration ?: null,
            'qualifying_duration'  => $request->qualifying_duration ?: null,
            'race_duration'        => $request->race_duration ?: null,
            'car_class'            => $request->car_class ?: null,
            'weather'              => $request->weather ?: null,
            'weather_randomness'   => $request->weather_randomness ?: null,
            'rain_level'           => $request->filled('rain_level') ? (float) $request->rain_level : null,
            'time_of_day'          => $request->time_of_day ?: null,
            'ambient_temp'         => $request->ambient_temp ?? null,
            'sr_requirement'       => $request->sr_requirement ?: null,
            'min_rating'           => $request->min_rating ?: null,
            'max_rating'           => $request->max_rating ?: null,
            'max_drivers'          => $request->max_drivers ?: null,
            'description'          => $request->description ?: null,
            'status'               => 'open',
        ];

        // Per row, event_tag / event_format_id / ftp_server_id fall back to the shared
        // selection only when the row itself doesn't specify one — a whole week can mix
        // formats/tags/servers (e.g. imported from a CSV), or share one via the shared
        // fields (the day/week generator, which has no per-row concept of these).
        $eventData     = [];
        $rowServerIds  = [];
        foreach ($request->events as $i => $event) {
            $eventTag = ($event['event_tag'] ?? null) ?: $shared['event_tag'];
            if (!$eventTag) {
                return back()->withInput()->withErrors(['events.' . $i . '.event_tag' => 'Row ' . ($i + 1) . ': no Event Tag set (neither per-row nor shared).']);
            }

            $rowServerIds[$i] = ($event['ftp_server_id'] ?? null) ?: $request->ftp_server_id;

            $eventData[] = $this->deriveFormatFields($this->normalizeRainLevel(array_merge($shared, [
                'title'            => $event['title'],
                'track'            => $event['track'],
                'scheduled_at'     => \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $event['scheduled_at'], 'Europe/London')->utc(),
                'event_tag'        => $eventTag,
                'event_format_id'  => ($event['event_format_id'] ?? null) ?: $shared['event_format_id'],
                'weather'          => $event['weather'] ?: $shared['weather'],
                'time_of_day'      => $event['time_of_day'] ?: $shared['time_of_day'],
                'ambient_temp'     => $event['ambient_temp'] ?? $shared['ambient_temp'],
                'max_drivers'      => $event['max_drivers'] ?: $shared['max_drivers'],
            ])));
        }

        // Validate every event's slot up front (against the DB and against each other,
        // per its own resolved server) before creating anything — same rule as a single
        // race: the event's own time is its slot on that server.
        $serversById = FtpServer::whereIn('id', array_filter(array_unique($rowServerIds)))->get()->keyBy('id');
        $seenSlots   = [];
        foreach ($eventData as $i => $data) {
            $server = $serversById->get($rowServerIds[$i]);
            if (!$server) {
                continue;
            }
            if (!$server->isValidSlot($data['scheduled_at'])) {
                return back()->withInput()->withErrors(['events.' . $i . '.scheduled_at' => 'Row ' . ($i + 1) . ': ' . self::ERR_SLOT_WRONG_SERVER]);
            }
            $slotKey = $server->id . '|' . $data['scheduled_at']->format('Y-m-d H:i');
            if (in_array($slotKey, $seenSlots, true) || in_array($data['scheduled_at']->format('Y-m-d H:i'), $server->takenSlots(), true)) {
                return back()->withInput()->withErrors(['events.' . $i . '.scheduled_at' => 'Row ' . ($i + 1) . ': ' . self::ERR_SLOT_TAKEN]);
            }
            $seenSlots[] = $slotKey;
        }

        $races = [];
        foreach ($eventData as $i => $data) {
            $server = $serversById->get($rowServerIds[$i]);
            $data['ftp_server_id'] = $server?->id;
            if ($server) {
                $data['slot_time']          = $data['scheduled_at']->copy();
                $data['config_push_status'] = 'pending';
            }
            $races[] = Race::create($data);
        }

        if ($request->boolean('is_multiclass')) {
            $classesJson = json_decode($request->input('classes_json', '[]'), true) ?: [];
            foreach ($races as $race) {
                $this->syncRaceClasses($request, $race);
            }
        }

        $count = count($request->events);
        return redirect()->route('admin.races.index')
            ->with('success', $count . ' ' . ($count === 1 ? 'race' : 'races') . ' created successfully!');
    }

    public function importExport()
    {
        $tags    = EventTag::orderBy('name')->get();
        $servers = FtpServer::where('active', true)->orderBy('name')->get();
        $formats = EventFormat::orderBy('game')->orderBy('sort_order')->get();

        return view('admin.races.import-export', compact('tags', 'servers', 'formats'));
    }

    // CSV columns (header row, case-insensitive, any order): track, date, time —
    // required. weather / time_of_day / ambient_temp / event_tag / format / server —
    // all optional, and event_tag/format/server (when given) override the page's
    // shared defaults for that one row, so a single import can mix formats/tags/
    // servers across a whole week. Parses to the same row shape the Bulk Schedule
    // table already uses, so the page's JS can render an editable preview and submit
    // it straight to bulkStore() — no new creation path.
    public function bulkImportCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'game' => 'nullable|in:acc,lmu,iracing,ac',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle, null, ',', '"', '\\');
        if (!$header) {
            fclose($handle);
            return response()->json(['errors' => ['The file is empty.']], 422);
        }
        // Excel-exported CSVs commonly start with a UTF-8 BOM, which would otherwise
        // corrupt the first header cell (e.g. "track" becomes "\xEF\xBB\xBFtrack") and
        // make the very first column silently fail to match.
        $header   = array_map(fn($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', $h ?? ''))), $header);
        $colIndex = array_flip($header);

        foreach (['track', 'date', 'time'] as $col) {
            if (!isset($colIndex[$col])) {
                fclose($handle);
                return response()->json(['errors' => ["Missing required column \"{$col}\". Expected headers: track, date, time, weather, time_of_day, ambient_temp."]], 422);
            }
        }

        // Lookup maps for the optional per-row overrides, keyed lowercase for case-insensitive matching.
        $tagsByKey = EventTag::all()->flatMap(fn($t) => [
            strtolower($t->slug) => $t->slug,
            strtolower($t->name) => $t->slug,
        ])->all();
        $formatsByKey = EventFormat::when($request->filled('game'), fn($q) => $q->where('game', $request->game))
            ->get()->keyBy(fn($f) => strtolower($f->name))->map->id->all();
        $serversByKey = [];
        foreach (FtpServer::where('active', true)->get() as $s) {
            $serversByKey[strtolower($s->name)] = $s->id;
            if ($s->server_number) {
                $serversByKey[(string) $s->server_number] = $s->id;
            }
        }

        $rows    = [];
        $errors  = [];
        $lineNum = 1;

        while (($line = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
            $lineNum++;
            if (count(array_filter($line, fn($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $track = trim($line[$colIndex['track']] ?? '');
            $date  = trim($line[$colIndex['date']] ?? '');
            $time  = trim($line[$colIndex['time']] ?? '');

            if ($track === '' || $date === '' || $time === '') {
                $errors[] = "Row {$lineNum}: missing track/date/time — skipped.";
                continue;
            }

            try {
                $dt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . substr($time, 0, 5));
                if (!$dt) throw new \Exception();
            } catch (\Throwable $e) {
                $errors[] = "Row {$lineNum}: invalid date/time \"{$date} {$time}\" (expected YYYY-MM-DD and HH:MM) — skipped.";
                continue;
            }

            $weather = isset($colIndex['weather']) ? strtolower(trim($line[$colIndex['weather']] ?? '')) : '';
            if ($weather !== '' && !in_array($weather, ['dry', 'wet', 'mixed', 'random'], true)) {
                $errors[] = "Row {$lineNum}: unknown weather \"{$weather}\" — ignored.";
                $weather = '';
            }

            $timeOfDay = isset($colIndex['time_of_day']) ? trim($line[$colIndex['time_of_day']] ?? '') : '';
            if ($timeOfDay !== '' && !preg_match('/^\d{1,2}:\d{2}$/', $timeOfDay)) {
                $errors[] = "Row {$lineNum}: invalid time_of_day \"{$timeOfDay}\" (expected HH:MM) — ignored.";
                $timeOfDay = '';
            }

            $ambientTemp = isset($colIndex['ambient_temp']) ? trim($line[$colIndex['ambient_temp']] ?? '') : '';
            if ($ambientTemp !== '' && !is_numeric($ambientTemp)) {
                $errors[] = "Row {$lineNum}: invalid ambient_temp \"{$ambientTemp}\" — ignored.";
                $ambientTemp = '';
            }

            $eventTagSlug = '';
            $rawTag = isset($colIndex['event_tag']) ? trim($line[$colIndex['event_tag']] ?? '') : '';
            if ($rawTag !== '') {
                $eventTagSlug = $tagsByKey[strtolower($rawTag)] ?? '';
                if ($eventTagSlug === '') {
                    $errors[] = "Row {$lineNum}: unknown event_tag \"{$rawTag}\" — using the shared default.";
                }
            }

            $formatId = '';
            $rawFormat = isset($colIndex['format']) ? trim($line[$colIndex['format']] ?? '') : '';
            if ($rawFormat !== '') {
                $formatId = $formatsByKey[strtolower($rawFormat)] ?? '';
                if ($formatId === '') {
                    $errors[] = "Row {$lineNum}: unknown format \"{$rawFormat}\" — using the shared default.";
                }
            }

            $serverId = '';
            $rawServer = isset($colIndex['server']) ? trim($line[$colIndex['server']] ?? '') : '';
            if ($rawServer !== '') {
                $serverId = $serversByKey[strtolower($rawServer)] ?? '';
                if ($serverId === '') {
                    $errors[] = "Row {$lineNum}: unknown server \"{$rawServer}\" — using the shared default.";
                }
            }

            $rows[] = [
                'title'            => $track,
                'track'            => $track,
                'scheduled_at'     => $dt->format('Y-m-d\TH:i'),
                'event_tag'        => $eventTagSlug,
                'event_format_id'  => $formatId,
                'ftp_server_id'    => $serverId,
                'weather'          => $weather,
                'time_of_day'      => $timeOfDay,
                'ambient_temp'     => $ambientTemp,
            ];
        }
        fclose($handle);

        if (empty($rows)) {
            return response()->json(['errors' => array_merge(['No valid rows found in the file.'], $errors)], 422);
        }

        return response()->json(['rows' => $rows, 'errors' => $errors]);
    }

    // Exports races in a date range (optionally filtered to one game) back out in the
    // same column format bulkImportCsv() expects — lets an admin export a finished
    // week and re-import it to duplicate the schedule onto a future week.
    public function exportCsv(Request $request)
    {
        $request->validate(['game' => 'required|in:acc,lmu,iracing,ac']);

        // Custom races (no event_format_id) have no fixed session durations/title —
        // same "real" races filter the admin races index already uses.
        $races = Race::where('game', $request->game)
            ->where('is_endurance', false)
            ->whereNotNull('event_format_id')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->get();

        $filename = 'xcl-races-upcoming-' . $request->game . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($races) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['track', 'date', 'time', 'weather', 'time_of_day', 'ambient_temp'], ',', '"', '\\');
            foreach ($races as $race) {
                $local = $race->scheduledAtUk();
                fputcsv($out, [
                    $race->track,
                    $local->format('Y-m-d'),
                    $local->format('H:i'),
                    $race->weather,
                    $race->time_of_day,
                    $race->ambient_temp,
                ], ',', '"', '\\');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function create(Request $request)
    {
        $prefillDate = $request->date('date')?->format('Y-m-d\TH:i');
        $tags    = EventTag::orderBy('name')->get();
        $race    = null;

        $servers = FtpServer::where('active', true)->orderBy('name')->get();

        return view('admin.races.form', array_merge(
            compact('race', 'prefillDate', 'tags', 'servers'),
            $this->formatBuilderData()
        ));
    }

    // Shared preview/media data for the create+edit form (also used by edit())
    private function formatBuilderData(): array
    {
        $formats = EventFormat::orderBy('game')->orderBy('sort_order')->get();

        $trackFilenames   = array_values(self::TRACK_IMAGE_MAP);
        $trackMediaByName = Media::whereIn('original_name', $trackFilenames)->get()->keyBy('original_name');
        $trackPreviewUrls = collect(self::TRACK_IMAGE_MAP)
            ->map(fn($fname) => $trackMediaByName->get($fname)?->url)
            ->all();

        $formatPreviewUrls = [];
        foreach ($formats as $fmt) {
            $slug = Str::slug($fmt->name, '_');
            $key  = self::FORMAT_IMAGE_OVERRIDES[$slug] ?? $slug;
            $formatPreviewUrls[$fmt->id] = Media::where('title', $key)
                ->orWhere('original_name', 'like', $key . '%')
                ->first()?->url;
        }

        return compact('formats', 'trackPreviewUrls', 'formatPreviewUrls');
    }

    // Derives title/durations/icon from the chosen Format + track image, for format-based races.
    // Custom races (no event_format_id) keep whatever title/durations/image were submitted directly.
    // Rain Level is only meaningful for wet/mixed weather — the slider stays in the DOM
    // (just hidden) for dry/random/unset, so without this a stray submitted value would
    // otherwise leak into AccServerConfigService's rain override for a supposedly dry race.
    private function normalizeRainLevel(array $data): array
    {
        if (!in_array($data['weather'] ?? null, ['wet', 'mixed'], true)) {
            $data['rain_level'] = null;
        }

        return $data;
    }

    private function deriveFormatFields(array $data): array
    {
        if (!empty($data['event_format_id'])) {
            // Endurance/driver-swap is Custom Race only — a format-based race never sets it.
            $data['is_endurance']                = false;
            $data['driver_stint_time_mins']      = null;
            $data['max_total_driving_time_mins'] = null;
            $data['mandatory_driver_swap']       = false;

            $fmt = EventFormat::find($data['event_format_id']);
            if ($fmt) {
                $data['title']               = $fmt->name;
                $data['duration_key']        = null;
                $data['practice_duration']   = $fmt->practice_mins ?: null;
                $data['qualifying_duration'] = $fmt->quali_mins ?: null;
                $data['race_duration']       = $fmt->race1_mins ?: null;

                $formatSlug     = Str::slug($fmt->name, '_');
                $formatImageKey = self::FORMAT_IMAGE_OVERRIDES[$formatSlug] ?? $formatSlug;

                $data['icon'] = Media::where('title', $formatImageKey)
                    ->orWhere('original_name', 'like', $formatImageKey . '%')
                    ->value('path');
            }

            $trackFilename = self::TRACK_IMAGE_MAP[$data['track']] ?? null;
            $data['image'] = $trackFilename
                ? Media::where('original_name', $trackFilename)->value('path')
                : null;
        }

        return $data;
    }

    // Track name → background image filename in media library
    public const TRACK_IMAGE_MAP = [
        'Barcelona'      => 'Barcelona.png',
        'Brands Hatch'   => 'Brands.png',
        'COTA'           => 'COTA.png',
        'Donington'      => 'Donington.png',
        'Hungaroring'    => 'Hungaroring.png',
        'Imola'          => 'Imola.png',
        'Indianapolis'   => 'Indy.png',
        'Kyalami'        => 'Kyalami.png',
        'Laguna Seca'    => 'Laguna Seca.png',
        'Misano'         => 'Misano.png',
        'Monza'          => 'Monza.png',
        'Mount Panorama' => 'Bathurst.png',
        'Nürburgring'    => 'Nurburgring.png',
        'Nordschleife'   => 'Nords.png',
        'Oulton Park'    => 'Oulton.png',
        'Paul Ricard'    => 'Paul Ricard.png',
        'Red Bull Ring'  => 'RBR.png',
        'Silverstone'    => 'Silverstone.png',
        'Snetterton'     => 'Snetterton.png',
        'Spa'            => 'Spa.png',
        'Suzuka'         => 'Suzuka.png',
        'Valencia'       => 'Valencia.png',
        'Watkins Glen'   => 'Watkins.png',
        'Zandvoort'      => 'Zandvoort.png',
        'Zolder'         => 'Zolder.png',
    ];

    // Format slug override map (for slugs that differ from Str::slug(name))
    private const FORMAT_IMAGE_OVERRIDES = [
        'multiclass' => 'multiclass_race',
    ];

    public function store(Request $request)
    {
        $data = $request->validate([
            'game'                 => 'required|in:acc,lmu,iracing,ac',
            'track'                => 'required|string|max:255',
            'scheduled_at'         => 'required|date',
            'event_tag'            => 'required|exists:event_tags,slug',
            'event_format_id'      => 'nullable|exists:event_formats,id',
            'title'                => 'required_without:event_format_id|string|max:255',
            'duration_key'         => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'xcl_r_multiplier'     => 'nullable|numeric|min:0.1|max:10',
            'practice_duration'    => 'nullable|integer|min:1|max:1440',
            'qualifying_duration'  => 'nullable|integer|min:1|max:1440',
            'race_duration'        => 'required_without:event_format_id|integer|min:1|max:1440',
            'car_class'            => 'nullable|string|max:50',
            'sr_requirement'       => 'nullable|in:3,4,5,6,7,8,9',
            'min_rating'           => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'max_rating'           => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'weather'              => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness'   => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level'           => 'nullable|numeric|min:0|max:1',
            'time_of_day'          => 'nullable|date_format:H:i',
            'ambient_temp'         => 'nullable|integer|min:-30|max:50',
            'max_drivers'          => 'nullable|integer|min:1',
            'description'          => 'nullable|string',
            'is_multiclass'        => 'nullable|boolean',
            'is_endurance'         => 'nullable|boolean',
            'driver_stint_time_mins'      => 'nullable|integer|min:1|max:1440',
            'max_total_driving_time_mins' => 'nullable|integer|min:1|max:1440',
            'mandatory_driver_swap'       => 'nullable|boolean',
            'ftp_server_id'        => empty($request->event_format_id) ? 'required|exists:ftp_servers,id' : 'nullable|exists:ftp_servers,id',
            'image'                => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg,mov|max:204800',
            'image_path'           => 'nullable|string|max:500',
            'icon'                 => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg|max:4096',
            'icon_path'            => 'nullable|string|max:500',
            'pitstop_count'        => 'nullable|integer|min:0|max:9',
            'min_stop_secs'        => 'nullable|integer|min:1|max:3600',
        ]);

        $data['scheduled_at']  = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Europe/London')->utc();
        $data['is_multiclass'] = $request->boolean('is_multiclass');
        $data['is_endurance']  = $request->boolean('is_endurance');
        $data['mandatory_driver_swap'] = $request->boolean('mandatory_driver_swap');
        $data = $this->normalizeRainLevel($data);

        $data = $this->deriveFormatFields($data);

        if (empty($data['event_format_id'])) {
            // Custom race — use uploaded/selected image, or fall back to track's stock image
            $data['image'] = $this->resolveMedia($request);
            if (!$data['image'] && isset($data['track'])) {
                $trackFilename  = self::TRACK_IMAGE_MAP[$data['track']] ?? null;
                $data['image']  = $trackFilename
                    ? Media::where('original_name', $trackFilename)->value('path')
                    : null;
            }
            $data['icon'] = $this->resolveIcon($request);
        }
        unset($data['image_path'], $data['icon_path']);

        if (!empty($data['ftp_server_id'])) {
            $server = FtpServer::find($data['ftp_server_id']);

            // The race's own date & time (set above) is always the server slot now —
            // no separate slot grid. Reject it if that time isn't actually free on
            // this server.
            if ($server && !$server->isValidSlot($data['scheduled_at'])) {
                return back()->withInput()->withErrors(['scheduled_at' => self::ERR_SLOT_WRONG_SERVER]);
            }
            if ($server && in_array($data['scheduled_at']->format('Y-m-d H:i'), $server->takenSlots(), true)) {
                return back()->withInput()->withErrors(['scheduled_at' => self::ERR_SLOT_TAKEN]);
            }

            $data['slot_time']          = $data['scheduled_at']->copy();
            $data['config_push_status'] = 'pending';
        } else {
            $data['slot_time'] = null;
        }

        $race = Race::create($data);

        $this->syncRaceClasses($request, $race);

        return redirect()->route('admin.races.index')->with('success', 'Race created successfully!');
    }

    public function edit(Race $race)
    {
        if ($race->isPast()) {
            return redirect()->route('admin.races.index')
                ->with('error', 'Past races cannot be edited. You can still manage results.');
        }

        $race->load('raceClasses');
        $tags        = EventTag::orderBy('name')->get();
        $prefillDate = null;

        $servers = FtpServer::where('active', true)->orderBy('name')->get();

        return view('admin.races.form', array_merge(
            compact('race', 'prefillDate', 'tags', 'servers'),
            $this->formatBuilderData()
        ));
    }

    public function update(Request $request, Race $race)
    {
        if ($race->isPast()) {
            return redirect()->route('admin.races.index')
                ->with('error', 'Past races cannot be edited.');
        }

        $data = $request->validate([
            'game'                 => 'required|in:acc,lmu,iracing,ac',
            'track'                => 'required|string|max:255',
            'scheduled_at'         => 'required|date',
            'status'               => 'required|in:open,closed,finished',
            'event_tag'            => 'required|exists:event_tags,slug',
            'event_format_id'      => 'nullable|exists:event_formats,id',
            'title'                => 'required_without:event_format_id|string|max:255',
            'duration_key'         => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'xcl_r_multiplier'     => 'nullable|numeric|min:0.1|max:10',
            'practice_duration'    => 'nullable|integer|min:1|max:1440',
            'qualifying_duration'  => 'nullable|integer|min:1|max:1440',
            'race_duration'        => 'required_without:event_format_id|integer|min:1|max:1440',
            'car_class'            => 'nullable|string|max:50',
            'sr_requirement'       => 'nullable|in:3,4,5,6,7,8,9',
            'min_rating'           => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'max_rating'           => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'weather'              => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness'   => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level'           => 'nullable|numeric|min:0|max:1',
            'time_of_day'          => 'nullable|date_format:H:i',
            'ambient_temp'         => 'nullable|integer|min:-30|max:50',
            'max_drivers'          => 'nullable|integer|min:1',
            'description'          => 'nullable|string',
            'image'                => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg,mov|max:204800',
            'image_path'           => 'nullable|string|max:500',
            'image_keep'           => 'nullable|in:0,1',
            'icon'                 => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg|max:4096',
            'icon_path'            => 'nullable|string|max:500',
            'icon_keep'            => 'nullable|in:0,1',
            'is_multiclass'        => 'nullable|boolean',
            'is_endurance'         => 'nullable|boolean',
            'driver_stint_time_mins'      => 'nullable|integer|min:1|max:1440',
            'max_total_driving_time_mins' => 'nullable|integer|min:1|max:1440',
            'mandatory_driver_swap'       => 'nullable|boolean',
            'ftp_server_id'        => 'nullable|exists:ftp_servers,id',
            'pitstop_count'        => 'nullable|integer|min:0|max:9',
            'min_stop_secs'        => 'nullable|integer|min:1|max:3600',
        ]);

        $data['scheduled_at']  = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Europe/London')->utc();
        $data['is_multiclass'] = $request->boolean('is_multiclass');
        $data['is_endurance']  = $request->boolean('is_endurance');
        $data['mandatory_driver_swap'] = $request->boolean('mandatory_driver_swap');
        $data = $this->normalizeRainLevel($data);

        $data = $this->deriveFormatFields($data);

        if (!empty($data['ftp_server_id'])) {
            $server = FtpServer::find($data['ftp_server_id']);

            if ($server && !$server->isValidSlot($data['scheduled_at'])) {
                return back()->withInput()->withErrors(['scheduled_at' => self::ERR_SLOT_WRONG_SERVER]);
            }
            if ($server && in_array($data['scheduled_at']->format('Y-m-d H:i'), $server->takenSlots($race->id), true)) {
                return back()->withInput()->withErrors(['scheduled_at' => self::ERR_SLOT_TAKEN]);
            }

            $data['slot_time']          = $data['scheduled_at']->copy();
            $data['config_push_status'] = 'pending';
        } else {
            $data['slot_time'] = null;
        }

        if (empty($data['event_format_id'])) {
            // Custom race — respect manual media upload/keep controls
            $resolvedImage = $this->resolveMedia($request);
            $data['image'] = $resolvedImage ?? ($request->input('image_keep') === '0' ? null : $race->image);

            $resolvedIcon  = $this->resolveIcon($request);
            $data['icon']  = $resolvedIcon ?? ($request->input('icon_keep') === '0' ? null : $race->icon);
        }
        // else: image/icon already derived from track/format inside deriveFormatFields()

        unset($data['image_path'], $data['image_keep'], $data['icon_path'], $data['icon_keep']);

        $race->update($data);

        $this->syncRaceClasses($request, $race);

        return redirect()->route('admin.races.index')->with('success', 'Race updated successfully!');
    }

    public function pushConfig(Request $request, Race $race, AccServerConfigService $config)
    {
        $request->validate(['server_id' => 'required|exists:ftp_servers,id']);

        $server = FtpServer::findOrFail($request->server_id);

        $files = [
            'entrylist.json'  => $request->input('entrylist_json')
                ?? $race->configFile('entrylist.json')
                ?? json_encode($config->entryList($race), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'event.json'      => $request->input('event_json')
                ?? $race->configFile('event.json')
                ?? json_encode($config->configuration($race, $server), JSON_PRETTY_PRINT),
            'settings.json'   => $request->input('settings_json')
                ?? $race->configFile('settings.json')
                ?? json_encode($config->settings($race, $server), JSON_PRETTY_PRINT),
            'eventrules.json'  => $race->configFile('eventrules.json')
                ?? json_encode($config->eventRules($race, $server), JSON_PRETTY_PRINT),
            'assistrules.json' => json_encode($config->assistRules($server), JSON_PRETTY_PRINT),
        ];

        foreach ($files as $filename => $content) {
            json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', "Invalid JSON in {$filename}: " . json_last_error_msg());
            }
        }

        $ftp = new FtpService();

        if (!$ftp->connect($server)) {
            return back()->with('error', 'Could not connect to ' . $server->host . '.');
        }

        $cfgPath = rtrim($server->cfg_path, '/');

        $failed = [];
        foreach ($files as $filename => $content) {
            if (!$ftp->uploadFile($cfgPath . '/' . $filename, $content)) {
                $failed[] = $filename;
            }
        }

        $ftp->disconnect();

        if ($failed) {
            $error = 'Failed to upload: ' . implode(', ', $failed);
            $race->update([
                'config_push_status' => 'failed',
                'config_push_error'  => $error,
                'config_pushed_at'   => now(),
            ]);
            return back()->with('error', $error);
        }

        $race->update([
            'config_push_status'   => 'pushed',
            'config_push_error'    => null,
            'config_pushed_at'     => now(),
            'config_push_attempts' => 0,
        ]);

        return back()->with('success', 'Config pushed to ' . $server->name . ' — entrylist.json, event.json, settings.json, eventrules.json, assistrules.json uploaded.');
    }

    public function uploadEntrylist(Request $request, Race $race)
    {
        $request->validate([
            'entrylist_file' => 'required|file|mimes:json|max:10240',
        ]);

        $content = file_get_contents($request->file('entrylist_file')->getRealPath());
        json_decode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('config_error', 'Invalid JSON: ' . json_last_error_msg());
        }

        $overrides = $race->config_overrides ?? [];
        $overrides['entrylist.json'] = $content;
        $race->update(['config_overrides' => $overrides]);

        return back()->with('config_success', 'entrylist.json uploaded and saved.');
    }

    public function saveConfig(Request $request, Race $race)
    {
        $request->validate([
            'file'    => 'required|in:entrylist.json,event.json,settings.json,eventrules.json,assistrules.json',
            'content' => 'required|string',
        ]);

        $content = $request->input('content');
        json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('config_error', 'Invalid JSON: ' . json_last_error_msg())->withInput();
        }

        $overrides = $race->config_overrides ?? [];
        $overrides[$request->input('file')] = $content;
        $race->update(['config_overrides' => $overrides]);

        return back()->with('config_success', '"' . $request->input('file') . '" saved.');
    }

    public function bulkDestroy(Request $request)
    {
        $races = Race::whereIn('id', $request->input('ids', []))->get();
        foreach ($races as $race) {
            $race->registrations()->delete();
            $race->delete();
        }
        $count = $races->count();
        return redirect()->route('admin.races.index')
            ->with('success', $count . ' event' . ($count !== 1 ? 's' : '') . ' deleted.');
    }

    public function destroy(Race $race)
    {
        $race->registrations()->delete();
        $race->delete();

        return redirect()->route('admin.races.index')
            ->with('success', '"' . $race->title . '" has been deleted.');
    }

    public function resetConfig(Request $request, Race $race)
    {
        $request->validate([
            'file' => 'required|in:entrylist.json,event.json,settings.json,eventrules.json,assistrules.json',
        ]);

        $overrides = $race->config_overrides ?? [];
        unset($overrides[$request->input('file')]);
        $race->update(['config_overrides' => empty($overrides) ? null : $overrides]);

        return back()->with('config_success', '"' . $request->input('file') . '" reset to auto-generated.');
    }

    private function syncRaceClasses(Request $request, Race $race): void
    {
        $classesJson = $request->input('classes_json');
        if (!$classesJson) {
            return;
        }

        $classes = json_decode($classesJson, true);
        if (!is_array($classes)) {
            return;
        }

        $race->raceClasses()->delete();

        foreach ($classes as $i => $class) {
            $race->raceClasses()->create([
                'name'           => $class['name'] ?? 'Class ' . ($i + 1),
                'color'          => $class['color'] ?? '#db2777',
                'car_class'      => $class['car_class'] ?? null,
                'max_drivers'    => $class['max_drivers'] ?? null,
                'sr_requirement' => $class['sr_requirement'] ?? null,
                'min_rating'     => $class['min_rating'] ?? null,
                'sort_order'     => $i,
            ]);
        }
    }

    private function resolveMedia(Request $request): ?string
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            return $file->storeAs('images/media', Str::uuid() . '.' . $file->getClientOriginalExtension(), 'media');
        }

        return $request->filled('image_path') ? $request->image_path : null;
    }

    private function resolveIcon(Request $request): ?string
    {
        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            return $file->storeAs('images/icons', Str::uuid() . '.' . $file->getClientOriginalExtension(), 'media');
        }

        return $request->filled('icon_path') ? $request->icon_path : null;
    }
}