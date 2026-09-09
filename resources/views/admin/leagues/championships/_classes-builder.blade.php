@php $classes = $championship->settings->format->classes ?? []; @endphp

<div class="mt-2 mb-3" data-classes-builder style="{{ ($championship->settings->format->multiclass_enabled ?? false) ? '' : 'display:none' }}">
    <label class="form-label">Classes</label>
    <div class="form-text mb-2" style="font-size:.72rem;color:#9ca3af">Each class has a name and a comma-separated list of eligible cars.</div>

    <div data-class-rows>
        @foreach($classes as $i => $class)
        <div class="d-flex gap-2 mb-2" data-class-row>
            <input type="text" placeholder="Class name" value="{{ $class['name'] ?? '' }}" data-class-name class="form-control form-control-sm" style="max-width:180px">
            <input type="text" placeholder="Eligible cars (comma separated)" value="{{ implode(', ', $class['eligible_cars'] ?? []) }}" data-class-cars class="form-control form-control-sm">
            <input type="number" placeholder="Max" value="{{ $class['max_entries'] ?? '' }}" data-class-max class="form-control form-control-sm" style="max-width:90px">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-remove-class>×</button>
        </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-add-class>+ Add Class</button>
    <input type="hidden" name="classes_json" data-classes-json value="">
</div>

@push('scripts')
<script>
(function () {
    var builder = document.querySelector('[data-classes-builder]');
    var multiclassToggle = document.getElementById('f-format-multiclass_enabled');
    if (!builder) return;

    var rows = builder.querySelector('[data-class-rows]');
    var jsonInput = builder.querySelector('[data-classes-json]');
    var addBtn = builder.querySelector('[data-add-class]');

    function addRow(name, cars, max) {
        var row = document.createElement('div');
        row.className = 'd-flex gap-2 mb-2';
        row.setAttribute('data-class-row', '');
        row.innerHTML =
            '<input type="text" placeholder="Class name" data-class-name class="form-control form-control-sm" style="max-width:180px">' +
            '<input type="text" placeholder="Eligible cars (comma separated)" data-class-cars class="form-control form-control-sm">' +
            '<input type="number" placeholder="Max" data-class-max class="form-control form-control-sm" style="max-width:90px">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary" data-remove-class>×</button>';
        row.querySelector('[data-class-name]').value = name || '';
        row.querySelector('[data-class-cars]').value = cars || '';
        row.querySelector('[data-class-max]').value = max || '';
        rows.appendChild(row);
    }

    addBtn.addEventListener('click', function () { addRow(); });

    rows.addEventListener('click', function (e) {
        if (e.target.matches('[data-remove-class]')) {
            e.target.closest('[data-class-row]').remove();
        }
    });

    if (multiclassToggle) {
        multiclassToggle.addEventListener('change', function () {
            builder.style.display = multiclassToggle.checked ? '' : 'none';
        });
    }

    builder.closest('form').addEventListener('submit', function () {
        var classes = [];
        rows.querySelectorAll('[data-class-row]').forEach(function (row) {
            var name = row.querySelector('[data-class-name]').value.trim();
            if (!name) return;
            var cars = row.querySelector('[data-class-cars]').value.split(',').map(function (c) { return c.trim(); }).filter(Boolean);
            var max = row.querySelector('[data-class-max]').value;
            classes.push({ name: name, eligible_cars: cars, max_entries: max ? parseInt(max, 10) : null });
        });
        jsonInput.value = JSON.stringify(classes);
    });
})();
</script>
@endpush
