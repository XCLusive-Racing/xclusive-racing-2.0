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
    public function index()
    {
        // Auto-close races whose start time has passed but are still open
        Race::where('status', 'open')
            ->where('scheduled_at', '<', now())
            ->update(['status' => 'closed']);

        $races = Race::select(['id','title','game','track','scheduled_at','status','is_championship','event_tag','max_drivers','duration_key'])
            ->orderBy('scheduled_at', 'desc')
            ->get();
        $races->loadCount('registrations');

        return view('admin.races.index', compact('races'));
    }

    public function show(Race $race, AccServerConfigService $config)
    {
        $raceResults   = $race->results()->where('session_type', 'race')->with('user')->get();
        $qualiResults  = $race->results()->where('session_type', 'quali')->with('user')->get();
        $registrations = $race->registrations()->with('user')->orderBy('created_at')->get();

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
                    $ftp->disconnect();

                    // Only show files from around this race's session (filename = YYMMDD_HHMMSS_*.json)
                    $cutoff = $race->scheduled_at->subHours(2);
                    $ftpFiles = array_values(array_filter($result['json'], function ($file) use ($cutoff) {
                        $parts = explode('_', pathinfo($file['name'], PATHINFO_FILENAME));
                        if (count($parts) < 2 || strlen($parts[0]) !== 6 || !is_numeric($parts[0])) return true;
                        $dp = $parts[0]; $tp = $parts[1];
                        $fileTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',
                            '20'.substr($dp,0,2).'-'.substr($dp,2,2).'-'.substr($dp,4,2).' '.
                            substr($tp,0,2).':'.substr($tp,2,2).':'.(strlen($tp)>=6 ? substr($tp,4,2) : '00'),
                            'UTC'
                        );
                        return $fileTime !== false && $fileTime >= $cutoff;
                    }));
                } else {
                    $ftpError = 'Could not connect to ' . $selectedServer->host . '.';
                }
                $importedFiles = FtpImportedFile::where('race_id', $race->id)->pluck('filename')->toArray();
            }
        }

        $configFiles = [
            'entrylist.json' => json_encode($config->entryList($race),     JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'event.json'     => json_encode($config->configuration($race), JSON_PRETTY_PRINT),
            'settings.json'  => json_encode($config->settings($race),      JSON_PRETTY_PRINT),
        ];

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
            'race', 'raceResults', 'qualiResults', 'registrations',
            'ftpServers', 'selectedServer', 'ftpFiles', 'ftpAllFiles', 'ftpError', 'importedFiles',
            'configFiles', 'entrylistDrivers'
        ))->with('configData', $config);
    }

    public function downloadEntryList(Race $race)
    {
        $registrations = $race->registrations()->with('user')->orderBy('created_at')->get();

        $entries = $registrations->map(function ($reg) use ($race) {
            $user      = $reg->user;
            $lastName  = $user->team ? $user->name . "\n" . $user->team : ($user->name ?? '');
            $shortName = strtoupper(substr(preg_replace('/\s+/', '', $user->name ?? ''), 0, 3));

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
        $tags = EventTag::orderBy('name')->get();
        return view('admin.races.custom-create', compact('tags'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'game'                 => 'required|in:acc,lmu,iracing,ac',
            'event_tag'            => 'required|exists:event_tags,slug',
            'event_format_id'      => 'nullable|exists:event_formats,id',
            'duration_key'         => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'practice_duration'    => 'nullable|integer|min:1|max:999',
            'qualifying_duration'  => 'nullable|integer|min:1|max:999',
            'race_duration'        => 'nullable|integer|min:1|max:999',
            'car_class'            => 'nullable|string|max:50',
            'weather'              => 'nullable|in:dry,wet,mixed,random',
            'time_of_day'          => 'nullable|in:day,dusk,night,dynamic',
            'sr_requirement'       => 'nullable|numeric|in:3,4,5,6,7,8,9',
            'min_rating'           => 'nullable|string|in:rookie,bronze,silver,gold,platinum,alien',
            'max_rating'           => 'nullable|string|in:rookie,bronze,silver,gold,platinum,alien',
            'max_drivers'          => 'nullable|integer|min:1',
            'description'          => 'nullable|string',
            'events'               => 'required|array|min:1|max:20',
            'events.*.title'           => 'required|string|max:255',
            'events.*.track'           => 'required|string|max:255',
            'events.*.scheduled_at'    => 'required|date',
        ]);

        $shared = [
            'game'                 => $request->game,
            'event_tag'            => $request->event_tag,
            'event_format_id'      => $request->event_format_id ?: null,
            'duration_key'         => $request->duration_key ?: null,
            'practice_duration'    => $request->practice_duration ?: null,
            'qualifying_duration'  => $request->qualifying_duration ?: null,
            'race_duration'        => $request->race_duration ?: null,
            'car_class'            => $request->car_class ?: null,
            'weather'              => $request->weather ?: null,
            'time_of_day'          => $request->time_of_day ?: null,
            'sr_requirement'       => $request->sr_requirement ?: null,
            'min_rating'           => $request->min_rating ?: null,
            'max_rating'           => $request->max_rating ?: null,
            'max_drivers'          => $request->max_drivers ?: null,
            'description'          => $request->description ?: null,
            'status'               => 'open',
        ];

        foreach ($request->events as $event) {
            Race::create(array_merge($shared, [
                'title'        => $event['title'],
                'track'        => $event['track'],
                'scheduled_at' => \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $event['scheduled_at'], 'Europe/London')->utc(),
            ]));
        }

        $count = count($request->events);
        return redirect()->route('admin.races.index')
            ->with('success', $count . ' ' . ($count === 1 ? 'race' : 'races') . ' created successfully!');
    }

    public function create(Request $request)
    {
        $prefillDate = $request->date('date')?->format('Y-m-d\TH:i');
        $tags    = EventTag::orderBy('name')->get();
        $formats = EventFormat::orderBy('game')->orderBy('sort_order')->get();

        $servers = FtpServer::where('active', true)->orderBy('name')->get();
        $serverSlots = $servers->mapWithKeys(fn($s) => [
            $s->id => [
                'type'       => $s->server_type,
                'slots'      => $s->slotsForDays(7),
                'takenSlots' => $s->takenSlots(),
            ],
        ]);

        // Build preview image URLs for the live preview panel
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

        $endurancePreviewUrls = [];
        foreach (['4h', '6h', '8h', '10h', '12h', '24h'] as $dur) {
            $key = $dur . '_endurance';
            $endurancePreviewUrls[$dur] = Media::where('title', $key)
                ->orWhere('original_name', 'like', $key . '%')
                ->first()?->url;
        }

        return view('admin.races.create', compact(
            'prefillDate', 'tags', 'formats', 'servers', 'serverSlots',
            'trackPreviewUrls', 'formatPreviewUrls', 'endurancePreviewUrls'
        ));
    }

    // Track name → background image filename in media library
    private const TRACK_IMAGE_MAP = [
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
            'event_format_id'      => 'required|exists:event_formats,id',
            'endurance_duration'   => 'nullable|in:4h,6h,8h,10h,12h,24h',
            'duration_key'         => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'practice_duration'    => 'nullable|integer|min:1|max:999',
            'qualifying_duration'  => 'nullable|integer|min:1|max:999',
            'race_duration'        => 'nullable|integer|min:1|max:999',
            'car_class'            => 'nullable|string|max:50',
            'sr_requirement'       => 'nullable|in:3,4,5,6,7,8,9',
            'min_rating'           => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'max_rating'           => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'weather'              => 'nullable|in:dry,wet,mixed,random',
            'time_of_day'          => 'nullable|in:day,dusk,night,dynamic',
            'max_drivers'          => 'nullable|integer|min:1',
            'description'          => 'nullable|string',
            'is_multiclass'        => 'nullable|boolean',
            'ftp_server_id'        => 'nullable|exists:ftp_servers,id',
            'slot_time'            => 'nullable|date',
        ]);

        // Derive title and media from format + track
        $fmt = EventFormat::find($data['event_format_id']);
        if ($fmt) {
            $data['title']           = $fmt->name;
            $data['duration_key']    = null;
            $data['practice_duration']   = $fmt->practice_mins ?: null;
            $data['qualifying_duration'] = $fmt->quali_mins ?: null;
            $data['race_duration']       = $fmt->race1_mins ?: null;

            $formatSlug = Str::slug($fmt->name, '_');
            if ($formatSlug === 'endurance' && !empty($data['endurance_duration'])) {
                $formatImageKey = $data['endurance_duration'] . '_endurance';
            } else {
                $formatImageKey = self::FORMAT_IMAGE_OVERRIDES[$formatSlug] ?? $formatSlug;
            }

            // Format image → icon field (centered overlay on card)
            $data['icon'] = Media::where('title', $formatImageKey)
                ->orWhere('original_name', 'like', $formatImageKey . '%')
                ->value('path');
        }

        // Track image → image field (full-bleed card background)
        $trackFilename    = self::TRACK_IMAGE_MAP[$data['track']] ?? null;
        $data['image']    = $trackFilename
            ? Media::where('original_name', $trackFilename)->value('path')
            : null;

        $data['scheduled_at']  = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Europe/London')->utc();
        $data['is_multiclass'] = $request->boolean('is_multiclass');
        unset($data['endurance_duration']);

        if (!empty($data['ftp_server_id']) && !empty($data['slot_time'])) {
            $data['slot_time']          = \Carbon\Carbon::parse($data['slot_time'])->utc();
            $data['config_push_status'] = 'pending';
        } else {
            $data['ftp_server_id'] = null;
            $data['slot_time']     = null;
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

        $tags = EventTag::orderBy('name')->get();
        return view('admin.races.edit', compact('race', 'tags'));
    }

    public function update(Request $request, Race $race)
    {
        if ($race->isPast()) {
            return redirect()->route('admin.races.index')
                ->with('error', 'Past races cannot be edited.');
        }

        $data = $request->validate([
            'title'                => 'required|string|max:255',
            'game'                 => 'required|in:acc,lmu,iracing,ac',
            'track'                => 'required|string|max:255',
            'scheduled_at'         => 'required|date',
            'status'               => 'required|in:open,closed,finished',
            'event_tag'            => 'required|exists:event_tags,slug',
            'duration_key'         => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'practice_duration'    => 'nullable|integer|min:1|max:999',
            'qualifying_duration'  => 'nullable|integer|min:1|max:999',
            'race_duration'        => 'nullable|integer|min:1|max:999',
            'car_class'            => 'nullable|string|max:50',
            'sr_requirement'       => 'nullable|in:3,4,5,6,7,8,9',
            'min_rating'           => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'weather'              => 'nullable|in:dry,wet,mixed,random',
            'time_of_day'          => 'nullable|in:day,dusk,night,dynamic',
            'max_drivers'          => 'nullable|integer|min:1',
            'description'          => 'nullable|string',
            'image'                => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg,mov|max:204800',
            'image_path'           => 'nullable|string|max:500',
            'image_keep'           => 'nullable|in:0,1',
            'icon'                 => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg|max:4096',
            'icon_path'            => 'nullable|string|max:500',
            'icon_keep'            => 'nullable|in:0,1',
            'is_multiclass'        => 'nullable|boolean',
        ]);

        $data['scheduled_at']  = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Europe/London')->utc();
        $data['is_multiclass'] = $request->boolean('is_multiclass');

        $resolvedImage  = $this->resolveMedia($request);
        $data['image']  = $resolvedImage ?? ($request->input('image_keep') === '0' ? null : $race->image);

        $resolvedIcon   = $this->resolveIcon($request);
        $data['icon']   = $resolvedIcon ?? ($request->input('icon_keep') === '0' ? null : $race->icon);

        unset($data['image_path'], $data['image_keep'], $data['icon_path'], $data['icon_keep']);

        $race->update($data);

        $this->syncRaceClasses($request, $race);

        return redirect()->route('admin.races.index')->with('success', 'Race updated successfully!');
    }

    public function pushConfig(Request $request, Race $race, AccServerConfigService $config)
    {
        $request->validate(['server_id' => 'required|exists:ftp_servers,id']);

        $files = [
            'entrylist.json' => $request->input('entrylist_json')
                ?? $race->configFile('entrylist.json')
                ?? json_encode($config->entryList($race), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'event.json'     => $request->input('event_json')
                ?? $race->configFile('event.json')
                ?? json_encode($config->configuration($race), JSON_PRETTY_PRINT),
            'settings.json'  => $request->input('settings_json')
                ?? $race->configFile('settings.json')
                ?? json_encode($config->settings($race), JSON_PRETTY_PRINT),
        ];

        foreach ($files as $filename => $content) {
            json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', "Invalid JSON in {$filename}: " . json_last_error_msg());
            }
        }

        $server = FtpServer::findOrFail($request->server_id);
        $ftp    = new FtpService();

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
            return back()->with('error', 'Failed to upload: ' . implode(', ', $failed));
        }

        return back()->with('success', 'Config pushed to ' . $server->name . ' — entrylist.json, configuration.json, settings.json uploaded.');
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
            'file'    => 'required|in:entrylist.json,event.json,settings.json',
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

    public function destroy(Race $race)
    {
        $race->registrations()->delete();
        $race->results()->delete();
        $race->delete();

        return redirect()->route('admin.races.index')
            ->with('success', '"' . $race->title . '" has been deleted.');
    }

    public function resetConfig(Request $request, Race $race)
    {
        $request->validate([
            'file' => 'required|in:entrylist.json,event.json,settings.json',
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
                'name'        => $class['name'] ?? 'Class ' . ($i + 1),
                'color'       => $class['color'] ?? '#db2777',
                'car_class'   => $class['car_class'] ?? null,
                'max_drivers' => $class['max_drivers'] ?? null,
                'sort_order'  => $i,
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