@php $report = $report ?? null; @endphp

<div class="mb-3">
    <label class="form-label fw-bold" style="font-size:.8rem">Session <span class="text-danger">*</span></label>
    <select name="session_type" required class="form-select form-select-sm @error('session_type') is-invalid @enderror">
        @foreach(\App\Models\Report::sessionTypes() as $code => $label)
        <option value="{{ $code }}" {{ old('session_type', $report?->session_type) === $code ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <div class="form-text" style="font-size:.72rem">The session in which the incident occurred</div>
    @error('session_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

{{-- A multi-race round (R1/R2): which race. Filled from the selected race on the report
     form (data-race-numbers on its option), from the report's own race when editing. --}}
@php $formRaceNumbers = $report?->race?->resultRaceNumbers() ?? []; @endphp
<div class="mb-3" data-race-number-wrap style="{{ count($formRaceNumbers) > 1 && old('session_type', $report?->session_type ?? 'R') === 'R' ? '' : 'display:none' }}">
    <label class="form-label fw-bold" style="font-size:.8rem">Which race <span class="text-danger">*</span></label>
    <select name="race_number" data-race-number-select data-selected="{{ old('race_number', $report?->race_number) }}"
            class="form-select form-select-sm @error('race_number') is-invalid @enderror">
        @foreach($formRaceNumbers as $n)
        <option value="{{ $n }}" {{ (int) old('race_number', $report?->race_number) === $n ? 'selected' : '' }}>Race {{ $n }}</option>
        @endforeach
    </select>
    <div class="form-text" style="font-size:.72rem">This round had more than one race</div>
    @error('race_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

@once
@push('scripts')
<script>
(function () {
    const wrap = document.querySelector('[data-race-number-wrap]');
    const select = wrap?.querySelector('[data-race-number-select]');
    const session = document.querySelector('select[name="session_type"]');
    const raceSelect = document.getElementById('race-select');
    if (!wrap || !select || !session) return;

    let numbers = [...select.options].map(o => o.value);

    function render() {
        if (raceSelect) {
            const picked = raceSelect.selectedOptions[0];
            numbers = JSON.parse(picked?.dataset.raceNumbers || '[]').map(String);
            const keep = select.value || select.dataset.selected;
            select.innerHTML = '<option value="">— Select the race —</option>' + numbers.map(n =>
                '<option value="' + n + '"' + (n === keep ? ' selected' : '') + '>Race ' + n + '</option>').join('');
        }
        const show = numbers.length > 1 && session.value === 'R';
        wrap.style.display = show ? '' : 'none';
        select.disabled = !show;
        select.required = show;
    }

    raceSelect?.addEventListener('change', render);
    session.addEventListener('change', render);
    render();
})();
</script>
@endpush
@endonce

<div class="row g-2 mb-3">
    <div class="col-6">
        <label class="form-label fw-bold" style="font-size:.8rem">Lap number</label>
        <input type="number" name="lap_number" value="{{ old('lap_number', $report?->lap_number) }}"
               class="form-control form-control-sm @error('lap_number') is-invalid @enderror"
               placeholder="e.g. 5" min="1" max="999">
        @error('lap_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-6">
        <label class="form-label fw-bold" style="font-size:.8rem">Corner</label>
        <input type="text" name="incident_corner" value="{{ old('incident_corner', $report?->incident_corner) }}"
               class="form-control form-control-sm @error('incident_corner') is-invalid @enderror"
               placeholder="e.g. T1, Raidillon">
        @error('incident_corner')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold" style="font-size:.8rem">Description <span class="text-danger">*</span></label>
    <textarea name="description" rows="4"
              class="form-control form-control-sm @error('description') is-invalid @enderror"
              placeholder="Describe what happened in detail (min. 20 characters)...">{{ old('description', $report?->description) }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" style="font-size:.8rem">Clip Link <span class="text-danger">*</span></label>
    <input type="url" name="video_url" value="{{ old('video_url', $report?->video_url) }}"
           class="form-control form-control-sm @error('video_url') is-invalid @enderror"
           placeholder="YouTube or Twitch clip URL (required)">
    @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text" style="font-size:.72rem">A clip is required for all incident reports. Reports without a valid clip link will not be reviewed.</div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold" style="font-size:.8rem">Clip 2 <span class="text-secondary fw-normal">(optional)</span></label>
    <input type="url" name="clip_bad_driver_url" value="{{ old('clip_bad_driver_url', $report?->clip_bad_driver_url) }}"
           class="form-control form-control-sm @error('clip_bad_driver_url') is-invalid @enderror"
           placeholder="https://youtube.com/...">
    @error('clip_bad_driver_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text" style="font-size:.72rem">Footage from the Accused driver&rsquo;s point of view.</div>
</div>

<div class="mb-4">
    <label class="form-label fw-bold" style="font-size:.8rem">Clip 3 <span class="text-secondary fw-normal">(optional)</span></label>
    <input type="url" name="clip_heli_url" value="{{ old('clip_heli_url', $report?->clip_heli_url) }}"
           class="form-control form-control-sm @error('clip_heli_url') is-invalid @enderror"
           placeholder="https://youtube.com/...">
    @error('clip_heli_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text" style="font-size:.72rem">Heli / overview footage of the incident.</div>
</div>

<div class="mb-4 d-flex align-items-start gap-2 p-2 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
    <input type="checkbox" name="hide_reporter_name" id="hide-reporter-name" value="1"
           class="form-check-input mt-1" {{ old('hide_reporter_name', $report?->hide_reporter_name) ? 'checked' : '' }}>
    <label for="hide-reporter-name" class="mb-0" style="font-size:.78rem;line-height:1.4">
        <span class="fw-bold">Hide my name from the reported driver</span>
        <div class="text-secondary" style="font-size:.72rem">Stewards will always see who filed this report — this only keeps your name hidden from the driver you're reporting.</div>
    </label>
</div>
