@extends('layouts.admin')

@section('title', 'Import / Export')
@section('page-title', 'Import / Export')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

@php
$accTracks = [
    'Barcelona'       => ['min' => 30, 'max' => 50],
    'Brands Hatch'    => ['min' => 30, 'max' => 35],
    'COTA'            => ['min' => 30, 'max' => 50],
    'Donington'       => ['min' => 30, 'max' => 40],
    'Hungaroring'     => ['min' => 30, 'max' => 40],
    'Imola'           => ['min' => 30, 'max' => 40],
    'Indianapolis'    => ['min' => 30, 'max' => 40],
    'Kyalami'         => ['min' => 30, 'max' => 50],
    'Laguna Seca'     => ['min' => 30, 'max' => 35],
    'Misano'          => ['min' => 30, 'max' => 40],
    'Monza'           => ['min' => 30, 'max' => 50],
    'Mount Panorama'  => ['min' => 30, 'max' => 50],
    'Nürburgring'     => ['min' => 30, 'max' => 50],
    'Nordschleife'    => ['min' => 30, 'max' => 50],
    'Oulton Park'     => ['min' => 30, 'max' => 35],
    'Paul Ricard'     => ['min' => 30, 'max' => 50],
    'Red Bull Ring'   => ['min' => 30, 'max' => 40],
    'Silverstone'     => ['min' => 30, 'max' => 50],
    'Snetterton'      => ['min' => 30, 'max' => 35],
    'Spa'             => ['min' => 30, 'max' => 50],
    'Suzuka'          => ['min' => 30, 'max' => 50],
    'Valencia'        => ['min' => 30, 'max' => 40],
    'Watkins Glen'    => ['min' => 30, 'max' => 50],
    'Zandvoort'       => ['min' => 30, 'max' => 35],
    'Zolder'          => ['min' => 30, 'max' => 35],
];
$gameLabels = ['acc' => 'ACC Console', 'lmu' => 'Le Mans Ultimate', 'iracing' => 'iRacing', 'ac' => 'ACC PC'];

// Built here (not inline in @json() below) since Blade's @json() directive splits its
// argument on every top-level comma — an array literal with more than 2 keys silently
// gets truncated. A bare variable reference has zero commas, so it's always safe.
$ieFormatsForJs = $formats->map(fn($f) => [
    'value' => (string) $f->id, 'label' => $f->name, 'game' => $f->game,
    'server_group' => $f->server_group, 'default_event_tag' => $f->default_event_tag,
]);
$ieServersForJs = $servers->map(fn($s) => ['value' => (string) $s->id, 'label' => $s->name, 'number' => $s->server_number]);
@endphp

