@php
    // Driver classes are ChampionshipDriverClass rows (not a settings list) so
    // entries can point at them; each row keeps its id so a rename keeps its
    // entries (ChampionshipWizardController::syncDriverClasses()).
    $driverClasses = $championship->driverClasses()->get();
    $bannerOptions = \App\Models\ChampionshipDriverClass::ACC_CATEGORIES;
    // Lowest rank first, the order a From/To pick reads naturally in.
    $rankOptions = collect(\App\Models\User::ranks())->reverse()->pluck('name', 'slug');
@endphp

<div class="mt-2 mb-3" data-driver-classes-builder style="{{ $championship->usesDriverClasses() ? '' : 'display:none' }}">
    <label class="form-label">Classes</label>
    <div class="form-text mb-2" style="font-size:.72rem;color:#9ca3af">
        Name each class and pick its in-game number banner. "By XCL rating" keeps each driver's usual banner.
        Max is optional — leave it blank and only the championship's total entry cap applies.
        From/To puts a driver in the class automatically by their XCL rank when they register
        (e.g. Pro: From Silver; Am: To Bronze) — leave both blank to assign that class by hand.
    </div>

    <div data-driver-class-rows>
        @foreach($driverClasses as $class)
        <div class="d-flex flex-wrap gap-2 mb-2" data-driver-class-row data-id="{{ $class->id }}">
            <input type="text" maxlength="50" placeholder="e.g. Pro" value="{{ $class->name }}" data-driver-class-name class="form-control form-control-sm" style="max-width:180px">
            <select data-driver-class-banner class="form-select form-select-sm" style="max-width:160px">
                <option value="">By XCL rating</option>
                @foreach($bannerOptions as $value => $option)
                <option value="{{ $value }}" @selected($class->acc_category === $value)>{{ $option['label'] }} banner</option>
                @endforeach
            </select>
            <input type="number" min="1" placeholder="Max" value="{{ $class->max_entries }}" data-driver-class-max class="form-control form-control-sm" style="max-width:90px">
            <select data-driver-class-min-rank class="form-select form-select-sm" style="max-width:130px" title="Lowest XCL rank in this class">
                <option value="">From: any</option>
                @foreach($rankOptions as $slug => $label)
                <option value="{{ $slug }}" @selected($class->min_rank === $slug)>From: {{ $label }}</option>
                @endforeach
            </select>
            <select data-driver-class-max-rank class="form-select form-select-sm" style="max-width:130px" title="Highest XCL rank in this class">
                <option value="">To: any</option>
                @foreach($rankOptions as $slug => $label)
                <option value="{{ $slug }}" @selected($class->max_rank === $slug)>To: {{ $label }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-remove-driver-class>×</button>
        </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-add-driver-class>+ Add Class</button>
    <input type="hidden" name="driver_classes_json" data-driver-classes-json value="">
</div>

@push('scripts')
<script>
(function () {
    var builder = document.querySelector('[data-driver-classes-builder]');
    var toggle = document.getElementById('f-format-driver_classes_enabled');
    if (!builder) return;

    var rows = builder.querySelector('[data-driver-class-rows]');
    var bannerOptions = @json(collect($bannerOptions)->map(fn ($option) => $option['label']));
    var rankOptions = @json($rankOptions->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])->values());

    function rankSelect(attr, prefix, title) {
        var html = '<select ' + attr + ' class="form-select form-select-sm" style="max-width:130px" title="' + title + '">' +
            '<option value="">' + prefix + ': any</option>';
        rankOptions.forEach(function (rank) {
            html += '<option value="' + rank.slug + '">' + prefix + ': ' + rank.label + '</option>';
        });
        return html + '</select>';
    }

    builder.querySelector('[data-add-driver-class]').addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'd-flex flex-wrap gap-2 mb-2';
        row.setAttribute('data-driver-class-row', '');

        var options = '<option value="">By XCL rating</option>';
        Object.keys(bannerOptions).sort().reverse().forEach(function (value) {
            options += '<option value="' + value + '">' + bannerOptions[value] + ' banner</option>';
        });

        row.innerHTML =
            '<input type="text" maxlength="50" placeholder="e.g. Pro" data-driver-class-name class="form-control form-control-sm" style="max-width:180px">' +
            '<select data-driver-class-banner class="form-select form-select-sm" style="max-width:160px">' + options + '</select>' +
            '<input type="number" min="1" placeholder="Max" data-driver-class-max class="form-control form-control-sm" style="max-width:90px">' +
            rankSelect('data-driver-class-min-rank', 'From', 'Lowest XCL rank in this class') +
            rankSelect('data-driver-class-max-rank', 'To', 'Highest XCL rank in this class') +
            '<button type="button" class="btn btn-sm btn-outline-secondary" data-remove-driver-class>×</button>';
        rows.appendChild(row);
        row.querySelector('[data-driver-class-name]').focus();
    });

    rows.addEventListener('click', function (e) {
        if (e.target.matches('[data-remove-driver-class]')) {
            e.target.closest('[data-driver-class-row]').remove();
        }
    });

    if (toggle) {
        toggle.addEventListener('change', function () {
            builder.style.display = toggle.checked ? '' : 'none';
        });
    }

    builder.closest('form').addEventListener('submit', function () {
        var classes = [];
        rows.querySelectorAll('[data-driver-class-row]').forEach(function (row) {
            var name = row.querySelector('[data-driver-class-name]').value.trim();
            if (!name) return;
            var banner = row.querySelector('[data-driver-class-banner]').value;
            var max = row.querySelector('[data-driver-class-max]').value;
            classes.push({
                id: row.dataset.id ? parseInt(row.dataset.id, 10) : null,
                name: name,
                acc_category: banner === '' ? null : parseInt(banner, 10),
                max_entries: max ? parseInt(max, 10) : null,
                min_rank: row.querySelector('[data-driver-class-min-rank]').value || null,
                max_rank: row.querySelector('[data-driver-class-max-rank]').value || null,
            });
        });
        builder.querySelector('[data-driver-classes-json]').value = JSON.stringify(classes);
    });
})();
</script>
@endpush
