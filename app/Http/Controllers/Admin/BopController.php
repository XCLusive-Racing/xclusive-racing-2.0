<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bop;
use App\Models\FtpServer;
use App\Services\AccServerConfigService;
use App\Services\FtpService;
use Illuminate\Http\Request;

class BopController extends Controller
{
    public function index()
    {
        $bops      = Bop::orderBy('game')->orderBy('car_model')->get()->groupBy('game');
        $games     = Bop::games();
        $ftpServers = FtpServer::where('active', true)->orderBy('name')->get();
        return view('admin.bops.index', compact('bops', 'games', 'ftpServers'));
    }

    public function create()
    {
        $games = Bop::games();
        return view('admin.bops.create', compact('games'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'game'       => 'required|in:acc,lmu,iracing,ac',
            'car_model'  => 'required|string|max:100',
            'track'      => 'nullable|string|max:100',
            'ballast_kg' => 'required|integer|min:-100|max:200',
            'restrictor' => 'required|integer|min:0|max:20',
            'notes'      => 'nullable|string|max:500',
        ]);

        Bop::create($request->only('game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'));

        return redirect()->route('admin.bops.index')->with('success', 'BOP entry created.');
    }

    public function edit(Bop $bop)
    {
        $games = Bop::games();
        return view('admin.bops.edit', compact('bop', 'games'));
    }

    public function update(Request $request, Bop $bop)
    {
        $request->validate([
            'game'       => 'required|in:acc,lmu,iracing,ac',
            'car_model'  => 'required|string|max:100',
            'track'      => 'nullable|string|max:100',
            'ballast_kg' => 'required|integer|min:-100|max:200',
            'restrictor' => 'required|integer|min:0|max:20',
            'notes'      => 'nullable|string|max:500',
        ]);

        $bop->update($request->only('game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'));

        return redirect()->route('admin.bops.index')->with('success', 'BOP entry updated.');
    }

    public function destroy(Bop $bop)
    {
        $bop->delete();
        return redirect()->route('admin.bops.index')->with('success', 'BOP entry deleted.');
    }

    public function pushBop(Request $request, AccServerConfigService $config)
    {
        $request->validate([
            'server_id' => 'required|exists:ftp_servers,id',
            'game'      => 'required|in:acc,lmu,iracing,ac',
        ]);

        $game   = $request->input('game');
        $count  = Bop::where('game', $game)->count();

        if ($count === 0) {
            return back()->with('push_error', 'No BOP entries found for ' . strtoupper($game) . '.');
        }

        $json    = json_encode($config->bop($game), JSON_PRETTY_PRINT);
        $server  = FtpServer::findOrFail($request->server_id);
        $ftp     = new FtpService();

        if (! $ftp->connect($server)) {
            return back()->with('push_error', 'Could not connect to ' . $server->host . '.');
        }

        $cfgPath = rtrim($server->cfg_path ?: $server->path, '/');
        $ok      = $ftp->uploadFile($cfgPath . '/bop.json', $json);
        $ftp->disconnect();

        if (! $ok) {
            return back()->with('push_error', 'Failed to upload bop.json to ' . $server->name . '.');
        }

        $mapped  = json_decode($json, true)['entries'];
        $skipped = $count - count($mapped);
        $msg     = 'bop.json pushed to ' . $server->name . ' — ' . count($mapped) . ' entries.';
        if ($skipped > 0) {
            $msg .= ' ' . $skipped . ' entries skipped (unknown car model ID).';
        }

        return back()->with('push_success', $msg);
    }

    public function import(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json,txt|max:4096',
            'game'      => 'required|in:acc,lmu,iracing,ac',
            'mode'      => 'required|in:merge,replace',
        ]);

        $content = file_get_contents($request->file('json_file')->getRealPath());
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('import_error', 'Invalid JSON: ' . json_last_error_msg());
        }

        $entries = isset($decoded['entries']) ? $decoded['entries'] : $decoded;

        if (!is_array($entries) || empty($entries)) {
            return back()->with('import_error', 'JSON must contain an array of BOP entries.');
        }

        set_time_limit(120);

        $game    = $request->input('game');
        $mode    = $request->input('mode');
        $now     = now();
        $rows    = [];
        $skipped = 0;

        foreach ($entries as $entry) {
            if (!is_array($entry)) { $skipped++; continue; }

            $carModel = $entry['car_model'] ?? $entry['carModel'] ?? null;

            if (is_int($carModel)) {
                $carModel = Bop::carModels()[$carModel] ?? ('Car #' . $carModel);
            }

            if (!$carModel) { $skipped++; continue; }

            $rows[] = [
                'game'       => $game,
                'car_model'  => $carModel,
                'track'      => ($entry['track'] ?? null) ?: null,
                'ballast_kg' => (int) ($entry['ballast_kg'] ?? $entry['ballastKg'] ?? 0),
                'restrictor' => (int) ($entry['restrictor'] ?? 0),
                'notes'      => ($entry['notes'] ?? null) ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($mode === 'replace') {
            Bop::where('game', $game)->delete();
            $created = count($rows);
            $updated = 0;
        } else {
            // Merge: delete only rows that will be re-inserted, keep the rest
            $existingKeys = Bop::where('game', $game)
                ->get(['id', 'car_model', 'track'])
                ->keyBy(fn($b) => $b->car_model . '|' . ($b->track ?? ''));

            $idsToDelete = [];
            $newCount    = 0;

            foreach ($rows as $row) {
                $key = $row['car_model'] . '|' . ($row['track'] ?? '');
                if (isset($existingKeys[$key])) {
                    $idsToDelete[] = $existingKeys[$key]->id;
                } else {
                    $newCount++;
                }
            }

            if ($idsToDelete) {
                foreach (array_chunk($idsToDelete, 500) as $chunk) {
                    Bop::whereIn('id', $chunk)->delete();
                }
            }

            $updated = count($idsToDelete);
            $created = $newCount;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Bop::insert($chunk);
        }

        $parts = [];
        if ($created) $parts[] = "{$created} created";
        if ($updated) $parts[] = "{$updated} updated";
        if ($skipped) $parts[] = "{$skipped} skipped";

        return back()->with('success', 'Import complete: ' . implode(', ', $parts) . '.');
    }
}