{{-- Server/Notes/Session-override fields shared by round-create (single + bulk)
     AND round-edit — one round's worth of settings. $idPrefix keeps element ids
     unique when more than one copy exists in the DOM at once (round-create's
     single/bulk panels). $defaults resolves each field's starting value: the
     championship's Sessions-step defaults for a brand new round, or the round's
     own saved values when editing one -- the parent view computes this array,
     this partial just renders it. $openByDefault controls whether the override
     panel starts expanded: false for a fresh round (every field starts blank/
     inherited, nothing to see yet), true when editing an existing round (its
     current values are worth seeing right away).
     $servers/$league/$championship come from the parent view. --}}
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
        <option value="{{ $srv->id }}" {{ (string) old('ftp_server_id', $defaults['ftp_server_id']) === (string) $srv->id ? 'selected' : '' }}>{{ $srv->name }}</option>
        @endforeach
    </select>
    @endif
</div>

<div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
    <label class="form-label" style="font-size:.75rem">Notes <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
    <textarea name="description" rows="2" class="form-control form-control-sm">{{ old('description', $defaults['description']) }}</textarea>
</div>

<details class="px-4 py-3" style="border-top:1px solid #f3f4f6" {{ ($openByDefault ?? false) || $errors->any() ? 'open' : '' }}>
    <summary class="fw-black text-uppercase fst-italic" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af;cursor:pointer;list-style:none">
        <span style="display:inline-block;width:.8em">▸</span> Override Session Defaults for This Round
    </summary>

    <div class="row g-3 mb-3 mt-2">
        <div class="col-6 col-sm-2">
            <label class="form-label" style="font-size:.75rem">Practice <span class="fw-normal text-secondary">(min)</span></label>
            <input type="number" name="practice_duration" value="{{ old('practice_duration', $defaults['practice_duration']) }}"
                   class="form-control form-control-sm @error('practice_duration') is-invalid @enderror" min="1" max="999" placeholder="—">
            @error('practice_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-2">
            <label class="form-label" style="font-size:.75rem">Quali <span class="fw-normal text-secondary">(min)</span></label>
            <input type="number" name="qualifying_duration" value="{{ old('qualifying_duration', $defaults['qualifying_duration']) }}"
                   class="form-control form-control-sm @error('qualifying_duration') is-invalid @enderror" min="1" max="999" placeholder="—">
            @error('qualifying_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-2">
            <label class="form-label" style="font-size:.75rem">Race <span class="fw-normal text-secondary">(min)</span></label>
            <input type="number" name="race_duration" value="{{ old('race_duration', $defaults['race_duration']) }}"
                   class="form-control form-control-sm @error('race_duration') is-invalid @enderror" min="1" max="999" placeholder="{{ $defaults['race_duration'] }}">
            @error('race_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-3">
            <label class="form-label" style="font-size:.75rem">Start Time <span class="fw-normal text-secondary">(in-game)</span></label>
            <input type="time" name="time_of_day" class="form-control form-control-sm" value="{{ old('time_of_day', $defaults['time_of_day']) }}" step="3600">
        </div>
        <div class="col-6 col-sm-3">
            <label class="form-label" style="font-size:.75rem">Ambient Temp (°C)</label>
            <input type="number" name="ambient_temp" value="{{ old('ambient_temp', $defaults['ambient_temp']) }}"
                   class="form-control form-control-sm @error('ambient_temp') is-invalid @enderror" placeholder="Server default">
            @error('ambient_temp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row g-3 align-items-end mb-3">
        <div class="col-sm-3">
            <label class="form-label" style="font-size:.75rem">Weather</label>
            <select name="weather" id="{{ $idPrefix }}-weather" class="form-select form-select-sm">
                <option value="">— Not set —</option>
                <option value="dry"    {{ old('weather', $defaults['weather']) === 'dry'    ? 'selected' : '' }}>Dry</option>
                <option value="wet"    {{ old('weather', $defaults['weather']) === 'wet'    ? 'selected' : '' }}>Wet</option>
                <option value="mixed"  {{ old('weather', $defaults['weather']) === 'mixed'  ? 'selected' : '' }}>Mixed</option>
                <option value="random" {{ old('weather', $defaults['weather']) === 'random' ? 'selected' : '' }}>Random</option>
            </select>
        </div>
        <div class="col-sm-4">
            <label class="form-label" style="font-size:.75rem">Dynamic Weather <span class="fw-normal text-secondary" style="text-transform:none">(how much it changes mid-session)</span></label>
            @php $wr = old('weather_randomness', $defaults['weather_randomness']); @endphp
            <select name="weather_randomness" class="form-select form-select-sm">
                <option value="" {{ $wr === null || $wr === '' ? 'selected' : '' }}>— Not set —</option>
                <option value="0" {{ (string) $wr === '0' ? 'selected' : '' }}>0 — Static</option>
                @foreach(['1','2','3','4'] as $n)
                <option value="{{ $n }}" {{ (string) $wr === $n ? 'selected' : '' }}>{{ $n }} — Realistic</option>
                @endforeach
                @foreach(['5','6','7'] as $n)
                <option value="{{ $n }}" {{ (string) $wr === $n ? 'selected' : '' }}>{{ $n }} — Sensational</option>
                @endforeach
                <option value="random" {{ $wr === 'random' ? 'selected' : '' }}>Randomize</option>
            </select>
        </div>
        <div class="col-sm-3">
            {{-- A standalone option, not tucked behind Weather=wet/mixed — a league
                 may want a rain chance set regardless of the fixed/random weather
                 pick above. --}}
            @php $savedRainLevel = old('rain_level', $defaults['rain_level']); @endphp
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

    <div class="row g-3 mb-3">
        <div class="col-6 col-sm-3">
            <label class="form-label" style="font-size:.75rem">XCL-R Multiplier</label>
            <input type="number" name="xcl_r_multiplier" step="0.1" min="0.6" max="2.5" value="{{ old('xcl_r_multiplier', $defaults['xcl_r_multiplier']) }}"
                   class="form-control form-control-sm @error('xcl_r_multiplier') is-invalid @enderror" placeholder="1.0"
                   {{ $championship->xcl_rating_enabled ? '' : 'disabled' }}>
            @error('xcl_r_multiplier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @unless($championship->xcl_rating_enabled)
            <div class="form-text" style="font-size:.68rem;color:#9ca3af">Only available once XCL Rating is enabled for this championship.</div>
            @endunless
        </div>
        <div class="col-6 col-sm-3">
            <label class="form-label" style="font-size:.75rem">Mandatory Pitstops</label>
            <input type="number" name="pitstop_count" id="{{ $idPrefix }}-pitstop-count" min="0" max="9" value="{{ old('pitstop_count', $defaults['pitstop_count']) }}"
                   class="form-control form-control-sm @error('pitstop_count') is-invalid @enderror">
            @error('pitstop_count')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-3 d-flex flex-column justify-content-end pb-1">
            @php $fixedStopDefault = old('fixed_stop_time', $defaults['fixed_stop_time']); @endphp
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="fixed_stop_time" id="{{ $idPrefix }}-fixed-stop" value="1"
                       {{ $fixedStopDefault ? 'checked' : '' }}>
                <label class="form-check-label fw-bold" for="{{ $idPrefix }}-fixed-stop" style="font-size:.78rem">Fixed Stop Time</label>
            </div>
            <div class="form-text mt-0" style="font-size:.68rem;color:#9ca3af">Off = game default (dynamic). On = a fixed 25 seconds.</div>
        </div>
    </div>

    @if($championship->settings->format->driver_swaps_enabled ?? false)
    <div class="row g-3">
        <div class="col-6 col-sm-4">
            <label class="form-label" style="font-size:.75rem">Max. Stint Time (min)</label>
            <input type="number" name="driver_stint_time_mins" value="{{ old('driver_stint_time_mins', $defaults['driver_stint_time_mins']) }}"
                   class="form-control form-control-sm @error('driver_stint_time_mins') is-invalid @enderror" placeholder="No limit">
            @error('driver_stint_time_mins')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-4">
            <label class="form-label" style="font-size:.75rem">Max Driving Time / Driver (min)</label>
            <input type="number" name="max_total_driving_time_mins" value="{{ old('max_total_driving_time_mins', $defaults['max_total_driving_time_mins']) }}"
                   class="form-control form-control-sm @error('max_total_driving_time_mins') is-invalid @enderror" placeholder="No limit">
            @error('max_total_driving_time_mins')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-sm-4 d-flex align-items-end pb-1">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="mandatory_driver_swap" id="{{ $idPrefix }}-swap" value="1"
                       {{ old('mandatory_driver_swap', $defaults['mandatory_driver_swap']) ? 'checked' : '' }}>
                <label class="form-check-label fw-bold" for="{{ $idPrefix }}-swap" style="font-size:.78rem">Mandatory Pitstop Swap</label>
            </div>
        </div>
    </div>
    @endif
</details>

<script>
(function () {
    var rainRange  = document.getElementById('{{ $idPrefix }}-rain-level');
    var rainVal    = document.getElementById('{{ $idPrefix }}-rain-level-val');
    if (rainRange) {
        rainRange.addEventListener('input', function () { rainVal.textContent = parseFloat(rainRange.value).toFixed(1); });
    }
})();
</script>
