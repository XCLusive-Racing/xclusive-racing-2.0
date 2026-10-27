{{-- Renders one ChampionshipSettingsSchema field, so a new scalar field added there
     shows up in the wizard with nothing to change here. Expects a parent
     <div class="row g-3">; $col (optional) overrides the default width.
     The input stays a direct child of the wrapper: depends_on hides that wrapper. --}}
@php
    $name    = "settings[{$field['group']}][{$field['key']}]";
    $id      = "f-{$field['group']}-{$field['key']}";
    $current = $championship->settings->{$field['group']}->{$field['key']} ?? $field['default'];
    $errorKey = "settings.{$field['group']}.{$field['key']}";
    $col = $col ?? match ($field['type']) {
        'text' => 'col-12',
        'string' => 'col-md-6',
        default => 'col-md-4',
    };

    // 'depends_on' names a boolean field (same group) that decides whether this
    // one shows; a leading '!' inverts it (e.g. Car Class only while Multiclass is
    // off). Hiding never clears the value, so switching back restores it.
    // Rendered hidden from the first paint, so there's no flash pre-JS.
    $dependsOnRaw    = $field['depends_on'] ?? null;
    $dependsInverted = $dependsOnRaw !== null && str_starts_with($dependsOnRaw, '!');
    $dependsOnKey    = $dependsOnRaw !== null ? ltrim($dependsOnRaw, '!') : null;
    $dependsOnId     = $dependsOnKey ? "f-{$field['group']}-{$dependsOnKey}" : null;
    $dependsOnOn     = $dependsOnKey ? (bool) old("settings.{$field['group']}.{$dependsOnKey}", $championship->settings->{$field['group']}->{$dependsOnKey} ?? false) : true;
    $shouldShow      = $dependsInverted ? !$dependsOnOn : $dependsOnOn;

    // Locked (greyed, not editable): XCL-R Multiplier while XCL Rating is off, or a
    // schema field marked 'locked' for something that isn't operational yet.
    $xclGated = $field['key'] === 'xcl_r_multiplier' && !$championship->xcl_rating_enabled;
    $locked   = $xclGated || ($field['locked'] ?? false);
    $lockedHelp = $locked ? ($field['locked_help'] ?? ($xclGated ? 'Turn on XCL Rating on Basics first.' : null)) : null;
    $info = $field['info'] ?? null;
@endphp

<div class="{{ $col }}" @if($locked) style="opacity:.6" @endif @if($dependsOnId) data-shown-if="{{ $dependsOnId }}" @if($dependsInverted) data-invert @endif @if(!$shouldShow) hidden @endif @endif>
    @if($field['type'] === 'boolean')
        @php $isOn = (bool) old($errorKey, $current); @endphp
        <div class="d-flex align-items-center gap-2">
            <label class="cw-toggle {{ $locked ? 'is-locked' : '' }}">
                <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1" {{ $isOn ? 'checked' : '' }} {{ $locked ? 'disabled' : '' }}>
                <span class="cw-toggle__dot"></span>
                {{ $field['label'] }}
                @if($locked)<span class="fw-normal">({{ $isOn ? 'On' : 'Off' }})</span>@endif
            </label>
            @if($info)<span class="cw-info" tabindex="0" data-tip="{{ $info }}">i</span>@endif
        </div>
        @if($lockedHelp)
        <div class="cw-help">{{ $lockedHelp }}</div>
        @elseif($field['help'])
        <div class="cw-help">{{ $field['help'] }}</div>
        @endif
    @else
        <label class="cw-label" for="{{ $id }}">
            {{ $field['label'] }}
            @if($info)<span class="cw-info" tabindex="0" data-tip="{{ $info }}">i</span>@endif
        </label>
        @if($field['type'] === 'enum')
        <select name="{{ $name }}" id="{{ $id }}" class="form-select @error($errorKey) is-invalid @enderror" {{ $locked ? 'disabled' : '' }}>
            @if($field['nullable'] ?? false)
            <option value="">Not set</option>
            @endif
            @foreach($field['options'] as $option)
            <option value="{{ $option }}" {{ old($errorKey, $current) === $option ? 'selected' : '' }}>
                {{ ucfirst(str_replace('_', ' ', $option)) }}
            </option>
            @endforeach
        </select>
        @elseif($field['type'] === 'text')
        <textarea name="{{ $name }}" id="{{ $id }}" rows="3" class="form-control @error($errorKey) is-invalid @enderror" {{ $locked ? 'disabled' : '' }}>{{ old($errorKey, $current) }}</textarea>
        @else
        @php
            $inputType = match ($field['type']) {
                'time' => 'time',
                'date' => 'date',
                'datetime' => 'datetime-local',
                'string' => 'text',
                default => 'number',
            };
        @endphp
        <input type="{{ $inputType }}" name="{{ $name }}" id="{{ $id }}" value="{{ old($errorKey, $current) }}"
               @if($field['type'] === 'float') step="0.01" @endif
               class="form-control @error($errorKey) is-invalid @enderror" {{ $locked ? 'disabled' : '' }}>
        @endif
        @if($lockedHelp)
        <div class="cw-help">{{ $lockedHelp }}</div>
        @elseif($field['help'])
        <div class="cw-help">{{ $field['help'] }}</div>
        @endif
        @error($errorKey) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    @endif
</div>
