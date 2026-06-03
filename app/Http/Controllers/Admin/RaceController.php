<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    private function storeImage(\Illuminate\Http\UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('images/races', $filename, 'public');
    }

    private function deleteImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
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
            'image'        => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        }

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
            'image'        => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImage($race->image);
            $data['image'] = $this->storeImage($request->file('image'));
        }

        $race->update($data);

        return redirect()->route('admin.races.index')->with('success', 'Race updated successfully!');
    }
}