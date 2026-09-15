@php
    $adjustments = $championship->settings->balance->adjustments ?? [];

    // Same car classes the championship itself actually races — the real
    // ChampionshipClass rows when multiclass (kept in sync from the Format
    // step's dropdown, so their car_class already is "GT3"/"GT4"/etc.), or the
    // single car_class column otherwise. Falls through to every car for the
    // game if neither is set, rather than showing an empty picker.
    $classFilter = $championship->is_multiclass
        ? $championship->classes->pluck('car_class')->filter()->values()
        : collect([$championship->car_class])->filter();
    $carOptions = \App\Models\Car::where('game', $championship->game)
        ->when($classFilter->isNotEmpty(), fn ($q) => $q->whereIn('car_class', $classFilter))
        ->orderBy('name')->pluck('name');

    // The championship's actual entry list — a team registration shows as the
    // team's name, an individual as their display name. Same "team registers,
    // not each member" model ChampionshipRegistration/RaceTeamEntry use
    // elsewhere (Championship::isRegistered(), RaceResult::groupedByCar()).
    $entrantOptions = $championship->registrations()->with(['user', 'racingTeam'])->get()
        ->filter(fn ($r) => !$r->is_spectator)
        ->map(fn ($r) => $r->racingTeam?->name ?? $r->user?->displayName())
        ->filter()->unique()->values();
@endphp

<div class="mt-2" data-adjustments-builder>
    <label class="form-label">Ballast &amp; Restrictor Adjustments</label>
    <div class="form-text mb-2" style="font-size:.72rem;color:#9ca3af">Per-car overrides pick from this championship's own car class(es); per-driver/team overrides pick from its entry list.</div>

    <div data-adjustment-rows>
        @foreach($adjustments as $a)
        @php
            $rowScope = ($a['scope'] ?? '') === 'driver' ? 'driver' : 'car';
            $rowOptions = $rowScope === 'car' ? $carOptions : $entrantOptions;
        @endphp
        <div class="d-flex gap-2 mb-2" data-adjustment-row>
            <select data-adj-scope class="form-select form-select-sm" style="max-width:130px">
                <option value="car" {{ $rowScope === 'car' ? 'selected' : '' }}>Car</option>
                <option value="driver" {{ $rowScope === 'driver' ? 'selected' : '' }}>Driver/Team</option>
            </select>
            <select data-adj-target class="form-select form-select-sm">
                <option value="">— Select —</option>
                @foreach($rowOptions as $option)
                <option value="{{ $option }}" {{ ($a['target'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <input type="number" placeholder="Ballast kg" value="{{ $a['ballast_kg'] ?? '' }}" data-adj-ballast class="form-control form-control-sm" style="max-width:110px">
            <input type="number" placeholder="Restrictor %" value="{{ $a['restrictor_percent'] ?? '' }}" data-adj-restrictor class="form-control form-control-sm" style="max-width:120px">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-remove-adjustment>×</button>
        </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-add-adjustment>+ Add Adjustment</button>
    <input type="hidden" name="adjustments_json" data-adjustments-json value="">
</div>

@push('scripts')
<script>
(function () {
    var builder = document.querySelector('[data-adjustments-builder]');
    if (!builder) return;

    var rows = builder.querySelector('[data-adjustment-rows]');
    var jsonInput = builder.querySelector('[data-adjustments-json]');
    var carOptions = @json($carOptions);
    var entrantOptions = @json($entrantOptions);

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function optionsHtml(list) {
        return '<option value="">— Select —</option>' + list.map(function (o) {
            var safe = escapeHtml(o);
            return '<option value="' + safe + '">' + safe + '</option>';
        }).join('');
    }

    function fillTarget(row) {
        var scope = row.querySelector('[data-adj-scope]').value;
        row.querySelector('[data-adj-target]').innerHTML = optionsHtml(scope === 'car' ? carOptions : entrantOptions);
    }

    function addRow() {
        var row = document.createElement('div');
        row.className = 'd-flex gap-2 mb-2';
        row.setAttribute('data-adjustment-row', '');
        row.innerHTML =
            '<select data-adj-scope class="form-select form-select-sm" style="max-width:130px">' +
                '<option value="car">Car</option><option value="driver">Driver/Team</option>' +
            '</select>' +
            '<select data-adj-target class="form-select form-select-sm"></select>' +
            '<input type="number" placeholder="Ballast kg" data-adj-ballast class="form-control form-control-sm" style="max-width:110px">' +
            '<input type="number" placeholder="Restrictor %" data-adj-restrictor class="form-control form-control-sm" style="max-width:120px">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary" data-remove-adjustment>×</button>';
        rows.appendChild(row);
        fillTarget(row);
        row.querySelector('[data-adj-scope]').addEventListener('change', function () { fillTarget(row); });
    }

    // Existing (server-rendered) rows need the same live scope->target swap.
    rows.querySelectorAll('[data-adjustment-row]').forEach(function (row) {
        row.querySelector('[data-adj-scope]').addEventListener('change', function () { fillTarget(row); });
    });

    builder.querySelector('[data-add-adjustment]').addEventListener('click', addRow);

    rows.addEventListener('click', function (e) {
        if (e.target.matches('[data-remove-adjustment]')) {
            e.target.closest('[data-adjustment-row]').remove();
        }
    });

    builder.closest('form').addEventListener('submit', function () {
        var adjustments = [];
        rows.querySelectorAll('[data-adjustment-row]').forEach(function (row) {
            var target = row.querySelector('[data-adj-target]').value;
            if (!target) return;
            var ballast = row.querySelector('[data-adj-ballast]').value;
            var restrictor = row.querySelector('[data-adj-restrictor]').value;
            adjustments.push({
                scope: row.querySelector('[data-adj-scope]').value,
                target: target,
                ballast_kg: ballast ? parseInt(ballast, 10) : null,
                restrictor_percent: restrictor ? parseInt(restrictor, 10) : null,
            });
        });
        jsonInput.value = JSON.stringify(adjustments);
    });
})();
</script>
@endpush
