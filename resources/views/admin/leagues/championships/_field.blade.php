{{-- Renders one ChampionshipSettingsSchema field. $field comes straight from the
     schema, so a new scalar rule added there appears in the wizard automatically —
     nothing here needs to change for it to show up. Expects a parent <div class="row g-3">
     around a run of these (see wizard.blade.php) — the col width below only makes
     sense inside one, matching the race wizard's grid layout
     (resources/views/admin/races/form.blade.php) instead of one full-width field
     per line. --}}
@php
    $name    = "settings[{$field['group']}][{$field['key']}]";
    $id      = "f-{$field['group']}-{$field['key']}";
    $current = $championship->settings->{$field['group']}->{$field['key']} ?? $field['default'];
    $errorKey = "settings.{$field['group']}.{$field['key']}";
    $col = match ($field['type']) {
        'boolean', 'text' => 'col-12',
        'enum', 'datetime' => 'col-sm-4',
        default => 'col-sm-3', // integer, float, date, time
    };
    // Two independent sources: a section-level dependency the caller already
    // resolved (wizard.blade.php's $disabled param), or — specific to this one
    // field — XCL-R Multiplier only meaning anything once XCL Rating itself is on.
    // Just the disabled attribute here (functional correctness — a disabled
    // field never submits a value); the visual greying is wizard.blade.php's
    // [data-depends-on] script, which stays reactive to a live toggle instead
    // of freezing whatever this field's opacity was at page load. XCL-R
    // Multiplier's own disabled state has no live toggle to react to (XCL
    // Rating is admin-approval-only, not a form field on this page) so its grey
    // style lives inline here instead.
    $disabled = $disabled ?? false;
    $xclGated = $field['key'] === 'xcl_r_multiplier' && !$championship->xcl_rating_enabled;
    $disabled = $disabled || $xclGated;
@endphp

<div class="{{ $col }}" style="{{ $xclGated ? 'opacity:.5' : '' }}">
    @if($field['type'] === 'boolean')
        {{-- Toggle pill, same pattern as the admin user-roles page
             (resources/views/admin/users/edit.blade.php, [data-role-pill]) instead of
             a bare checkbox — one shared toggle script lives once in wizard.blade.php. --}}
        @php $isOn = (bool) old($errorKey, $current); @endphp
        <label data-bool-pill
               class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-bold"
               style="cursor:{{ $disabled ? 'not-allowed' : 'pointer' }};user-select:none;font-size:.82rem;transition:all .15s;{{ $isOn ? 'border:2px solid #7c3aed;background:#7c3aed18;color:#7c3aed' : 'border:2px solid #e5e7eb;background:#fff;color:#374151' }}">
            <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1" class="d-none" {{ $isOn ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }}>
            {{ $field['label'] }}
        </label>
        @if($field['help'])
        <div class="form-text" style="font-size:.72rem;color:#9ca3af">{{ $field['help'] }}</div>
        @endif
    @else
        <label class="form-label" for="{{ $id }}">{{ $field['label'] }}</label>

        @if($field['type'] === 'enum')
        <select name="{{ $name }}" id="{{ $id }}" class="form-select @error($errorKey) is-invalid @enderror" {{ $disabled ? 'disabled' : '' }}>
            @if($field['nullable'] ?? false)
            <option value="">— Not set —</option>
            @endif
            @foreach($field['options'] as $option)
            <option value="{{ $option }}" {{ old($errorKey, $current) === $option ? 'selected' : '' }}>
                {{ ucfirst(str_replace('_', ' ', $option)) }}
            </option>
            @endforeach
        </select>
        @elseif($field['type'] === 'text')
        <textarea name="{{ $name }}" id="{{ $id }}" rows="3" class="form-control @error($errorKey) is-invalid @enderror" {{ $disabled ? 'disabled' : '' }}>{{ old($errorKey, $current) }}</textarea>
        @elseif($field['type'] === 'time')
        <input type="time" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror" {{ $disabled ? 'disabled' : '' }}>
        @elseif($field['type'] === 'date')
        <input type="date" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror" {{ $disabled ? 'disabled' : '' }}>
        @elseif($field['type'] === 'datetime')
        <input type="datetime-local" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror" {{ $disabled ? 'disabled' : '' }}>
        @elseif($field['type'] === 'float')
        <input type="number" step="0.01" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror" {{ $disabled ? 'disabled' : '' }}>
        @else
        <input type="number" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror" {{ $disabled ? 'disabled' : '' }}>
        @endif

        @if($field['help'])
        <div class="form-text" style="font-size:.72rem;color:#9ca3af">{{ $field['help'] }}</div>
        @endif
        @error($errorKey) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    @endif
</div>
