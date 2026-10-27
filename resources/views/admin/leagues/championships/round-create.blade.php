@extends('layouts.admin')

@php
    $accTracks = \App\Models\Race::ACC_TRACKS;
    $sessionDefaults = $championship->settings->sessions;
    $suggestedRoundNumber = old('round_number', $nextRoundNumber);
    // Only the bulk form posts `rounds`; its presence in old input means this
    // page is the redirect back from a rejected bulk submit.
    $bulkFailed = old('rounds') !== null || $errors->has('rounds');

    // Starting values for _round-shared-fields: a new round has nothing of its
    // own yet, so every field starts from the championship's Sessions defaults
    // (round-edit.blade.php builds the same shape from the round's own values).
    $defaults = [
        'ftp_server_id'               => $championship->ftp_server_id,
        'round_type'                  => null,
        'description'                 => null,
        'practice_duration'           => $sessionDefaults->practice_enabled ? $sessionDefaults->practice_length_minutes : '',
        'qualifying_duration'         => $sessionDefaults->qualifying_enabled ? $sessionDefaults->qualifying_length_minutes : '',
        'race_lengths'                => $sessionDefaults->race_length_minutes,
        'session_format_id'           => null,
        'time_of_day'                 => $sessionDefaults->ingame_time_of_day ?? '14:00',
        'ambient_temp'                => $sessionDefaults->ambient_temp,
        // "Randomised" maps onto the round's "Random"; "fixed" has no single
        // dry/wet/mixed value, so it stays unset and the server's defaults apply.
        'weather'                     => $sessionDefaults->weather_mode === 'randomised' ? 'random' : '',
        'weather_randomness'          => null,
        'rain_level'                  => $sessionDefaults->rain_level ?? 0.0,
        'xcl_r_multiplier'            => $sessionDefaults->xcl_r_multiplier,
        'points_multiplier'           => 1.0,
        'pitstop_count'               => $sessionDefaults->pitstop_count,
        'tyre_set_count'              => $sessionDefaults->tyre_set_count,
        'fixed_stop_time'             => $sessionDefaults->min_stop_secs !== null,
        'mandatory_refuelling'        => (bool) $sessionDefaults->mandatory_refuelling,
        'mandatory_tyre_change'       => (bool) ($sessionDefaults->mandatory_tyre_change ?? false),
        'driver_stint_time_mins'      => $championship->settings->format->driver_stint_time_mins ?? null,
        'max_total_driving_time_mins' => $championship->settings->format->max_total_driving_time_mins ?? null,
        'mandatory_driver_swap'       => $championship->settings->format->mandatory_driver_swap ?? false,
    ];
@endphp

@section('title', 'Add Round · ' . $championship->name)
@section('page-title', $league->name . ' · Add Round')

@section('page-actions')
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back to Rounds
    </a>
@endsection

@section('content')

<div class="row g-4">
<div class="col-12 col-xl-9">

<div class="cw-tabs">
    <button type="button" data-rc-mode-btn="single" class="cw-tab">Single Round</button>
    <button type="button" data-rc-mode-btn="bulk" class="cw-tab">Bulk Add Rounds</button>
</div>

