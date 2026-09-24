<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bop;
use App\Models\FtpServer;
use App\Services\AccCarCatalog;
use App\Services\AccServerConfigService;
use App\Services\FtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BopController extends Controller
{
    public function index()
    {
        $bops = Bop::orderBy('game')->orderBy('car_model')->get()->groupBy('game');
        $games = Bop::games();
        $ftpServers = FtpServer::where('active', true)->orderBy('name')->get();

        return view('admin.bops.index', compact('bops', 'games', 'ftpServers'));
    }

    public function create()
    {
        $games = Bop::games();
        $carCatalog = $this->carCatalog();

        return view('admin.bops.create', compact('games', 'carCatalog'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'game' => 'required|in:acc,lmu,iracing,ac',
            'car_model' => ['required', 'string', 'max:100', ...$this->carModelRules($request)],
            'track' => 'nullable|string|max:100',
            'ballast_kg' => 'required|integer|min:-100|max:200',
            'restrictor' => 'required|integer|min:0|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        Bop::create($request->only('game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'));

        return redirect()->route('admin.bops.index')->with('success', 'BOP entry created.');
    }

    // ACC console/PC BOP rows are matched to a car model ID by exact name at push time
    // (AccCarCatalog::id()), so a free-typed name that isn't in that game's catalogue
    // would just be silently skipped. Other games have no catalogue and stay free text.
    private function carModelRules(Request $request): array
    {
        $game = (string) $request->input('game');

        return AccCarCatalog::supports($game)
            ? [Rule::in(AccCarCatalog::cars($game))]
            : [];
    }

    /** game => car names, for the car model suggestions on the BOP form. */
    private function carCatalog(): array
    {
        return collect(array_keys(Bop::games()))
            ->filter(fn ($game) => AccCarCatalog::supports($game))
            ->mapWithKeys(fn ($game) => [$game => array_values(AccCarCatalog::cars($game))])
            ->all();
    }

    public function edit(Bop $bop)
    {
        $games = Bop::games();
        $carCatalog = $this->carCatalog();

        return view('admin.bops.edit', compact('bop', 'games', 'carCatalog'));
    }

    public function update(Request $request, Bop $bop)
    {
        $request->validate([
            'game' => 'required|in:acc,lmu,iracing,ac',
            'car_model' => ['required', 'string', 'max:100', ...$this->carModelRules($request)],
            'track' => 'nullable|string|max:100',
            'ballast_kg' => 'required|integer|min:-100|max:200',
            'restrictor' => 'required|integer|min:0|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $bop->update($request->only('game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'));

        return redirect()->route('admin.bops.index')->with('success', 'BOP entry updated.');
    }

    public function destroy(Bop $bop)
    {
        $bop->delete();

        return redirect()->route('admin.bops.index')->with('success', 'BOP entry deleted.');
    }

    public function toggle(Bop $bop)
    {
        $bop->update(['active' => ! $bop->active]);

        return back()->with('success', $bop->car_model.' marked as '.($bop->active ? 'active' : 'inactive').'.');
    }

    public function toggleGame(Request $request)
    {
        $request->validate(['game' => 'required|in:acc,lmu,iracing,ac', 'active' => 'required|boolean']);
        Bop::where('game', $request->game)->update(['active' => $request->boolean('active')]);
        $label = $request->boolean('active') ? 'activated' : 'deactivated';

        return back()->with('success', (Bop::games()[$request->game] ?? $request->game).' BOPs '.$label.'.');
    }

    public function toggleAll(Request $request)
    {
        $request->validate(['active' => 'required|boolean']);
        Bop::query()->update(['active' => $request->boolean('active')]);
        $label = $request->boolean('active') ? 'activated' : 'deactivated';

        return back()->with('success', 'All BOPs '.$label.'.');
    }

    public function pushBop(Request $request, AccServerConfigService $config)
    {
        $request->validate([
            'server_id' => 'required|exists:ftp_servers,id',
            'game' => 'required|in:acc,lmu,iracing,ac',
        ]);

        $game = $request->input('game');
        $count = Bop::where('game', $game)->where('active', true)->count();

        if ($count === 0) {
            return back()->with('push_error', 'No BOP entries found for '.strtoupper($game).'.');
        }

        $json = json_encode($config->bop($game), JSON_PRETTY_PRINT);
        if ($json === false) {
            return back()->with('push_error', 'Invalid JSON in bop.json: '.json_last_error_msg());
        }

        $server = FtpServer::findOrFail($request->server_id);
        if (! $server->supportsRaceGame($game)) {
            return back()->with('push_error', FtpServer::ERR_WRONG_PLATFORM);
        }
        $ftp = new FtpService;

        if (! $ftp->connect($server)) {
            return back()->with('push_error', 'Could not connect to '.$server->host.'.');
        }

        $cfgPath = rtrim($server->cfg_path ?: $server->path, '/');
        $ok = $ftp->uploadFile($cfgPath.'/bop.json', $json);
        $ftp->disconnect();

        if (! $ok) {
            return back()->with('push_error', 'Failed to upload bop.json to '.$server->name.'.');
        }

        $mapped = json_decode($json, true)['entries'];
        $skipped = $count - count($mapped);
        $msg = 'bop.json pushed to '.$server->name.' — '.count($mapped).' entries.';
        if ($skipped > 0) {
            $msg .= ' '.$skipped.' entries skipped (unknown car model ID).';
        }

        return back()->with('push_success', $msg);
    }

    public function import(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json,txt|max:4096',
            'game' => 'required|in:acc,lmu,iracing,ac',
            'mode' => 'required|in:merge,replace',
        ]);

        $content = file_get_contents($request->file('json_file')->getRealPath());
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('import_error', 'Invalid JSON: '.json_last_error_msg());
        }

        $entries = isset($decoded['entries']) ? $decoded['entries'] : $decoded;

        if (! is_array($entries) || empty($entries)) {
            return back()->with('import_error', 'JSON must contain an array of BOP entries.');
        }

        set_time_limit(120);

        $game = $request->input('game');
        $mode = $request->input('mode');
        $now = now();
        $rows = [];
        $skipped = 0;
        $unknownCars = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                $skipped++;

                continue;
            }

            $carModel = $entry['car_model'] ?? $entry['carModel'] ?? null;

            if (is_int($carModel)) {
                $carModel = AccCarCatalog::name($carModel, $game);
            }

            if (! $carModel) {
                $skipped++;

                continue;
            }

            // Same rule as the BOP form (carModelRules()): an ACC name outside the game's
            // catalogue can never be pushed, so it isn't stored either.
            if (AccCarCatalog::supports($game) && AccCarCatalog::id($carModel, $game) === null) {
                $unknownCars[$carModel] = true;
                $skipped++;

                continue;
            }

            $rows[] = [
                'game' => $game,
                'car_model' => $carModel,
                'track' => ($entry['track'] ?? null) ?: null,
                'ballast_kg' => (int) ($entry['ballast_kg'] ?? $entry['ballastKg'] ?? 0),
                'restrictor' => (int) ($entry['restrictor'] ?? 0),
                'notes' => ($entry['notes'] ?? null) ?: null,
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
                ->keyBy(fn ($b) => $b->car_model.'|'.($b->track ?? ''));

            $idsToDelete = [];
            $newCount = 0;

            foreach ($rows as $row) {
                $key = $row['car_model'].'|'.($row['track'] ?? '');
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
        if ($created) {
            $parts[] = "{$created} created";
        }
        if ($updated) {
            $parts[] = "{$updated} updated";
        }
        if ($skipped) {
            $parts[] = "{$skipped} skipped";
        }

        $message = 'Import complete: '.implode(', ', $parts).'.';
        if ($unknownCars) {
            $message .= ' Unknown car names (not in the '.Bop::games()[$game].' car list): '.implode(', ', array_keys($unknownCars)).'.';
        }

        return back()->with('success', $message);
    }
}
