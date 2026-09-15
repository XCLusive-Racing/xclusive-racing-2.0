@extends('layouts.admin')

@php
    // ACC's real track roster — reused from admin.races.form so a typo here can
    // never produce a track name gPortal's event.json doesn't recognise.
    $accTracks = [
        'Barcelona', 'Brands Hatch', 'COTA', 'Donington', 'Hungaroring', 'Imola',
        'Indianapolis', 'Kyalami', 'Laguna Seca', 'Misano', 'Monza', 'Mount Panorama',
        'Nürburgring', 'Nordschleife', 'Oulton Park', 'Paul Ricard', 'Red Bull Ring',
        'Silverstone', 'Snetterton', 'Spa', 'Suzuka', 'Valencia', 'Watkins Glen',
        'Zandvoort', 'Zolder',
    ];
    $sessionDefaults = $championship->settings->sessions;
    $suggestedRoundNumber = old('round_number', $nextRoundNumber);

    // Resolved starting values for _round-shared-fields -- a brand new round has
    // nothing of its own yet, so every field falls back to the championship's
    // Sessions-step defaults (round-edit.blade.php builds the same shape from
    // the round's own saved values instead).
    $defaults = [
        'ftp_server_id'               => $championship->ftp_server_id,
        'description'                 => null,
        'practice_duration'           => $sessionDefaults->practice_enabled ? $sessionDefaults->practice_length_minutes : '',
        'qualifying_duration'         => $sessionDefaults->qualifying_enabled ? $sessionDefaults->qualifying_length_minutes : '',
        'race_duration'               => $sessionDefaults->race_length_minutes,
        'time_of_day'                 => $sessionDefaults->ingame_time_of_day ?? '14:00',
        'ambient_temp'                => $sessionDefaults->ambient_temp,
        // "Randomised" maps cleanly onto the round's own "Random" option; "fixed"
        // doesn't map onto a single dry/wet/mixed value, so it's left unset here
        // — the round falls through to the server's own event_defaults instead.
        'weather'                     => $sessionDefaults->weather_mode === 'randomised' ? 'random' : '',
        'weather_randomness'          => null,
        'rain_level'                  => $sessionDefaults->rain_level ?? 0.0,
        'xcl_r_multiplier'            => $sessionDefaults->xcl_r_multiplier,
        'pitstop_count'               => $sessionDefaults->pitstop_count,
        'fixed_stop_time'             => $sessionDefaults->min_stop_secs !== null,
        'driver_stint_time_mins'      => $championship->settings->format->driver_stint_time_mins ?? null,
        'max_total_driving_time_mins' => $championship->settings->format->max_total_driving_time_mins ?? null,
        'mandatory_driver_swap'       => $championship->settings->format->mandatory_driver_swap ?? false,
    ];
@endphp

@section('title', 'Add Round — ' . $championship->name)
@section('page-title', $league->name . ' — Add Round')

@section('page-actions')
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back to Rounds
    </a>
@endsection

@section('content')

{{-- Mode toggle — same tab-button pattern as admin/races/form.blade.php's
     Single Event / Bulk Schedule / Custom Race switch. --}}
<div class="d-flex mb-4" style="border-bottom:2px solid #e5e7eb">
    <button type="button" data-rc-mode-btn="single"
            class="btn fw-black text-uppercase rounded-0 border-0 px-4 py-2"
            style="font-size:.76rem;letter-spacing:.08em;margin-bottom:-2px">
        Single Round
    </button>
    <button type="button" data-rc-mode-btn="bulk"
            class="btn fw-black text-uppercase rounded-0 border-0 px-4 py-2"
            style="font-size:.76rem;letter-spacing:.08em;margin-bottom:-2px">
        Bulk Add Rounds
    </button>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- SINGLE ROUND                                                   --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div data-rc-mode-panel="single">
