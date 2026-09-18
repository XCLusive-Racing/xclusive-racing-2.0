<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventFormat;
use App\Models\EventTag;
use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\Media;
use App\Models\PracticeServer;
use App\Models\Race;
use App\Models\RaceClass;
use App\Models\RaceRegistration;
use App\Models\RaceTeamEntry;
use App\Models\User;
use App\Rules\PracticeWindowNotOverlapping;
use App\Services\Contracts\ServerConfigGenerator;
use App\Services\FtpService;
use App\Services\PracticeServer\PracticeServerSessionManager;
use App\Services\PracticeServer\PracticeWindowCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RaceController extends Controller
{
    public const ERR_SLOT_WRONG_SERVER = 'This time doesn\'t match a restart slot on the selected server — pick a different server or adjust the time.';

    public const ERR_SLOT_TAKEN = 'This time slot is already taken on the selected server.';

    // Column order for the bulk-import CSV — the blank "Download Template", the "export
    // upcoming races" CSV, and the live-edit table's own CSV export all use this exact
    // order, so a filled-in template and a re-exported edited batch always look the same
    // and re-import cleanly. `game` isn't here — a batch's game is the page's own shared
    // selector, same as today, not a per-row column (every other column already has a
    // per-row/shared-default split; game doesn't need a third way to set it).
    // event_tag isn't here — it's never picked by hand, it always auto-follows whichever
    // format the row resolves to (see deriveFormatFields()), so there's nothing for a
    // column to set.
    public const CSV_COLUMNS = [
        'format', 'track', 'weather', 'date', 'time', 'time_of_day',
        'ambient_temp', 'practice_time_multiplier', 'qualifying_time_multiplier', 'race_time_multiplier',
        'weather_randomness', 'has_practice_server', 'server',
        'sr_requirement', 'min_rating', 'max_rating', 'car_class', 'car_class_2', 'car_class_3', 'description',
    ];

    public function index()
    {
        // Auto-close races whose start time has passed but are still open
        Race::where('status', 'open')
            ->where('scheduled_at', '<', now())
            ->update(['status' => 'closed']);

        $races = Race::select(['id', 'title', 'game', 'track', 'scheduled_at', 'status', 'is_championship', 'event_tag', 'max_drivers', 'duration_key', 'is_endurance', 'event_format_id'])
            ->where('is_endurance', false)
            ->whereNotNull('event_format_id')
            ->orderBy('scheduled_at', 'asc')
            ->get();
        $races->loadCount(['registrations', 'teamEntries']);

        return view('admin.races.index', compact('races'));
    }

    public function specialIndex()
    {
        $races = Race::select(['id', 'title', 'game', 'track', 'scheduled_at', 'status', 'is_championship', 'event_tag', 'max_drivers', 'duration_key', 'event_format_id', 'is_endurance'])
            ->where(fn ($q) => $q->where('is_endurance', true)->orWhereNull('event_format_id'))
            ->orderBy('scheduled_at', 'desc')
            ->get();
        $races->loadCount(['registrations', 'teamEntries']);

        return view('admin.races.special', compact('races'));
    }

    public function show(Race $race, ServerConfigGenerator $config)
    {
        $race->loadMissing(['raceClasses', 'teamEntries']);

        $raceResults = $race->results()->where('session_type', 'race')->with('user')->get();
        $qualiResults = $race->results()->where('session_type', 'quali')->with('user')->get();
        $registrations = $race->registrations()->with('user')->orderBy('created_at')->get();
        $teamEntries = $race->is_endurance ? $race->teamEntries()->count() : null;
        $teamEntryRows = $race->is_endurance
            ? $race->teamEntries()->with(['team', 'startingDriver', 'registrations.user'])->orderBy('created_at')->get()
            : collect();

        $ftpServers = FtpServer::where('active', true)->orderBy('name')->get();
        $selectedServer = null;
        $ftpFiles = [];
        $ftpAllFiles = [];
        $ftpError = null;
        $importedFiles = [];

        if ($serverId = request('server')) {
            $selectedServer = $ftpServers->firstWhere('id', $serverId);
            if ($selectedServer) {
                $ftp = new FtpService;
                if ($ftp->connect($selectedServer)) {
                    $result = $ftp->listFiles($selectedServer->path);
                    $ftpAllFiles = $result['all'];
                    $ftpFiles = $result['json'];
                    $ftp->disconnect();
                } else {
                    $ftpError = 'Could not connect to '.$selectedServer->host.'.';
                }
                $importedFiles = FtpImportedFile::where('race_id', $race->id)->pluck('filename')->toArray();
            }
        }

        $entrylistDrivers = [];
        $uploadedEntrylist = $race->configFile('entrylist.json');
        if ($uploadedEntrylist) {
            $parsed = json_decode($uploadedEntrylist, true);
            $playerIds = collect($parsed['entries'] ?? [])
                ->map(fn ($e) => $e['drivers'][0]['playerID'] ?? null)
                ->filter()->values()->all();

            $usersByPlatformId = User::whereIn('platform_id', $playerIds)
                ->get()->keyBy('platform_id');

            foreach ($parsed['entries'] ?? [] as $entry) {
                $driver = $entry['drivers'][0] ?? null;
                if (! $driver) {
                    continue;
                }
                $playerId = $driver['playerID'] ?? null;
                $name = trim(($driver['firstName'] ?? '').' '.($driver['lastName'] ?? ''));
                $entrylistDrivers[] = [
                    'name' => $name ?: 'Unknown',
                    'player_id' => $playerId,
                    'car_number' => $entry['raceNumber'] ?? null,
                    'user' => $playerId ? $usersByPlatformId->get($playerId) : null,
                ];
            }
        }

        return view('admin.races.show', compact(
            'race', 'raceResults', 'qualiResults', 'registrations', 'teamEntries', 'teamEntryRows',
            'ftpServers', 'selectedServer', 'ftpFiles', 'ftpAllFiles', 'ftpError', 'importedFiles',
            'entrylistDrivers'
        ))->with('configData', $config);
    }

    // Admin override — removes a single driver's registration regardless of race status,
    // for cases where a driver needs pulling and can no longer unregister themselves
    // (race closed, they've lost access, etc).
    public function removeRegistration(Race $race, RaceRegistration $registration)
    {
        abort_unless($registration->race_id === $race->id, 404);

        $name = $registration->user?->name ?? 'Driver';
        $registration->delete();

        return back()->with('success', "{$name} has been removed from the entry list.");
    }

    // Admin override — removes an entire team entry (and its driver registrations)
    // regardless of race status.
    public function removeTeamEntry(Race $race, RaceTeamEntry $entry)
    {
        abort_unless($entry->race_id === $race->id, 404);

        $label = $entry->team?->name ?? ('Car #'.$entry->car_number);

        DB::transaction(function () use ($entry) {
            $entry->registrations()->delete();
            $entry->delete();
        });

        return back()->with('success', "{$label} has been removed from the entry list.");
    }

    public function downloadEntryList(Race $race)
    {
        $registrations = $race->registrations()->with('user')->orderBy('created_at')->get();

        $entries = $registrations->map(function ($reg) use ($race) {
            $user = $reg->user;
            $shortName = mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $user->name ?? ''), 0, 3));

            return [
                'drivers' => [
                    [
                        'firstName' => '',
                        'lastName' => $user->name ?? '',
                        'shortName' => $shortName,
                        'playerID' => $user->platform_id ?? '',
                        'driverCategory' => $user->ratingClass($race->game),
                    ],
                ],
                'raceNumber' => is_numeric($user->car_number) ? (int) $user->car_number : '',
                'defaultGridPosition' => -1,
                'ballastKg' => 0,
                'forcedCarModel' => -1,
                'overrideDriverInfo' => 1,
            ];
        });

        $data = [
            'entries' => $entries,
            'configVersion' => 1,
            'forceEntryList' => 0,
        ];

        $filename = Str::slug($race->title).'-entry-list.json';

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ], JSON_PRETTY_PRINT);
    }

    public function bulkCreate()
    {
        return redirect()->route('admin.races.create', ['tab' => 'bulk']);
    }

    public function customCreate()
    {
        $tags = EventTag::orderBy('name')->get();
        // Standalone XCL events only ever use XCL's own servers — a league's dedicated
        // partner server (league_id pointing at that league) must stay visible only on
        // that league's own championship wizard, never leak into this general list.
        $servers = FtpServer::where('active', true)->where('league_id', League::system()->id)->orderBy('name')->get();
        $accTracks = array_keys(self::TRACK_IMAGE_MAP);

        $trackFilenames = array_values(self::TRACK_IMAGE_MAP);
        $trackMediaByName = Media::whereIn('original_name', $trackFilenames)->get()->keyBy('original_name');
        $trackPreviewUrls = collect(self::TRACK_IMAGE_MAP)
            ->map(fn ($fname) => $trackMediaByName->get($fname)?->url)
            ->all();

        return view('admin.races.custom-create', compact('tags', 'servers', 'accTracks', 'trackPreviewUrls'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'game' => 'required|in:acc,lmu,iracing,ac',
            'event_tag' => 'nullable|exists:event_tags,slug',
            'event_format_id' => 'nullable|exists:event_formats,id',
            'duration_key' => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'xcl_r_multiplier' => 'nullable|numeric|min:0.1|max:10',
            'practice_duration' => 'nullable|integer|min:1|max:1440',
            'qualifying_duration' => 'nullable|integer|min:1|max:1440',
            'race_duration' => 'nullable|integer|min:1|max:1440',
            'car_class' => 'nullable|string|max:50',
            'weather' => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness' => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level' => 'nullable|numeric|min:0|max:1',
            'time_of_day' => 'nullable|date_format:H:i',
            'ambient_temp' => 'nullable|integer|min:-30|max:50',
            'practice_time_multiplier' => 'nullable|integer|min:1|max:24',
            'qualifying_time_multiplier' => 'nullable|integer|min:1|max:24',
            'race_time_multiplier' => 'nullable|integer|min:1|max:24',
            'sr_requirement' => 'nullable|numeric|in:3,4,5,6,7,8,9',
            'min_rating' => 'nullable|string|in:all,rookie,bronze,silver,gold,platinum,alien',
            'max_rating' => 'nullable|string|in:all,rookie,bronze,silver,gold,platinum,alien',
            'max_drivers' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'has_practice_server' => 'nullable|boolean',
            'is_multiclass' => 'nullable|boolean',
            'classes_json' => 'nullable|string',
            'ftp_server_id' => 'nullable|exists:ftp_servers,id',
            'events' => 'required|array|min:1|max:200',
            'events.*.title' => 'required|string|max:255',
            'events.*.track' => 'required|string|max:255',
            'events.*.scheduled_at' => 'required|date',
            'events.*.event_tag' => 'nullable|exists:event_tags,slug',
            'events.*.event_format_id' => 'nullable|exists:event_formats,id',
            'events.*.ftp_server_id' => 'nullable|exists:ftp_servers,id',
            'events.*.weather' => 'nullable|in:dry,wet,mixed,random',
            'events.*.weather_randomness' => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'events.*.time_of_day' => 'nullable|date_format:H:i',
            'events.*.ambient_temp' => 'nullable|integer|min:-30|max:50',
            'events.*.practice_time_multiplier' => 'nullable|integer|min:1|max:24',
            'events.*.qualifying_time_multiplier' => 'nullable|integer|min:1|max:24',
            'events.*.race_time_multiplier' => 'nullable|integer|min:1|max:24',
            'events.*.max_drivers' => 'nullable|integer|min:1',
            'events.*.car_class' => 'nullable|string|max:50',
            'events.*.sr_requirement' => 'nullable|numeric|in:3,4,5,6,7,8,9',
            'events.*.min_rating' => 'nullable|string|in:all,rookie,bronze,silver,gold,platinum,alien',
            'events.*.max_rating' => 'nullable|string|in:all,rookie,bronze,silver,gold,platinum,alien',
            'events.*.description' => 'nullable|string',
            'events.*.has_practice_server' => 'nullable|boolean',
            'events.*.classes_json' => 'nullable|string',
        ]);

        $shared = [
            'game' => $request->game,
            'event_tag' => $request->event_tag ?: null,
            'event_format_id' => $request->event_format_id ?: null,
            'duration_key' => $request->duration_key ?: null,
            'practice_duration' => $request->practice_duration ?: null,
            'qualifying_duration' => $request->qualifying_duration ?: null,
            'race_duration' => $request->race_duration ?: null,
            'car_class' => $request->car_class ?: null,
            'weather' => $request->weather ?: null,
            'weather_randomness' => $request->weather_randomness ?: null,
            'rain_level' => $request->filled('rain_level') ? (float) $request->rain_level : null,
            'time_of_day' => $request->time_of_day ?: null,
            'ambient_temp' => $request->ambient_temp ?? null,
            'practice_time_multiplier' => $request->practice_time_multiplier ?: 1,
            'qualifying_time_multiplier' => $request->qualifying_time_multiplier ?: 1,
            'race_time_multiplier' => $request->race_time_multiplier ?: 1,
            'sr_requirement' => $request->sr_requirement ?: null,
            'min_rating' => $request->min_rating ?: null,
            'max_rating' => $request->max_rating ?: null,
            'max_drivers' => $request->max_drivers ?: null,
            'description' => $request->description ?: null,
            'has_practice_server' => $request->boolean('has_practice_server'),
            'status' => 'open',
        ];

        // Per row, event_tag / event_format_id / ftp_server_id fall back to the shared
        // selection only when the row itself doesn't specify one — a whole week can mix
        // formats/tags/servers (e.g. imported from a CSV), or share one via the shared
        // fields (the day/week generator, which has no per-row concept of these).
        $eventData = [];
        $rowServerIds = [];
        $rowClasses = [];
        foreach ($request->events as $i => $event) {
            // No longer required up front — deriveFormatFields() below fills it in from
            // the row's own resolved format once that's known, or it stays null for a
            // formatless row (a CSV row's own value, if it somehow still has one, is only
            // a last-resort fallback).
            $eventTag = ($event['event_tag'] ?? null) ?: $shared['event_tag'];

            $rowServerIds[$i] = ($event['ftp_server_id'] ?? null) ?: $request->ftp_server_id;

            // A row's own classes_json (2+ car classes → multiclass) always wins over the
            // shared/page-level one below — a CSV import can mix multiclass and single-class
            // rows in one file, unlike the day/week generator, which only ever has one
            // shared multiclass setup for the whole batch.
            $classes = json_decode($event['classes_json'] ?? '', true);
            $rowClasses[$i] = is_array($classes) ? $classes : [];

            $eventData[] = $this->deriveFormatFields($this->normalizeRainLevel(array_merge($shared, [
                'title' => $event['title'],
                'track' => $event['track'],
                'scheduled_at' => Carbon::createFromFormat('Y-m-d\TH:i', $event['scheduled_at'], 'Europe/London')->utc(),
                'event_tag' => $eventTag,
                'event_format_id' => ($event['event_format_id'] ?? null) ?: $shared['event_format_id'],
                'weather' => $event['weather'] ?: $shared['weather'],
                'weather_randomness' => ($event['weather_randomness'] ?? null) ?: $shared['weather_randomness'],
                'time_of_day' => $event['time_of_day'] ?: $shared['time_of_day'],
                'ambient_temp' => $event['ambient_temp'] ?? $shared['ambient_temp'],
                'practice_time_multiplier' => $event['practice_time_multiplier'] ?? $shared['practice_time_multiplier'],
                'qualifying_time_multiplier' => $event['qualifying_time_multiplier'] ?? $shared['qualifying_time_multiplier'],
                'race_time_multiplier' => $event['race_time_multiplier'] ?? $shared['race_time_multiplier'],
                'max_drivers' => $event['max_drivers'] ?: $shared['max_drivers'],
                'car_class' => ($event['car_class'] ?? null) ?: $shared['car_class'],
                'sr_requirement' => ($event['sr_requirement'] ?? null) ?: $shared['sr_requirement'],
                'min_rating' => ($event['min_rating'] ?? null) ?: $shared['min_rating'],
                'max_rating' => ($event['max_rating'] ?? null) ?: $shared['max_rating'],
                'description' => ($event['description'] ?? null) ?: $shared['description'],
                'is_multiclass' => count($rowClasses[$i]) > 1 || $request->boolean('is_multiclass'),
                // '0' is a meaningful explicit "off" here, not "unset" — unlike the
                // ?: fallback used above, so it isn't silently swallowed back to the
                // shared checkbox the way a falsy string would be everywhere else.
                'has_practice_server' => isset($event['has_practice_server']) && $event['has_practice_server'] !== ''
                    ? (bool) $event['has_practice_server']
                    : $shared['has_practice_server'],
            ])));
        }

        // Validate every event's slot up front (against the DB and against each other,
        // per its own resolved server) before creating anything — same rule as a single
        // race: the event's own time is its slot on that server.
        $serversById = FtpServer::whereIn('id', array_filter(array_unique($rowServerIds)))->get()->keyBy('id');
        $seenSlots = [];
        foreach ($eventData as $i => $data) {
            $server = $serversById->get($rowServerIds[$i]);
            if (! $server) {
                continue;
            }
            if (! $server->isValidSlot($data['scheduled_at'])) {
                return back()->withInput()->withErrors(['events.'.$i.'.scheduled_at' => 'Row '.($i + 1).': '.self::ERR_SLOT_WRONG_SERVER]);
            }
            $slotKey = $server->id.'|'.$data['scheduled_at']->format('Y-m-d H:i');
            if (in_array($slotKey, $seenSlots, true) || in_array($data['scheduled_at']->format('Y-m-d H:i'), $server->takenSlots(), true)) {
                return back()->withInput()->withErrors(['events.'.$i.'.scheduled_at' => 'Row '.($i + 1).': '.self::ERR_SLOT_TAKEN]);
            }
            $seenSlots[] = $slotKey;
        }

        $races = [];
        foreach ($eventData as $i => $data) {
            $server = $serversById->get($rowServerIds[$i]);
            $data['ftp_server_id'] = $server?->id;
            if ($server) {
                $data['slot_time'] = $data['scheduled_at']->copy();
                $data['config_push_status'] = 'pending';
            }
            $races[] = Race::create($data);
        }

        // Each row's own classes (e.g. from car_class_2/car_class_3 in a CSV) win over the
        // shared/page-level multiclass setup for that race; a row with none falls back to
        // the shared one when the page-level multiclass toggle is on.
        $sharedClasses = $request->boolean('is_multiclass')
            ? (json_decode($request->input('classes_json', '[]'), true) ?: [])
            : [];
        foreach ($races as $i => $race) {
            $classes = $rowClasses[$i] ?: $sharedClasses;
            if ($classes) {
                $this->syncRaceClasses($classes, $race);
            }
        }

        $practiceWarnings = [];
        $sessionManager = new PracticeServerSessionManager;
        foreach ($races as $race) {
            if ($race->has_practice_server) {
                $warning = $sessionManager->sync($race, true);
                if ($warning) {
                    $practiceWarnings[] = $race->title.': '.$warning;
                }
            }
        }

        $count = count($request->events);
        $redirect = redirect()->route('admin.races.index')
            ->with('success', $count.' '.($count === 1 ? 'race' : 'races').' created successfully!');

        return $practiceWarnings ? $redirect->with('practice_warning', implode(' ', $practiceWarnings)) : $redirect;
    }

    public function importExport()
    {
        $tags = EventTag::orderBy('name')->get();
        // Standalone XCL events only ever use XCL's own servers — a league's dedicated
        // partner server (league_id pointing at that league) must stay visible only on
        // that league's own championship wizard, never leak into this general list.
        $servers = FtpServer::where('active', true)->where('league_id', League::system()->id)->orderBy('name')->get();
        $formats = EventFormat::orderBy('game')->orderBy('sort_order')->get();

        return view('admin.races.import-export', compact('tags', 'servers', 'formats'));
    }

    // CSV columns (header row, case-insensitive, any order, see CSV_COLUMNS/the CSV
    // Format card): track, date, time — required. Everything else — including
    // event_tag/format/server — is optional and, when given, overrides the page's
    // shared defaults for that one row, so a single import can mix formats/tags/
    // servers/requirements across a whole week. Parses to the same row shape the
    // Bulk Schedule table already uses, so the page's JS can render an editable
    // preview and submit it straight to bulkStore() — no new creation path.
    public function bulkImportCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'game' => 'nullable|in:acc,lmu,iracing,ac',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle, null, ',', '"', '\\');
        if (! $header) {
            fclose($handle);

            return response()->json(['errors' => ['The file is empty.']], 422);
        }
        // Excel-exported CSVs commonly start with a UTF-8 BOM, which would otherwise
        // corrupt the first header cell (e.g. "track" becomes "\xEF\xBB\xBFtrack") and
        // make the very first column silently fail to match.
        $header = array_map(fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', $h ?? ''))), $header);
        $colIndex = array_flip($header);

        foreach (['track', 'date', 'time'] as $col) {
            if (! isset($colIndex[$col])) {
                fclose($handle);

                return response()->json(['errors' => ["Missing required column \"{$col}\". See the CSV Format card for every expected column."]], 422);
            }
        }

        // Lookup maps for the optional per-row overrides, keyed lowercase for case-insensitive matching.
        $formatsByKey = EventFormat::when($request->filled('game'), fn ($q) => $q->where('game', $request->game))
            ->get()->keyBy(fn ($f) => strtolower($f->name))->map->id->all();
        $serversByKey = [];
        foreach (FtpServer::where('active', true)->where('league_id', League::system()->id)->get() as $s) {
            $serversByKey[strtolower($s->name)] = $s->id;
            if ($s->server_number) {
                $serversByKey[(string) $s->server_number] = $s->id;
            }
        }

        $rows = [];
        $errors = [];
        $lineNum = 1;

        while (($line = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
            $lineNum++;
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $track = trim($line[$colIndex['track']] ?? '');
            $date = trim($line[$colIndex['date']] ?? '');
            $time = trim($line[$colIndex['time']] ?? '');

            if ($track === '' || $date === '' || $time === '') {
                $errors[] = "Row {$lineNum}: missing track/date/time — skipped.";

                continue;
            }

            try {
                // Accepts "2026-09-21" (the app's own export/template format) and
                // "2026/09/21" (what a spreadsheet's date column commonly renders as)
                // interchangeably -- the year always comes first in both, so swapping
                // '/' for '-' can't silently reinterpret a day-first/month-first date,
                // it just fails the same as before if the value isn't one of these two.
                $dt = Carbon::createFromFormat('Y-m-d H:i', str_replace('/', '-', $date).' '.substr($time, 0, 5));
                if (! $dt) {
                    throw new \Exception;
                }
            } catch (\Throwable $e) {
                $errors[] = "Row {$lineNum}: invalid date/time \"{$date} {$time}\" (expected YYYY-MM-DD and HH:MM) — skipped.";

                continue;
            }

            $weather = isset($colIndex['weather']) ? strtolower(trim($line[$colIndex['weather']] ?? '')) : '';
            if ($weather !== '' && ! in_array($weather, ['dry', 'wet', 'mixed', 'random'], true)) {
                $errors[] = "Row {$lineNum}: unknown weather \"{$weather}\" — ignored.";
                $weather = '';
            }

            $timeOfDay = isset($colIndex['time_of_day']) ? trim($line[$colIndex['time_of_day']] ?? '') : '';
            if ($timeOfDay !== '' && ! preg_match('/^\d{1,2}:\d{2}$/', $timeOfDay)) {
                $errors[] = "Row {$lineNum}: invalid time_of_day \"{$timeOfDay}\" (expected HH:MM) — ignored.";
                $timeOfDay = '';
            }

            $ambientTemp = isset($colIndex['ambient_temp']) ? trim($line[$colIndex['ambient_temp']] ?? '') : '';
            if ($ambientTemp !== '' && ! is_numeric($ambientTemp)) {
                $errors[] = "Row {$lineNum}: invalid ambient_temp \"{$ambientTemp}\" — ignored.";
                $ambientTemp = '';
            }

            $practiceTimeMultiplier = $this->parseTimeMultiplierColumn($line, $colIndex, 'practice_time_multiplier', $lineNum, $errors);
            $qualifyingTimeMultiplier = $this->parseTimeMultiplierColumn($line, $colIndex, 'qualifying_time_multiplier', $lineNum, $errors);
            $raceTimeMultiplier = $this->parseTimeMultiplierColumn($line, $colIndex, 'race_time_multiplier', $lineNum, $errors);

            $formatId = '';
            $rawFormat = isset($colIndex['format']) ? trim($line[$colIndex['format']] ?? '') : '';
            if ($rawFormat !== '') {
                $formatId = $formatsByKey[strtolower($rawFormat)] ?? '';
                // "Multiclass Race" for the format named just "Multiclass", "Endurance Race"
                // for "Endurance", etc. — a trailing " race" is dropped and retried once
                // before giving up, rather than requiring the exact name only.
                if ($formatId === '') {
                    $formatId = $formatsByKey[preg_replace('/\s+race$/i', '', strtolower($rawFormat))] ?? '';
                }
                if ($formatId === '') {
                    $errors[] = "Row {$lineNum}: unknown format \"{$rawFormat}\" — using the shared default.";
                }
            }

            $serverId = '';
            $rawServer = isset($colIndex['server']) ? trim($line[$colIndex['server']] ?? '') : '';
            if ($rawServer !== '') {
                $serverId = $serversByKey[strtolower($rawServer)] ?? '';
                // A server's full display name ("XCL SERVER 1 - Playstation 5 & Xbox Series
                // S/X") is easy to mistype/abbreviate — fall back to just the leading server
                // number ("XCL SERVER 1", "Server 1", "1") before giving up on it entirely.
                if ($serverId === '' && preg_match('/(\d+)/', $rawServer, $m)) {
                    $serverId = $serversByKey[$m[1]] ?? '';
                }
                if ($serverId === '') {
                    $errors[] = "Row {$lineNum}: unknown server \"{$rawServer}\" — using the shared default.";
                }
            }

            $weatherRandomness = isset($colIndex['weather_randomness']) ? strtolower(trim($line[$colIndex['weather_randomness']] ?? '')) : '';
            if ($weatherRandomness !== '' && ! in_array($weatherRandomness, ['0', '1', '2', '3', '4', '5', '6', '7', 'random'], true)) {
                $errors[] = "Row {$lineNum}: invalid weather_randomness \"{$weatherRandomness}\" (expected 0-7 or \"random\") — ignored.";
                $weatherRandomness = '';
            }

            $hasPracticeServer = '';
            if (isset($colIndex['has_practice_server'])) {
                $raw = strtolower(trim($line[$colIndex['has_practice_server']] ?? ''));
                if (in_array($raw, ['on', 'yes', 'true', '1'], true)) {
                    $hasPracticeServer = '1';
                } elseif (in_array($raw, ['off', 'no', 'false', '0', ''], true)) {
                    $hasPracticeServer = '0';
                } else {
                    $errors[] = "Row {$lineNum}: unknown has_practice_server \"{$raw}\" (expected on/off) — ignored.";
                }
            }

            // Accepts "5", "5.00" or the European "5,00" alike, and 0 (or blank) means "no
            // requirement" rather than an error — matching how the field reads elsewhere
            // ("Standard is just open"). Anything else numeric is rounded to the nearest
            // whole tier and clamped into 3-9, rather than rejected outright.
            $srRequirement = isset($colIndex['sr_requirement']) ? trim($line[$colIndex['sr_requirement']] ?? '') : '';
            if ($srRequirement !== '') {
                $normalized = str_replace(',', '.', $srRequirement);
                if (! is_numeric($normalized)) {
                    $errors[] = "Row {$lineNum}: invalid sr_requirement \"{$srRequirement}\" — ignored.";
                    $srRequirement = '';
                } else {
                    $num = (float) $normalized;
                    $srRequirement = $num <= 0 ? '' : (string) max(3, min(9, (int) round($num)));
                }
            }

            $ratingValues = ['all', 'rookie', 'bronze', 'silver', 'gold', 'platinum', 'alien'];
            $minRating = isset($colIndex['min_rating']) ? $this->normalizeRatingTier($line[$colIndex['min_rating']] ?? '') : '';
            if ($minRating !== '' && ! in_array($minRating, $ratingValues, true)) {
                $errors[] = "Row {$lineNum}: unknown min_rating \"{$minRating}\" — ignored.";
                $minRating = '';
            }
            $maxRating = isset($colIndex['max_rating']) ? $this->normalizeRatingTier($line[$colIndex['max_rating']] ?? '') : '';
            if ($maxRating !== '' && ! in_array($maxRating, $ratingValues, true)) {
                $errors[] = "Row {$lineNum}: unknown max_rating \"{$maxRating}\" — ignored.";
                $maxRating = '';
            }

            $carClass = isset($colIndex['car_class']) ? trim($line[$colIndex['car_class']] ?? '') : '';
            $description = isset($colIndex['description']) ? trim($line[$colIndex['description']] ?? '') : '';

            // Multiclass: car_class_2 (and optionally car_class_3) turns this row into a
            // multiclass race with one RaceClass per column. Restrictions aren't asked for
            // per class in the CSV yet — Class 1 defaults to a Bronze+ minimum rating, every
            // other class is left fully open, and an event manager can tighten either by
            // hand afterwards from the race's own edit page.
            $carClass2 = isset($colIndex['car_class_2']) ? trim($line[$colIndex['car_class_2']] ?? '') : '';
            $carClass3 = isset($colIndex['car_class_3']) ? trim($line[$colIndex['car_class_3']] ?? '') : '';
            $classes = [];
            if ($carClass !== '' && $carClass2 !== '') {
                $classColors = ['GT3' => '#7c3aed', 'GT4' => '#2563eb', 'GT2' => '#db2777', 'TCX' => '#16a34a', 'GTC' => '#ea580c'];
                foreach (array_values(array_filter([$carClass, $carClass2, $carClass3], fn ($c) => $c !== '')) as $idx => $cls) {
                    $classes[] = [
                        'name' => $cls,
                        'car_class' => $cls,
                        'color' => $classColors[strtoupper($cls)] ?? '#6b7280',
                        'min_rating' => $idx === 0 ? 'bronze' : null,
                        'sr_requirement' => null,
                        'max_drivers' => null,
                    ];
                }
            }

            $rows[] = [
                'title' => $track,
                'track' => $track,
                'scheduled_at' => $dt->format('Y-m-d\TH:i'),
                'event_format_id' => $formatId,
                'ftp_server_id' => $serverId,
                'weather' => $weather,
                'time_of_day' => $timeOfDay,
                'ambient_temp' => $ambientTemp,
                'practice_time_multiplier' => $practiceTimeMultiplier,
                'qualifying_time_multiplier' => $qualifyingTimeMultiplier,
                'race_time_multiplier' => $raceTimeMultiplier,
                'weather_randomness' => $weatherRandomness,
                'has_practice_server' => $hasPracticeServer,
                'sr_requirement' => $srRequirement,
                'min_rating' => $minRating,
                'max_rating' => $maxRating,
                'car_class' => $carClass,
                'description' => $description,
                'classes' => $classes,
            ];
        }
        fclose($handle);

        if (empty($rows)) {
            return response()->json(['errors' => array_merge(['No valid rows found in the file.'], $errors)], 422);
        }

        return response()->json(['rows' => $rows, 'errors' => $errors]);
    }

    // A min_rating/max_rating CSV cell often carries a human qualifier the column
    // header already implies -- "bronze+" in a min_rating column, "rookie max" in a
    // max_rating one -- rather than the bare tier name the app stores. Strips that
    // qualifier so both read the same as plain "bronze"/"rookie" would.
    private function normalizeRatingTier(string $value): string
    {
        $value = strtolower(trim($value));

        return trim(preg_replace('/\s*(\+|min(imum)?|max(imum)?|only|and up|or (higher|above|lower|below))\s*$/', '', $value));
    }

    // Parses one of the three optional time-multiplier CSV columns (1-24) for a single row,
    // appending a warning and falling back to blank (→ shared default) on an invalid value.
    private function parseTimeMultiplierColumn(array $line, array $colIndex, string $column, int $lineNum, array &$errors): string
    {
        $value = isset($colIndex[$column]) ? trim($line[$colIndex[$column]] ?? '') : '';
        // "1x" / "2×" (how the multiplier reads everywhere else on the site, e.g. the
        // Create Race form's own "×" dropdown) is just as valid as the bare number.
        $stripped = preg_replace('/\s*[x×]\s*$/iu', '', $value);
        if ($value !== '' && (! ctype_digit($stripped) || (int) $stripped < 1 || (int) $stripped > 24)) {
            $errors[] = "Row {$lineNum}: invalid {$column} \"{$value}\" — ignored.";

            return '';
        }

        return $stripped;
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
            ->with(['eventFormat', 'ftpServer', 'raceClasses'])
            ->orderBy('scheduled_at')
            ->get();

        $filename = 'xcl-races-upcoming-'.$request->game.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($races) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_COLUMNS, ',', '"', '\\');
            foreach ($races as $race) {
                fputcsv($out, $this->raceToCsvRow($race), ',', '"', '\\');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // A blank copy of the same CSV shape exportCsv()/bulkImportCsv() use — headers only,
    // ready to fill in (e.g. a 4-week schedule) rather than requiring an existing race to
    // export from first.
    public function downloadTemplate()
    {
        $filename = 'xcl-races-template.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_COLUMNS, ',', '"', '\\');
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // Maps a Race to one CSV row in CSV_COLUMNS order — shared by exportCsv() so a
    // re-exported week round-trips through bulkImportCsv() without losing any of the
    // fields the template documents.
    private function raceToCsvRow(Race $race): array
    {
        $local = $race->scheduledAtUk();
        $format = $race->eventFormat;
        $server = $race->ftpServer;
        // Multiclass races carry their classes on race_classes, in grid order, instead of
        // the single car_class column — export those back out as car_class/_2/_3 so the
        // row round-trips through bulkImportCsv() as the same multiclass event.
        $classCars = $race->is_multiclass ? $race->raceClasses->pluck('car_class')->values() : collect();

        return [
            'format' => $format?->name,
            'track' => $race->track,
            'weather' => $race->weather,
            'date' => $local->format('Y-m-d'),
            'time' => $local->format('H:i'),
            'time_of_day' => $race->time_of_day,
            'ambient_temp' => $race->ambient_temp,
            'practice_time_multiplier' => $race->practice_time_multiplier,
            'qualifying_time_multiplier' => $race->qualifying_time_multiplier,
            'race_time_multiplier' => $race->race_time_multiplier,
            'weather_randomness' => $race->weather_randomness,
            'has_practice_server' => $race->has_practice_server ? 'on' : 'off',
            'server' => $server?->name,
            'sr_requirement' => $race->sr_requirement,
            'min_rating' => $race->min_rating,
            'max_rating' => $race->max_rating,
            'car_class' => $classCars->get(0) ?? $race->car_class,
            'car_class_2' => $classCars->get(1),
            'car_class_3' => $classCars->get(2),
            'description' => $race->description,
        ];
    }

    public function create(Request $request)
    {
        $prefillDate = $request->date('date')?->format('Y-m-d\TH:i');
        $tags = EventTag::orderBy('name')->get();
        $race = null;

        // Standalone XCL events only ever use XCL's own servers — a league's dedicated
        // partner server (league_id pointing at that league) must stay visible only on
        // that league's own championship wizard, never leak into this general list.
        $servers = FtpServer::where('active', true)->where('league_id', League::system()->id)->orderBy('name')->get();

        return view('admin.races.form', array_merge(
            compact('race', 'prefillDate', 'tags', 'servers'),
            $this->formatBuilderData()
        ));
    }

    // Shared preview/media data for the create+edit form (also used by edit())
    private function formatBuilderData(): array
    {
        $formats = EventFormat::orderBy('game')->orderBy('sort_order')->get();

        $trackFilenames = array_values(self::TRACK_IMAGE_MAP);
        $trackMediaByName = Media::whereIn('original_name', $trackFilenames)->get()->keyBy('original_name');
        $trackPreviewUrls = collect(self::TRACK_IMAGE_MAP)
            ->map(fn ($fname) => $trackMediaByName->get($fname)?->url)
            ->all();

        $formatPreviewUrls = [];
        foreach ($formats as $fmt) {
            $slug = Str::slug($fmt->name, '_');
            $key = self::FORMAT_IMAGE_OVERRIDES[$slug] ?? $slug;
            $formatPreviewUrls[$fmt->id] = Media::where('title', $key)
                ->orWhere('original_name', 'like', $key.'%')
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
        if (! in_array($data['weather'] ?? null, ['wet', 'mixed'], true)) {
            $data['rain_level'] = null;
        }

        return $data;
    }

    private function deriveFormatFields(array $data): array
    {
        if (! empty($data['event_format_id'])) {
            // Endurance/driver-swap is Custom Race only — a format-based race never sets it.
            $data['is_endurance'] = false;
            $data['driver_stint_time_mins'] = null;
            $data['max_total_driving_time_mins'] = null;
            $data['mandatory_driver_swap'] = false;

            $fmt = EventFormat::find($data['event_format_id']);
            if ($fmt) {
                $data['title'] = $fmt->name;
                $data['duration_key'] = null;
                $data['practice_duration'] = $fmt->practice_mins ?: null;
                $data['qualifying_duration'] = $fmt->quali_mins ?: null;
                $data['race_duration'] = $fmt->race1_mins ?: null;
                // event_tag is never picked by hand any more — it always follows the
                // chosen format 1:1 (matching the Events page filter list exactly), so
                // this is the single source of truth for it, overriding anything a form
                // field or CSV column might still have carried in.
                if ($fmt->default_event_tag) {
                    $data['event_tag'] = $fmt->default_event_tag;
                }

                $formatSlug = Str::slug($fmt->name, '_');
                $formatImageKey = self::FORMAT_IMAGE_OVERRIDES[$formatSlug] ?? $formatSlug;

                $data['icon'] = Media::where('title', $formatImageKey)
                    ->orWhere('original_name', 'like', $formatImageKey.'%')
                    ->value('path');
            }

            $trackFilename = self::TRACK_IMAGE_MAP[$data['track']] ?? null;
            $data['image'] = $trackFilename
                ? Media::where('original_name', $trackFilename)->value('path')
                : null;
        }

        // A formatless (Custom) row, or a format with no default_event_tag of its own,
        // leaves event_tag unresolved. Race::create() would otherwise insert that as an
        // explicit NULL and trip the column's NOT NULL constraint — unset it instead so
        // the insert omits the column and the DB's own 'daily' default applies, same as
        // a single Custom Event created via store().
        if (empty($data['event_tag'])) {
            unset($data['event_tag']);
        }

        return $data;
    }

    // Track name → background image filename in media library
    public const TRACK_IMAGE_MAP = [
        'Barcelona' => 'Barcelona.png',
        'Brands Hatch' => 'Brands.png',
        'COTA' => 'COTA.png',
        'Donington' => 'Donington.png',
        'Hungaroring' => 'Hungaroring.png',
        'Imola' => 'Imola.png',
        'Indianapolis' => 'Indy.png',
        'Kyalami' => 'Kyalami.png',
        'Laguna Seca' => 'Laguna Seca.png',
        'Misano' => 'Misano.png',
        'Monza' => 'Monza.png',
        'Mount Panorama' => 'Bathurst.png',
        'Nürburgring' => 'Nurburgring.png',
        'Nordschleife' => 'Nords.png',
        'Oulton Park' => 'Oulton.png',
        'Paul Ricard' => 'Paul Ricard.png',
        'Red Bull Ring' => 'RBR.png',
        'Silverstone' => 'Silverstone.png',
        'Snetterton' => 'Snetterton.png',
        'Spa' => 'Spa.png',
        'Suzuka' => 'Suzuka.png',
        'Valencia' => 'Valencia.png',
        'Watkins Glen' => 'Watkins.png',
        'Zandvoort' => 'Zandvoort.png',
        'Zolder' => 'Zolder.png',
    ];

    // Format slug override map (for slugs that differ from Str::slug(name))
    private const FORMAT_IMAGE_OVERRIDES = [
        'multiclass' => 'multiclass_race',
    ];

    public function store(Request $request)
    {
        $data = $request->validate([
            'game' => 'required|in:acc,lmu,iracing,ac',
            'track' => 'required|string|max:255',
            'scheduled_at' => array_filter([
                'required', 'date',
                $request->boolean('has_practice_server') ? new PracticeWindowNotOverlapping : null,
            ]),
            // Auto-derived from the format's own default_event_tag in deriveFormatFields()
            // below — never picked by hand. Stays nullable for a Custom Event, which has
            // no format to derive one from.
            'event_tag' => 'nullable|exists:event_tags,slug',
            'event_format_id' => 'nullable|exists:event_formats,id',
            'title' => 'required_without:event_format_id|string|max:255',
            'duration_key' => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'xcl_r_multiplier' => 'nullable|numeric|min:0.1|max:10',
            'practice_duration' => 'nullable|integer|min:1|max:1440',
            'qualifying_duration' => 'nullable|integer|min:1|max:1440',
            'race_duration' => 'required_without:event_format_id|integer|min:1|max:1440',
            'car_class' => 'nullable|string|max:50',
            'sr_requirement' => 'nullable|in:3,4,5,6,7,8,9',
            'min_rating' => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'max_rating' => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'weather' => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness' => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level' => 'nullable|numeric|min:0|max:1',
            'time_of_day' => 'nullable|date_format:H:i',
            'ambient_temp' => 'nullable|integer|min:-30|max:50',
            'practice_time_multiplier' => 'nullable|integer|min:1|max:24',
            'qualifying_time_multiplier' => 'nullable|integer|min:1|max:24',
            'race_time_multiplier' => 'nullable|integer|min:1|max:24',
            'max_drivers' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_multiclass' => 'nullable|boolean',
            'is_endurance' => 'nullable|boolean',
            'driver_stint_time_mins' => 'nullable|integer|min:1|max:1440',
            'max_total_driving_time_mins' => 'nullable|integer|min:1|max:1440',
            'mandatory_driver_swap' => 'nullable|boolean',
            'ftp_server_id' => empty($request->event_format_id) ? 'required|exists:ftp_servers,id' : 'nullable|exists:ftp_servers,id',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg,mov|max:204800',
            'image_path' => 'nullable|string|max:500',
            'icon' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg|max:4096',
            'icon_path' => 'nullable|string|max:500',
            'pitstop_count' => 'nullable|integer|min:0|max:9',
            'min_stop_secs' => 'nullable|integer|min:1|max:3600',
            'has_practice_server' => 'nullable|boolean',
            'practice_notes' => 'nullable|string|max:2000',
        ]);

        $data['scheduled_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Europe/London')->utc();
        $data['is_multiclass'] = $request->boolean('is_multiclass');
        $data['is_endurance'] = $request->boolean('is_endurance');
        $data['has_practice_server'] = $request->boolean('has_practice_server');
        $data['mandatory_driver_swap'] = $request->boolean('mandatory_driver_swap');
        $data['practice_time_multiplier'] = $data['practice_time_multiplier'] ?? 1;
        $data['qualifying_time_multiplier'] = $data['qualifying_time_multiplier'] ?? 1;
        $data['race_time_multiplier'] = $data['race_time_multiplier'] ?? 1;
        $data = $this->normalizeRainLevel($data);

        $data = $this->deriveFormatFields($data);

        if (empty($data['event_format_id'])) {
            // Custom race — use uploaded/selected image, or fall back to track's stock image
            $data['image'] = $this->resolveMedia($request);
            if (! $data['image'] && isset($data['track'])) {
                $trackFilename = self::TRACK_IMAGE_MAP[$data['track']] ?? null;
                $data['image'] = $trackFilename
                    ? Media::where('original_name', $trackFilename)->value('path')
                    : null;
            }
            $data['icon'] = $this->resolveIcon($request);
        }
        unset($data['image_path'], $data['icon_path']);

        if (! empty($data['ftp_server_id'])) {
            $server = FtpServer::find($data['ftp_server_id']);

            // The race's own date & time (set above) is always the server slot now —
            // no separate slot grid. Reject it if that time isn't actually free on
            // this server.
            if ($server && ! $server->isValidSlot($data['scheduled_at'])) {
                return back()->withInput()->withErrors(['scheduled_at' => self::ERR_SLOT_WRONG_SERVER]);
            }
            if ($server && in_array($data['scheduled_at']->format('Y-m-d H:i'), $server->takenSlots(), true)) {
                return back()->withInput()->withErrors(['scheduled_at' => self::ERR_SLOT_TAKEN]);
            }

            $data['slot_time'] = $data['scheduled_at']->copy();
            $data['config_push_status'] = 'pending';
        } else {
            $data['slot_time'] = null;
        }

        $race = Race::create($data);

        $this->syncRaceClasses(json_decode($request->input('classes_json') ?: '[]', true) ?: [], $race);

        $practiceWarning = (new PracticeServerSessionManager)
            ->sync($race, $data['has_practice_server']);

        $redirect = redirect()->route('admin.races.index')->with('success', 'Race created successfully!');

        return $practiceWarning ? $redirect->with('practice_warning', $practiceWarning) : $redirect;
    }

    public function edit(Race $race)
    {
        if ($race->isPast()) {
            return redirect()->route('admin.races.index')
                ->with('error', 'Past races cannot be edited. You can still manage results.');
        }

        $race->load(['raceClasses', 'practiceServerSession']);
        $tags = EventTag::orderBy('name')->get();
        $prefillDate = null;

        // Standalone XCL events only ever use XCL's own servers — a league's dedicated
        // partner server (league_id pointing at that league) must stay visible only on
        // that league's own championship wizard, never leak into this general list.
        $servers = FtpServer::where('active', true)->where('league_id', League::system()->id)->orderBy('name')->get();

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
            'game' => 'required|in:acc,lmu,iracing,ac',
            'track' => 'required|string|max:255',
            'scheduled_at' => array_filter([
                'required', 'date',
                $request->boolean('has_practice_server') ? new PracticeWindowNotOverlapping($race->id) : null,
            ]),
            'status' => 'required|in:open,closed,finished',
            // Auto-derived from the format's own default_event_tag in deriveFormatFields()
            // below — never picked by hand. Stays nullable for a Custom Event, which has
            // no format to derive one from.
            'event_tag' => 'nullable|exists:event_tags,slug',
            'event_format_id' => 'nullable|exists:event_formats,id',
            'title' => 'required_without:event_format_id|string|max:255',
            'duration_key' => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'xcl_r_multiplier' => 'nullable|numeric|min:0.1|max:10',
            'practice_duration' => 'nullable|integer|min:1|max:1440',
            'qualifying_duration' => 'nullable|integer|min:1|max:1440',
            'race_duration' => 'required_without:event_format_id|integer|min:1|max:1440',
            'car_class' => 'nullable|string|max:50',
            'sr_requirement' => 'nullable|in:3,4,5,6,7,8,9',
            'min_rating' => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'max_rating' => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'weather' => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness' => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level' => 'nullable|numeric|min:0|max:1',
            'time_of_day' => 'nullable|date_format:H:i',
            'ambient_temp' => 'nullable|integer|min:-30|max:50',
            'practice_time_multiplier' => 'nullable|integer|min:1|max:24',
            'qualifying_time_multiplier' => 'nullable|integer|min:1|max:24',
            'race_time_multiplier' => 'nullable|integer|min:1|max:24',
            'max_drivers' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg,mov|max:204800',
            'image_path' => 'nullable|string|max:500',
            'image_keep' => 'nullable|in:0,1',
            'icon' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg|max:4096',
            'icon_path' => 'nullable|string|max:500',
            'icon_keep' => 'nullable|in:0,1',
            'is_multiclass' => 'nullable|boolean',
            'is_endurance' => 'nullable|boolean',
            'driver_stint_time_mins' => 'nullable|integer|min:1|max:1440',
            'max_total_driving_time_mins' => 'nullable|integer|min:1|max:1440',
            'mandatory_driver_swap' => 'nullable|boolean',
            'ftp_server_id' => 'nullable|exists:ftp_servers,id',
            'pitstop_count' => 'nullable|integer|min:0|max:9',
            'min_stop_secs' => 'nullable|integer|min:1|max:3600',
            'has_practice_server' => 'nullable|boolean',
            'practice_notes' => 'nullable|string|max:2000',
        ]);

        $data['scheduled_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Europe/London')->utc();
        $data['is_multiclass'] = $request->boolean('is_multiclass');
        $data['is_endurance'] = $request->boolean('is_endurance');
        $data['has_practice_server'] = $request->boolean('has_practice_server');
        $data['mandatory_driver_swap'] = $request->boolean('mandatory_driver_swap');
        $data['practice_time_multiplier'] = $data['practice_time_multiplier'] ?? 1;
        $data['qualifying_time_multiplier'] = $data['qualifying_time_multiplier'] ?? 1;
        $data['race_time_multiplier'] = $data['race_time_multiplier'] ?? 1;
        $data = $this->normalizeRainLevel($data);

        // Preserve driver swap fields before deriveFormatFields() resets them for format-based races
        $driverSwap = [
            'is_endurance' => $data['is_endurance'],
            'mandatory_driver_swap' => $data['mandatory_driver_swap'],
            'driver_stint_time_mins' => $data['driver_stint_time_mins'] ?? null,
            'max_total_driving_time_mins' => $data['max_total_driving_time_mins'] ?? null,
        ];

        $data = $this->deriveFormatFields($data);
        $data = array_merge($data, $driverSwap);

        if (! empty($data['ftp_server_id'])) {
            $server = FtpServer::find($data['ftp_server_id']);

            if ($server && ! $server->isValidSlot($data['scheduled_at'])) {
                return back()->withInput()->withErrors(['scheduled_at' => self::ERR_SLOT_WRONG_SERVER]);
            }
            if ($server && in_array($data['scheduled_at']->format('Y-m-d H:i'), $server->takenSlots($race->id), true)) {
                return back()->withInput()->withErrors(['scheduled_at' => self::ERR_SLOT_TAKEN]);
            }

            $data['slot_time'] = $data['scheduled_at']->copy();
            $data['config_push_status'] = 'pending';
        } else {
            $data['slot_time'] = null;
        }

        if (empty($data['event_format_id'])) {
            // Custom race — respect manual media upload/keep controls
            $resolvedImage = $this->resolveMedia($request);
            $data['image'] = $resolvedImage ?? ($request->input('image_keep') === '0' ? null : $race->image);

            $resolvedIcon = $this->resolveIcon($request);
            $data['icon'] = $resolvedIcon ?? ($request->input('icon_keep') === '0' ? null : $race->icon);
        }
        // else: image/icon already derived from track/format inside deriveFormatFields()

        unset($data['image_path'], $data['image_keep'], $data['icon_path'], $data['icon_keep']);

        $race->update($data);

        $this->syncRaceClasses(json_decode($request->input('classes_json') ?: '[]', true) ?: [], $race);

        $practiceWarning = (new PracticeServerSessionManager)
            ->sync($race, $data['has_practice_server']);

        $redirect = redirect()->route('admin.races.index')->with('success', 'Race updated successfully!');

        return $practiceWarning ? $redirect->with('practice_warning', $practiceWarning) : $redirect;
    }

    // Live preview of the computed practice window for the create/edit form — recomputes
    // via the same PracticeWindowCalculator used on save, so there's a single source of
    // truth for the math shown to admins.
    public function practiceWindowPreview(Request $request)
    {
        $request->validate(['starts_at' => 'required|date_format:Y-m-d\TH:i']);

        $server = PracticeServer::where('is_active', true)->first();

        if (! $server) {
            return response()->json(['error' => 'No active practice server is configured.'], 422);
        }

        $startsAt = Carbon::createFromFormat('Y-m-d\TH:i', $request->starts_at, 'Europe/London')->utc();
        $race = new Race(['scheduled_at' => $startsAt]);

        $window = (new PracticeWindowCalculator)->calculate($race, $server);

        return response()->json([
            'window_start' => $window->windowStart->timezone('Europe/London')->format('D d M, H:i T'),
            'upload_at' => $window->uploadAt->timezone('Europe/London')->format('D d M, H:i T'),
            'window_end' => $window->windowEnd->timezone('Europe/London')->format('D d M, H:i T'),
            'is_past' => $window->isAlreadyPast(),
        ]);
    }

    public function pushConfig(Request $request, Race $race, ServerConfigGenerator $config)
    {
        $request->validate(['server_id' => 'required|exists:ftp_servers,id']);

        $server = FtpServer::findOrFail($request->server_id);

        // A saved/pasted settings.json can carry a password or serverName left over from
        // a different server — force these two fields to match the server we're pushing
        // to now, so a stale override can never send the wrong password to a live server.
        $freshSettings = $config->settings($race, $server);
        $settingsInput = $request->input('settings_json') ?? $race->configFile('settings.json');
        $decodedSettings = $settingsInput ? json_decode($settingsInput, true) : null;
        $settingsData = $decodedSettings
            ? array_merge($decodedSettings, [
                'password' => $freshSettings['password'],
                'serverName' => $freshSettings['serverName'],
            ])
            : $freshSettings;
        // Malformed pasted JSON is passed through as-is so the validation loop below
        // still reports it, instead of the fresh-fields merge silently masking it.
        $settingsJson = ($settingsInput && $decodedSettings === null)
            ? $settingsInput
            : json_encode($settingsData, JSON_PRETTY_PRINT);

        $files = [
            'entrylist.json' => $request->input('entrylist_json')
                ?? $race->configFile('entrylist.json')
                ?? json_encode($config->entryList($race), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'event.json' => $request->input('event_json')
                ?? $race->configFile('event.json')
                ?? json_encode($config->configuration($race, $server), JSON_PRETTY_PRINT),
            'settings.json' => $settingsJson,
            'eventrules.json' => $race->configFile('eventrules.json')
                ?? json_encode($config->eventRules($race, $server), JSON_PRETTY_PRINT),
            'assistrules.json' => json_encode($config->assistRules($server), JSON_PRETTY_PRINT),
        ];

        foreach ($files as $filename => $content) {
            json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', "Invalid JSON in {$filename}: ".json_last_error_msg());
            }
        }

        $ftp = new FtpService;

        if (! $ftp->connect($server)) {
            return back()->with('error', 'Could not connect to '.$server->host.'.');
        }

        $cfgPath = rtrim($server->cfg_path, '/');

        $failed = [];
        foreach ($files as $filename => $content) {
            if (! $ftp->uploadFile($cfgPath.'/'.$filename, $content)) {
                $failed[] = $filename;
            }
        }

        $ftp->disconnect();

        if ($failed) {
            $error = 'Failed to upload: '.implode(', ', $failed);
            $race->update([
                'config_push_status' => 'failed',
                'config_push_error' => $error,
                'config_pushed_at' => now(),
            ]);

            return back()->with('error', $error);
        }

        $race->update([
            'config_push_status' => 'pushed',
            'config_push_error' => null,
            'config_pushed_at' => now(),
            'config_push_attempts' => 0,
        ]);

        return back()->with('success', 'Config pushed to '.$server->name.' — entrylist.json, event.json, settings.json, eventrules.json, assistrules.json uploaded.');
    }

    public function uploadEntrylist(Request $request, Race $race)
    {
        $request->validate([
            'entrylist_file' => 'required|file|mimes:json|max:10240',
        ]);

        $content = file_get_contents($request->file('entrylist_file')->getRealPath());
        json_decode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('config_error', 'Invalid JSON: '.json_last_error_msg());
        }

        $overrides = $race->config_overrides ?? [];
        $overrides['entrylist.json'] = $content;
        $race->update(['config_overrides' => $overrides]);

        return back()->with('config_success', 'entrylist.json uploaded and saved.');
    }

    public function saveConfig(Request $request, Race $race)
    {
        $request->validate([
            'file' => 'required|in:entrylist.json,event.json,settings.json,eventrules.json,assistrules.json',
            'content' => 'required|string',
        ]);

        $content = $request->input('content');
        json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('config_error', 'Invalid JSON: '.json_last_error_msg())->withInput();
        }

        $overrides = $race->config_overrides ?? [];
        $overrides[$request->input('file')] = $content;
        $race->update(['config_overrides' => $overrides]);

        return back()->with('config_success', '"'.$request->input('file').'" saved.');
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
            ->with('success', $count.' event'.($count !== 1 ? 's' : '').' deleted.');
    }

    public function destroy(Race $race)
    {
        $race->registrations()->delete();
        $race->delete();

        return redirect()->route('admin.races.index')
            ->with('success', '"'.$race->title.'" has been deleted.');
    }

    public function resetConfig(Request $request, Race $race)
    {
        $request->validate([
            'file' => 'required|in:entrylist.json,event.json,settings.json,eventrules.json,assistrules.json',
        ]);

        $overrides = $race->config_overrides ?? [];
        unset($overrides[$request->input('file')]);
        $race->update(['config_overrides' => empty($overrides) ? null : $overrides]);

        return back()->with('config_success', '"'.$request->input('file').'" reset to auto-generated.');
    }

    // Matches submitted classes against the race's existing ones by car_class (the
    // stable key from the fixed GT3/GT4/GT2/TCX/GTC vocabulary the admin picks from,
    // falling back to name) and updates them in place, rather than deleting everything
    // and recreating it from scratch on every save. A class row's id is what
    // RaceRegistration.race_class_id points to, so recreating it -- even with
    // identical values -- previously nulled out every registration's class link
    // (race_class_id is nullOnDelete) purely because the *row* changed, not because
    // the class itself was actually removed. Only classes genuinely dropped from the
    // submitted set are deleted now, so only registrations tied to a truly-removed
    // class lose their link.
    private function syncRaceClasses(array $classes, Race $race): void
    {
        if (! $classes) {
            return;
        }

        $existing = $race->raceClasses()->get()->keyBy(fn ($c) => $c->car_class ?? $c->name);
        $keptIds = [];

        foreach ($classes as $i => $class) {
            $attrs = [
                'name' => $class['name'] ?? 'Class '.($i + 1),
                'color' => $class['color'] ?? '#db2777',
                'car_class' => $class['car_class'] ?? null,
                'max_drivers' => $class['max_drivers'] ?? null,
                'sr_requirement' => $class['sr_requirement'] ?? null,
                'min_rating' => $class['min_rating'] ?? null,
                'sort_order' => $i,
            ];

            $key = $attrs['car_class'] ?? $attrs['name'];
            $match = $existing->get($key);

            if ($match) {
                $match->update($attrs);
                $keptIds[] = $match->id;
            } else {
                $keptIds[] = $race->raceClasses()->create($attrs)->id;
            }
        }

        $race->raceClasses()->whereNotIn('id', $keptIds)->delete();
    }

    private function resolveMedia(Request $request): ?string
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');

            return $file->storeAs('images/media', Str::uuid().'.'.$file->getClientOriginalExtension(), 'media');
        }

        return $request->filled('image_path') ? $request->image_path : null;
    }

    private function resolveIcon(Request $request): ?string
    {
        if ($request->hasFile('icon')) {
            $file = $request->file('icon');

            return $file->storeAs('images/icons', Str::uuid().'.'.$file->getClientOriginalExtension(), 'media');
        }

        return $request->filled('icon_path') ? $request->icon_path : null;
    }
}
