@php
    $currentSchemeId = $championship->settings->scoring->points_scheme_id ?? null;
    $psPreview = function ($scheme, $count = 6) {
        $table = collect($scheme->points_table ?? [])->sortKeys();
        return $table->take($count)->map(fn ($pts, $pos) => $pos . ':' . rtrim(rtrim((string) $pts, '0'), '.'))->implode(', ');
    };
@endphp

<style>
    .ps-pick-list { display: grid; gap: .6rem; max-height: 360px; overflow-y: auto; padding: .1rem; }
    .ps-pick-card { display: block; border: 1px solid #e5e7eb; border-radius: 8px; padding: .7rem .9rem; cursor: pointer; }
    .ps-pick-card input { position: absolute; opacity: 0; pointer-events: none; }
    .ps-pick-card:has(input:checked) { border-color: #7c3aed; background: #f3e8ff; }
</style>

<div class="mb-3">
    <label class="form-label">Points Scheme</label>
    <div class="ps-pick-list">
        @forelse($pointsSchemes as $scheme)
        <label class="ps-pick-card">
            <input type="radio" name="settings[scoring][points_scheme_id]" value="{{ $scheme->id }}"
                   {{ (string) old('settings.scoring.points_scheme_id', $currentSchemeId) === (string) $scheme->id ? 'checked' : '' }}>
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <span class="fw-bold text-dark" style="font-size:.85rem">{{ $scheme->name }}</span>
                    @if($scheme->is_template)
                    <span class="badge xcl-badge" style="background:#f3f4f6;color:#6b7280;font-size:.6rem;padding:2px 6px;border-radius:5px;font-weight:700">Template</span>
                    @endif
                    <div class="text-secondary" style="font-size:.76rem">{{ $psPreview($scheme, 5) }}{{ count($scheme->points_table ?? []) > 5 ? '…' : '' }}</div>
                </div>
                <span class="text-secondary text-end" style="font-size:.72rem;white-space:nowrap">FL {{ $scheme->fastest_lap_points }} · Pole {{ $scheme->pole_points }}</span>
            </div>
        </label>
        @empty
        <p class="text-secondary mb-0" style="font-size:.82rem">No points schemes yet — create one or copy a template.</p>
        @endforelse
    </div>
    <div class="form-text mt-2" style="font-size:.72rem;color:#9ca3af">
        Templates are read-only — copy one to create your own scheme.
        <a href="{{ route('admin.leagues.points-schemes.index', $league) }}">Manage points schemes →</a>
    </div>
    @error('settings.scoring.points_scheme_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
</div>
