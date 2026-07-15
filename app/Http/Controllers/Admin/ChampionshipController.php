<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipPenalty;
use App\Models\Media;
use App\Models\Race;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ChampionshipController extends Controller
{
    public function index()
    {
        $championships = Championship::withCount(['rounds', 'registrations'])
            ->orderBy('season', 'desc')
            ->orderBy('name')
            ->get();

        return view('admin.championships.index', compact('championships'));
    }

    public function create()
    {
        return view('admin.championships.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'game'                   => 'required|in:acc,lmu,iracing,ac',
            'season'                 => 'required|integer|min:1|max:999',
            'status'                 => 'required|in:draft,active,finished',
            'description'            => 'nullable|string',
            'image'                  => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'image_path'             => 'nullable|string|max:500',
            'icon'                   => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg|max:4096',
            'icon_path'              => 'nullable|string|max:500',
            'max_drivers'            => 'nullable|integer|min:1',
            'is_multiclass'          => 'nullable|boolean',
            'points_system'          => 'nullable|string',
            'bonus_fastest_lap'      => 'nullable|integer|min:0',
            'bonus_pole'             => 'nullable|integer|min:0',
            'drop_rounds'            => 'nullable|integer|min:0',
            'max_missed_rounds'           => 'nullable|integer|min:0',
            'missed_rounds_action'        => 'nullable|in:none,penalise',
            'missed_rounds_penalty_points'=> 'nullable|integer|min:1',
            'registration_open'      => 'nullable|boolean',
            'registration_deadline'  => 'nullable|date',
            'sr_requirement'         => 'nullable|in:none,5,7',
            'min_rating'             => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'car_class'              => 'nullable|string|max:50',
            'practice_duration'      => 'nullable|integer|min:1|max:999',
            'qualifying_duration'    => 'nullable|integer|min:1|max:999',
            'race_duration'          => 'nullable|integer|min:1|max:999',
            'weather'                => 'nullable|in:dry,wet,mixed,random',
            'time_of_day'            => 'nullable|in:day,dusk,night,dynamic',
            'duration_key'           => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
        ]);

        $data['image']          = $this->resolveMedia($request);
        $data['icon']           = $this->resolveIcon($request);
        $data['points_system']  = $this->parsePointsSystem($request->input('points_system'));
        $data['is_multiclass']  = $request->boolean('is_multiclass');
        $data['registration_open'] = $request->boolean('registration_open');

        unset($data['image_path'], $data['icon_path']);

        $championship = Championship::create($data);

        $this->syncClasses($request, $championship);

        return redirect()->route('admin.championships.show', $championship)
            ->with('success', 'Championship created successfully!');
    }

    public function show(Championship $championship)
    {
        $championship->load(['classes', 'registrations.user', 'registrations.championshipClass', 'penalties.user', 'penalties.race']);
        $rounds   = $championship->rounds()->with('registrations')->get();
        $standings = $championship->computeStandings();
        $users    = User::orderBy('name')->get();

        return view('admin.championships.show', compact('championship', 'rounds', 'standings', 'users'));
    }

    public function edit(Championship $championship)
    {
        $championship->load('classes');
        return view('admin.championships.edit', compact('championship'));
    }

    public function update(Request $request, Championship $championship)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'game'                   => 'required|in:acc,lmu,iracing,ac',
            'season'                 => 'required|integer|min:1|max:999',
            'status'                 => 'required|in:draft,active,finished',
            'description'            => 'nullable|string',
            'image'                  => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'image_path'             => 'nullable|string|max:500',
            'image_keep'             => 'nullable|in:0,1',
            'icon'                   => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg|max:4096',
            'icon_path'              => 'nullable|string|max:500',
            'icon_keep'              => 'nullable|in:0,1',
            'max_drivers'            => 'nullable|integer|min:1',
            'is_multiclass'          => 'nullable|boolean',
            'points_system'          => 'nullable|string',
            'bonus_fastest_lap'      => 'nullable|integer|min:0',
            'bonus_pole'             => 'nullable|integer|min:0',
            'drop_rounds'            => 'nullable|integer|min:0',
            'max_missed_rounds'           => 'nullable|integer|min:0',
            'missed_rounds_action'        => 'nullable|in:none,penalise',
            'missed_rounds_penalty_points'=> 'nullable|integer|min:1',
            'registration_open'      => 'nullable|boolean',
            'registration_deadline'  => 'nullable|date',
            'sr_requirement'         => 'nullable|in:none,5,7',
            'min_rating'             => 'nullable|in:all,rookie,bronze,silver,gold,platinum,alien',
            'car_class'              => 'nullable|string|max:50',
            'practice_duration'      => 'nullable|integer|min:1|max:999',
            'qualifying_duration'    => 'nullable|integer|min:1|max:999',
            'race_duration'          => 'nullable|integer|min:1|max:999',
            'weather'                => 'nullable|in:dry,wet,mixed,random',
            'time_of_day'            => 'nullable|in:day,dusk,night,dynamic',
            'duration_key'           => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
        ]);

        $resolvedImage = $this->resolveMedia($request);
        $data['image'] = $resolvedImage ?? ($request->input('image_keep') === '0' ? null : $championship->image);

        $resolvedIcon = $this->resolveIcon($request);
        $data['icon'] = $resolvedIcon ?? ($request->input('icon_keep') === '0' ? null : $championship->icon);

        $data['points_system']     = $this->parsePointsSystem($request->input('points_system'));
        $data['is_multiclass']     = $request->boolean('is_multiclass');
        $data['registration_open'] = $request->boolean('registration_open');

        unset($data['image_path'], $data['image_keep'], $data['icon_path'], $data['icon_keep']);

        $championship->update($data);

        $this->syncClasses($request, $championship);

        return redirect()->route('admin.championships.show', $championship)
            ->with('success', 'Championship updated successfully!');
    }

    public function roundCreate(Championship $championship)
    {
        $trackFilenames   = array_values(RaceController::TRACK_IMAGE_MAP);
        $trackMediaByName = Media::whereIn('original_name', $trackFilenames)->get()->keyBy('original_name');
        $trackPreviewUrls = collect(RaceController::TRACK_IMAGE_MAP)
            ->map(fn($fname) => $trackMediaByName->get($fname)?->url)
            ->all();

        return view('admin.championships.round-create', compact('championship', 'trackPreviewUrls'));
    }

    public function addRound(Request $request, Championship $championship)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'track'               => 'required|string|max:255',
            'scheduled_at'        => 'required|date',
            'round_number'        => 'nullable|integer|min:1',
            'max_drivers'         => 'nullable|integer|min:1',
            'practice_duration'   => 'nullable|integer|min:1|max:999',
            'qualifying_duration' => 'nullable|integer|min:1|max:999',
            'race_duration'       => 'nullable|integer|min:1|max:999',
            'car_class'           => 'nullable|string|max:50',
            'weather'             => 'nullable|in:dry,wet,mixed,random',
            'time_of_day'         => 'nullable|in:day,dusk,night,dynamic',
            'duration_key'        => 'nullable|string|in:15,20,30,30+,30++,45,45+,60,60+,90,90+',
            'sr_requirement'      => 'nullable|in:3,4,5,6,7,8,9',
            'min_rating'          => 'nullable|in:rookie,bronze,silver,gold,platinum,alien',
            'max_rating'          => 'nullable|in:rookie,bronze,silver,gold,platinum,alien',
            'description'         => 'nullable|string',
        ]);

        $data['championship_id'] = $championship->id;
        $data['game']            = $championship->game;
        $data['status']          = 'open';
        $data['is_championship'] = true;
        $data['scheduled_at']    = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Europe/London')->utc();
        $data['image']           = $this->resolveMedia($request);
        $data['icon']            = $this->resolveIcon($request);

        if (!$data['round_number']) {
            $data['round_number'] = $championship->rounds()->max('round_number') + 1;
        }

        Race::create($data);

        return redirect()->route('admin.championships.show', $championship)
            ->with('success', 'Round added successfully!');
    }

    public function removeRound(Championship $championship, Race $race)
    {
        $race->update(['championship_id' => null, 'round_number' => null]);

        return redirect()->route('admin.championships.show', $championship)
            ->with('success', 'Round removed from championship.');
    }

    public function addPenalty(Request $request, Championship $championship)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'points'  => 'required|integer',
            'race_id' => 'nullable|exists:races,id',
            'reason'  => 'nullable|string|max:255',
        ]);

        ChampionshipPenalty::create([
            'championship_id' => $championship->id,
            'user_id'         => $request->user_id,
            'race_id'         => $request->race_id ?: null,
            'points'          => $request->points,
            'reason'          => $request->reason,
        ]);

        return redirect()->route('admin.championships.show', $championship)
            ->with('success', 'Penalty added.');
    }

    public function destroyPenalty(Championship $championship, ChampionshipPenalty $penalty)
    {
        $penalty->delete();

        return redirect()->route('admin.championships.show', $championship)
            ->with('success', 'Penalty removed.');
    }

    private function syncClasses(Request $request, Championship $championship): void
    {
        $classesJson = $request->input('classes_json');
        if (!$classesJson) {
            return;
        }

        $classes = json_decode($classesJson, true);
        if (!is_array($classes)) {
            return;
        }

        $championship->classes()->delete();

        foreach ($classes as $i => $class) {
            $championship->classes()->create([
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

    private function parsePointsSystem(?string $value): ?array
    {
        if (!$value) {
            return null;
        }
        $parts = array_map('trim', explode(',', $value));
        $pts   = array_filter(array_map(fn($p) => is_numeric($p) ? (int) $p : null, $parts), fn($v) => $v !== null);
        return array_values($pts) ?: null;
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
