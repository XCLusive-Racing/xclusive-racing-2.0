<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        return view('admin.races.create', compact('prefillDate'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'game'         => 'required|in:acc,lmu,iracing',
            'track'        => 'required|string|max:255',
            'scheduled_at' => 'required|date',
            'max_drivers'  => 'nullable|integer|min:1',
            'description'  => 'nullable|string',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg,mov|max:204800',
            'image_path'   => 'nullable|string|max:500',
        ]);

        $data['image'] = $this->resolveMedia($request);
        unset($data['image_path']);

        Race::create($data);

        return redirect()->route('admin.races.index')->with('success', 'Race created successfully!');
    }

    public function edit(Race $race)
    {
        if ($race->isPast()) {
            return redirect()->route('admin.races.index')
                ->with('error', 'Past races cannot be edited. You can still manage results.');
        }

        return view('admin.races.edit', compact('race'));
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
            'game'         => 'required|in:acc,lmu,iracing',
            'track'        => 'required|string|max:255',
            'scheduled_at' => 'required|date',
            'status'       => 'required|in:open,closed,finished',
            'max_drivers'  => 'nullable|integer|min:1',
            'description'  => 'nullable|string',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg,mov|max:204800',
            'image_path'   => 'nullable|string|max:500',
        ]);

        $data['image'] = $this->resolveMedia($request);
        unset($data['image_path']);

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
}