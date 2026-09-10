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
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1"
                   {{ old($errorKey, $current) ? 'checked' : '' }}>
            <label class="form-check-label fw-bold text-dark" for="{{ $id }}" style="font-size:.82rem">
                {{ $field['label'] }}
            </label>
        </div>
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
