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
@endphp

<div class="{{ $col }}">
    @if($field['type'] === 'boolean')
        {{-- Toggle pill, same pattern as the admin user-roles page
             (resources/views/admin/users/edit.blade.php, [data-role-pill]) instead of
             a bare checkbox — one shared toggle script lives once in wizard.blade.php. --}}
        @php $isOn = (bool) old($errorKey, $current); @endphp
        <label data-bool-pill
               class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-bold"
               style="cursor:pointer;user-select:none;font-size:.82rem;transition:all .15s;{{ $isOn ? 'border:2px solid #7c3aed;background:#7c3aed18;color:#7c3aed' : 'border:2px solid #e5e7eb;background:#fff;color:#374151' }}">
            <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1" class="d-none" {{ $isOn ? 'checked' : '' }}>
            {{ $field['label'] }}
        </label>
        @if($field['help'])
        <div class="form-text" style="font-size:.72rem;color:#9ca3af">{{ $field['help'] }}</div>
        @endif
    @else
        <label class="form-label" for="{{ $id }}">{{ $field['label'] }}</label>

        @if($field['type'] === 'enum')
        <select name="{{ $name }}" id="{{ $id }}" class="form-select @error($errorKey) is-invalid @enderror">
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
        <textarea name="{{ $name }}" id="{{ $id }}" rows="3" class="form-control @error($errorKey) is-invalid @enderror">{{ old($errorKey, $current) }}</textarea>
        @elseif($field['type'] === 'time')
        <input type="time" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror">
        @elseif($field['type'] === 'date')
        <input type="date" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror">
        @elseif($field['type'] === 'datetime')
        <input type="datetime-local" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror">
        @elseif($field['type'] === 'float')
        <input type="number" step="0.01" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror">
        @else
        <input type="number" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               class="form-control @error($errorKey) is-invalid @enderror">
        @endif

        @if($field['help'])
        <div class="form-text" style="font-size:.72rem;color:#9ca3af">{{ $field['help'] }}</div>
        @endif
        @error($errorKey) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    @endif
</div>
