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
$ieTagsForJs = $tags->map(fn($t) => ['value' => $t->slug, 'label' => $t->name]);
$ieFormatsForJs = $formats->map(fn($f) => [
    'value' => (string) $f->id, 'label' => $f->name, 'game' => $f->game,
    'server_group' => $f->server_group, 'default_event_tag' => $f->default_event_tag,
]);
$ieServersForJs = $servers->map(fn($s) => ['value' => (string) $s->id, 'label' => $s->name, 'number' => $s->server_number]);
@endphp

<div class="row g-4 align-items-start">

    {{-- ── Export ─────────────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-5">
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
                            ['track', true, 'Exact track name', 'Silverstone'],
                            ['date', true, 'YYYY-MM-DD', '2026-09-01'],
                            ['time', true, 'HH:MM, 24h', '20:00'],
                            ['format', false, 'Exact format name — also auto-fills event_tag/server below when they\'re left blank', 'Daily Race'],
                            ['event_tag', false, 'Tag name or slug — leave blank to use the tag the format auto-fills', 'Daily'],
                            ['server', false, 'Server name or number — leave blank to use the server the format auto-fills', '2'],
                            ['weather', false, 'dry / wet / mixed / random', 'dry'],
                            ['time_of_day', false, 'HH:MM, 24h — in-game start time', '21:00'],
                            ['ambient_temp', false, 'Whole number, °C', '20'],
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
        </div>
    </div>

    {{-- ── Import ─────────────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-7">
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
                            Event Tag, Format and Server are set per row (CSV column, auto-detected from the row's Format, or edited directly in the table below) — see the "CSV Format" card.
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
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Track</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:190px">Date &amp; Time (BST/GMT)</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:130px">Event Tag</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:150px">Format</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:150px">Server</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:120px">Weather</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:120px">In-game Time</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:100px">Amb. Temp</th>
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
    window.__ieTags    = @json($ieTagsForJs);
    window.__ieFormats = @json($ieFormatsForJs);
    window.__ieServers = @json($ieServersForJs);
</script>

@endsection
