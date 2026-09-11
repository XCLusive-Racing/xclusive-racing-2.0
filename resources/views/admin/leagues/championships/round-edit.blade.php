@extends('layouts.admin')

@php
    // ACC's real track roster — reused from admin.races.form so a typo here can
    // never produce a track name gPortal's event.json doesn't recognise.
    $accTracks = [
        'Barcelona', 'Brands Hatch', 'COTA', 'Donington', 'Hungaroring', 'Imola',
        'Indianapolis', 'Kyalami', 'Laguna Seca', 'Misano', 'Monza', 'Mount Panorama',
        'Nürburgring', 'Nordschleife', 'Oulton Park', 'Paul Ricard', 'Red Bull Ring',
        'Silverstone', 'Snetterton', 'Spa', 'Suzuka', 'Valencia', 'Watkins Glen',
        'Zandvoort', 'Zolder',
    ];
@endphp

@section('title', 'Edit Round — ' . $championship->name)
@section('page-title', $league->name . ' — Edit Round ' . $race->round_number)

@section('page-actions')
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back to Rounds
    </a>
@endsection

@section('content')

<form action="{{ route('admin.leagues.championships.rounds.update', [$league, $championship, $race]) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="admin-card mb-4">
        <div class="px-4 pt-4 pb-2">
            <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">
                Editing {{ $race->title }}
            </p>
        </div>

        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <div class="row g-3">
                <div class="col-sm-3">
                    <label class="form-label">Round #</label>
                    <input type="number" name="round_number" value="{{ old('round_number', $race->round_number) }}"
                           class="form-control @error('round_number') is-invalid @enderror" min="1">
                    @error('round_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-5">
                    <label class="form-label">Track <span class="text-danger">*</span></label>
                    @if($championship->game === 'acc')
                    <select name="track" class="form-select @error('track') is-invalid @enderror" required>
                        <option value="">Select track…</option>
                        @foreach($accTracks as $trackName)
                        <option value="{{ $trackName }}" {{ old('track', $race->track) === $trackName ? 'selected' : '' }}>{{ $trackName }}</option>
                        @endforeach
                    </select>
                    @else
                    <input type="text" name="track" value="{{ old('track', $race->track) }}"
                           class="form-control @error('track') is-invalid @enderror" placeholder="e.g. Le Mans" required>
                    @endif
                    @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Date &amp; Time <span class="text-danger">*</span> <span class="fw-normal text-secondary" style="text-transform:none">(on the hour, BST)</span></label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', $race->scheduledAtUk()->format('Y-m-d\TH:i')) }}" step="3600"
                           class="form-control @error('scheduled_at') is-invalid @enderror">
                    @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Sessions &amp; Conditions</p>

            <div class="row g-3 mb-3">
                <div class="col-6 col-sm-2">
                    <label class="form-label" style="font-size:.75rem">Practice <span class="fw-normal text-secondary">(min)</span></label>
                    <input type="number" name="practice_duration" value="{{ old('practice_duration', $race->practice_duration) }}"
                           class="form-control form-control-sm @error('practice_duration') is-invalid @enderror" min="1" max="999" placeholder="—">
                    @error('practice_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-2">
                    <label class="form-label" style="font-size:.75rem">Quali <span class="fw-normal text-secondary">(min)</span></label>
                    <input type="number" name="qualifying_duration" value="{{ old('qualifying_duration', $race->qualifying_duration) }}"
                           class="form-control form-control-sm @error('qualifying_duration') is-invalid @enderror" min="1" max="999" placeholder="—">
                    @error('qualifying_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-2">
                    <label class="form-label" style="font-size:.75rem">Race <span class="text-danger">*</span> <span class="fw-normal text-secondary">(min)</span></label>
                    <input type="number" name="race_duration" value="{{ old('race_duration', $race->race_duration) }}"
                           class="form-control form-control-sm @error('race_duration') is-invalid @enderror" min="1" max="999" required>
                    @error('race_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label" style="font-size:.75rem">Start Time <span class="fw-normal text-secondary">(in-game)</span></label>
                    <input type="time" name="time_of_day" class="form-control form-control-sm" value="{{ old('time_of_day', $race->time_of_day) }}" step="3600">
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label" style="font-size:.75rem">Ambient Temp (°C)</label>
                    <input type="number" name="ambient_temp" value="{{ old('ambient_temp', $race->ambient_temp) }}"
                           class="form-control form-control-sm @error('ambient_temp') is-invalid @enderror" placeholder="Server default">
                    @error('ambient_temp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-sm-3">
                    <label class="form-label" style="font-size:.75rem">Weather</label>
                    <select name="weather" id="re-weather" class="form-select form-select-sm">
                        <option value="">— Not set —</option>
                        <option value="dry"    {{ old('weather', $race->weather) === 'dry'    ? 'selected' : '' }}>Dry</option>
                        <option value="wet"    {{ old('weather', $race->weather) === 'wet'    ? 'selected' : '' }}>Wet</option>
                        <option value="mixed"  {{ old('weather', $race->weather) === 'mixed'  ? 'selected' : '' }}>Mixed</option>
                        <option value="random" {{ old('weather', $race->weather) === 'random' ? 'selected' : '' }}>Random</option>
                    </select>
                </div>
                <div class="col-sm-4">
                    <label class="form-label" style="font-size:.75rem">Dynamic Weather <span class="fw-normal text-secondary" style="text-transform:none">(how much it changes mid-session)</span></label>
                    @php $wr = old('weather_randomness', $race->weather_randomness); @endphp
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
                <div class="col-sm-3" id="re-rain-level-wrap" style="display:none">
                    @php $savedRainLevel = old('rain_level', $race->rain_level ?? 0.3); @endphp
                    <label class="form-label" style="font-size:.75rem">Rain Level <span class="fw-normal text-secondary">(0–1)</span></label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="range" name="rain_level" id="re-rain-level" min="0" max="1" step="0.1"
                               value="{{ $savedRainLevel }}" class="form-range flex-grow-1" style="accent-color:#7c3aed">
                        <span id="re-rain-level-val" class="fw-bold text-dark" style="min-width:2rem;font-size:.82rem;text-align:right">
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
                    <input type="number" name="xcl_r_multiplier" step="0.1" value="{{ old('xcl_r_multiplier', $race->xcl_r_multiplier) }}"
                           class="form-control form-control-sm @error('xcl_r_multiplier') is-invalid @enderror" placeholder="1.0">
                    @error('xcl_r_multiplier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label" style="font-size:.75rem">Mandatory Pitstops</label>
                    <input type="number" name="pitstop_count" min="0" max="9" value="{{ old('pitstop_count', $race->pitstop_count) }}"
                           class="form-control form-control-sm @error('pitstop_count') is-invalid @enderror">
                    @error('pitstop_count')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label" style="font-size:.75rem">Min. Stop Time (s)</label>
                    <input type="number" name="min_stop_secs" min="1" max="3600" value="{{ old('min_stop_secs', $race->min_stop_secs) }}"
                           class="form-control form-control-sm @error('min_stop_secs') is-invalid @enderror" placeholder="—">
                    @error('min_stop_secs')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-3 d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="has_practice_server" id="re-practice" value="1"
                               {{ old('has_practice_server', $race->has_practice_server) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="re-practice" style="font-size:.78rem">Practice Server</label>
                    </div>
                </div>
            </div>

            @if($championship->settings->format->driver_swaps_enabled ?? false)
            <div class="row g-3 mb-3">
                <div class="col-6 col-sm-4">
                    <label class="form-label" style="font-size:.75rem">Max. Stint Time (min)</label>
                    <input type="number" name="driver_stint_time_mins" value="{{ old('driver_stint_time_mins', $race->driver_stint_time_mins) }}"
                           class="form-control form-control-sm @error('driver_stint_time_mins') is-invalid @enderror" placeholder="No limit">
                    @error('driver_stint_time_mins')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-4">
                    <label class="form-label" style="font-size:.75rem">Max Driving Time / Driver (min)</label>
                    <input type="number" name="max_total_driving_time_mins" value="{{ old('max_total_driving_time_mins', $race->max_total_driving_time_mins) }}"
                           class="form-control form-control-sm @error('max_total_driving_time_mins') is-invalid @enderror" placeholder="No limit">
                    @error('max_total_driving_time_mins')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-4 d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="mandatory_driver_swap" id="re-swap" value="1"
                               {{ old('mandatory_driver_swap', $race->mandatory_driver_swap) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="re-swap" style="font-size:.78rem">Mandatory Pitstop Swap</label>
                    </div>
                </div>
            </div>
            @endif

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" style="font-size:.75rem">Practice Server Notes <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                    <textarea name="practice_notes" rows="2" class="form-control form-control-sm">{{ old('practice_notes', $race->practice_notes) }}</textarea>
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
                <option value="{{ $srv->id }}" {{ (string) old('ftp_server_id', $race->ftp_server_id) === (string) $srv->id ? 'selected' : '' }}>{{ $srv->name }}</option>
                @endforeach
            </select>
            @endif
        </div>

        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <label class="form-label" style="font-size:.75rem">Notes <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
            <textarea name="description" rows="2" class="form-control form-control-sm">{{ old('description', $race->description) }}</textarea>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Save Changes</button>
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
    </div>
</form>

<script>
(function () {
    var weatherSel = document.getElementById('re-weather');
    var rainWrap   = document.getElementById('re-rain-level-wrap');
    var rainRange  = document.getElementById('re-rain-level');
    var rainVal    = document.getElementById('re-rain-level-val');
    if (!weatherSel) return;

    function updateRainVisibility() {
        rainWrap.style.display = ['wet', 'mixed'].includes(weatherSel.value) ? '' : 'none';
    }
    weatherSel.addEventListener('change', updateRainVisibility);
    rainRange.addEventListener('input', function () { rainVal.textContent = parseFloat(rainRange.value).toFixed(1); });
    updateRainVisibility();
})();
</script>

@endsection
