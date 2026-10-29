@php
    $currentSchemeId = $championship->settings->scoring->points_scheme_id ?? null;
    // Clearer preview than the old "1:25, 2:18, 3:15" — labelled positions, and
    // the top 3 stand out since those are what people actually scan for.
    $psPreview = function ($scheme, $count = 6) {
        $table = collect($scheme->points_table ?? [])->sortKeys()->take($count);
        return $table->map(function ($pts, $pos) {
            $pts = rtrim(rtrim((string) $pts, '0'), '.');
            return $pos <= 3
                ? '<strong style="color:#374151">P' . $pos . ' ' . $pts . '</strong>'
                : 'P' . $pos . ' ' . $pts;
        })->implode(' · ');
    };
    $ownedSchemes    = $pointsSchemes->where('is_template', false)->values();
    $templateSchemes = $pointsSchemes->where('is_template', true)->values();
@endphp

<style>
    .ps-pick-list { display: grid; gap: .6rem; max-height: 320px; overflow-y: auto; padding: .1rem; }
    .ps-pick-card { display: block; border: 1px solid #e5e7eb; border-radius: 8px; padding: .7rem .9rem; cursor: pointer; }
    .ps-pick-card input { position: absolute; opacity: 0; pointer-events: none; }
    .ps-pick-card:has(input:checked) { border-color: #7c3aed; background: #f3e8ff; }
</style>

<div class="mb-3">
    <p class="cw-section__title">Points Scheme</p>
    <p class="cw-section__note">Pick a template or your own copy. Templates are read-only.</p>
    @error('settings.scoring.points_scheme_id') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror

    @if($ownedSchemes->isEmpty() && $templateSchemes->isEmpty())
    <p class="cw-section__note mb-0">No points schemes yet. Create one or copy a template.</p>
    @endif

    @if($ownedSchemes->isNotEmpty())
    <p class="cw-label">{{ $league->name }}</p>
    <div class="ps-pick-list mb-3">
        @foreach($ownedSchemes as $scheme)
        <label class="ps-pick-card">
            <input type="radio" name="settings[scoring][points_scheme_id]" value="{{ $scheme->id }}"
                   {{ (string) old('settings.scoring.points_scheme_id', $currentSchemeId) === (string) $scheme->id ? 'checked' : '' }}>
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <span class="fw-bold text-dark" style="font-size:.85rem">{{ $scheme->name }}</span>
                    <div class="text-secondary" style="font-size:.76rem">{!! $psPreview($scheme, 5) !!}{{ count($scheme->points_table ?? []) > 5 ? ' …' : '' }}</div>
                </div>
                <span class="text-secondary text-end" style="font-size:.72rem;white-space:nowrap">FL {{ $scheme->fastest_lap_points }} · Pole {{ $scheme->pole_points }} · Lead {{ $scheme->leading_lap_points }}</span>
            </div>
        </label>
        @endforeach
    </div>
    @endif

    @if($templateSchemes->isNotEmpty())
    <p class="cw-label">XCL Templates</p>
    <div class="ps-pick-list">
        @foreach($templateSchemes as $scheme)
        <label class="ps-pick-card">
            <input type="radio" name="settings[scoring][points_scheme_id]" value="{{ $scheme->id }}"
                   {{ (string) old('settings.scoring.points_scheme_id', $currentSchemeId) === (string) $scheme->id ? 'checked' : '' }}>
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <span class="fw-bold text-dark" style="font-size:.85rem">{{ $scheme->name }}</span>
                    <span class="cw-tag cw-tag--muted">Template</span>
                    <div class="text-secondary" style="font-size:.76rem">{!! $psPreview($scheme, 5) !!}{{ count($scheme->points_table ?? []) > 5 ? ' …' : '' }}</div>
                </div>
                <span class="text-secondary text-end" style="font-size:.72rem;white-space:nowrap">FL {{ $scheme->fastest_lap_points }} · Pole {{ $scheme->pole_points }} · Lead {{ $scheme->leading_lap_points }}</span>
            </div>
        </label>
        @endforeach
    </div>
    @endif

    <div class="cw-help mt-2">
        <a href="{{ route('admin.leagues.points-schemes.index', $league) }}">Manage points schemes →</a>
    </div>
</div>
