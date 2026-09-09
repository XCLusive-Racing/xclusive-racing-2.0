@php $adjustments = $championship->settings->balance->adjustments ?? []; @endphp

<div class="mt-2" data-adjustments-builder>
    <label class="form-label">Ballast &amp; Restrictor Adjustments</label>
    <div class="form-text mb-2" style="font-size:.72rem;color:#9ca3af">Per-driver or per-car overrides.</div>

    <div data-adjustment-rows>
        @foreach($adjustments as $a)
        <div class="d-flex gap-2 mb-2" data-adjustment-row>
            <select data-adj-scope class="form-select form-select-sm" style="max-width:110px">
                <option value="car" {{ ($a['scope'] ?? '') === 'car' ? 'selected' : '' }}>Car</option>
                <option value="driver" {{ ($a['scope'] ?? '') === 'driver' ? 'selected' : '' }}>Driver</option>
            </select>
            <input type="text" placeholder="Car model or driver" value="{{ $a['target'] ?? '' }}" data-adj-target class="form-control form-control-sm">
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

    function addRow() {
        var row = document.createElement('div');
        row.className = 'd-flex gap-2 mb-2';
        row.setAttribute('data-adjustment-row', '');
        row.innerHTML =
            '<select data-adj-scope class="form-select form-select-sm" style="max-width:110px">' +
                '<option value="car">Car</option><option value="driver">Driver</option>' +
            '</select>' +
            '<input type="text" placeholder="Car model or driver" data-adj-target class="form-control form-control-sm">' +
            '<input type="number" placeholder="Ballast kg" data-adj-ballast class="form-control form-control-sm" style="max-width:110px">' +
            '<input type="number" placeholder="Restrictor %" data-adj-restrictor class="form-control form-control-sm" style="max-width:120px">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary" data-remove-adjustment>×</button>';
        rows.appendChild(row);
    }

    builder.querySelector('[data-add-adjustment]').addEventListener('click', addRow);

    rows.addEventListener('click', function (e) {
        if (e.target.matches('[data-remove-adjustment]')) {
            e.target.closest('[data-adjustment-row]').remove();
        }
    });

    builder.closest('form').addEventListener('submit', function () {
        var adjustments = [];
        rows.querySelectorAll('[data-adjustment-row]').forEach(function (row) {
            var target = row.querySelector('[data-adj-target]').value.trim();
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
