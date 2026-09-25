<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\Race;
use App\Models\SessionFormat;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

// A league's own race formats (League feedback, NLRL 2026-09) — same "owned by the
// league, managed by its manager" model as points schemes. A format only prefills
// a round's fields on Add/Edit Round, so editing or deleting one never touches a
// round that already used it.
class SessionFormatController extends Controller
{
    public function index(Request $request, League $league)
    {
        $this->authorizeLeague($request, $league);

        $formats = SessionFormat::where('league_id', $league->id)->orderBy('name')->get();

        return view('admin.leagues.session-formats.index', compact('league', 'formats'));
    }

    public function create(Request $request, League $league)
    {
        $this->authorizeLeague($request, $league);

        $format = new SessionFormat(['race_durations' => [25], 'pitstop_count' => 0]);

        return view('admin.leagues.session-formats.form', compact('league', 'format'));
    }

    public function store(Request $request, League $league)
    {
        $this->authorizeLeague($request, $league);

        $format = SessionFormat::create(['league_id' => $league->id] + $this->validatedInput($request));

        AuditLogger::record($request->user(), $format, 'session_format.created', ['name' => $format->name], $league->id);

        return redirect()->route('admin.leagues.session-formats.index', $league)->with('success', $format->name.' created.');
    }

    public function edit(Request $request, League $league, SessionFormat $format)
    {
        $this->authorizeLeague($request, $league, $format);

        return view('admin.leagues.session-formats.form', compact('league', 'format'));
    }

    public function update(Request $request, League $league, SessionFormat $format)
    {
        $this->authorizeLeague($request, $league, $format);

        $before = $format->only([
            'name', 'practice_duration', 'qualifying_duration', 'race_durations', 'pitstop_count', 'fixed_stop_time', 'tyre_set_count',
            'driver_stint_time_mins', 'max_total_driving_time_mins',
        ]);
        $format->update($this->validatedInput($request));

        AuditLogger::record($request->user(), $format, 'session_format.updated', ['before' => $before], $league->id);

        return redirect()->route('admin.leagues.session-formats.index', $league)->with('success', $format->name.' saved.');
    }

    public function destroy(Request $request, League $league, SessionFormat $format)
    {
        $this->authorizeLeague($request, $league, $format);

        $name = $format->name;
        $format->delete();

        AuditLogger::record($request->user(), $format, 'session_format.deleted', ['name' => $name], $league->id);

        return redirect()->route('admin.leagues.session-formats.index', $league)->with('success', $name.' deleted.');
    }

    private function authorizeLeague(Request $request, League $league, ?SessionFormat $format = null): void
    {
        abort_unless($request->user()->canManage() || $request->user()->managesLeague($league), 403);
        abort_if($format && $format->league_id !== $league->id, 404);
    }

    private function validatedInput(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'practice_duration' => 'nullable|integer|min:1|max:999',
            'qualifying_duration' => 'nullable|integer|min:1|max:999',
            'race_lengths' => ['required', 'string', 'regex:'.self::RACE_LENGTHS_REGEX],
            'pitstop_count' => 'nullable|integer|min:0|max:9',
            'fixed_stop_time' => 'nullable|boolean',
            'tyre_set_count' => 'nullable|integer|min:1|max:50',
            'driver_stint_time_mins' => 'nullable|integer|min:1|max:1440',
            'max_total_driving_time_mins' => 'nullable|integer|min:1|max:1440',
        ], self::RACE_LENGTHS_MESSAGES);

        $lengths = Race::parseRaceLengths($data['race_lengths']);
        unset($data['race_lengths']);

        return $data + [
            'race_durations' => $lengths,
            'pitstop_count' => (int) ($data['pitstop_count'] ?? 0),
            'fixed_stop_time' => $request->boolean('fixed_stop_time'),
        ];
    }

    // "25" or "25, 20" — 1 to 999 minutes per race, at most 4 races. Shared with the
    // round forms.
    public const RACE_LENGTHS_REGEX = '/^\s*[1-9]\d{0,2}(\s*,\s*[1-9]\d{0,2}){0,3}\s*$/';

    public const RACE_LENGTHS_MESSAGES = [
        'race_lengths.regex' => 'Enter each race\'s length in minutes, comma-separated — e.g. "25" or "25, 25" (at most 4 races).',
    ];
}
