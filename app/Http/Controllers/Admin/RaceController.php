<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventTag;
use App\Models\Media;
use App\Models\Race;
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

        $stats = [
            'total'         => $races->count(),
            'open'          => $races->where('status', 'open')->count(),
            'finished'      => $races->where('status', 'finished')->count(),
            'registrations' => $races->sum('registrations_count'),
        ];

        return view('admin.races.index', compact('races', 'stats'));
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

    public function destroy(Race $race)
    {
        if ($race->status !== 'finished') {
            return redirect()->route('admin.races.index')
                ->with('error', 'Only finished races can be deleted.');
        }

        $title = $race->title;
        $race->delete();

        return redirect()->route('admin.races.index')
            ->with('success', '"' . $title . '" deleted. Results are preserved on driver profiles.');
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
            return Media::createFromUpload($request->file('image'))->path;
        }

        return $request->filled('image_path') ? $request->image_path : null;
    }

    private function resolveIcon(Request $request): ?string
    {
        if ($request->hasFile('icon')) {
            return Media::createFromUpload($request->file('icon'), 'icon')->path;
        }

        return $request->filled('icon_path') ? $request->icon_path : null;
    }
}