{{-- ── Single round ── --}}
<div data-rc-mode-panel="single">
<form action="{{ route('admin.leagues.championships.rounds.store', [$league, $championship]) }}" method="POST">
    @csrf

    <div class="cw-card">
        <div class="cw-card__head">
            <h2 class="cw-card__title">Round {{ $suggestedRoundNumber }}</h2>
            <span class="cw-help mt-0">Title: {{ $championship->roundTitle((int) $suggestedRoundNumber) }}</span>
        </div>

        <div class="cw-section">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="cw-label">Round #</label>
                    <input type="number" name="round_number" value="{{ old('round_number') }}"
                           class="form-control @error('round_number') is-invalid @enderror" min="1" placeholder="Auto">
                    @error('round_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="cw-label">Track <span class="text-danger">*</span></label>
                    @if(in_array($championship->game, ['acc', 'ac'], true))
                    <select name="track" class="form-select @error('track') is-invalid @enderror" required data-track-randomize>
                        <option value="">Select track…</option>
                        <option value="{{ \App\Models\Race::RANDOM_TRACK }}">🎲 Randomize</option>
                        @foreach($accTracks as $trackName)
                        <option value="{{ $trackName }}" @selected(old('track') === $trackName)>{{ $trackName }}</option>
                        @endforeach
                    </select>
                    @else
                    {{-- No verified track list for this game yet, so free text. --}}
                    <input type="text" name="track" value="{{ old('track') }}"
                           class="form-control @error('track') is-invalid @enderror" placeholder="Le Mans" required>
                    @endif
                    @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="cw-label">Date &amp; Time (UK) <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', optional($suggestedScheduledAt)->format('Y-m-d\TH:i')) }}" step="1800"
                           class="form-control @error('scheduled_at') is-invalid @enderror">
                    <div class="cw-help">On the hour or half hour.{{ $suggestedScheduledAt && !old('scheduled_at') ? ' Suggested from the schedule.' : '' }}</div>
                    @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        @include('admin.leagues.championships._round-shared-fields', ['idPrefix' => 'rc', 'defaults' => $defaults])
    </div>

    <div class="cw-actions">
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="cw-btn-secondary text-decoration-none">Cancel</a>
        <button type="submit" class="cw-btn-primary">Add Round</button>
    </div>
</form>
</div>

{{-- ── Bulk add rounds ── --}}
<div data-rc-mode-panel="bulk" style="display:none">
<form action="{{ route('admin.leagues.championships.rounds.bulk-store', [$league, $championship]) }}" method="POST" id="rcb-form">
    @csrf

    <div class="cw-card">
        <div class="cw-card__head">
            <h2 class="cw-card__title">Generate Rounds</h2>
        </div>
        <div class="cw-section">
            <p class="cw-section__note">Dates come from the schedule on the Rounds step. Pick how many, then a track per round.</p>
            <div class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="cw-label" for="rcb-count">Number of Rounds</label>
                    <input type="number" id="rcb-count" value="4" min="1" max="20" class="form-control" style="width:120px">
                </div>
                <button type="button" id="rcb-generate" class="cw-btn-primary">Generate Rows</button>
            </div>
        </div>

        <div id="rcb-table-wrap" style="display:none">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.85rem">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <tr>
                            <th class="cw-label ps-4" style="width:50px">#</th>
                            <th class="cw-label">Track</th>
                            <th class="cw-label" style="width:210px">Date &amp; Time (UK)</th>
                            <th class="pe-4" style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="rcb-tbody"></tbody>
                </table>
            </div>
            <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                <button type="button" id="rcb-add-row" class="cw-btn-add">+ Add Row</button>
            </div>
        </div>

        @include('admin.leagues.championships._round-shared-fields', ['idPrefix' => 'rcb', 'defaults' => $defaults])
    </div>

    @if($bulkFailed && $errors->any())
    {{-- Every error, not just 'rounds': a shared field or rounds.N.* rule can fail
         too, and none of those have an inline slot in this panel. --}}
    <div class="alert alert-danger mb-3" style="font-size:.85rem">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
    @endif

    {{-- Shown by JS once there are rows. --}}
    <div id="rcb-submit-wrap" style="display:none">
        <div class="cw-actions">
            <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="cw-btn-secondary text-decoration-none">Cancel</a>
            <button type="submit" class="cw-btn-primary">Add <span id="rcb-submit-count">0</span> Rounds</button>
        </div>
    </div>
</form>
</div>

</div>
</div>

<script>
(function () {
    // ── Mode toggle ──────────────────────────────────────────────
    var modeBtns   = document.querySelectorAll('[data-rc-mode-btn]');
    var modePanels = document.querySelectorAll('[data-rc-mode-panel]');

    function setMode(mode) {
        modePanels.forEach(function (p) { p.style.display = p.dataset.rcModePanel === mode ? '' : 'none'; });
        modeBtns.forEach(function (b) {
            b.classList.toggle('is-active', b.dataset.rcModeBtn === mode);
        });
    }
    modeBtns.forEach(function (b) { b.addEventListener('click', function () { setMode(b.dataset.rcModeBtn); }); });
    // A rejected bulk submit comes back to this page: reopen Bulk, not Single,
    // or its errors and rows would sit in the hidden panel.
    setMode(@json($bulkFailed ? 'bulk' : 'single'));

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
    var isAcc         = {{ in_array($championship->game, ['acc', 'ac'], true) ? 'true' : 'false' }};

    var rows = @json(array_values(old('rounds', [])));

    // render() rebuilds every row from `rows`, so pull what the manager typed
    // back in first, otherwise adding/removing a row wipes the others.
    function syncFromDom() {
        tbody.querySelectorAll('tr').forEach(function (tr, i) {
            if (!rows[i]) return;
            rows[i].track = tr.querySelector('[name$="[track]"]').value;
            rows[i].scheduled_at = tr.querySelector('[name$="[scheduled_at]"]').value;
        });
    }

    function render() {
        tbody.innerHTML = '';
        rows.forEach(function (row, i) {
            var tr = document.createElement('tr');
            var trackField = isAcc
                ? '<select name="rounds[' + i + '][track]" class="form-select form-select-sm" required data-track-randomize>'
                    + '<option value="">Select track…</option>'
                    + '<option value="{{ \App\Models\Race::RANDOM_TRACK }}">🎲 Randomize</option>'
                    + @json($accTracks).map(function (t) {
                        return '<option value="' + t + '"' + (row.track === t ? ' selected' : '') + '>' + t + '</option>';
                    }).join('')
                    + '</select>'
                : '<input type="text" name="rounds[' + i + '][track]" value="' + (row.track || '') + '" class="form-control form-control-sm" placeholder="Le Mans" required>';

            tr.innerHTML =
                '<td class="ps-4 fw-bold text-secondary">' + (startRoundNum + i) + '</td>' +
                '<td data-track-cell></td>' +
                '<td><input type="datetime-local" name="rounds[' + i + '][scheduled_at]" value="' + (row.scheduled_at || '') + '" step="1800" class="form-control form-control-sm" required></td>' +
                '<td class="pe-4">' +
                    '<button type="button" data-rcb-remove class="cw-btn-remove">✕</button>' +
                '</td>';
            tr.querySelector('[data-track-cell]').innerHTML = trackField;
            tr.querySelector('[data-rcb-remove]').addEventListener('click', function () {
                syncFromDom();
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

    // Next schedule suggestion, not a copy of the last row's date: a copy would
    // claim the same server slot twice and get the whole batch rejected.
    function addRow() {
        syncFromDom();
        rows.push({ track: '', scheduled_at: suggestions[rows.length] || '' });
        render();
    }

    generateBtn.addEventListener('click', generate);
    addRowBtn.addEventListener('click', addRow);
    render();
})();
</script>

@endsection
