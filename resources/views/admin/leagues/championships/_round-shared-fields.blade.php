{{-- Sessions/weather/server/notes fields shared by both round-create forms
     (single and bulk) — one round's worth of settings in single mode, the
     whole bulk batch's shared settings in bulk mode. $idPrefix keeps element
     ids unique since both forms exist in the DOM at once (only one is shown).
     $sessionDefaults/$servers/$league/$championship come from the parent view. --}}
<div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Sessions &amp; Conditions</p>

    <div class="row g-3 mb-3">
        <div class="col-6 col-sm-2">
            <label class="form-label" style="font-size:.75rem">Practice <span class="fw-normal text-secondary">(min)</span></label>
            <input type="number" name="practice_duration" value="{{ old('practice_duration', $sessionDefaults->practice_enabled ? $sessionDefaults->practice_length_minutes : '') }}"
                   class="form-control form-control-sm @error('practice_duration') is-invalid @enderror" min="1" max="999" placeholder="—">
            @error('practice_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-2">
            <label class="form-label" style="font-size:.75rem">Quali <span class="fw-normal text-secondary">(min)</span></label>
            <input type="number" name="qualifying_duration" value="{{ old('qualifying_duration', $sessionDefaults->qualifying_enabled ? $sessionDefaults->qualifying_length_minutes : '') }}"
                   class="form-control form-control-sm @error('qualifying_duration') is-invalid @enderror" min="1" max="999" placeholder="—">
            @error('qualifying_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-2">
            <label class="form-label" style="font-size:.75rem">Race <span class="text-danger">*</span> <span class="fw-normal text-secondary">(min)</span></label>
            <input type="number" name="race_duration" value="{{ old('race_duration', $sessionDefaults->race_length_minutes) }}"
                   class="form-control form-control-sm @error('race_duration') is-invalid @enderror" min="1" max="999" required>
            @error('race_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-3">
            <label class="form-label" style="font-size:.75rem">Start Time <span class="fw-normal text-secondary">(in-game)</span></label>
            <input type="time" name="time_of_day" class="form-control form-control-sm" value="{{ old('time_of_day', $championship->settings->schedule->time_of_day ?? '14:00') }}" step="3600">
        </div>
        <div class="col-6 col-sm-3">
            <label class="form-label" style="font-size:.75rem">Ambient Temp (°C)</label>
            <input type="number" name="ambient_temp" value="{{ old('ambient_temp', $sessionDefaults->ambient_temp) }}"
                   class="form-control form-control-sm @error('ambient_temp') is-invalid @enderror" placeholder="Server default">
            @error('ambient_temp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row g-3 align-items-end">
        <div class="col-sm-3">
            <label class="form-label" style="font-size:.75rem">Weather</label>
            @php
                // "Randomised" maps cleanly onto the round's own "Random" option;
                // "fixed" doesn't map onto a single dry/wet/mixed value, so it's
                // left unset here — the round falls through to the server's own
                // event_defaults, same as today.
                $weatherDefault = old('weather', $sessionDefaults->weather_mode === 'randomised' ? 'random' : '');
            @endphp
            <select name="weather" id="{{ $idPrefix }}-weather" class="form-select form-select-sm">
                <option value="">— Not set —</option>
                <option value="dry"    {{ $weatherDefault === 'dry'    ? 'selected' : '' }}>Dry</option>
                <option value="wet"    {{ $weatherDefault === 'wet'    ? 'selected' : '' }}>Wet</option>
                <option value="mixed"  {{ $weatherDefault === 'mixed'  ? 'selected' : '' }}>Mixed</option>
                <option value="random" {{ $weatherDefault === 'random' ? 'selected' : '' }}>Random</option>
            </select>
        </div>
        <div class="col-sm-4">
            <label class="form-label" style="font-size:.75rem">Dynamic Weather <span class="fw-normal text-secondary" style="text-transform:none">(how much it changes mid-session)</span></label>
            <select name="weather_randomness" class="form-select form-select-sm">
                <option value="" {{ old('weather_randomness') === '' ? 'selected' : '' }}>— Not set —</option>
                <option value="0" {{ old('weather_randomness') === '0' ? 'selected' : '' }}>0 — Static</option>
                @foreach(['1','2','3','4'] as $n)
                <option value="{{ $n }}" {{ old('weather_randomness') === $n ? 'selected' : '' }}>{{ $n }} — Realistic</option>
                @endforeach
                @foreach(['5','6','7'] as $n)
                <option value="{{ $n }}" {{ old('weather_randomness') === $n ? 'selected' : '' }}>{{ $n }} — Sensational</option>
                @endforeach
                <option value="random" {{ old('weather_randomness') === 'random' ? 'selected' : '' }}>Randomize</option>
            </select>
        </div>
        <div class="col-sm-3" id="{{ $idPrefix }}-rain-level-wrap" style="display:none">
            @php $savedRainLevel = old('rain_level', $sessionDefaults->rain_level ?: 0.3); @endphp
            <label class="form-label" style="font-size:.75rem">Rain Level <span class="fw-normal text-secondary">(0–1)</span></label>
            <div class="d-flex align-items-center gap-2">
                <input type="range" name="rain_level" id="{{ $idPrefix }}-rain-level" min="0" max="1" step="0.1"
                       value="{{ $savedRainLevel }}" class="form-range flex-grow-1" style="accent-color:#7c3aed">
                <span id="{{ $idPrefix }}-rain-level-val" class="fw-bold text-dark" style="min-width:2rem;font-size:.82rem;text-align:right">
                    {{ number_format($savedRainLevel, 1) }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Rating, Pitstops &amp; Practice</p>

    <div class="row g-3 mb-3">
        <div class="col-6 col-sm-3">
            <label class="form-label" style="font-size:.75rem">XCL-R Multiplier</label>
            <input type="number" name="xcl_r_multiplier" step="0.1" value="{{ old('xcl_r_multiplier', $sessionDefaults->xcl_r_multiplier) }}"
                   class="form-control form-control-sm @error('xcl_r_multiplier') is-invalid @enderror" placeholder="1.0">
            @error('xcl_r_multiplier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-3">
            <label class="form-label" style="font-size:.75rem">Mandatory Pitstops</label>
            <input type="number" name="pitstop_count" min="0" max="9" value="{{ old('pitstop_count', $sessionDefaults->pitstop_count) }}"
                   class="form-control form-control-sm @error('pitstop_count') is-invalid @enderror">
            @error('pitstop_count')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-3">
            <label class="form-label" style="font-size:.75rem">Min. Stop Time (s)</label>
            <input type="number" name="min_stop_secs" min="1" max="3600" value="{{ old('min_stop_secs', $sessionDefaults->min_stop_secs) }}"
                   class="form-control form-control-sm @error('min_stop_secs') is-invalid @enderror" placeholder="—">
            @error('min_stop_secs')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-3 d-flex align-items-end pb-1">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="has_practice_server" id="{{ $idPrefix }}-practice" value="1"
                       {{ old('has_practice_server', $sessionDefaults->has_practice_server) ? 'checked' : '' }}>
                <label class="form-check-label fw-bold" for="{{ $idPrefix }}-practice" style="font-size:.78rem">Practice Server</label>
            </div>
        </div>
    </div>

    @if($championship->settings->format->driver_swaps_enabled ?? false)
    <div class="row g-3 mb-3">
        <div class="col-6 col-sm-4">
            <label class="form-label" style="font-size:.75rem">Max. Stint Time (min)</label>
            <input type="number" name="driver_stint_time_mins" value="{{ old('driver_stint_time_mins', $championship->settings->format->driver_stint_time_mins) }}"
                   class="form-control form-control-sm @error('driver_stint_time_mins') is-invalid @enderror" placeholder="No limit">
            @error('driver_stint_time_mins')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-4">
            <label class="form-label" style="font-size:.75rem">Max Driving Time / Driver (min)</label>
            <input type="number" name="max_total_driving_time_mins" value="{{ old('max_total_driving_time_mins', $championship->settings->format->max_total_driving_time_mins) }}"
                   class="form-control form-control-sm @error('max_total_driving_time_mins') is-invalid @enderror" placeholder="No limit">
            @error('max_total_driving_time_mins')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-4 d-flex align-items-end pb-1">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="mandatory_driver_swap" id="{{ $idPrefix }}-swap" value="1"
                       {{ old('mandatory_driver_swap', $championship->settings->format->mandatory_driver_swap) ? 'checked' : '' }}>
                <label class="form-check-label fw-bold" for="{{ $idPrefix }}-swap" style="font-size:.78rem">Mandatory Pitstop Swap</label>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" style="font-size:.75rem">Practice Server Notes <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
            <textarea name="practice_notes" rows="2" class="form-control form-control-sm">{{ old('practice_notes', $sessionDefaults->practice_notes) }}</textarea>
        </div>
    </div>
</div>

<div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
    <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Server <span class="fw-normal" style="text-transform:none">(optional)</span></p>
    @if($servers->isEmpty())
    <p class="text-secondary mb-0" style="font-size:.82rem">
        No servers assigned to {{ $league->name }} yet — an XCL admin needs to assign one from the League page before rounds can auto-push.
    </p>
    @else
    <select name="ftp_server_id" class="form-select form-select-sm">
        <option value="">— No server assigned —</option>
        @foreach($servers as $srv)
        <option value="{{ $srv->id }}" {{ (string) old('ftp_server_id', $championship->ftp_server_id) === (string) $srv->id ? 'selected' : '' }}>{{ $srv->name }}</option>
        @endforeach
    </select>
    @endif
</div>

<div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
    <label class="form-label" style="font-size:.75rem">Notes <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
    <textarea name="description" rows="2" class="form-control form-control-sm">{{ old('description') }}</textarea>
</div>

<script>
(function () {
    var weatherSel = document.getElementById('{{ $idPrefix }}-weather');
    var rainWrap   = document.getElementById('{{ $idPrefix }}-rain-level-wrap');
    var rainRange  = document.getElementById('{{ $idPrefix }}-rain-level');
    var rainVal    = document.getElementById('{{ $idPrefix }}-rain-level-val');
    if (!weatherSel) return;

    function updateRainVisibility() {
        rainWrap.style.display = ['wet', 'mixed'].includes(weatherSel.value) ? '' : 'none';
    }
    weatherSel.addEventListener('change', updateRainVisibility);
    rainRange.addEventListener('input', function () { rainVal.textContent = parseFloat(rainRange.value).toFixed(1); });
    updateRainVisibility();
})();
</script>
