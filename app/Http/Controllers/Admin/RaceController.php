<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventTag;
use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\Race;
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

        $races = Race::withCount('registrations')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return view('admin.races.index', compact('races'));
    }

    public function show(Race $race)
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
                    $ftpFiles    = $result['json'];
                    $ftpAllFiles = $result['all'];
                    $ftp->disconnect();
                } else {
                    $ftpError = 'Could not connect to ' . $selectedServer->host . '.';
                }
                $importedFiles = FtpImportedFile::where('race_id', $race->id)->pluck('filename')->toArray();
            }
        }

        return view('admin.races.show', compact(
            'race', 'raceResults', 'qualiResults', 'registrations',
            'ftpServers', 'selectedServer', 'ftpFiles', 'ftpAllFiles', 'ftpError', 'importedFiles'
        ));
    }

    public function downloadEntryList(Race $race)
    {
        $registrations = $race->registrations()->with('user')->orderBy('created_at')->get();

        $data = [
            'event' => [
                'title' => $race->title,
                'track' => $race->track,
                'game'  => $race->gameLabel(),
                'date'  => $race->scheduled_at->toISOString(),
            ],
            'entries' => $registrations->values()->map(fn ($reg, $i) => [
                'position'      => $i + 1,
                'name'          => $reg->user->name,
                'platform'      => $reg->user->platform,
                'platform_id'   => $reg->user->platform_id,
                'team'          => $reg->user->team,
                'registered_at' => $reg->created_at->toISOString(),
            ])->values(),
        ];

        $filename = Str::slug($race->title) . '-entry-list.json';

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], JSON_PRETTY_PRINT);
    }

    public function bulkCreate()
    {
        $tags = EventTag::orderBy('name')->get();
        return view('admin.races.bulk-create', compact('tags'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'game'              => 'required|in:acc,lmu,iracing,ac',
            'event_tag'         => 'required|exists:event_tags,slug',
            'duration_key'      => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'max_drivers'       => 'nullable|integer|min:1',
            'description'       => 'nullable|string',
            'events'            => 'required|array|min:1|max:20',
            'events.*.title'        => 'required|string|max:255',
            'events.*.track'        => 'required|string|max:255',
            'events.*.scheduled_at' => 'required|date',
        ]);

        $shared = [
            'game'         => $request->game,
            'event_tag'    => $request->event_tag,
            'duration_key' => $request->duration_key ?: null,
            'max_drivers'  => $request->max_drivers ?: null,
            'description'  => $request->description ?: null,
            'status'       => 'open',
        ];

        foreach ($request->events as $event) {
            Race::create(array_merge($shared, [
                'title'        => $event['title'],
                'track'        => $event['track'],
                'scheduled_at' => $event['scheduled_at'],
            ]));
        }

        $count = count($request->events);
        return redirect()->route('admin.races.index')
            ->with('success', $count . ' ' . ($count === 1 ? 'race' : 'races') . ' created successfully!');
    }

    public function create(Request $request)
    {
        $prefillDate = $request->date('date')?->format('Y-m-d\TH:i');
        $tags = EventTag::orderBy('name')->get();
        return view('admin.races.create', compact('prefillDate', 'tags'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'game'         => 'required|in:acc,lmu,iracing,ac',
            'track'        => 'required|string|max:255',
            'scheduled_at' => 'required|date',
            'event_tag'    => 'required|exists:event_tags,slug',
            'duration_key' => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'max_drivers'  => 'nullable|integer|min:1',
            'description'  => 'nullable|string',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg,mov|max:204800',
            'image_path'   => 'nullable|string|max:500',
            'icon'         => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg|max:4096',
            'icon_path'    => 'nullable|string|max:500',
        ]);

        $data['image'] = $this->resolveMedia($request);
        $data['icon']  = $this->resolveIcon($request);
        unset($data['image_path'], $data['icon_path']);

        Race::create($data);

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
            'title'        => 'required|string|max:255',
            'game'         => 'required|in:acc,lmu,iracing,ac',
            'track'        => 'required|string|max:255',
            'scheduled_at' => 'required|date',
            'status'       => 'required|in:open,closed,finished',
            'event_tag'    => 'required|exists:event_tags,slug',
            'duration_key' => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'max_drivers'  => 'nullable|integer|min:1',
            'description'  => 'nullable|string',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg,mov|max:204800',
            'image_path'   => 'nullable|string|max:500',
            'image_keep'   => 'nullable|in:0,1',
            'icon'         => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg|max:4096',
            'icon_path'    => 'nullable|string|max:500',
            'icon_keep'    => 'nullable|in:0,1',
        ]);

        $resolvedImage  = $this->resolveMedia($request);
        $data['image']  = $resolvedImage ?? ($request->input('image_keep') === '0' ? null : $race->image);

        $resolvedIcon   = $this->resolveIcon($request);
        $data['icon']   = $resolvedIcon ?? ($request->input('icon_keep') === '0' ? null : $race->icon);

        unset($data['image_path'], $data['image_keep'], $data['icon_path'], $data['icon_keep']);

        $race->update($data);

        return redirect()->route('admin.races.index')->with('success', 'Race updated successfully!');
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