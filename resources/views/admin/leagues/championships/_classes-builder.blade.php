@php
    $classes = $championship->settings->format->classes ?? [];
    // Same fixed 5-class list the race wizard's own multiclass picker uses
    // (resources/js/components/multiclass.js CLASS_DEFS, and this schema's own
    // format.car_class field) — a class here just *is* one of these, matching
    // the event maker's model instead of a free-typed name with a separately
    // hand-picked car list.
    $classOptions = ['GT2', 'GT3', 'GT4', 'TCX', 'GTC'];
@endphp

<div class="mt-2 mb-3" data-classes-builder style="{{ ($championship->settings->format->multiclass_enabled ?? false) ? '' : 'display:none' }}">
    <label class="form-label">Classes</label>
    <div class="form-text mb-2" style="font-size:.72rem;color:#9ca3af">Pick which classes race in this championship, and an optional entry cap per class.</div>

    <div data-class-rows>
        @foreach($classes as $i => $class)
        @php $selectedClass = $class['name'] ?? ''; @endphp
        <div class="d-flex gap-2 mb-2" data-class-row>
            <select data-class-name class="form-select form-select-sm" style="max-width:150px">
                @foreach($classOptions as $option)
                <option value="{{ $option }}" {{ $selectedClass === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
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
    var classOptions = @json($classOptions);

    function addRow(name, max) {
        var row = document.createElement('div');
        row.className = 'd-flex gap-2 mb-2';
        row.setAttribute('data-class-row', '');

        var optionsHtml = classOptions.map(function (c) {
            return '<option value="' + c + '">' + c + '</option>';
        }).join('');

        row.innerHTML =
            '<select data-class-name class="form-select form-select-sm" style="max-width:150px">' + optionsHtml + '</select>' +
            '<input type="number" placeholder="Max" data-class-max class="form-control form-control-sm" style="max-width:90px">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary" data-remove-class>×</button>';
        if (name) row.querySelector('[data-class-name]').value = name;
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
        var seen = {};
        rows.querySelectorAll('[data-class-row]').forEach(function (row) {
            var name = row.querySelector('[data-class-name]').value;
            if (!name || seen[name]) return; // a class picked twice would collide on name in syncChampionshipClasses()
            seen[name] = true;
            var max = row.querySelector('[data-class-max]').value;
            classes.push({ name: name, max_entries: max ? parseInt(max, 10) : null });
        });
        jsonInput.value = JSON.stringify(classes);
    });
})();
</script>
@endpush
