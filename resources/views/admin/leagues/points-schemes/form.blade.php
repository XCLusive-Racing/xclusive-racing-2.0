@extends('layouts.admin')

@php
    $isEdit = $scheme->exists;
    $config = $scheme->config ?? [];
@endphp

@section('title', ($isEdit ? 'Edit' : 'New') . ' Points Scheme — ' . $league->name)
@section('page-title', $league->name . ' — ' . ($isEdit ? 'Edit ' . $scheme->name : 'New Points Scheme'))

@section('page-actions')
    <a href="{{ route('admin.leagues.points-schemes.index', $league) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back to Points Schemes
    </a>
@endsection

@section('content')

<style>
    .ps-editor { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start; }
    @media (max-width: 900px) { .ps-editor { grid-template-columns: 1fr; } }
    .ps-type-panel { display: none; }
    input[type="radio"][name="type"]#ps-type-manual:checked ~ .ps-panels .ps-panel-manual,
    input[type="radio"][name="type"]#ps-type-linear:checked ~ .ps-panels .ps-panel-linear,
    input[type="radio"][name="type"]#ps-type-curved:checked ~ .ps-panels .ps-panel-curved { display: block; }
    .ps-type-tabs { display: flex; gap: .5rem; }
    .ps-type-tab {
        flex: 1; text-align: center; padding: .6rem .5rem; border: 1px solid #e5e7eb; border-radius: 8px;
        font-weight: 700; text-transform: uppercase; font-size: .74rem; letter-spacing: .04em; color: #6b7280;
        cursor: pointer; user-select: none;
    }
    input[type="radio"][name="type"] { position: absolute; opacity: 0; pointer-events: none; }
    #ps-type-manual:checked ~ .ps-type-tabs label[for="ps-type-manual"],
    #ps-type-linear:checked ~ .ps-type-tabs label[for="ps-type-linear"],
    #ps-type-curved:checked ~ .ps-type-tabs label[for="ps-type-curved"] { background: #f3e8ff; color: #7c3aed; border-color: #7c3aed; }
    .ps-preview-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .ps-preview-table th, .ps-preview-table td { padding: .35rem .5rem; text-align: right; border-bottom: 1px solid #f3f4f6; }
    .ps-preview-table th:first-child, .ps-preview-table td:first-child { text-align: left; color: #6b7280; }
    .ps-manual-row { display: flex; gap: .5rem; margin-bottom: .5rem; align-items: center; }
</style>

@if($locked)
<div class="admin-card mb-4" style="border-color:#fca5a5">
    <div class="px-4 py-3" style="background:#fef2f2">
        <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.78rem;color:#b91c1c">Locked</p>
        <p class="mb-0" style="font-size:.85rem;color:#7f1d1d">
            A round scored with this scheme already exists in a championship using it. Points already awarded must never
            change retroactively, so edits are blocked here
            @can('update', $scheme) unless an XCL admin explicitly overrides it below @endcan.
        </p>
    </div>
</div>
@endif

<form action="{{ $isEdit ? route('admin.leagues.points-schemes.update', [$league, $scheme]) : route('admin.leagues.points-schemes.store', $league) }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="admin-card mb-4">
        <div class="px-4 py-3">
            <div class="row g-3 mb-3">
                <div class="col-sm-8">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $scheme->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $scheme->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <input type="radio" name="type" id="ps-type-manual" value="manual" {{ old('type', $scheme->type) === 'manual' ? 'checked' : '' }}>
            <input type="radio" name="type" id="ps-type-linear" value="linear" {{ old('type', $scheme->type) === 'linear' ? 'checked' : '' }}>
            <input type="radio" name="type" id="ps-type-curved" value="curved" {{ old('type', $scheme->type) === 'curved' ? 'checked' : '' }}>

            <div class="ps-type-tabs mb-3">
                <label class="ps-type-tab" for="ps-type-manual">Manual</label>
                <label class="ps-type-tab" for="ps-type-linear">Linear</label>
                <label class="ps-type-tab" for="ps-type-curved">Curved</label>
            </div>

            <div class="ps-panels">
                <div class="ps-editor">
                    <div>
                        {{-- Manual --}}
                        <div class="ps-type-panel ps-panel-manual">
                            <p class="text-secondary mb-2" style="font-size:.8rem">Enter a points value for each finishing position you want to score. Positions left out score 0.</p>
                            <div data-manual-rows></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-add-row>+ Add Position</button>
                            <input type="hidden" name="table_json" data-table-json value="">
                        </div>

                        {{-- Linear --}}
                        <div class="ps-type-panel ps-panel-linear">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Top Points <span class="text-danger">*</span></label>
                                    <input type="number" name="top" data-gen-top value="{{ old('top', $config['top'] ?? 25) }}" class="form-control @error('top') is-invalid @enderror" min="1">
                                    @error('top')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Gap Between Positions <span class="text-danger">*</span></label>
                                    <input type="number" name="gap" data-gen-gap value="{{ old('gap', $config['gap'] ?? 1) }}" class="form-control @error('gap') is-invalid @enderror" min="0">
                                    @error('gap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Floor <span class="fw-normal text-secondary" style="text-transform:none">(optional — points never drop below this; scoring stops once reached)</span></label>
                                <input type="number" name="floor" data-gen-floor value="{{ old('floor', $config['floor'] ?? 0) }}" class="form-control @error('floor') is-invalid @enderror" min="0" style="max-width:160px">
                                @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @include('admin.leagues.points-schemes._depth-fields', ['config' => $config])
                        </div>

                        {{-- Curved --}}
                        <div class="ps-type-panel ps-panel-curved">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Top Points <span class="text-danger">*</span></label>
                                    <input type="number" name="top" data-gen-top value="{{ old('top', $config['top'] ?? 30) }}" class="form-control @error('top') is-invalid @enderror" min="1">
                                    @error('top')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Floor <span class="text-danger">*</span></label>
                                    <input type="number" name="floor" data-gen-floor value="{{ old('floor', $config['floor'] ?? 1) }}" class="form-control @error('floor') is-invalid @enderror" min="0">
                                    @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Steepness</label>
                                <select name="steepness" data-gen-steepness class="form-select @error('steepness') is-invalid @enderror" style="max-width:220px">
                                    @foreach($steepnessPresets as $key => $preset)
                                    <option value="{{ $key }}" {{ old('steepness', $config['steepness'] ?? 'standard') === $key ? 'selected' : '' }}>{{ $preset['label'] }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text" style="font-size:.72rem;color:#9ca3af">How much winning is worth — steeper means the front of the field is paid much more than the back.</div>
                                @error('steepness')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @include('admin.leagues.points-schemes._depth-fields', ['config' => $config])
                        </div>
                    </div>

                    {{-- Live preview --}}
                    <div class="admin-card" style="background:#f9fafb">
                        <div class="px-3 py-3">
                            <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Resolved Table</p>
                            <div style="max-height:360px;overflow-y:auto">
                                <table class="ps-preview-table">
                                    <thead><tr><th>Position</th><th>Points</th></tr></thead>
                                    <tbody data-preview-body></tbody>
                                </table>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-3" style="font-size:.8rem">
                                <label class="text-secondary" for="ps-season-rounds">Rounds in season</label>
                                <input type="number" id="ps-season-rounds" value="10" min="1" max="40" class="form-control form-control-sm" style="width:70px">
                            </div>
                            <p class="mb-0 mt-2" style="font-size:.82rem">
                                Max per round: <strong data-preview-max-round>0</strong> ·
                                Season total for the winner: <strong data-preview-season-total>0</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Bonus Points <span class="fw-normal" style="text-transform:none">(any scheme type)</span></p>
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label">Fastest Lap</label>
                    <input type="number" name="fastest_lap_points" data-bonus-fl value="{{ old('fastest_lap_points', $scheme->fastest_lap_points ?? 0) }}" class="form-control @error('fastest_lap_points') is-invalid @enderror" min="0">
                    @error('fastest_lap_points')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Pole Position</label>
                    <input type="number" name="pole_points" data-bonus-pole value="{{ old('pole_points', $scheme->pole_points ?? 0) }}" class="form-control @error('pole_points') is-invalid @enderror" min="0">
                    @error('pole_points')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Leading a Lap <span class="fw-normal text-secondary" style="text-transform:none">(where used)</span></label>
                    <input type="number" name="leading_lap_points" data-bonus-lead value="{{ old('leading_lap_points', $scheme->leading_lap_points ?? 0) }}" class="form-control @error('leading_lap_points') is-invalid @enderror" min="0">
                    @error('leading_lap_points')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        @if($locked)
        @can('update', $scheme)
        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="override_lock" value="1" id="ps-override">
                <label class="form-check-label fw-bold text-dark" for="ps-override" style="font-size:.85rem">
                    Override the lock and save anyway
                </label>
            </div>
            <div class="form-text" style="font-size:.72rem;color:#9ca3af">Recorded in the audit log. Any round already scored with the old table keeps its already-awarded points untouched — this only changes the table going forward.</div>
        </div>
        @endcan
        @endif
    </div>

    <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
        {{ $isEdit ? 'Save Changes' : 'Create Scheme' }}
    </button>
</form>

@push('scripts')
<script>
(function () {
    var STEEPNESS = @json(collect($steepnessPresets)->map(fn ($p) => $p['exponent']));
    var existingTable = @json(collect($scheme->points_table ?? [])->map(fn ($points, $pos) => ['position' => (int) $pos, 'points' => $points])->values());

    // --- Manual row builder (same pattern as the Format step's class builder) ---
    var manualRows = document.querySelector('[data-manual-rows]');
    var tableJsonInput = document.querySelector('[data-table-json]');
    var addRowBtn = document.querySelector('[data-add-row]');

    function addManualRow(position, points) {
        var row = document.createElement('div');
        row.className = 'ps-manual-row';
        row.setAttribute('data-manual-row', '');
        row.innerHTML =
            '<span class="text-secondary" style="width:60px;font-size:.8rem">Pos.</span>' +
            '<input type="number" min="1" class="form-control form-control-sm" style="max-width:80px" data-row-position>' +
            '<span class="text-secondary" style="font-size:.8rem">Points</span>' +
            '<input type="number" step="0.5" class="form-control form-control-sm" style="max-width:100px" data-row-points>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary" data-remove-row>&times;</button>';
        row.querySelector('[data-row-position]').value = position ?? '';
        row.querySelector('[data-row-points]').value = points ?? '';
        manualRows.appendChild(row);
    }

    (existingTable.length ? existingTable : [{ position: 1, points: 25 }]).forEach(function (r) {
        addManualRow(r.position, r.points);
    });

    addRowBtn.addEventListener('click', function () {
        var rows = manualRows.querySelectorAll('[data-manual-row]');
        var last = rows[rows.length - 1];
        var nextPos = last ? (parseInt(last.querySelector('[data-row-position]').value, 10) || 0) + 1 : 1;
        addManualRow(nextPos, '');
        renderPreview();
    });

    manualRows.addEventListener('click', function (e) {
        if (e.target.matches('[data-remove-row]')) {
            e.target.closest('[data-manual-row]').remove();
            renderPreview();
        }
    });

    manualRows.addEventListener('input', renderPreview);

    function manualTable() {
        var out = [];
        manualRows.querySelectorAll('[data-manual-row]').forEach(function (row) {
            var pos = parseInt(row.querySelector('[data-row-position]').value, 10);
            var pts = row.querySelector('[data-row-points]').value;
            if (pos >= 1 && pts !== '') out.push([pos, parseFloat(pts)]);
        });
        out.sort(function (a, b) { return a[0] - b[0]; });
        return out;
    }

    // --- Generator math, mirroring App\Services\PointsSchemeGenerator ---
    function linearTable(top, gap, depth, floor) {
        floor = floor || 0;
        var out = [];
        for (var i = 0; i < depth; i++) {
            var pos = i + 1;
            var pts = top - gap * i;
            if (pts <= floor) { out.push([pos, floor]); break; }
            out.push([pos, pts]);
        }
        return out;
    }

    function curvedTable(top, floor, depth, exponent) {
        var out = [];
        var prev = null;
        for (var pos = 1; pos <= depth; pos++) {
            var pts;
            if (depth === 1) {
                pts = top;
            } else {
                var ratio = (depth - pos) / (depth - 1);
                pts = Math.round(floor + (top - floor) * Math.pow(ratio, exponent));
            }
            if (prev !== null && pts > prev) pts = prev;
            out.push([pos, pts]);
            prev = pts;
        }
        return out;
    }

    function resolveDepth(depthType, depthValue, referenceFieldSize) {
        var depth = depthType === 'percentage'
            ? Math.ceil((depthValue / 100) * Math.max(referenceFieldSize, 1))
            : depthValue;
        return Math.max(1, depth || 1);
    }

    function currentType() {
        if (document.getElementById('ps-type-linear').checked) return 'linear';
        if (document.getElementById('ps-type-curved').checked) return 'curved';
        return 'manual';
    }

    function currentTable() {
        var type = currentType();
        var panel = document.querySelector(type === 'linear' ? '.ps-panel-linear' : type === 'curved' ? '.ps-panel-curved' : '.ps-panel-manual');

        if (type === 'manual') return manualTable();

        var top = parseFloat(panel.querySelector('[data-gen-top]').value) || 0;
        var floor = parseFloat(panel.querySelector('[data-gen-floor]').value) || 0;
        var depthType = panel.querySelector('[data-depth-type]').value;
        var depthValue = parseFloat(panel.querySelector('[data-depth-value]').value) || 1;
        var refSize = parseFloat(panel.querySelector('[data-reference-field-size]').value) || 30;
        var depth = resolveDepth(depthType, depthValue, refSize);

        if (type === 'linear') {
            var gap = parseFloat(panel.querySelector('[data-gen-gap]').value) || 0;
            return linearTable(top, gap, depth, floor);
        }

        var steepness = panel.querySelector('[data-gen-steepness]').value;
        return curvedTable(top, floor, depth, STEEPNESS[steepness] || STEEPNESS.standard);
    }

    // The linear and curved panels share field names (top, floor, depth_type,
    // depth_value, reference_field_size) since only one panel is ever
    // meaningful at once — but both stay in the DOM for the CSS-only tab
    // switch to work, so whichever panel isn't active must be disabled or
    // both would submit and collide on the wire.
    function syncActivePanel() {
        var type = currentType();
        document.querySelectorAll('.ps-type-panel').forEach(function (panel) {
            var active = panel.classList.contains('ps-panel-' + type);
            panel.querySelectorAll('input, select').forEach(function (el) { el.disabled = !active; });
        });
    }

    function renderPreview() {
        syncActivePanel();
        var table = currentTable();
        var body = document.querySelector('[data-preview-body]');
        body.innerHTML = table.map(function (row) {
            return '<tr><td>' + row[0] + '</td><td>' + row[1] + '</td></tr>';
        }).join('');

        var maxRound = (table.length ? table[0][1] : 0)
            + (parseFloat(document.querySelector('[data-bonus-fl]').value) || 0)
            + (parseFloat(document.querySelector('[data-bonus-pole]').value) || 0)
            + (parseFloat(document.querySelector('[data-bonus-lead]').value) || 0);
        var rounds = parseFloat(document.getElementById('ps-season-rounds').value) || 0;

        document.querySelector('[data-preview-max-round]').textContent = maxRound;
        document.querySelector('[data-preview-season-total]').textContent = Math.round(maxRound * rounds);

        if (currentType() === 'manual') {
            tableJsonInput.value = JSON.stringify(table.map(function (r) { return { position: r[0], points: r[1] }; }));
        }
    }

    document.querySelectorAll('.ps-panels input, .ps-panels select, [name="type"], [data-bonus-fl], [data-bonus-pole], [data-bonus-lead], #ps-season-rounds')
        .forEach(function (el) { el.addEventListener('input', renderPreview); el.addEventListener('change', renderPreview); });

    document.querySelector('form').addEventListener('submit', function () {
        syncActivePanel();
        if (currentType() === 'manual') {
            tableJsonInput.value = JSON.stringify(manualTable().map(function (r) { return { position: r[0], points: r[1] }; }));
        }
    });

    renderPreview();
})();
</script>
@endpush

@endsection