<form action="{{ route('admin.leagues.championships.rounds.store', [$league, $championship]) }}" method="POST">
    @csrf

    <div class="admin-card mb-4">
        <div class="px-4 pt-4 pb-2">
            <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">
                Round {{ $suggestedRoundNumber }}
            </p>
            <p class="text-secondary mb-0" style="font-size:.78rem">
                Titled automatically: <strong>{{ $championship->name }} — Round {{ $suggestedRoundNumber }}</strong>
            </p>
        </div>

        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <div class="row g-3">
                <div class="col-sm-3">
                    <label class="form-label">Round #</label>
                    <input type="number" name="round_number" value="{{ old('round_number') }}"
                           class="form-control @error('round_number') is-invalid @enderror" min="1" placeholder="Auto">
                    @error('round_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-5">
                    <label class="form-label">Track <span class="text-danger">*</span></label>
                    @if($championship->game === 'acc')
                    <select name="track" class="form-select @error('track') is-invalid @enderror" required>
                        <option value="">Select track…</option>
                        @foreach($accTracks as $trackName)
                        <option value="{{ $trackName }}" {{ old('track') === $trackName ? 'selected' : '' }}>{{ $trackName }}</option>
                        @endforeach
                    </select>
                    @else
                    {{-- No verified LMU track list yet — free text for now so this
                         game isn't blocked; ask XCL to supply the exact roster. --}}
                    <input type="text" name="track" value="{{ old('track') }}"
                           class="form-control @error('track') is-invalid @enderror" placeholder="e.g. Le Mans" required>
                    @endif
                    @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Date &amp; Time <span class="text-danger">*</span> <span class="fw-normal text-secondary" style="text-transform:none">(on the hour or half hour, BST)</span></label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', optional($suggestedScheduledAt)->format('Y-m-d\TH:i')) }}" step="1800"
                           class="form-control @error('scheduled_at') is-invalid @enderror">
                    @if($suggestedScheduledAt && !old('scheduled_at'))
                    <div class="form-text" style="font-size:.72rem;color:#9ca3af">Suggested from the championship's schedule — change it just for this round if needed.</div>
                    @endif
                    @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        @include('admin.leagues.championships._round-shared-fields', ['idPrefix' => 'rc', 'defaults' => $defaults])
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Add Round</button>
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
    </div>
</form>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- BULK ADD ROUNDS                                                --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div data-rc-mode-panel="bulk" style="display:none">
<form action="{{ route('admin.leagues.championships.rounds.bulk-store', [$league, $championship]) }}" method="POST" id="rcb-form">
    @csrf

    <div class="admin-card mb-4">
        <div class="px-4 pt-4 pb-3">
            <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Generate Rounds</p>
            <p class="text-secondary mb-3" style="font-size:.78rem">
                Dates are suggested from the championship's own schedule (Basics step) — pick how many rounds to add, then fill in a track per round below.
            </p>
            <div class="row g-3 align-items-end">
                <div class="col-sm-3">
                    <label class="form-label">Number of Rounds</label>
                    <input type="number" id="rcb-count" value="4" min="1" max="20" class="form-control">
                </div>
                <div class="col-sm-3">
                    <button type="button" id="rcb-generate" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                        Generate Rows
                    </button>
                </div>
            </div>
        </div>

        <div id="rcb-table-wrap" style="display:none">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.85rem">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <tr>
                            <th class="fw-bold text-uppercase ps-4" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:50px">#</th>
                            <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Track</th>
                            <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:210px">Date &amp; Time (BST/GMT)</th>
                            <th class="pe-4" style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="rcb-tbody"></tbody>
                </table>
            </div>
            <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                <button type="button" id="rcb-add-row" class="btn btn-sm fw-bold text-uppercase"
                        style="font-size:.68rem;padding:3px 10px;background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.3);border-radius:6px">
                    + Add Row
                </button>
            </div>
        </div>

        @include('admin.leagues.championships._round-shared-fields', ['idPrefix' => 'rcb', 'defaults' => $defaults])
    </div>

    <div class="d-flex gap-2" id="rcb-submit-wrap" style="display:none">
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
            Add <span id="rcb-submit-count">0</span> Rounds
        </button>
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
    </div>
    @error('rounds')<div class="alert alert-danger mt-3">{{ $message }}</div>@enderror
</form>
</div>

<script>
(function () {
    // ── Mode toggle ──────────────────────────────────────────────
    var modeBtns   = document.querySelectorAll('[data-rc-mode-btn]');
    var modePanels = document.querySelectorAll('[data-rc-mode-panel]');

    function setMode(mode) {
        modePanels.forEach(function (p) { p.style.display = p.dataset.rcModePanel === mode ? '' : 'none'; });
        modeBtns.forEach(function (b) {
            var active = b.dataset.rcModeBtn === mode;
            b.style.background = active ? '#f3e8ff' : 'transparent';
            b.style.color      = active ? '#7c3aed' : '#6b7280';
            b.style.borderBottom = active ? '2px solid #7c3aed' : '2px solid transparent';
        });
    }
    modeBtns.forEach(function (b) { b.addEventListener('click', function () { setMode(b.dataset.rcModeBtn); }); });
    setMode('single');

    // ── Bulk row generator ───────────────────────────────────────
    var suggestions   = @json($bulkSuggestions);
    var startRoundNum = {{ $nextRoundNumber }};
    var countInput    = document.getElementById('rcb-count');
    var generateBtn   = document.getElementById('rcb-generate');
    var addRowBtn     = document.getElementById('rcb-add-row');
    var tbody         = document.getElementById('rcb-tbody');
    var tableWrap     = document.getElementById('rcb-table-wrap');
    var submitWrap    = document.getElementById('rcb-submit-wrap');
    var submitCount   = document.getElementById('rcb-submit-count');
    var isAcc         = {{ $championship->game === 'acc' ? 'true' : 'false' }};

    var rows = [];

    function render() {
        tbody.innerHTML = '';
        rows.forEach(function (row, i) {
            var tr = document.createElement('tr');
            var trackField = isAcc
                ? '<select name="rounds[' + i + '][track]" class="form-select form-select-sm" required>'
                    + '<option value="">Select track…</option>'
                    + @json($accTracks).map(function (t) {
                        return '<option value="' + t + '"' + (row.track === t ? ' selected' : '') + '>' + t + '</option>';
                    }).join('')
                    + '</select>'
                : '<input type="text" name="rounds[' + i + '][track]" value="' + (row.track || '') + '" class="form-control form-control-sm" placeholder="e.g. Le Mans" required>';

            tr.innerHTML =
                '<td class="ps-4 fw-bold text-secondary">' + (startRoundNum + i) + '</td>' +
                '<td data-track-cell></td>' +
                '<td><input type="datetime-local" name="rounds[' + i + '][scheduled_at]" value="' + (row.scheduled_at || '') + '" step="1800" class="form-control form-control-sm" required></td>' +
                '<td class="pe-4">' +
                    '<button type="button" data-rcb-remove class="btn btn-sm d-flex align-items-center justify-content-center" ' +
                    'style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;width:28px;height:28px;padding:0;font-size:.85rem">✕</button>' +
                '</td>';
            tr.querySelector('[data-track-cell]').innerHTML = trackField;
            tr.querySelector('[data-rcb-remove]').addEventListener('click', function () {
                rows.splice(i, 1);
                render();
            });
            tbody.appendChild(tr);
        });

        var has = rows.length > 0;
        tableWrap.style.display  = has ? '' : 'none';
        submitWrap.style.display = has ? '' : 'none';
        submitCount.textContent  = rows.length;
    }

    function generate() {
        var n = Math.min(Math.max(parseInt(countInput.value) || 1, 1), 20);
        rows = [];
        for (var i = 0; i < n; i++) {
            rows.push({ track: '', scheduled_at: suggestions[i] || '' });
        }
        render();
    }

    function addRow() {
        var last = rows[rows.length - 1];
        rows.push({ track: '', scheduled_at: last ? last.scheduled_at : (suggestions[rows.length] || '') });
        render();
    }

    generateBtn.addEventListener('click', generate);
    addRowBtn.addEventListener('click', addRow);
})();
</script>

@endsection
