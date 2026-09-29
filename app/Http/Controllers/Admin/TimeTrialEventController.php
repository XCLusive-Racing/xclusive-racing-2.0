<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtpServer;
use App\Models\TimeTrialEvent;
use App\Models\TimeTrialLap;
use App\Services\TimeTrials\TimeTrialResultCollector;
use App\Services\TimeTrials\TimeTrialServerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// Admin > Events > Time Trials: the weekly Time Trial events (track, car class, window,
// server). Pushing and result collection run on the scheduler; the buttons here only
// trigger the same steps on demand.
class TimeTrialEventController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canManageEvents(), 403);

        $events = TimeTrialEvent::with('server')->withCount(['registrations', 'laps'])
            ->orderByDesc('starts_at')->paginate(25);

        return view('admin.time-trials.index', compact('events'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canManageEvents(), 403);

        // Defaults: the next full week, Monday 00:00 to Monday 00:00 (UK).
        $start = now('Europe/London')->next(Carbon::MONDAY)->startOfDay();
        $event = new TimeTrialEvent([
            'starts_at' => $start->copy()->utc(),
            'ends_at' => $start->copy()->addWeek()->utc(),
            'ftp_server_id' => $this->servers()->firstWhere('server_number', 6)?->id,
            'car_class' => 'GT3',
        ]);

        return view('admin.time-trials.form', $this->formData($event));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canManageEvents(), 403);

        $event = TimeTrialEvent::create($this->validated($request));

        return redirect()->route('admin.time-trials.index')->with('success', "Time Trial on {$event->trackName()} created.");
    }

    public function edit(TimeTrialEvent $timeTrial)
    {
        abort_unless(auth()->user()->canManageEvents(), 403);

        return view('admin.time-trials.form', $this->formData($timeTrial));
    }

    public function update(Request $request, TimeTrialEvent $timeTrial): RedirectResponse
    {
        abort_unless(auth()->user()->canManageEvents(), 403);
        abort_if($timeTrial->finalized_at !== null, 422, 'A finished Time Trial can no longer be changed.');

        $timeTrial->update($this->validated($request, $timeTrial));

        return redirect()->route('admin.time-trials.index')->with('success', "Time Trial on {$timeTrial->trackName()} updated.");
    }

    public function destroy(TimeTrialEvent $timeTrial): RedirectResponse
    {
        abort_unless(auth()->user()->canManageEvents(), 403);

        // Rating points were awarded and laps merged into the records: keep the history.
        if ($timeTrial->finalized_at !== null || $timeTrial->laps()->exists()) {
            return back()->with('error', 'This Time Trial already has lap times, so it can no longer be deleted. Unpublish it instead.');
        }

        $timeTrial->delete();

        return redirect()->route('admin.time-trials.index')->with('success', 'Time Trial deleted.');
    }

    public function push(TimeTrialEvent $timeTrial, TimeTrialServerService $servers): RedirectResponse
    {
        abort_unless(auth()->user()->canManageEvents(), 403);

        if (! $timeTrial->server) {
            return back()->with('error', 'Pick a server first.');
        }

        $error = $servers->push($timeTrial);

        return back()->with($error ? 'error' : 'success', $error
            ?? "Uploaded to {$timeTrial->server->name} ({$timeTrial->last_entry_count} members in the entry list). The server uses it from its next own restart.");
    }

    public function collect(TimeTrialEvent $timeTrial, TimeTrialResultCollector $collector): RedirectResponse
    {
        abort_unless(auth()->user()->canManageEvents(), 403);

        $summary = $collector->collect($timeTrial);

        return back()->with($summary['error'] ? 'error' : 'success', $summary['error']
            ?? "{$summary['files']} new result file(s), {$summary['laps']} lap(s) stored.");
    }

    private function formData(TimeTrialEvent $event): array
    {
        return [
            'event' => $event,
            'tracks' => collect(TimeTrialLap::trackNames())->sort(),
            'classes' => TimeTrialEvent::CAR_CLASSES,
            'servers' => $this->servers(),
        ];
    }

    private function servers()
    {
        return FtpServer::withoutTenantScope()->where('active', true)->orderBy('server_number')->orderBy('name')->get()
            ->filter(fn (FtpServer $server) => $server->supportsRaceGame('acc'))
            ->values();
    }

    private function validated(Request $request, ?TimeTrialEvent $event = null): array
    {
        $serverIds = $this->servers()->pluck('id')->all();

        $validator = validator($request->all(), [
            'title' => ['nullable', 'string', 'max:120'],
            'track' => ['required', Rule::in(array_keys(TimeTrialLap::trackNames()))],
            'car_class' => ['nullable', Rule::in(TimeTrialEvent::CAR_CLASSES)],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ftp_server_id' => ['required', Rule::in($serverIds)],
            'is_published' => ['nullable', 'boolean'],
            'forced_entry_list' => ['nullable', 'boolean'],
        ]);

        $validator->after(function (Validator $v) use ($request, $event) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            [$start, $end] = $this->window($request);
            if ($end->lte($start)) {
                $v->errors()->add('ends_at', 'The end must be after the start.');

                return;
            }

            $overlap = TimeTrialEvent::where('ftp_server_id', $request->ftp_server_id)
                ->when($event, fn ($q) => $q->whereKeyNot($event->id))
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)
                ->first();
            if ($overlap) {
                $v->errors()->add('starts_at', "This overlaps the {$overlap->trackName()} Time Trial on the same server.");
            }
        });

        $data = $validator->validate();
        [$data['starts_at'], $data['ends_at']] = $this->window($request);
        $data['is_published'] = $request->boolean('is_published');
        $data['forced_entry_list'] = $request->boolean('forced_entry_list');

        return $data;
    }

    // The form's times are UK wall clock, like every other admin schedule.
    private function window(Request $request): array
    {
        return [
            Carbon::createFromFormat('Y-m-d\TH:i', $request->starts_at, 'Europe/London')->utc(),
            Carbon::createFromFormat('Y-m-d\TH:i', $request->ends_at, 'Europe/London')->utc(),
        ];
    }
}
