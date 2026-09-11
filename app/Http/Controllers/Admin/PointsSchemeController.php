<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\PointsScheme;
use App\Services\AuditLogger;
use App\Services\PointsSchemeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// League managers never edit or delete a template directly — they copy one
// into their own league, then edit the copy. Templates (league_id null) have
// no edit/update/destroy route at all, and PointsSchemePolicy refuses both
// even for an XCL admin, so "never by editing a shared one" holds at the
// route table and the policy layer, not just the UI.
class PointsSchemeController extends Controller
{
    public function index(Request $request, League $league)
    {
        abort_unless($request->user()->canManage() || $request->user()->managesLeague($league), 403);

        $templates = PointsScheme::withoutTenantScope()->where('is_template', true)->orderBy('name')->get();
        $owned     = PointsScheme::where('league_id', $league->id)->where('is_template', false)->orderBy('name')->get();

        return view('admin.leagues.points-schemes.index', compact('league', 'templates', 'owned'));
    }

    // Cross-league browse — deliberately not tenant-scoped: every league (and XCL's
    // templates) is visible here read-only, so a league can see what scoring format
    // another league runs before building their own. A points scheme carries no
    // credentials or infrastructure, so sharing it isn't a leak.
    public function browse(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canManage() || $user->isLeagueManager() || $user->isLeagueSteward(), 403);

