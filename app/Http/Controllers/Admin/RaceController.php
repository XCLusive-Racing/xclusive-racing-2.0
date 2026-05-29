<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Carbon\Carbon;
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

    public function bulkCreate()
    {
        return view('admin.races.bulk_create');
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'game'        => 'required|in:acc,lmu,iracing',
            'track'       => 'required|string|max:255',
            'start_date'  => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'rounds'      => 'required|integer|min:1|max:366',
            'interval'    => 'required|in:daily,weekly,monthly',
            'days'        => 'nullable|array',
            'days.*'      => 'integer|in:0,1,2,3,4,5,6',
            'max_drivers' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $base    = Carbon::parse($data['start_date'] . ' ' . $data['start_time']);
        $rounds  = (int) $data['rounds'];
        $payload = fn ($at) => [
            'title'        => $data['title'],
            'game'         => $data['game'],
            'track'        => $data['track'],
            'scheduled_at' => $at,
            'max_drivers'  => $data['max_drivers'] ?? null,
            'description'  => $data['description'] ?? null,
            'status'       => 'open',
        ];

        if ($data['interval'] === 'weekly' && !empty($data['days'])) {
            $days    = array_map('intval', $data['days']);
            sort($days);
            $created = 0;
            $current = $base->copy();
            $limit   = $base->copy()->addYears(2);

            while ($created < $rounds && $current < $limit) {
                if (in_array($current->dayOfWeek, $days)) {
                    Race::create($payload($current->copy()));
                    $created++;
                }
                $current->addDay();
            }
        } else {
            for ($i = 0; $i < $rounds; $i++) {
                $at = match ($data['interval']) {
                    'daily'   => $base->copy()->addDays($i),
                    'monthly' => $base->copy()->addMonths($i),
                    default   => $base->copy()->addWeeks($i),
                };
                Race::create($payload($at));
            }
        }

        return redirect()->route('admin.calendar')
            ->with('success', $rounds . ' events scheduled successfully!');
    }

    private function storeImage(\Illuminate\Http\UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/races'), $filename);
        return 'images/races/' . $filename;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && file_exists(public_path($path))) {
            unlink(public_path($path));
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