<div class="row g-4 align-items-start">

    {{-- ── Export ─────────────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-4">
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-2">
                <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Blank Template</p>
                <p class="text-secondary mb-3" style="font-size:.78rem">
                    A blank CSV with every column, in order, ready to fill in by hand — for a 4-week schedule, say — instead of starting from an existing week's export.
                </p>
            </div>
            <div class="px-4 pb-4">
                <a href="{{ route('admin.races.download-template') }}" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed;font-size:.78rem">
                    Download Template
                </a>
            </div>
        </div>

        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-2">
                <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Export Races to CSV</p>
                <p class="text-secondary mb-3" style="font-size:.78rem">
                    Exports all upcoming races for the selected game (custom races excluded) so you can re-import them below to duplicate the schedule onto a future week.
                </p>
            </div>
            <form action="{{ route('admin.races.export-csv') }}" method="GET" class="px-4 pb-4">
                <div class="mb-3">
                    <label class="form-label">Game</label>
                    <select name="game" required class="form-select">
                        <option value="">— Select —</option>
                        @foreach($gameLabels as $slug => $label)
                        <option value="{{ $slug }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#111827;font-size:.78rem">
                    Download CSV
                </button>
            </form>
        </div>

        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">CSV Format</p>
                <p class="text-secondary mb-0" style="font-size:.78rem">
                    First row must be a header row with these column names (any order — extra columns are ignored).
                    <strong>game</strong> isn't a column — it's the shared Game selector on this page, same for every row in one file.
                    <strong>event_tag</strong> isn't a column either — it's never picked by hand, it always auto-follows the row's <strong>format</strong> one-to-one.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.8rem">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <tr>
                            <th class="fw-bold text-uppercase ps-4" style="font-size:.65rem;letter-spacing:.06em;color:#9ca3af">Column</th>
                            <th class="fw-bold text-uppercase" style="font-size:.65rem;letter-spacing:.06em;color:#9ca3af">Required</th>
                            <th class="fw-bold text-uppercase" style="font-size:.65rem;letter-spacing:.06em;color:#9ca3af">Format</th>
                            <th class="fw-bold text-uppercase pe-4" style="font-size:.65rem;letter-spacing:.06em;color:#9ca3af">Example</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['track', true, 'Exact track name — see the list below', 'Silverstone'],
                            ['date', true, 'YYYY-MM-DD', '2026-09-01'],
                            ['time', true, 'HH:MM, 24h, BST/GMT (real-world scheduled time)', '20:00'],
                            ['format', false, 'Exact format name (a trailing " Race", e.g. "Multiclass Race", is also accepted) — also auto-fills server below when it\'s left blank, and always sets the event tag', 'Daily Race'],
                            ['weather', false, 'dry / wet / mixed / random', 'dry'],
                            ['time_of_day', false, 'HH:MM, 24h — in-game start time', '21:00'],
                            ['ambient_temp', false, 'Whole number, °C', '20'],
                            ['practice_time_multiplier', false, '1-24, "x"/"×" suffix optional, defaults to 1×', '2x'],
                            ['qualifying_time_multiplier', false, '1-24, "x"/"×" suffix optional, defaults to 1×', '2x'],
                            ['race_time_multiplier', false, '1-24, "x"/"×" suffix optional, defaults to 1×', '1x'],
                            ['weather_randomness', false, '0-7, or "random"', 'random'],
                            ['has_practice_server', false, 'on / off', 'on'],
                            ['server', false, 'Server name or number — a leading number like "Server 1" also matches — leave blank to use the server the format auto-fills', '2'],
                            ['sr_requirement', false, 'Whole number 3-9 (minimum Safety Rating), comma or dot decimals accepted — 0 or blank means none', '5'],
                            ['min_rating', false, 'rookie / bronze / silver / gold / platinum / alien / all', 'rookie'],
                            ['max_rating', false, 'rookie / bronze / silver / gold / platinum / alien / all — e.g. rookie for a Rookies Only event', 'all'],
                            ['car_class', false, 'open / GT3 / GT4 / GT2 / TCX / GTC', 'open'],
                            ['car_class_2', false, 'Same values as car_class — fill this in to make the row multiclass (2 classes)', 'GT4'],
                            ['car_class_3', false, 'Same values as car_class — a 3rd class, only used when car_class_2 is also set', ''],
                            ['description', false, 'Free text, shown on the event page', ''],
                        ] as [$col, $required, $format, $example])
                        <tr>
                            <td class="ps-4"><code>{{ $col }}</code></td>
                            <td>
                                @if($required)
                                <span class="badge" style="background:rgba(220,38,38,.1);color:#dc2626;font-size:.65rem">Required</span>
                                @else
                                <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.65rem">Optional</span>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $format }}</td>
                            <td class="pe-4 text-secondary">{{ $example }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                <p class="fw-bold text-uppercase mb-2" style="font-size:.65rem;letter-spacing:.06em;color:#9ca3af">Valid track names</p>
                <p class="text-secondary mb-0" style="font-size:.78rem">{{ implode(' · ', array_keys($accTracks)) }}</p>
            </div>
            <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                <p class="fw-bold text-uppercase mb-2" style="font-size:.65rem;letter-spacing:.06em;color:#9ca3af">Multiclass defaults</p>
                <p class="text-secondary mb-0" style="font-size:.78rem">
                    Filling in car_class_2 turns the row multiclass. Class 1 (car_class) gets a Bronze+ minimum rating by default; every other class is left fully open. Adjust either from the race's own edit page afterwards.
                </p>
            </div>
        </div>
    </div>

    {{-- ── Import ─────────────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-8">
        <div data-import-export-wrap data-ie-import-url="{{ route('admin.races.bulk-import-csv') }}">

            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-2">
                    <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Import Races from CSV</p>
                    <p class="text-secondary mb-0" style="font-size:.78rem">
                        See the "CSV Format" card for the exact columns expected.
                    </p>
                </div>

                <form action="{{ route('admin.races.bulk-store') }}" method="POST" id="ie-form">
                    @csrf

                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                        <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Game</p>
                        <p class="text-secondary mb-3" style="font-size:.75rem">
                            Format and Server are set per row (CSV column, or edited directly in the table below) — see the "CSV Format" card. Event Tag isn't picked at all — it always follows the row's Format automatically.
                        </p>
                        <select name="game" data-ie-game required class="form-select @error('game') is-invalid @enderror" style="max-width:280px">
                            @foreach($gameLabels as $slug => $label)
                            <option value="{{ $slug }}" {{ old('game') === $slug ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                        <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">CSV File</p>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="file" data-ie-file accept=".csv,.txt" class="form-control" style="max-width:320px">
                            <button type="button" data-ie-import
                                    class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed;font-size:.78rem">
                                Upload &amp; Preview
                            </button>
                        </div>
                        <div data-ie-errors class="alert alert-warning mt-3 mb-0" style="display:none;font-size:.78rem"></div>
                    </div>

                    <div data-ie-events-section style="display:none">
                        <div class="px-4 py-3 d-flex align-items-center justify-content-between" style="border-top:1px solid #f3f4f6">
                            <p class="fw-black text-uppercase fst-italic mb-0" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">
                                Events — <span data-ie-count-display>0</span> races
                            </p>
                            <div class="d-flex gap-2">
                                <button type="button" data-ie-download
                                        class="btn btn-sm fw-bold text-uppercase"
                                        style="font-size:.68rem;padding:3px 10px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:6px">
                                    Download CSV
                                </button>
                                <button type="button" data-ie-add-row
                                        class="btn btn-sm fw-bold text-uppercase"
                                        style="font-size:.68rem;padding:3px 10px;background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.3);border-radius:6px">
                                    + Add Row
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="font-size:.875rem">
                                <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                    <tr>
                                        <th class="fw-bold text-uppercase ps-4" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:36px">#</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;min-width:220px">Track</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:120px">Date (BST/GMT)</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:80px">Time</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:150px">Format</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:150px">Server</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:120px">Weather</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:110px">In-game Time</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:90px">Amb. Temp</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:75px">Prac. ×</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:75px">Qual. ×</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:75px">Race ×</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:90px">Min Rating</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:70px">SR</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:90px">Max Rating</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:80px">Car Class</th>
                                        <th class="pe-4" style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody data-ie-tbody></tbody>
                            </table>
                        </div>

                        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                            <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed;font-size:.78rem">
                                Create Events →
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
    window.__ceTracks  = @json($accTracks);
    window.__ieFormats = @json($ieFormatsForJs);
    window.__ieServers = @json($ieServersForJs);
</script>

@endsection
