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
    <label class="form-label mb-1">Points Scheme</label>
    @error('settings.scoring.points_scheme_id') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror

    @if($ownedSchemes->isEmpty() && $templateSchemes->isEmpty())
    <p class="text-secondary mb-0" style="font-size:.82rem">No points schemes yet — create one or copy a template.</p>
    @endif

    @if($ownedSchemes->isNotEmpty())
    <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af">{{ $league->name }}'s Own</p>
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
    <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af">XCL Templates <span class="fw-normal text-secondary" style="text-transform:none">(read-only — copy one to edit it)</span></p>
    <div class="ps-pick-list">
        @foreach($templateSchemes as $scheme)
        <label class="ps-pick-card">
            <input type="radio" name="settings[scoring][points_scheme_id]" value="{{ $scheme->id }}"
                   {{ (string) old('settings.scoring.points_scheme_id', $currentSchemeId) === (string) $scheme->id ? 'checked' : '' }}>
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <span class="fw-bold text-dark" style="font-size:.85rem">{{ $scheme->name }}</span>
                    <span class="badge xcl-badge" style="background:#f3f4f6;color:#6b7280;font-size:.6rem;padding:2px 6px;border-radius:5px;font-weight:700">Template</span>
                    <div class="text-secondary" style="font-size:.76rem">{!! $psPreview($scheme, 5) !!}{{ count($scheme->points_table ?? []) > 5 ? ' …' : '' }}</div>
                </div>
                <span class="text-secondary text-end" style="font-size:.72rem;white-space:nowrap">FL {{ $scheme->fastest_lap_points }} · Pole {{ $scheme->pole_points }} · Lead {{ $scheme->leading_lap_points }}</span>
            </div>
        </label>
        @endforeach
    </div>
    @endif

    <div class="form-text mt-2" style="font-size:.72rem;color:#9ca3af">
        <a href="{{ route('admin.leagues.points-schemes.index', $league) }}">Manage points schemes →</a>
    </div>
</div>