        $schemes = PointsScheme::withoutTenantScope()
            ->with('league')
            ->orderByDesc('is_template')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($s) => $s->is_template ? 'XCL Templates' : ($s->league?->name ?? 'Unknown league'));

        return view('admin.points-schemes.browse', compact('schemes'));
    }

    public function create(Request $request, League $league)
    {
        abort_unless($request->user()->canManage() || $request->user()->managesLeague($league), 403);

        $scheme           = new PointsScheme(['type' => 'manual']);
        $steepnessPresets = PointsSchemeGenerator::STEEPNESS_PRESETS;

        return view('admin.leagues.points-schemes.form', [
            'league' => $league, 'scheme' => $scheme, 'steepnessPresets' => $steepnessPresets, 'locked' => false,
        ]);
    }

    public function store(Request $request, League $league)
    {
        $user = $request->user();
        abort_unless($user->canManage() || $user->managesLeague($league), 403);

        $data = $this->validatedInput($request);

        $scheme = new PointsScheme([
            'league_id'          => $league->id,
            'name'               => $data['name'],
            'type'               => $data['type'],
            'description'        => $data['description'],
            'config'             => $data['config'],
            'fastest_lap_points' => $data['fastest_lap_points'],
            'pole_points'        => $data['pole_points'],
            'leading_lap_points' => $data['leading_lap_points'],
            'is_template'        => false,
        ]);

        $scheme->points_table = $this->resolveTable($scheme, $data);
        $scheme->save();

        AuditLogger::record($user, $scheme, 'points_scheme.created', ['name' => $scheme->name, 'type' => $scheme->type]);

        return redirect()->route('admin.leagues.points-schemes.index', $league)->with('success', $scheme->name . ' created.');
    }

    public function edit(Request $request, League $league, PointsScheme $scheme)
    {
        $this->assertOwned($league, $scheme);
        Gate::authorize('update', $scheme);

        $steepnessPresets = PointsSchemeGenerator::STEEPNESS_PRESETS;
        $locked           = $scheme->isLockedByCompletedRounds();

        return view('admin.leagues.points-schemes.form', compact('league', 'scheme', 'steepnessPresets', 'locked'));
    }

    public function update(Request $request, League $league, PointsScheme $scheme)
    {
        $this->assertOwned($league, $scheme);
        Gate::authorize('update', $scheme);

        $user     = $request->user();
        $locked   = $scheme->isLockedByCompletedRounds();
        $override = $locked && $user->canManage() && $request->boolean('override_lock');

        // Points already awarded must never change retroactively because
        // someone adjusted a setting mid season — once a round using this
        // scheme has been scored, only an explicit, audited admin override
        // can push a new table over the top of it.
        if ($locked && !$override) {
            return back()->withInput()->withErrors([
                'scheme' => 'This scheme is locked — a round using it has already been scored. An XCL admin can override this.',
            ]);
        }

        $data = $this->validatedInput($request);

        $scheme->name               = $data['name'];
        $scheme->type               = $data['type'];
        $scheme->description        = $data['description'];
        $scheme->config             = $data['config'];
        $scheme->fastest_lap_points = $data['fastest_lap_points'];
        $scheme->pole_points        = $data['pole_points'];
        $scheme->leading_lap_points = $data['leading_lap_points'];
        $scheme->points_table       = $this->resolveTable($scheme, $data);
        $scheme->save();

        AuditLogger::record(
            $user,
            $scheme,
            $override ? 'points_scheme.locked_edit_override' : 'points_scheme.updated',
            ['name' => $scheme->name]
        );

        return redirect()->route('admin.leagues.points-schemes.index', $league)->with('success', $scheme->name . ' updated.');
    }

    public function destroy(Request $request, League $league, PointsScheme $scheme)
    {
        $this->assertOwned($league, $scheme);
        Gate::authorize('delete', $scheme);

        if ($scheme->championshipsInUse()->isNotEmpty()) {
            return back()->withErrors(['scheme' => 'This scheme is selected by a championship and cannot be deleted — remove it from that championship\'s Scoring step first.']);
        }

        AuditLogger::record($request->user(), $scheme, 'points_scheme.deleted', ['name' => $scheme->name]);
        $scheme->delete();

        return redirect()->route('admin.leagues.points-schemes.index', $league)->with('success', 'Scheme deleted.');
    }

    public function copy(Request $request, League $league, PointsScheme $template)
    {
        abort_unless($request->user()->canManage() || $request->user()->managesLeague($league), 403);
        abort_unless($template->is_template, 404);

        $data = $request->validate(['name' => 'nullable|string|max:150']);

        $copy = $template->copyFor($league, $data['name'] ?? null);

        AuditLogger::record($request->user(), $copy, 'points_scheme.copied', ['from' => $template->id]);

        return redirect()->route('admin.leagues.points-schemes.index', $league)->with('success', 'Copied "' . $template->name . '" as "' . $copy->name . '".');
    }

    private function assertOwned(League $league, PointsScheme $scheme): void
    {
        abort_if($scheme->is_template, 404);
        abort_unless($scheme->league_id === $league->id, 404);
    }

    private function validatedInput(Request $request): array
    {
        $type = $request->input('type', 'manual');

        $rules = [
            'name'               => 'required|string|max:150',
            'description'        => 'nullable|string|max:1000',
            'type'               => 'required|in:manual,linear,curved',
            'fastest_lap_points' => 'nullable|integer|min:0|max:100',
            'pole_points'        => 'nullable|integer|min:0|max:100',
            'leading_lap_points' => 'nullable|integer|min:0|max:100',
        ];

        if ($type === 'manual') {
            $rules['table_json'] = 'required|string';
        }

        if (in_array($type, ['linear', 'curved'], true)) {
            $rules['top']                  = 'required|integer|min:1|max:1000';
            $rules['depth_type']           = 'required|in:fixed,percentage';
            // A percentage that rounds to zero scoring positions on even a
            // small grid is rejected below, after resolving depth — a plain
            // min:1 here only guards the raw input, not the resolved result.
            $rules['depth_value']          = 'required|numeric|min:1';
            $rules['reference_field_size'] = 'nullable|integer|min:1|max:100';
        }

        if ($type === 'linear') {
            $rules['gap']   = 'required|integer|min:0|max:100';
            $rules['floor'] = 'nullable|integer|min:0|max:1000';
        }

        if ($type === 'curved') {
            $rules['floor']     = 'required|integer|min:0|max:1000';
            $rules['steepness'] = 'required|in:' . implode(',', array_keys(PointsSchemeGenerator::STEEPNESS_PRESETS));
        }

        $validated = $request->validate($rules);

        $config = [];
        if (in_array($type, ['linear', 'curved'], true)) {
            $referenceFieldSize = (int) ($validated['reference_field_size'] ?? 30);

            $config = [
                'top'                  => (int) $validated['top'],
                'depth_type'           => $validated['depth_type'],
                'depth_value'          => (float) $validated['depth_value'],
                'reference_field_size' => $referenceFieldSize,
            ];

            if ($type === 'linear') {
                $config['gap']   = (int) $validated['gap'];
                $config['floor'] = isset($validated['floor']) ? (int) $validated['floor'] : null;
            } else {
                $config['floor']     = (int) $validated['floor'];
                $config['steepness'] = $validated['steepness'];
            }

            // "A percentage resolves to at least one scoring position on a
            // small grid" — validated against a deliberately small reference
            // grid (4 cars), independent of whatever reference field size the
            // organiser configured for their own preview.
            if ($config['depth_type'] === 'percentage') {
                $smallGridDepth = PointsSchemeGenerator::resolveDepth($config, 4);
                if ($smallGridDepth < 1) {
                    abort(422, 'This percentage would score zero positions on a small grid — raise it.');
                }
            }
        }

        return [
            'name'               => $validated['name'],
            'description'        => $validated['description'] ?? null,
            'type'               => $type,
            'config'             => $config,
            'fastest_lap_points' => $validated['fastest_lap_points'] ?? 0,
            'pole_points'        => $validated['pole_points'] ?? 0,
            'leading_lap_points' => $validated['leading_lap_points'] ?? 0,
            'table_json'         => $validated['table_json'] ?? null,
        ];
    }

    // Manual: whatever the row-builder posted, sanitised and validated.
    // Linear/curved: regenerated fresh from config every save — points_table
    // is never hand-edited for a generated scheme, only its generator inputs.
    private function resolveTable(PointsScheme $scheme, array $data): array
    {
        if ($scheme->type === 'manual') {
            $table = $this->decodeManualTable($data['table_json'] ?? '[]');
            PointsSchemeGenerator::validateTable($table);

            return $table;
        }

        $referenceFieldSize = (int) ($scheme->config['reference_field_size'] ?? 30);

        return $scheme->regenerateTable($referenceFieldSize);
    }

    private function decodeManualTable(string $json): array
    {
        $rows = json_decode($json, true);
        abort_if(!is_array($rows), 422, 'Invalid points table.');

        $table = [];
        foreach ($rows as $row) {
            $position = (int) ($row['position'] ?? 0);
            if ($position < 1 || !isset($row['points']) || $row['points'] === '') {
                continue;
            }

            $raw = $row['points'];
            $table[$position] = is_numeric($raw) && str_contains((string) $raw, '.') ? (float) $raw : (int) $raw;
        }

        ksort($table);

        return $table;
    }
}
