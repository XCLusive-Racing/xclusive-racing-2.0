@extends('layouts.admin')

@section('title', 'Add Round — ' . $championship->name)
@section('page-title', 'Add Round')

@section('page-actions')
    <a href="{{ route('admin.championships.show', $championship) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back to {{ $championship->name }}
    </a>
@endsection

@section('content')

<form action="{{ route('admin.championships.rounds.store', $championship) }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4 align-items-start">

        {{-- Left --}}
        <div class="col-12 col-lg-8">

            {{-- Event --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Round</p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-8">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" id="cr-title" name="title" value="{{ old('title') }}"
                                   class="form-control @error('title') is-invalid @enderror"
                                   placeholder="e.g. Round 1 — Spa" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Round # <span class="fw-normal text-secondary" style="text-transform:none">(auto if blank)</span></label>
                            <input type="number" name="round_number" value="{{ old('round_number') }}"
                                   class="form-control @error('round_number') is-invalid @enderror"
                                   min="1" placeholder="Auto">
                            @error('round_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Game</label>
                            <input type="text" class="form-control" value="{{ $championship->gameLabel() }}" disabled>
                            <input type="hidden" id="cr-game" name="game" value="{{ $championship->game }}">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Track <span class="text-danger">*</span></label>
                            <input type="text" id="cr-track" name="track" value="{{ old('track') }}"
                                   class="form-control @error('track') is-invalid @enderror"
                                   placeholder="e.g. Spa">
                            @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Car Class <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                            <select id="cr-car-class" name="car_class" class="form-select">
                                <option value="">— Not set —</option>
                                @foreach(['GT2', 'GT3', 'GT4', 'M2'] as $cls)
                                    <option value="{{ $cls }}" {{ old('car_class') === $cls ? 'selected' : '' }}>{{ $cls }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Sessions --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Sessions <span class="fw-normal" style="text-transform:none">(minutes — leave blank to omit)</span></p>

                    <div class="row g-3">
                        <div class="col-sm-3">
                            <label class="form-label">Practice</label>
                            <div class="input-group">
                                <input type="number" id="cr-practice" name="practice_duration" value="{{ old('practice_duration', $championship->practice_duration) }}"
                                       class="form-control @error('practice_duration') is-invalid @enderror"
                                       min="1" max="999" placeholder="—">
                                <span class="input-group-text" style="font-size:.78rem">min</span>
                            </div>
                            @error('practice_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Qualifying</label>
                            <div class="input-group">
                                <input type="number" id="cr-qualifying" name="qualifying_duration" value="{{ old('qualifying_duration', $championship->qualifying_duration) }}"
                                       class="form-control @error('qualifying_duration') is-invalid @enderror"
                                       min="1" max="999" placeholder="—">
                                <span class="input-group-text" style="font-size:.78rem">min</span>
                            </div>
                            @error('qualifying_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Race 1 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" id="cr-race" name="race_duration" value="{{ old('race_duration', $championship->race_duration) }}"
                                       class="form-control @error('race_duration') is-invalid @enderror"
                                       min="1" max="999" placeholder="e.g. 30" required>
                                <span class="input-group-text" style="font-size:.78rem">min</span>
                            </div>
                            @error('race_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">XCL-R Multiplier</label>
                            <select id="cr-duration-key" name="duration_key" class="form-select">
                                <option value="">1.0× (default)</option>
                                <option value="15"   {{ old('duration_key') === '15'   ? 'selected' : '' }}>0.6×</option>
                                <option value="20"   {{ old('duration_key') === '20'   ? 'selected' : '' }}>0.8×</option>
                                <option value="30"   {{ old('duration_key') === '30'   ? 'selected' : '' }}>1.0×</option>
                                <option value="30+"  {{ old('duration_key') === '30+'  ? 'selected' : '' }}>1.2×</option>
                                <option value="30++" {{ old('duration_key') === '30++' ? 'selected' : '' }}>1.3×</option>
                                <option value="45"   {{ old('duration_key') === '45'   ? 'selected' : '' }}>1.5×</option>
                                <option value="45+"  {{ old('duration_key') === '45+'  ? 'selected' : '' }}>1.6×</option>
                                <option value="60"   {{ old('duration_key') === '60'   ? 'selected' : '' }}>2.0×</option>
                                <option value="60+"  {{ old('duration_key') === '60+'  ? 'selected' : '' }}>2.1×</option>
                                <option value="90"   {{ old('duration_key') === '90'   ? 'selected' : '' }}>2.5×</option>
                                <option value="90+"  {{ old('duration_key') === '90+'  ? 'selected' : '' }}>2.6×</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Conditions --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Conditions</p>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Weather</label>
                            <select id="cr-weather" name="weather" class="form-select">
                                <option value="">— Not set —</option>
                                <option value="dry"    {{ old('weather', $championship->weather) === 'dry'    ? 'selected' : '' }}>Dry</option>
                                <option value="wet"    {{ old('weather', $championship->weather) === 'wet'    ? 'selected' : '' }}>Wet</option>
                                <option value="mixed"  {{ old('weather', $championship->weather) === 'mixed'  ? 'selected' : '' }}>Mixed</option>
                                <option value="random" {{ old('weather', $championship->weather) === 'random' ? 'selected' : '' }}>Random</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">In-game Time</label>
                            <select name="time_of_day" class="form-select">
                                <option value="">— Not set —</option>
                                <option value="day"     {{ old('time_of_day', $championship->time_of_day) === 'day'     ? 'selected' : '' }}>Day</option>
                                <option value="dusk"    {{ old('time_of_day', $championship->time_of_day) === 'dusk'    ? 'selected' : '' }}>Dusk</option>
                                <option value="night"   {{ old('time_of_day', $championship->time_of_day) === 'night'   ? 'selected' : '' }}>Night</option>
                                <option value="dynamic" {{ old('time_of_day', $championship->time_of_day) === 'dynamic' ? 'selected' : '' }}>Dynamic</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Max Drivers <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                            <input type="number" name="max_drivers" value="{{ old('max_drivers', $championship->max_drivers) }}"
                                   class="form-control @error('max_drivers') is-invalid @enderror"
                                   min="1">
                            @error('max_drivers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule</p>

                    <div class="col-sm-7 px-0">
                        <label class="form-label">Date & Time (BST) <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="cr-scheduled-at" name="scheduled_at"
                               value="{{ old('scheduled_at') }}"
                               class="form-control @error('scheduled_at') is-invalid @enderror">
                        @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Requirements --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Requirements <span class="fw-normal" style="text-transform:none">(optional)</span></p>

                    <div class="d-flex flex-column gap-3">
                        <div>
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="cr-sr-toggle" {{ old('sr_requirement') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="cr-sr-toggle">Safety Rating (SR)</label>
                            </div>
                            <div id="cr-sr-panel" style="{{ old('sr_requirement') ? '' : 'display:none' }}">
                                <select id="cr-sr-select" name="sr_requirement" class="form-select form-select-sm" style="max-width:280px">
                                    <option value="">— No requirement —</option>
                                    <option value="5" {{ old('sr_requirement') === '5' ? 'selected' : '' }}>SR ≥ 5.0 (grade B+)</option>
                                    <option value="7" {{ old('sr_requirement') === '7' ? 'selected' : '' }}>SR ≥ 7.0 (grade X+)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="cr-minrating-toggle" {{ old('min_rating') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="cr-minrating-toggle">XCL Rating (Min)</label>
                            </div>
                            <div id="cr-minrating-panel" style="{{ old('min_rating') ? '' : 'display:none' }}">
                                <select id="cr-minrating-select" name="min_rating" class="form-select form-select-sm" style="max-width:280px">
                                    <option value="">— No minimum —</option>
                                    @foreach(['rookie','bronze','silver','gold','platinum','alien'] as $r)
                                        <option value="{{ $r }}" {{ old('min_rating') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}+</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="cr-maxrating-toggle" {{ old('max_rating') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="cr-maxrating-toggle">XCL Rating (Max)</label>
                            </div>
                            <div id="cr-maxrating-panel" style="{{ old('max_rating') ? '' : 'display:none' }}">
                                <select id="cr-maxrating-select" name="max_rating" class="form-select form-select-sm" style="max-width:280px">
                                    <option value="">— No maximum —</option>
                                    @foreach(['rookie','bronze','silver','gold','platinum','alien'] as $r)
                                        <option value="{{ $r }}" {{ old('max_rating') === $r ? 'selected' : '' }}>{{ ucfirst($r) }} max</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Additional</p>
                    <div>
                        <label class="form-label">Description <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Additional round info…">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Add Round</button>
                <a href="{{ route('admin.championships.show', $championship) }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
            </div>
        </div>

        {{-- Right: preview + media --}}
        <div class="col-12 col-lg-4">
            <div style="position:sticky;top:80px">

                <p style="font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:.75rem">Preview</p>

                <div id="crp-preview" style="border-radius:14px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.18);border:1px solid rgba(124,58,237,.2)">

                    <div style="position:relative;height:185px;overflow:hidden;background:#111827">
                        <img id="crp-track-img" src="" alt="" loading="lazy"
                             style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:none">
                        <div id="crp-track-placeholder"
                             style="position:absolute;inset:0;background:linear-gradient(135deg,#1e1e3a 0%,#2d1b69 100%)"></div>

                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center">
                            <div id="crp-title-overlay" style="display:none;padding:8px 18px;background:rgba(0,0,0,.6);border-radius:8px;text-align:center">
                                <div id="crp-title-text" style="font-weight:900;font-style:italic;text-transform:uppercase;color:#fff;font-size:1rem;letter-spacing:.05em"></div>
                            </div>
                        </div>

                        <div id="crp-platforms" style="position:absolute;top:8px;right:8px;display:flex;gap:4px"></div>
                        <img id="crp-icon-img" src="" alt=""
                             style="position:absolute;bottom:8px;left:8px;width:34px;height:34px;object-fit:contain;border-radius:7px;background:rgba(0,0,0,.45);padding:4px;display:none">
                    </div>

                    <div style="background:#111827;border-top:1px solid rgba(255,255,255,.07);padding:12px 14px 14px">

                        <div id="crp-sessions-block" style="display:none;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.07)">
                            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px">
                                <span id="crp-round-label" style="font-size:.75rem;font-weight:900;font-style:italic;text-transform:uppercase;color:#7c3aed;letter-spacing:.04em">Round</span>
                                <span id="crp-xclr-label" style="font-size:.67rem;font-weight:700;color:#7c3aed;letter-spacing:.02em"></span>
                            </div>
                            <div id="crp-sessions-pills" style="display:flex;flex-wrap:wrap;gap:4px"></div>
                        </div>

                        <div id="crp-time"
                             style="font-size:.72rem;font-weight:900;color:#d4ee6a;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">— / — —</div>

                        <div id="crp-meta"
                             style="font-size:.72rem;color:#9ca3af;margin-bottom:10px">—</div>

                        <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;margin-bottom:10px">
                            <span id="crp-game-badge"
                                  style="font-size:.65rem;font-weight:900;text-transform:uppercase;letter-spacing:.05em;padding:2px 8px;border-radius:4px;color:#fff;background:{{ $championship->gameColor() }}">
                                {{ $championship->gameLabel() }}
                            </span>
                            <span id="crp-sr-badge" style="display:none;align-items:center;gap:4px">
                                <span id="crp-sr-circle"
                                      style="width:20px;height:20px;border-radius:50%;background:#1a1a2e;border:2px solid #dc2626;display:inline-flex;align-items:center;justify-content:center;color:#dc2626;font-size:.58rem;font-weight:900;flex-shrink:0">B</span>
                                <span id="crp-sr-text"
                                      style="font-size:.65rem;font-weight:900;color:#e5e7eb;white-space:nowrap">SR 5.0+</span>
                            </span>
                            <span id="crp-xcl-badge"
                                  style="display:none;font-size:.63rem;font-weight:900;text-transform:capitalize;padding:2px 8px;border-radius:4px;border:1px solid rgba(205,127,50,.4);background:rgba(205,127,50,.15);color:#cd7f32"></span>
                            <span id="crp-open-badge"
                                  style="font-size:.65rem;font-weight:900;text-transform:uppercase;padding:2px 8px;border-radius:4px;background:rgba(212,238,106,.12);color:#d4ee6a;border:1px solid rgba(212,238,106,.25)">OPEN</span>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:5px;font-size:.75rem">
                            <div style="display:flex;gap:10px">
                                <span style="color:#6b7280;width:58px;flex-shrink:0">CLASS</span>
                                <span id="crp-class" style="color:#e5e7eb;font-weight:600">Open</span>
                            </div>
                            <div style="display:flex;gap:10px">
                                <span style="color:#6b7280;width:58px;flex-shrink:0">TRACK</span>
                                <span id="crp-track-name" style="color:#e5e7eb;font-weight:600">—</span>
                            </div>
                            <div style="display:flex;gap:10px">
                                <span style="color:#6b7280;width:58px;flex-shrink:0">WEATHER</span>
                                <span id="crp-weather" style="color:#e5e7eb;font-weight:600">—</span>
                            </div>
                        </div>

                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:9px;border-top:1px solid rgba(255,255,255,.07)">
                            <span id="crp-duration"
                                  style="display:none;font-size:.68rem;font-weight:900;text-transform:uppercase;color:#111827;background:#d4ee6a;border-radius:999px;padding:2px 10px"></span>
                            <span id="crp-date" style="font-size:.7rem;color:#9ca3af;margin-left:auto">—</span>
                        </div>

                    </div>
                </div>

                <p style="font-size:.68rem;color:#9ca3af;margin-top:.6rem;text-align:center;letter-spacing:.02em">Updates as you fill in the form</p>

                <div class="admin-card mt-4 mb-4">
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Media</p>
                        <x-media-picker name="image" label="Background Image" />
                        <div class="mt-3">
                            <x-media-picker name="icon" label="Event Icon" currentType="icon" filterDefault="icon" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</form>

<script>
(function () {
    [['cr-sr-toggle','cr-sr-panel'],['cr-minrating-toggle','cr-minrating-panel'],['cr-maxrating-toggle','cr-maxrating-panel']].forEach(([tid,pid]) => {
        const t = document.getElementById(tid), p = document.getElementById(pid);
        t?.addEventListener('change', () => { p.style.display = t.checked ? '' : 'none'; });
    });
})();

// ── Live Event Preview ──────────────────────────────────────────────────────
(function () {
    const trackPreviewUrls = @json($trackPreviewUrls ?? []);

    const $ = id => document.getElementById(id);
    const prev = {
        trackImg:      $('crp-track-img'),
        trackPh:       $('crp-track-placeholder'),
        titleOverlay:  $('crp-title-overlay'),
        titleText:     $('crp-title-text'),
        platforms:     $('crp-platforms'),
        sessionsBlock: $('crp-sessions-block'),
        sessionsPills: $('crp-sessions-pills'),
        xclrLabel:     $('crp-xclr-label'),
        roundLabel:    $('crp-round-label'),
        time:          $('crp-time'),
        meta:          $('crp-meta'),
        srBadge:       $('crp-sr-badge'),
        srCircle:      $('crp-sr-circle'),
        srText:        $('crp-sr-text'),
        xclBadge:      $('crp-xcl-badge'),
        openBadge:     $('crp-open-badge'),
        classEl:       $('crp-class'),
        trackName:     $('crp-track-name'),
        weatherEl:     $('crp-weather'),
        duration:      $('crp-duration'),
        date:          $('crp-date'),
        iconImg:       $('crp-icon-img'),
    };

    let customBgUrl = '';

    const wxIcons  = { dry: '☀', wet: '🌧', mixed: '⛅', random: '🎲' };
    const wxLabels = { dry: 'Dry', wet: 'Wet', mixed: 'Mixed', random: 'Random' };
    const srTierMap = {
        '3': ['B','#dc2626'], '4': ['B','#dc2626'],
        '5': ['A','#16a34a'], '6': ['A','#16a34a'],
        '7': ['X','#2563eb'], '8': ['Y','#eab308'], '9': ['Z','#7c3aed'],
    };
    const xclColors = {
        rookie: '#ef4444', bronze: '#cd7f32', silver: '#9ca3af',
        gold: '#f59e0b', platinum: '#7c3aed', alien: '#10b981',
    };
    const dkMap = {
        '': '1.0×', '15': '0.6×', '20': '0.8×', '30': '1.0×',
        '30+': '1.2×', '30++': '1.3×', '45': '1.5×', '45+': '1.6×',
        '60': '2.0×', '60+': '2.1×', '90': '2.5×', '90+': '2.6×',
    };

    function updatePreview() {
        const track    = ($('cr-track')         || {}).value || '';
        const carClass = ($('cr-car-class')     || {}).value || '';
        const pracMin  = ($('cr-practice')      || {}).value || '';
        const qualiMin = ($('cr-qualifying')    || {}).value || '';
        const raceMin  = ($('cr-race')          || {}).value || '';
        const dkVal    = ($('cr-duration-key')  || {}).value || '';
        const weather  = ($('cr-weather')       || {}).value || '';
        const schedVal = ($('cr-scheduled-at')  || {}).value || '';
        const title    = ($('cr-title')         || {}).value || '';
        const roundNum = (document.querySelector('[name=round_number]') || {}).value || '';
        const srToggle = $('cr-sr-toggle');
        const srSelect = $('cr-sr-select');
        const minToggle = $('cr-minrating-toggle');
        const minSelect = $('cr-minrating-select');
        const maxToggle = $('cr-maxrating-toggle');
        const srOn  = srToggle  ? srToggle.checked  : false;
        const srVal = srOn && srSelect  ? srSelect.value  : '';
        const minOn = minToggle ? minToggle.checked : false;
        const minVal = minOn && minSelect ? minSelect.value : '';
        const maxOn = maxToggle ? maxToggle.checked : false;

        // Track background (custom bg image overrides track image)
        const bgUrl = customBgUrl || trackPreviewUrls[track] || '';
        if (bgUrl && prev.trackImg) {
            prev.trackImg.src = bgUrl;
            prev.trackImg.style.display = '';
            if (prev.trackPh) prev.trackPh.style.display = 'none';
        } else {
            if (prev.trackImg) prev.trackImg.style.display = 'none';
            if (prev.trackPh) prev.trackPh.style.display  = '';
        }

        // Title overlay
        if (prev.titleText) prev.titleText.textContent = title;
        if (prev.titleOverlay) prev.titleOverlay.style.display = title ? '' : 'none';

        // Round label
        if (prev.roundLabel) prev.roundLabel.textContent = roundNum ? 'Round ' + roundNum : 'Round';

        // Sessions block
        const hasSessions = pracMin || qualiMin || raceMin;
        if (prev.sessionsBlock) {
            if (hasSessions || dkVal) {
                if (prev.sessionsPills) {
                    prev.sessionsPills.innerHTML = '';
                    [
                        { key: 'P',  mins: pracMin,  bg: '#1f2937', color: '#9ca3af' },
                        { key: 'Q',  mins: qualiMin, bg: '#292524', color: '#f59e0b' },
                        { key: 'R1', mins: raceMin,  bg: '#2e1065', color: '#a78bfa' },
                    ].forEach(function (s) {
                        if (!s.mins) return;
                        const pill = document.createElement('span');
                        pill.style.cssText = 'font-size:.7rem;font-weight:600;border-radius:6px;padding:3px 8px;background:' + s.bg + ';color:' + s.color;
                        pill.textContent = s.key + " " + s.mins + "'";
                        prev.sessionsPills.appendChild(pill);
                    });
                }
                if (prev.xclrLabel) prev.xclrLabel.textContent = (dkMap[dkVal] || '1.0×') + ' XCL-R';
                prev.sessionsBlock.style.display = '';
            } else {
                prev.sessionsBlock.style.display = 'none';
            }
        }

        // Time / date / meta
        if (schedVal) {
            const d       = new Date(schedVal);
            const dayStr  = d.toLocaleDateString('en-GB', { weekday: 'long' }).toUpperCase();
            const timeStr = d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
            const dateStr = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            if (prev.time) prev.time.textContent = dayStr + ' / ' + timeStr;
            if (prev.date) prev.date.textContent = dateStr;
            if (prev.meta) prev.meta.textContent = dateStr + (track ? ' | ' + track : '');
        } else {
            if (prev.time) prev.time.textContent = '— / — —';
            if (prev.meta) prev.meta.textContent = track || '—';
            if (prev.date) prev.date.textContent = '—';
        }

        // SR tier badge
        if (prev.srBadge) {
            if (srOn && srVal && srTierMap[srVal]) {
                const [letter, color] = srTierMap[srVal];
                if (prev.srCircle) { prev.srCircle.textContent = letter; prev.srCircle.style.borderColor = color; prev.srCircle.style.color = color; }
                if (prev.srText)   prev.srText.textContent = 'SR ' + srVal + '.0+';
                prev.srBadge.style.display = 'inline-flex';
            } else if (srOn) {
                if (prev.srCircle) { prev.srCircle.textContent = 'SR'; prev.srCircle.style.borderColor = '#9ca3af'; prev.srCircle.style.color = '#9ca3af'; }
                if (prev.srText)   prev.srText.textContent = 'SR';
                prev.srBadge.style.display = 'inline-flex';
            } else {
                prev.srBadge.style.display = 'none';
            }
        }

        // XCL Rating badge
        if (prev.xclBadge) {
            if (minOn && minVal && xclColors[minVal]) {
                const color = xclColors[minVal];
                prev.xclBadge.textContent       = minVal.charAt(0).toUpperCase() + minVal.slice(1) + '+';
                prev.xclBadge.style.color       = color;
                prev.xclBadge.style.background  = color + '22';
                prev.xclBadge.style.borderColor = color + '66';
                prev.xclBadge.style.display     = '';
            } else {
                prev.xclBadge.style.display = 'none';
            }
        }

        // OPEN badge
        if (prev.openBadge) prev.openBadge.style.display = (!srOn && !minOn && !maxOn) ? '' : 'none';

        // Class / track / weather
        if (prev.classEl)   prev.classEl.textContent  = carClass || 'Open';
        if (prev.trackName) prev.trackName.textContent = track    || '—';
        if (prev.weatherEl) {
            const icon  = wxIcons[weather]  || '';
            const label = wxLabels[weather] || (weather ? weather : '—');
            prev.weatherEl.textContent = icon ? icon + ' ' + label : label;
        }

        // Duration badge
        if (prev.duration) {
            if (raceMin) {
                prev.duration.textContent   = raceMin + ' MIN';
                prev.duration.style.display = '';
            } else {
                prev.duration.style.display = 'none';
            }
        }
    }

    ['cr-weather','cr-duration-key',
     'cr-sr-toggle','cr-sr-select','cr-minrating-toggle','cr-minrating-select','cr-maxrating-toggle','cr-maxrating-select']
        .forEach(id => { const el = $(id); if (el) el.addEventListener('change', updatePreview); });
    ['cr-title','cr-track','cr-practice','cr-qualifying','cr-race']
        .forEach(id => { const el = $(id); if (el) el.addEventListener('input', updatePreview); });
    document.querySelector('[name=round_number]')?.addEventListener('input', updatePreview);
    const schedEl = $('cr-scheduled-at');
    if (schedEl) schedEl.addEventListener('change', updatePreview);

    document.addEventListener('mp:change', e => {
        const { name, url } = e.detail;
        if (name === 'image') {
            customBgUrl = url || '';
            updatePreview();
        } else if (name === 'icon') {
            if (prev.iconImg) {
                if (url) {
                    prev.iconImg.src = url;
                    prev.iconImg.style.display = '';
                } else {
                    prev.iconImg.style.display = 'none';
                    prev.iconImg.src = '';
                }
            }
        }
    });

    updatePreview();
})();
</script>

@endsection
