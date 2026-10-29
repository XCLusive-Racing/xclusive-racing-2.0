{{-- One round's settings, shared by round-create (single + bulk) and round-edit.
     $idPrefix keeps ids unique when two copies are on the page; $defaults holds the
     starting values (the championship's Sessions defaults for a new round, the
     round's own values when editing); $openByDefault opens the session block.
     $servers/$league/$championship/$sessionFormats come from the parent view. --}}
@php
    $roundTypeOptions = $league->roundTypeOptions();
    $currentRoundType = old('round_type', $defaults['round_type'] ?? null);
    $toggle = function (string $name, string $label, bool $on, string $id) {
        return '<label class="cw-toggle"><input type="checkbox" name="'.$name.'" id="'.$id.'" value="1"'.($on ? ' checked' : '').'>'
            .'<span class="cw-toggle__dot"></span>'.e($label).'</label>';
    };
@endphp

<div class="cw-section">
    <p class="cw-section__title">Server &amp; Type</p>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="cw-label">Server <span class="cw-optional">(optional)</span></label>
            @if($servers->isEmpty())
            <div class="cw-help mt-0">{{ $league->name }} has no server for this game yet. An XCL admin assigns one on the League page.</div>
            @else
            <select name="ftp_server_id" class="form-select form-select-sm">
                <option value="">No server</option>
                @foreach($servers as $srv)
                <option value="{{ $srv->id }}" @selected((string) old('ftp_server_id', $defaults['ftp_server_id']) === (string) $srv->id)>{{ $srv->name }}</option>
                @endforeach
            </select>
            @endif
        </div>

        {{-- Built-in types plus the league's own; "+ Add type…" adds one to the
             league's list (ChampionshipWizardController::resolveRoundType()). --}}
        <div class="col-md-6" data-round-type>
            <label class="cw-label">Round Type <span class="cw-optional">(optional)</span></label>
            <div class="d-flex flex-wrap gap-2">
                <select name="round_type" data-round-type-select class="form-select form-select-sm" style="max-width:220px">
                    <option value="">None</option>
                    @foreach($roundTypeOptions as $option)
                    <option value="{{ $option }}" @selected($currentRoundType === $option)>{{ $option }}</option>
                    @endforeach
                    @if($currentRoundType && $currentRoundType !== \App\Models\Race::NEW_ROUND_TYPE && !in_array($currentRoundType, $roundTypeOptions, true))
                    <option value="{{ $currentRoundType }}" selected>{{ $currentRoundType }}</option>
                    @endif
                    <option value="{{ \App\Models\Race::NEW_ROUND_TYPE }}" @selected($currentRoundType === \App\Models\Race::NEW_ROUND_TYPE)>+ Add type…</option>
                </select>
                <input type="text" name="round_type_new" data-round-type-new maxlength="50" value="{{ old('round_type_new') }}"
                       placeholder="New type, e.g. Night Race" class="form-control form-control-sm" style="max-width:220px"
                       @if($currentRoundType !== \App\Models\Race::NEW_ROUND_TYPE) hidden @endif>
            </div>
        </div>

        <div class="col-md-6">
            <label class="cw-label">Points</label>
            @php $pointsMultiplier = (float) old('points_multiplier', $defaults['points_multiplier'] ?? 1); @endphp
            <select name="points_multiplier" class="form-select form-select-sm @error('points_multiplier') is-invalid @enderror">
                @foreach(\App\Models\Race::POINTS_MULTIPLIERS as $multiplier)
                <option value="{{ $multiplier }}" @selected($pointsMultiplier === $multiplier)>{{ $multiplier === 1.0 ? 'Normal' : \App\Models\Race::multiplierLabel($multiplier) }}</option>
                @endforeach
            </select>
            <div class="cw-help">Every point in this round counts this many times.</div>
            @error('points_multiplier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        @php
            $streamUrl = old('official_stream_url', $defaults['official_stream_url'] ?? null);
            $streamOn = (bool) old('official_stream', filled($streamUrl));
        @endphp
        <div class="col-md-6" data-official-stream>
            <label class="cw-label">Official Stream</label>
            <div class="d-flex flex-column gap-2">
                {!! $toggle('official_stream', 'Streamed live', $streamOn, $idPrefix.'-official-stream') !!}
                <input type="url" name="official_stream_url" value="{{ $streamUrl }}" maxlength="255"
                       placeholder="https://twitch.tv/xclusiveracing" data-official-stream-url
                       class="form-control form-control-sm @error('official_stream_url') is-invalid @enderror"
                       @if(!$streamOn) hidden @endif>
            </div>
            <div class="cw-help">Linked on the championship and event pages.</div>
            @error('official_stream_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="cw-label">Notes <span class="cw-optional">(optional)</span></label>
            <textarea name="description" rows="1" class="form-control form-control-sm">{{ old('description', $defaults['description']) }}</textarea>
        </div>
    </div>
</div>

{{-- Race format: picking one fills the session block below (the round keeps its own
     editable copy). The <details> must stay this div's next sibling: the picker
     script opens it. --}}
<div class="cw-section">
    <p class="cw-section__title">Race Format <span class="cw-optional">(optional)</span></p>
    @if($sessionFormats->isEmpty())
    <p class="cw-section__note mb-0">
        No race formats yet. <a href="{{ route('admin.leagues.session-formats.index', $league) }}">Create one</a> to reuse lengths and multi-race setups.
    </p>
    @else
    <select name="session_format_id" data-session-format class="form-select form-select-sm" style="max-width:420px">
        <option value="">Custom (set below)</option>
        @foreach($sessionFormats as $sf)
        <option value="{{ $sf->id }}" {{ (string) old('session_format_id', $defaults['session_format_id'] ?? '') === (string) $sf->id ? 'selected' : '' }}
                data-values="{{ json_encode([
                    'practice_duration' => $sf->practice_duration,
                    'qualifying_duration' => $sf->qualifying_duration,
                    'race_lengths' => $sf->raceLengthsText(),
                    'pitstop_count' => $sf->pitstop_count,
                    'tyre_set_count' => $sf->tyre_set_count,
                    'fixed_stop_time' => $sf->fixed_stop_time,
                    'mandatory_refuelling' => $sf->mandatory_refuelling,
                    'mandatory_tyre_change' => $sf->mandatory_tyre_change,
                    'driver_stint_time_mins' => $sf->driver_stint_time_mins,
                    'max_total_driving_time_mins' => $sf->max_total_driving_time_mins,
                ]) }}">{{ $sf->name }} · {{ $sf->summary() }}</option>
        @endforeach
    </select>
    <div class="cw-help">Fills the session settings below. You can still change them.</div>
    @endif
</div>

<details class="cw-section cw-details" {{ ($openByDefault ?? false) || $errors->any() ? 'open' : '' }}>
    <summary class="cw-section__title mb-0">Session Settings for This Round</summary>
    @unless($openByDefault ?? false)
    <p class="cw-section__note mt-2">Filled in from the championship's Sessions step.</p>
    @endunless

    <p class="cw-label mt-3">Sessions</p>
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <label class="cw-label">Practice (min)</label>
            <input type="number" name="practice_duration" value="{{ old('practice_duration', $defaults['practice_duration']) }}"
                   class="form-control form-control-sm @error('practice_duration') is-invalid @enderror" min="1" max="999" placeholder="None">
            @error('practice_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-3">
            <label class="cw-label">Qualifying (min)</label>
            <input type="number" name="qualifying_duration" value="{{ old('qualifying_duration', $defaults['qualifying_duration']) }}"
                   class="form-control form-control-sm @error('qualifying_duration') is-invalid @enderror" min="1" max="999" placeholder="None">
            @error('qualifying_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-3">
            <label class="cw-label">
                Races (min)
                <span class="cw-info" tabindex="0" data-tip="One length per race: 25, 25 runs two races this round. Up to 4.">i</span>
            </label>
            <input type="text" name="race_lengths" data-sf="race_lengths" value="{{ old('race_lengths', $defaults['race_lengths']) }}"
                   class="form-control form-control-sm @error('race_lengths') is-invalid @enderror" placeholder="25, 25">
            @error('race_lengths')<div class="invalid-feedback d-block">Enter race lengths like "25" or "25, 25" (max 4 races).</div>@enderror
        </div>
        <div class="col-6 col-md-3">
            <label class="cw-label">In-Game Time</label>
            <input type="time" name="time_of_day" class="form-control form-control-sm" value="{{ old('time_of_day', $defaults['time_of_day']) }}" step="3600">
        </div>
    </div>

    <p class="cw-label mt-4">Weather</p>
    <div class="row g-3 align-items-start">
        <div class="col-6 col-md-3">
            <label class="cw-label">Weather</label>
            <select name="weather" id="{{ $idPrefix }}-weather" class="form-select form-select-sm">
                <option value="">Server default</option>
                @foreach(['dry' => 'Dry', 'wet' => 'Wet', 'mixed' => 'Mixed', 'random' => 'Random'] as $value => $label)
                <option value="{{ $value }}" @selected(old('weather', $defaults['weather']) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="cw-label">
                Changes
                <span class="cw-info" tabindex="0" data-tip="How much the weather changes during a session: 0 static, 1 to 4 realistic, 5 to 7 sensational.">i</span>
            </label>
            @php $wr = old('weather_randomness', $defaults['weather_randomness']); @endphp
            <select name="weather_randomness" class="form-select form-select-sm">
                <option value="" @selected($wr === null || $wr === '')>Server default</option>
                <option value="0" @selected((string) $wr === '0')>0 · Static</option>
                @foreach(['1','2','3','4'] as $n)
                <option value="{{ $n }}" @selected((string) $wr === $n)>{{ $n }} · Realistic</option>
                @endforeach
                @foreach(['5','6','7'] as $n)
                <option value="{{ $n }}" @selected((string) $wr === $n)>{{ $n }} · Sensational</option>
                @endforeach
                <option value="random" @selected($wr === 'random')>Random</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            {{-- Standalone, not tied to Weather = wet/mixed: a league may want a rain
                 chance whatever the weather pick. --}}
            @php $savedRainLevel = old('rain_level', $defaults['rain_level']); @endphp
            <label class="cw-label">Rain (0 to 1)</label>
            <div class="d-flex align-items-center gap-2">
                <input type="range" name="rain_level" id="{{ $idPrefix }}-rain-level" min="0" max="1" step="0.1"
                       value="{{ $savedRainLevel }}" class="form-range flex-grow-1" style="accent-color:#7c3aed">
                <span id="{{ $idPrefix }}-rain-level-val" class="fw-bold text-dark" style="min-width:2rem;font-size:.82rem;text-align:right">
                    {{ number_format($savedRainLevel, 1) }}
                </span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <label class="cw-label">Air (°C)</label>
            <input type="number" name="ambient_temp" value="{{ old('ambient_temp', $defaults['ambient_temp']) }}"
                   class="form-control form-control-sm @error('ambient_temp') is-invalid @enderror" placeholder="Server default">
            @error('ambient_temp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <p class="cw-label mt-4">Pitstops &amp; Rating</p>
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <label class="cw-label">Mandatory Stops</label>
            <input type="number" name="pitstop_count" id="{{ $idPrefix }}-pitstop-count" min="0" max="9" value="{{ old('pitstop_count', $defaults['pitstop_count']) }}"
                   class="form-control form-control-sm @error('pitstop_count') is-invalid @enderror">
            @error('pitstop_count')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-3">
            <label class="cw-label">Tyre Sets</label>
            <input type="number" name="tyre_set_count" id="{{ $idPrefix }}-tyre-sets" min="1" max="50" placeholder="50" value="{{ old('tyre_set_count', $defaults['tyre_set_count']) }}"
                   class="form-control form-control-sm @error('tyre_set_count') is-invalid @enderror">
            <div class="cw-help">Dry sets per car. Blank = 50.</div>
            @error('tyre_set_count')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-3">
            <label class="cw-label">XCL-R Multiplier</label>
            <input type="number" name="xcl_r_multiplier" step="0.01" min="0.6" max="2.5" value="{{ old('xcl_r_multiplier', $defaults['xcl_r_multiplier']) }}"
                   class="form-control form-control-sm @error('xcl_r_multiplier') is-invalid @enderror" placeholder="1.0"
                   {{ $championship->xcl_rating_enabled ? '' : 'disabled' }}>
            <div class="cw-help">{{ $championship->xcl_rating_enabled ? '0.6 to 2.5. Blank = 1.0.' : 'Needs XCL Rating (Basics).' }}</div>
            @error('xcl_r_multiplier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            {!! $toggle('fixed_stop_time', 'Fixed Stop Time (25s)', (bool) old('fixed_stop_time', $defaults['fixed_stop_time']), $idPrefix.'-fixed-stop') !!}
            {!! $toggle('mandatory_refuelling', 'Mandatory Refuelling', (bool) old('mandatory_refuelling', $defaults['mandatory_refuelling']), $idPrefix.'-mandatory-refuelling') !!}
            {!! $toggle('mandatory_tyre_change', 'Mandatory Tyre Change', (bool) old('mandatory_tyre_change', $defaults['mandatory_tyre_change']), $idPrefix.'-mandatory-tyre-change') !!}
        </div>
    </div>

    @if($championship->settings->format->driver_swaps_enabled ?? false)
    <p class="cw-label mt-4">Driver Swaps</p>
    <div class="row g-3 align-items-end">
        <div class="col-6 col-md-3">
            <label class="cw-label">Max Stint (min)</label>
            <input type="number" name="driver_stint_time_mins" value="{{ old('driver_stint_time_mins', $defaults['driver_stint_time_mins']) }}"
                   class="form-control form-control-sm @error('driver_stint_time_mins') is-invalid @enderror" placeholder="No limit">
            @error('driver_stint_time_mins')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-3">
            <label class="cw-label">Max per Driver (min)</label>
            <input type="number" name="max_total_driving_time_mins" value="{{ old('max_total_driving_time_mins', $defaults['max_total_driving_time_mins']) }}"
                   class="form-control form-control-sm @error('max_total_driving_time_mins') is-invalid @enderror" placeholder="No limit">
            @error('max_total_driving_time_mins')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            {!! $toggle('mandatory_driver_swap', 'Mandatory Driver Swap', (bool) old('mandatory_driver_swap', $defaults['mandatory_driver_swap']), $idPrefix.'-swap') !!}
        </div>
    </div>
    @endif
</details>

<script>
(function () {
    var rainRange = document.getElementById('{{ $idPrefix }}-rain-level');
    var rainVal   = document.getElementById('{{ $idPrefix }}-rain-level-val');
    if (rainRange) {
        rainRange.addEventListener('input', function () { rainVal.textContent = parseFloat(rainRange.value).toFixed(1); });
    }
})();
</script>

@once
<script>
// Race format picker: fills this form's session fields from the picked format.
document.addEventListener('change', function (e) {
    var select = e.target.closest('[data-session-format]');
    if (!select || !select.value) return;
    var values = JSON.parse(select.selectedOptions[0].dataset.values || '{}');
    var form = select.closest('form');
    Object.keys(values).forEach(function (name) {
        var input = form.querySelector('[name="' + name + '"]');
        if (!input) return;
        if (input.type === 'checkbox') {
            input.checked = !!values[name];
        } else {
            input.value = values[name] === null ? '' : values[name];
        }
    });
    var details = select.closest('div').nextElementSibling;
    if (details && details.tagName === 'DETAILS') details.open = true;
});

// Official Stream on shows the link field.
document.addEventListener('change', function (e) {
    var wrap = e.target.closest('[data-official-stream]');
    if (!wrap || e.target.name !== 'official_stream') return;
    var input = wrap.querySelector('[data-official-stream-url]');
    input.hidden = !e.target.checked;
    input.required = e.target.checked;
    if (e.target.checked) input.focus();
});

// Round type "+ Add type…" shows the field for the new name.
document.addEventListener('change', function (e) {
    var select = e.target.closest('[data-round-type-select]');
    if (!select) return;
    var input = select.closest('[data-round-type]').querySelector('[data-round-type-new]');
    var adding = select.value === @json(\App\Models\Race::NEW_ROUND_TYPE);
    input.hidden = !adding;
    input.required = adding;
    if (adding) input.focus();
});

// A field failing browser validation inside the closed session block can't show
// its message, so submit does nothing; open the block first ('invalid' doesn't
// bubble, hence the capture listener).
document.addEventListener('invalid', function (e) {
    var details = e.target.closest('details');
    if (details) details.open = true;
}, true);
</script>
@endonce
