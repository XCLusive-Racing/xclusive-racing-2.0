<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\PointsScheme;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

// League managers never edit a template directly — they copy one into their own
// league, then edit the copy. Templates (league_id null) are read-only here.
//
// points_map shape: ['drivers' => ['1' => 25, '2' => 18, ...], 'teams' => [...]|null].
// Only positions actually listed score points — position 21 scores 0 if the map
// stops at 20, which is the point: a league fills in as many rows as it races.
class PointsSchemeController extends Controller
{
    public function index(Request $request, League $league)
    {
        abort_unless($request->user()->canManage() || $request->user()->managesLeague($league), 403);

        $schemes = PointsScheme::orderByDesc('is_template')->orderBy('name')->get();

        return view('admin.leagues.points-schemes.index', compact('league', 'schemes'));
    }

    // Cross-league browse — deliberately not tenant-scoped: every league (and XCL's
    // templates) is visible here read-only, so a league can see what scoring format
    // another league runs before building their own. Unlike FTP servers, a points
    // scheme carries no credentials or infrastructure, so sharing it isn't a leak.
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

    public function store(Request $request, League $league)
    {
        $user = $request->user();
        abort_unless($user->canManage() || $user->managesLeague($league), 403);

        $data = $request->validate([
            'name'                => 'required|string|max:150',
            'fastest_lap_points'  => 'nullable|integer|min:0|max:100',
            'pole_points'         => 'nullable|integer|min:0|max:100',
            'csv'                 => 'required|file|mimes:csv,txt|max:512',
            'is_template'         => 'nullable|boolean',
        ]);

        $pointsMap = $this->parseCsv($request->file('csv'));

        $isTemplate = $user->canManage() && $request->boolean('is_template');

        $scheme = PointsScheme::create([
            'league_id'          => $isTemplate ? null : $league->id,
            'name'               => $data['name'],
            'points_map'         => $pointsMap,
            'fastest_lap_points' => $data['fastest_lap_points'] ?? 0,
            'pole_points'        => $data['pole_points'] ?? 0,
            'is_template'        => $isTemplate,
        ]);

        AuditLogger::record($user, $scheme, 'points_scheme.created', ['name' => $scheme->name, 'is_template' => $isTemplate]);

        return redirect()->route('admin.leagues.points-schemes.index', $league)->with('success', $scheme->name . ' created.');
    }

    public function copy(Request $request, League $league, PointsScheme $template)
    {
        abort_unless($request->user()->canManage() || $request->user()->managesLeague($league), 403);
        abort_unless($template->is_template, 404);

        $data = $request->validate(['name' => 'required|string|max:150']);

        $copy = $template->copyFor($league, $data['name']);

        AuditLogger::record($request->user(), $copy, 'points_scheme.copied', ['from' => $template->id]);

        return back()->with('success', 'Copied "' . $template->name . '" as "' . $copy->name . '".');
    }

    // Read-only export, reachable from both the per-league page and the cross-league
    // browse page — same "look, don't leak credentials" reasoning as browse() above.
    public function export(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canManage() || $user->isLeagueManager() || $user->isLeagueSteward(), 403);

        $scheme = PointsScheme::withoutTenantScope()->findOrFail($request->route('scheme'));

        $rows   = [];
        $hasTeam = !empty($scheme->points_map['teams'] ?? null);
        $rows[]  = $hasTeam ? ['position', 'points', 'team_points'] : ['position', 'points'];

        $drivers   = $scheme->points_map['drivers'] ?? [];
        $teams     = $scheme->points_map['teams'] ?? [];
        $positions = array_unique(array_merge(array_map('intval', array_keys($drivers)), array_map('intval', array_keys($teams))));
        sort($positions);

        foreach ($positions as $pos) {
            $row = [$pos, $drivers[$pos] ?? $drivers[(string) $pos] ?? ''];
            if ($hasTeam) {
                $row[] = $teams[$pos] ?? $teams[(string) $pos] ?? '';
            }
            $rows[] = $row;
        }

        $csv = implode("\n", array_map(fn ($r) => implode(',', $r), $rows));
        $filename = \Illuminate\Support\Str::slug($scheme->name) . '-points-scheme.csv';

        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // Positions are read straight from whichever rows the file actually has — a
    // 20-row file scores 1st-20th and leaves everyone after that at zero, a 35-row
    // file scores every position, matching whatever field size the league races.
    private function parseCsv($file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim($h)), fgetcsv($handle, 0, ',', '"', '\\') ?: []);

        $posIdx  = array_search('position', $header, true);
        $ptsIdx  = array_search('points', $header, true);
        $teamIdx = array_search('team_points', $header, true);

        abort_if($posIdx === false || $ptsIdx === false, 422, 'CSV must have "position" and "points" columns.');

        $drivers = [];
        $teams   = [];

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if (!isset($row[$posIdx]) || $row[$posIdx] === '') {
                continue;
            }

            $position = (int) $row[$posIdx];
            if ($position < 1) {
                continue;
            }

            if (isset($row[$ptsIdx]) && $row[$ptsIdx] !== '') {
                $drivers[$position] = (int) $row[$ptsIdx];
            }

            if ($teamIdx !== false && isset($row[$teamIdx]) && $row[$teamIdx] !== '') {
                $teams[$position] = (int) $row[$teamIdx];
            }
        }

        fclose($handle);

        abort_if(empty($drivers), 422, 'CSV had no valid position/points rows.');

        return [
            'drivers' => $drivers,
            'teams'   => $teams ?: null,
        ];
    }
}
