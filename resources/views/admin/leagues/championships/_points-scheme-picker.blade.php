@php $currentSchemeId = $championship->settings->scoring->points_scheme_id ?? null; @endphp

<div class="mb-3">
    <label class="form-label">Points Scheme</label>
    <select name="settings[scoring][points_scheme_id]" class="form-select @error('settings.scoring.points_scheme_id') is-invalid @enderror">
        <option value="">— Not set —</option>
        @foreach($pointsSchemes as $scheme)
        <option value="{{ $scheme->id }}" {{ (string) old('settings.scoring.points_scheme_id', $currentSchemeId) === (string) $scheme->id ? 'selected' : '' }}>
            {{ $scheme->name }}{{ $scheme->is_template ? ' (template)' : '' }}
        </option>
        @endforeach
    </select>
    <div class="form-text" style="font-size:.72rem;color:#9ca3af">
        Templates are read-only — copy one to create your own scheme.
        <a href="{{ route('admin.leagues.points-schemes.index', $league) }}">Manage points schemes →</a>
    </div>
    @error('settings.scoring.points_scheme_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
</div>
