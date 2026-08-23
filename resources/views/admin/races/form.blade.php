@extends('layouts.admin')

@php
$isEdit = isset($race) && $race;
$hasFormat = $isEdit ? (bool) $race->event_format_id : true;
@endphp

@section('title', $isEdit ? 'Edit Race' : 'Create Event')
@section('page-title', $isEdit ? 'Edit Race' : 'Create Event')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

@php
$accTracks = [
    'Barcelona'       => ['min' => 30, 'max' => 50],
    'Brands Hatch'    => ['min' => 30, 'max' => 35],
    'COTA'            => ['min' => 30, 'max' => 50],
    'Donington'       => ['min' => 30, 'max' => 40],
    'Hungaroring'     => ['min' => 30, 'max' => 40],
    'Imola'           => ['min' => 30, 'max' => 40],
    'Indianapolis'    => ['min' => 30, 'max' => 40],
    'Kyalami'         => ['min' => 30, 'max' => 50],
    'Laguna Seca'     => ['min' => 30, 'max' => 35],
    'Misano'          => ['min' => 30, 'max' => 40],
    'Monza'           => ['min' => 30, 'max' => 50],
    'Mount Panorama'  => ['min' => 30, 'max' => 50],
    'Nürburgring'     => ['min' => 30, 'max' => 50],
    'Nordschleife'    => ['min' => 30, 'max' => 50],
    'Oulton Park'     => ['min' => 30, 'max' => 35],
    'Paul Ricard'     => ['min' => 30, 'max' => 50],
    'Red Bull Ring'   => ['min' => 30, 'max' => 40],
    'Silverstone'     => ['min' => 30, 'max' => 50],
    'Snetterton'      => ['min' => 30, 'max' => 35],
    'Spa'             => ['min' => 30, 'max' => 50],
    'Suzuka'          => ['min' => 30, 'max' => 50],
    'Valencia'        => ['min' => 30, 'max' => 40],
    'Watkins Glen'    => ['min' => 30, 'max' => 50],
    'Zandvoort'       => ['min' => 30, 'max' => 35],
    'Zolder'          => ['min' => 30, 'max' => 35],
];

$tagsConfig = json_encode([
    'tags'        => $tags->map(fn($t) => ['slug' => $t->slug, 'name' => $t->name, 'color' => $t->color]),
    'storeUrl'    => route('admin.event-tags.store'),
    'csrfToken'   => csrf_token(),
    'selectedTag' => old('event_tag', $isEdit ? $race->event_tag : ''),
]);

// Attach derived slug to each format so JS can detect endurance
$formatsWithSlug = $formats->groupBy('game')->map(
    fn($g) => $g->map(fn($f) => array_merge($f->toArray(), [
        'slug' => \Illuminate\Support\Str::slug($f->name, '_'),
    ]))->values()
);

// Existing per-class values (edit only) — keyed by car_class, feeds the multiclass panels
$mcExisting = $isEdit
    ? $race->raceClasses->filter(fn($c) => $c->car_class)->keyBy('car_class')->map(fn($c) => [
        'max_drivers'    => $c->max_drivers,
        'sr_requirement' => $c->sr_requirement,
        'min_rating'     => $c->min_rating,
    ])
    : collect();
@endphp

<style>
[data-step-nav].active .ce-step-circle { background:#7c3aed; border-color:#7c3aed; color:#fff; }
[data-step-nav].active > div > span { color:#7c3aed; }
[data-step-nav].done .ce-step-circle { background:#7c3aed; border-color:#7c3aed; color:#fff; }
[data-step-nav].done > div > span { color:#374151; }
[data-step-nav].done .ce-step-connector,
[data-step-nav].active .ce-step-connector { background:#7c3aed; }
</style>

<form id="ce-form" action="{{ $isEdit ? route('admin.races.update', $race) : route('admin.races.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="_mode" id="ce-mode-input" value="{{ old('_mode', 'single') }}">

    <div class="row g-4 align-items-start">

        {{-- ── Left column ──────────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-8" data-bulk-wrap>

            {{-- ── Stepper nav ──────────────────────────────────────────────── --}}
            <div id="ce-stepper-nav" class="d-flex align-items-center mb-4">
                @foreach([1 => 'Game & Format', 2 => 'Schedule', 3 => 'Drivers', 4 => 'Details'] as $n => $label)
                <div data-step-nav="{{ $n }}" class="d-flex align-items-center {{ $n < 4 ? 'flex-grow-1' : '' }}">
                    <div class="d-flex align-items-center gap-2 flex-shrink-0" style="white-space:nowrap">
                        <div class="ce-step-circle fw-black" style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;border:2px solid #e5e7eb;background:#fff;color:#9ca3af;transition:all .2s">{{ $n }}</div>
                        <span class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;transition:color .2s">{{ $label }}</span>
                    </div>
                    @if($n < 4)
                    <div class="ce-step-connector" style="flex:1;height:2px;background:#e5e7eb;margin:0 12px;min-width:12px;transition:background .2s"></div>
                    @endif
                </div>
                @endforeach
            </div>

            {{-- ══════════════════════════════════════════════════════════════ --}}
            {{-- STEP 1 — Game & Format                                        --}}
            {{-- ══════════════════════════════════════════════════════════════ --}}
            <div data-step-panel="1" style="display:none">

                @unless($isEdit && $hasFormat)
                {{-- Mode toggle --}}
                <div class="d-flex mb-4" style="border-bottom:2px solid #e5e7eb">
                    <button type="button" data-mode-btn="single"
                            class="btn fw-black text-uppercase rounded-0 border-0 px-4 py-2"
                            style="font-size:.76rem;letter-spacing:.08em;margin-bottom:-2px">
                        Single Event
                    </button>
                    <button type="button" data-mode-btn="bulk"
                            class="btn fw-black text-uppercase rounded-0 border-0 px-4 py-2"
                            style="font-size:.76rem;letter-spacing:.08em;margin-bottom:-2px">
                        Bulk Schedule
                    </button>
                    <button type="button" data-mode-btn="custom"
                            class="btn fw-black text-uppercase rounded-0 border-0 px-4 py-2"
                            style="font-size:.76rem;letter-spacing:.08em;margin-bottom:-2px">
                        Custom Race
                    </button>
                </div>
                @endunless
                @if($isEdit && !$hasFormat)
                <style>[data-mode-btn="single"],[data-mode-btn="bulk"] { display:none !important; }</style>
                @endif

                <div class="admin-card mb-4">
                    {{-- ── Event ──────────────────────────────────────────── --}}
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Event</p>

                        <div class="row g-3">
                            <div class="col-sm-5">
                                <label class="form-label">Game <span class="text-danger">*</span></label>
                                <select name="game" id="ce-game" class="form-select @error('game') is-invalid @enderror">
                                    <option value="">Select game…</option>
                                    <option value="acc"     {{ old('game', $isEdit ? $race->game : '') === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                                    <option value="lmu"     {{ old('game', $isEdit ? $race->game : '') === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                                    <option value="iracing" {{ old('game', $isEdit ? $race->game : '') === 'iracing' ? 'selected' : '' }}>iRacing</option>
                                    <option value="ac"      {{ old('game', $isEdit ? $race->game : '') === 'ac'      ? 'selected' : '' }}>ACC PC</option>
                                </select>
                                @error('game')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Format selector — hidden in custom mode --}}
                            <div class="col-sm-7" data-mode-format-only>
                                <label class="form-label">Format <span class="text-danger">*</span></label>
                                <select name="event_format_id" id="ce-format" class="form-select @error('event_format_id') is-invalid @enderror">
                                    <option value="">— Select game first —</option>
                                </select>
                                @error('event_format_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Custom: title + car class --}}
                            <div class="col-sm-4" data-mode-custom style="display:none">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="ce-custom-title"
                                       value="{{ old('title', $isEdit && !$hasFormat ? $race->title : '') }}"
                                       class="form-control @error('title') is-invalid @enderror"
                                       placeholder="e.g. Saturday GT3 Sprint">
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-3" data-mode-custom style="display:none">
                                <label class="form-label">Car Class</label>
                                <select name="car_class" id="ce-custom-car-class" class="form-select">
                                    <option value="">Open</option>
                                    @foreach(['GT2','GT3','GT4','TCX','GTC','LMP1','LMP2','LMGT3','GTE','Hypercar','Oval','Road'] as $cls)
                                        <option value="{{ $cls }}" {{ old('car_class', $isEdit ? $race->car_class : '') === $cls ? 'selected' : '' }}>{{ $cls }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Format info — hidden in custom mode --}}
                        <div id="ce-format-info" class="mt-3 p-3 rounded-3" data-mode-format-only style="display:none;background:#f8f5ff;border:1px solid rgba(124,58,237,.2)">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <span id="ce-fi-name" class="fw-black text-uppercase fst-italic" style="color:#7c3aed;font-size:.85rem"></span>
                                <span id="ce-fi-xcl" class="fw-black" style="color:#7c3aed;font-size:.9rem"></span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-2" id="ce-fi-sessions" style="font-size:.78rem"></div>
                            <div class="d-flex flex-wrap gap-3" style="font-size:.75rem;color:#6b7280">
                                <span>Formation: <strong id="ce-fi-formation"></strong></span>
                                <span>Pitstop: <strong id="ce-fi-pitstop"></strong></span>
                                <span>Server: <strong id="ce-fi-server"></strong></span>
                            </div>
                        </div>

                        {{-- Endurance duration: single + custom mode only --}}
                        <div data-mode-single>
                            <div id="ce-endurance-wrap" class="mt-3" style="display:none">
                                <label class="form-label">Duration <span class="text-danger">*</span></label>
                                <select name="endurance_duration" id="ce-endurance-duration" class="form-select" style="max-width:200px">
                                    <option value="">Select duration…</option>
                                    <option value="4h"  {{ old('endurance_duration') === '4h'  ? 'selected' : '' }}>4 Hours</option>
                                    <option value="6h"  {{ old('endurance_duration') === '6h'  ? 'selected' : '' }}>6 Hours</option>
                                    <option value="8h"  {{ old('endurance_duration') === '8h'  ? 'selected' : '' }}>8 Hours</option>
                                    <option value="10h" {{ old('endurance_duration') === '10h' ? 'selected' : '' }}>10 Hours</option>
                                    <option value="12h" {{ old('endurance_duration') === '12h' ? 'selected' : '' }}>12 Hours</option>
                                    <option value="24h" {{ old('endurance_duration') === '24h' ? 'selected' : '' }}>24 Hours</option>
                                </select>
                            </div>
                        </div>

                        {{-- Custom: sessions + multiplier --}}
                        <div data-mode-custom style="display:none">
                            <div class="mt-3">
                                <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.7rem;letter-spacing:.06em;color:#9ca3af">Sessions <span class="fw-normal" style="text-transform:none">(minutes)</span></p>
                                <div class="row g-2">
                                    <div class="col-sm-3 col-6">
                                        <label class="form-label">Practice</label>
                                        <div class="input-group">
                                            <input type="number" name="practice_duration" id="ce-custom-practice"
                                                   value="{{ old('practice_duration', $isEdit && !$hasFormat ? $race->practice_duration : '') }}"
                                                   class="form-control" min="1" max="1440" placeholder="—">
                                            <span class="input-group-text" style="font-size:.78rem">min</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 col-6">
                                        <label class="form-label">Qualifying</label>
                                        <div class="input-group">
                                            <input type="number" name="qualifying_duration" id="ce-custom-qualifying"
                                                   value="{{ old('qualifying_duration', $isEdit && !$hasFormat ? $race->qualifying_duration : '') }}"
                                                   class="form-control" min="1" max="1440" placeholder="—">
                                            <span class="input-group-text" style="font-size:.78rem">min</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 col-6">
                                        <label class="form-label">Race <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" name="race_duration" id="ce-custom-race"
                                                   value="{{ old('race_duration', $isEdit && !$hasFormat ? $race->race_duration : '') }}"
                                                   class="form-control @error('race_duration') is-invalid @enderror"
                                                   min="1" max="1440" placeholder="e.g. 60">
                                            <span class="input-group-text" style="font-size:.78rem">min</span>
                                        </div>
                                        @error('race_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-sm-3 col-6">
                                        <label class="form-label">XCL-R Multiplier</label>
                                        <div class="input-group">
                                            <span class="input-group-text">×</span>
                                            <input type="number" name="xcl_r_multiplier" id="ce-custom-multiplier"
                                                   value="{{ old('xcl_r_multiplier', $isEdit && !$hasFormat ? ($race->xcl_r_multiplier ?? '') : '') }}"
                                                   class="form-control @error('xcl_r_multiplier') is-invalid @enderror"
                                                   min="0.1" max="10" step="0.1" placeholder="1.0">
                                        </div>
                                        @error('xcl_r_multiplier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Custom: pitstop --}}
                        <div data-mode-custom style="display:none">
                            <div class="mt-3 pt-3" style="border-top:1px solid #f3f4f6">
                                <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.7rem;letter-spacing:.06em;color:#9ca3af">Pit Stop</p>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="ce-custom-pitstop-toggle"
                                           {{ old('pitstop_count', $isEdit ? $race->pitstop_count : 0) > 0 ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="ce-custom-pitstop-toggle">Mandatory Pit Stop</label>
                                </div>
                                <div id="ce-custom-pitstop-panel" style="{{ old('pitstop_count', $isEdit ? $race->pitstop_count : 0) > 0 ? '' : 'display:none' }}">
                                    <div class="d-flex gap-3 flex-wrap align-items-end">
                                        <div>
                                            <label class="form-label" style="font-size:.82rem">Stops</label>
                                            <input type="number" name="pitstop_count" id="ce-custom-pitstop-count"
                                                   value="{{ old('pitstop_count', $isEdit ? $race->pitstop_count : 1) }}"
                                                   class="form-control form-control-sm" min="1" max="9" style="width:70px">
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="ce-custom-minstop-toggle"
                                                   {{ old('min_stop_secs', $isEdit ? $race->min_stop_secs : null) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="ce-custom-minstop-toggle" style="font-size:.85rem">25s minimum waiting time</label>
                                            <input type="hidden" name="min_stop_secs" id="ce-custom-minstop-val"
                                                   value="{{ old('min_stop_secs', $isEdit ? $race->min_stop_secs : '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($isEdit && !$hasFormat)
                        {{-- Edit-only warning for custom races --}}
                        <div class="mt-3 p-2 rounded-2" style="background:#fefce8;border:1px solid #fde047;font-size:.78rem;color:#854d0e">
                            <strong>Custom Race</strong> — no format assigned. Select a format above to convert it.
                        </div>
                        @endif
                    </div>
                </div>

            </div>{{-- /step 1 --}}

            {{-- ══════════════════════════════════════════════════════════════ --}}
            {{-- STEP 2 — Schedule                                             --}}
            {{-- ══════════════════════════════════════════════════════════════ --}}
            <div data-step-panel="2" style="display:none">

                <div class="admin-card mb-4">

                    {{-- ── Track & Conditions ──────────────────────────────── --}}
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Track & Conditions</p>

                        <div class="row g-3">
                            <div class="col-sm-5">
                                <label class="form-label">Track <span class="text-danger">*</span></label>
                                <select id="ce-track-select" name="track" class="form-select @error('track') is-invalid @enderror" style="display:none">
                                    <option value="">Select track…</option>
                                    @foreach($accTracks as $track => $limits)
                                        <option value="{{ $track }}"
                                                data-min="{{ $limits['min'] }}"
                                                data-max="{{ $limits['max'] }}"
                                                {{ old('track', $isEdit ? $race->track : '') === $track ? 'selected' : '' }}>
                                            {{ $track }}
                                        </option>
                                    @endforeach
                                </select>
                                <input id="ce-track-text" type="text" name="track" value="{{ old('track', $isEdit ? $race->track : '') }}"
                                       class="form-control @error('track') is-invalid @enderror"
                                       placeholder="e.g. Monza">
                                <div id="ce-track-hint" class="form-text" style="display:none"></div>
                                @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Rain</label>
                                @php $weatherVal = old('weather', $isEdit ? ($race->weather ?: 'dry') : 'dry'); @endphp
                                <select name="weather" id="ce-weather-select" class="form-select">
                                    <option value="">— Not set —</option>
                                    <option value="dry"    {{ $weatherVal === 'dry'    ? 'selected' : '' }}>Dry</option>
                                    <option value="wet"    {{ $weatherVal === 'wet'    ? 'selected' : '' }}>Wet</option>
                                    <option value="mixed"  {{ $weatherVal === 'mixed'  ? 'selected' : '' }}>Mixed</option>
                                    <option value="random" {{ $weatherVal === 'random' ? 'selected' : '' }}>Random</option>
                                </select>
                            </div>
                            <div class="col-sm-3" id="ce-rain-level-wrap" style="display:none">
                                @php
                                    $rainLevelPresets = ['wet' => 0.8, 'mixed' => 0.3];
                                    $savedRainLevel   = old('rain_level', $isEdit ? $race->rain_level : null);
                                @endphp
                                <label class="form-label">Rain Level
                                    <span class="fw-normal text-secondary" style="text-transform:none">(ACC rain)</span>
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="range" name="rain_level" id="ce-rain-level"
                                           min="0" max="1" step="0.1"
                                           value="{{ $savedRainLevel ?? '' }}"
                                           class="form-range flex-grow-1" style="accent-color:#7c3aed">
                                    <span id="ce-rain-level-val" class="fw-bold text-dark"
                                          style="min-width:2rem;font-size:.9rem;text-align:right">
                                        {{ $savedRainLevel !== null ? number_format($savedRainLevel, 1) : '—' }}
                                    </span>
                                </div>
                                <div class="form-text">0.0 dry · 0.3 damp · 0.5 light · 0.8 heavy · 1.0 flooded</div>
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">Race Start Time</label>
                                @php
                                    $rawTod = old('time_of_day', $isEdit ? $race->time_of_day : null);
                                    $todMap = ['day' => '14:00', 'dusk' => '17:00', 'night' => '21:00', 'dynamic' => '14:00'];
                                    $timeOfDayVal = ($rawTod && !preg_match('/^\d{2}:\d{2}$/', $rawTod))
                                        ? ($todMap[$rawTod] ?? '14:00')
                                        : ($rawTod ?: '14:00');
                                    $bulkTodVal = in_array($rawTod, ['day','dusk','night','dynamic']) ? $rawTod : '';
                                @endphp
                                {{-- Single mode: H:i time picker --}}
                                <input type="time" name="time_of_day" data-single-tod class="form-control"
                                       value="{{ $timeOfDayVal }}" step="3600">
                                {{-- Bulk mode: enum select, also used as default for generated rows --}}
                                <select name="time_of_day" data-bulk-tod class="form-select" style="display:none" disabled>
                                    <option value=""        {{ $bulkTodVal === ''        ? 'selected' : '' }}>— Not set —</option>
                                    <option value="day"     {{ $bulkTodVal === 'day'     ? 'selected' : '' }}>Day (14:00)</option>
                                    <option value="dusk"    {{ $bulkTodVal === 'dusk'    ? 'selected' : '' }}>Dusk (17:00)</option>
                                    <option value="night"   {{ $bulkTodVal === 'night'   ? 'selected' : '' }}>Night (21:00)</option>
                                    <option value="dynamic" {{ $bulkTodVal === 'dynamic' ? 'selected' : '' }}>Dynamic</option>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">Ambient Temp (°C)</label>
                                <input type="number" name="ambient_temp" class="form-control"
                                       value="{{ old('ambient_temp', $isEdit ? $race->ambient_temp : '') }}"
                                       placeholder="Server default">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Weather</label>
                            <div class="d-flex align-items-end gap-3 flex-wrap" id="ce-weather-pills">

                                {{-- Randomize --}}
                                <div>
                                    <div class="fw-bold text-uppercase mb-1" style="font-size:.6rem;letter-spacing:.06em;color:#9ca3af">&nbsp;</div>
                                    <label data-wr-label="random"
                                           class="d-flex align-items-center px-3 py-1 rounded-pill fw-bold"
                                           style="cursor:pointer;border:1px solid #e5e7eb;font-size:.8rem;user-select:none;background:#fff;color:#374151;transition:all .15s">
                                        <input type="radio" name="weather_randomness" value="random" class="d-none"
                                               data-wr-color="#7c3aed"
                                               {{ old('weather_randomness', $isEdit ? $race->weather_randomness : '') === 'random' ? 'checked' : '' }}>
                                        Randomize
                                    </label>
                                </div>

                                {{-- Static --}}
                                <div>
                                    <div class="fw-bold text-uppercase mb-1" style="font-size:.6rem;letter-spacing:.06em;color:#6b7280">Static</div>
                                    <div class="d-flex gap-1">
                                        <label data-wr-label="0"
                                               class="d-flex align-items-center justify-content-center fw-black rounded"
                                               style="cursor:pointer;width:34px;height:34px;border:2px solid #e5e7eb;font-size:.85rem;user-select:none;background:#fff;color:#9ca3af;transition:all .15s">
                                            <input type="radio" name="weather_randomness" value="0" class="d-none"
                                                   data-wr-color="#6b7280" {{ old('weather_randomness', $isEdit ? $race->weather_randomness : '') === '0' ? 'checked' : '' }}>
                                            0
                                        </label>
                                    </div>
                                </div>

                                {{-- Realistic --}}
                                <div>
                                    <div class="fw-bold text-uppercase mb-1" style="font-size:.6rem;letter-spacing:.06em;color:#2563eb">Realistic</div>
                                    <div class="d-flex gap-1">
                                        @foreach(['1','2','3','4'] as $n)
                                        <label data-wr-label="{{ $n }}"
                                               class="d-flex align-items-center justify-content-center fw-black rounded"
                                               style="cursor:pointer;width:34px;height:34px;border:2px solid #e5e7eb;font-size:.85rem;user-select:none;background:#fff;color:#9ca3af;transition:all .15s">
                                            <input type="radio" name="weather_randomness" value="{{ $n }}" class="d-none"
                                                   data-wr-color="#2563eb" {{ old('weather_randomness', $isEdit ? $race->weather_randomness : '') === $n ? 'checked' : '' }}>
                                            {{ $n }}
                                        </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Sensational --}}
                                <div>
                                    <div class="fw-bold text-uppercase mb-1" style="font-size:.6rem;letter-spacing:.06em;color:#ea580c">Sensational</div>
                                    <div class="d-flex gap-1">
                                        @foreach(['5','6','7'] as $n)
                                        <label data-wr-label="{{ $n }}"
                                               class="d-flex align-items-center justify-content-center fw-black rounded"
                                               style="cursor:pointer;width:34px;height:34px;border:2px solid #e5e7eb;font-size:.85rem;user-select:none;background:#fff;color:#9ca3af;transition:all .15s">
                                            <input type="radio" name="weather_randomness" value="{{ $n }}" class="d-none"
                                                   data-wr-color="#ea580c" {{ old('weather_randomness', $isEdit ? $race->weather_randomness : '') === $n ? 'checked' : '' }}>
                                            {{ $n }}
                                        </label>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                            {{-- hidden fallback so "none selected" posts empty string --}}
                            <input type="radio" name="weather_randomness" value="" class="d-none"
                                   {{ old('weather_randomness', $isEdit ? ($race->weather_randomness ?? '') : '') === '' ? 'checked' : '' }}>
                        </div>
                    </div>

                    {{-- ── Schedule ─────────────────────────────────────────── --}}
                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule</p>

                        {{-- Single schedule panel --}}
                        <div data-mode-single>
                            <div class="row g-3">
                                <div class="col-sm-7">
                                    <label class="form-label">Date & Time (BST) <span class="text-danger">*</span></label>
                                    <input type="text" name="scheduled_at" data-flatpickr {{ $isEdit ? '' : 'data-min-today="true"' }}
                                           value="{{ old('scheduled_at', $isEdit ? $race->scheduledAtUk()->format('Y-m-d\TH:i') : ($prefillDate ? $prefillDate . 'T20:00' : '')) }}"
                                           placeholder="Select date & time…"
                                           class="form-control @error('scheduled_at') is-invalid @enderror">
                                    @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-sm-5" id="ce-drivers-wrap" style="display:none">
                                    <label class="form-label">Max Drivers</label>
                                    <input type="text" id="ce-drivers-display" class="form-control" readonly
                                           style="background:#f9fafb;color:#374151;cursor:default">
                                    <input type="hidden" name="max_drivers" id="ce-max-drivers" value="{{ old('max_drivers', $isEdit ? $race->max_drivers : '') }}">
                                    <div class="form-text">Determined by track</div>
                                </div>
                            </div>

                            @if($isEdit)
                            <div class="row g-3 mt-1">
                                <div class="col-sm-4">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                                        <option value="open"     {{ old('status', $race->status) === 'open'     ? 'selected' : '' }}>Open</option>
                                        <option value="closed"   {{ old('status', $race->status) === 'closed'   ? 'selected' : '' }}>Closed</option>
                                        <option value="finished" {{ old('status', $race->status) === 'finished' ? 'selected' : '' }}>Finished</option>
                                    </select>
                                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- Bulk schedule panel --}}
                        @unless($isEdit)
                        <div data-mode-bulk style="display:none">

                            <div class="row g-3 mb-4">
                                <div class="col-sm-4">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" data-bulk-start-date class="form-control">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">Time (BST/GMT)</label>
                                    <input type="time" data-bulk-start-time value="20:00" step="3600" class="form-control">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">Number of Weeks</label>
                                    <input type="number" data-bulk-week-count value="1" min="1" max="52" class="form-control">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label d-block">Race Days</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach([0=>'Mon',1=>'Tue',2=>'Wed',3=>'Thu',4=>'Fri',5=>'Sat',6=>'Sun'] as $offset => $day)
                                    <label data-bulk-day-label
                                           class="d-flex align-items-center gap-1 px-3 py-1 rounded-pill fw-bold"
                                           style="cursor:pointer;border:1px solid #e5e7eb;font-size:.8rem;user-select:none;background:#fff;color:#374151;transition:all .15s">
                                        <input type="checkbox" data-bulk-day="{{ $offset }}" class="d-none">
                                        {{ $day }}
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <button type="button" data-bulk-generate disabled
                                        class="btn fw-black text-uppercase text-white px-4"
                                        style="background:#7c3aed">
                                    Generate Schedule
                                </button>
                                <span data-bulk-no-date class="text-secondary ms-2" style="font-size:.78rem"></span>
                            </div>

                        </div>
                        @endunless
                    </div>

                    {{-- ── gPortal Server & Slot (single + bulk share one selection) ─────────────── --}}
                    @if($servers->isNotEmpty())
                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                        <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">gPortal Server <span class="fw-normal" style="text-transform:none">(optional)</span></p>
                        <p class="text-secondary mb-3" style="font-size:.75rem">Assign a server — config will be auto-pushed 10 minutes before each reset.</p>

                        <div class="mb-3">
                            <label class="form-label">Server</label>
                            <select name="ftp_server_id" id="gp-server" class="form-select">
                                <option value="">— No server assigned —</option>
                                @foreach($servers as $srv)
                                    <option value="{{ $srv->id }}"
                                            data-type="{{ $srv->server_type }}"
                                            data-number="{{ $srv->server_number }}"
                                            {{ old('ftp_server_id', $isEdit ? $race->ftp_server_id : '') == $srv->id ? 'selected' : '' }}>
                                        {{ $srv->name }}
                                        @if($srv->server_type === 'rolling')
                                            (resets every {{ $srv->reset_interval_minutes }}min from {{ str_pad($srv->reset_start_hour,2,'0',STR_PAD_LEFT) }}:00)
                                        @else
                                            (manual restart)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="gp-scheduled-note" class="small text-secondary" style="display:none">
                            <span data-mode-single>This race's own date &amp; time (set above) is used as its slot on this server.</span>
                            <span data-mode-bulk style="display:none">Each event's own date &amp; time (set below) is used as its slot on this server — a time that doesn't line up with a restart, or that collides with another race, will be rejected for the whole batch.</span>
                        </div>
                        @error('scheduled_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    @endif

                </div>

                {{-- ── Bulk events table (managed by bulk JS) ─────────────── --}}
                @unless($isEdit)
                <div data-bulk-events-section style="display:none">
                    <div class="admin-card mb-4">
                        <div class="px-4 pt-4 pb-2 d-flex align-items-center justify-content-between">
                            <p class="fw-black text-uppercase fst-italic mb-0" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">
                                Events — <span data-bulk-count-display>0</span> races
                            </p>
                            <button type="button" data-bulk-add-row
                                    class="btn btn-sm fw-bold text-uppercase"
                                    style="font-size:.68rem;padding:3px 10px;background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.3);border-radius:6px">
                                + Add Row
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="font-size:.875rem">
                                <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                    <tr>
                                        <th class="fw-bold text-uppercase ps-4" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:36px">#</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Track</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:190px">Date & Time (BST/GMT)</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:120px">Weather</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:120px">In-game Time</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:100px">Amb. Temp</th>
                                        <th class="pe-4" style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody data-bulk-tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endunless

            </div>{{-- /step 2 --}}

            {{-- ══════════════════════════════════════════════════════════════ --}}
            {{-- STEP 3 — Drivers                                              --}}
            {{-- ══════════════════════════════════════════════════════════════ --}}
            <div data-step-panel="3" style="display:none">

                <div class="admin-card mb-4">

                    {{-- ── Requirements ────────────────────────────────────── --}}
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Requirements <span class="fw-normal" style="text-transform:none">(optional — all off by default)</span></p>

                        <div class="d-flex flex-column gap-3">
                            <div>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="ce-sr-toggle" {{ old('sr_requirement', $isEdit ? $race->sr_requirement : '') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="ce-sr-toggle">Safety Rating (SR)</label>
                                </div>
                                <div id="ce-sr-panel" style="{{ old('sr_requirement', $isEdit ? $race->sr_requirement : '') ? '' : 'display:none' }}">
                                    <select name="sr_requirement" id="ce-sr-select" class="form-select form-select-sm" style="max-width:300px">
                                        <option value="">— No requirement —</option>
                                        @foreach(['3','4','5','6','7','8','9'] as $sr)
                                        <option value="{{ $sr }}" {{ old('sr_requirement', $isEdit ? $race->sr_requirement : '') === $sr ? 'selected' : '' }}>SR {{ $sr }}.0+</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="ce-minrating-toggle" {{ old('min_rating', $isEdit ? $race->min_rating : '') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="ce-minrating-toggle">XCL Rating (Min)</label>
                                </div>
                                <div id="ce-minrating-panel" style="{{ old('min_rating', $isEdit ? $race->min_rating : '') ? '' : 'display:none' }}">
                                    <select name="min_rating" id="ce-minrating-select" class="form-select form-select-sm" style="max-width:280px">
                                        <option value="">— No minimum —</option>
                                        @foreach(['rookie','bronze','silver','gold','platinum','alien'] as $r)
                                            <option value="{{ $r }}" {{ old('min_rating', $isEdit ? $race->min_rating : '') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}+</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="ce-maxrating-toggle" {{ old('max_rating', $isEdit ? $race->max_rating : '') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="ce-maxrating-toggle">XCL Rating (Max)</label>
                                </div>
                                <div id="ce-maxrating-panel" style="{{ old('max_rating', $isEdit ? $race->max_rating : '') ? '' : 'display:none' }}">
                                    <select name="max_rating" class="form-select form-select-sm" style="max-width:280px">
                                        <option value="">— No maximum —</option>
                                        @foreach(['rookie','bronze','silver','gold','platinum','alien'] as $r)
                                            <option value="{{ $r }}" {{ old('max_rating', $isEdit ? $race->max_rating : '') === $r ? 'selected' : '' }}>{{ ucfirst($r) }} max</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Car Class ────────────────────────────────────────── --}}
                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Car Class</p>
                        <label class="form-label">Car Class <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                        <select name="car_class" id="ce-car-class" class="form-select" style="max-width:200px">
                            <option value="" {{ !old('car_class', $isEdit ? $race->car_class : '') ? 'selected' : '' }}>Open</option>
                            @foreach(['GT2', 'GT3', 'GT4', 'TCX', 'GTC'] as $cls)
                                <option value="{{ $cls }}" {{ old('car_class', $isEdit ? $race->car_class : '') === $cls ? 'selected' : '' }}>{{ $cls }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ── Multiclass (both single and bulk) ───────────────── --}}
                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6;display:none" id="ce-multiclass-wrap" data-multiclass-wrap
                         data-mc-existing="{{ json_encode($mcExisting) }}">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Multiclass <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></p>

                        <input type="hidden" name="is_multiclass" data-multiclass-flag value="{{ old('is_multiclass', $isEdit ? ($race->is_multiclass ? '1' : '0') : '0') }}">
                        <input type="hidden" name="classes_json" data-multiclass-json value="{{ old('classes_json', '[]') }}">

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach([
                                'GT3' => ['color' => '#7c3aed', 'label' => 'GT3'],
                                'GT4' => ['color' => '#2563eb', 'label' => 'GT4'],
                                'GT2' => ['color' => '#db2777', 'label' => 'GT2'],
                                'TCX' => ['color' => '#16a34a', 'label' => 'TCX'],
                                'GTC' => ['color' => '#ea580c', 'label' => 'GTC'],
                            ] as $cls => $meta)
                            <label data-mc-label="{{ $cls }}"
                                   class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-bold"
                                   style="cursor:pointer;border:2px solid #e5e7eb;font-size:.85rem;user-select:none;background:#fff;color:#374151;transition:all .15s">
                                <input type="checkbox" data-mc-class="{{ $cls }}" data-mc-color="{{ $meta['color'] }}" class="d-none"
                                       {{ $isEdit ? ($race->raceClasses->contains('car_class', $cls) ? 'checked' : '') : (in_array($cls, old('selected_classes', [])) ? 'checked' : '') }}>
                                <span style="width:10px;height:10px;border-radius:50%;background:{{ $meta['color'] }};flex-shrink:0"></span>
                                {{ $meta['label'] }}
                            </label>
                            @endforeach
                        </div>

                        {{-- Per-class max drivers / SR / rating (shown per selected class) --}}
                        <div data-mc-drivers-wrap class="d-flex flex-wrap gap-3" style="display:none"></div>

                        <div class="text-secondary mt-2" style="font-size:.75rem" data-mc-hint>Select one or more classes to enable multiclass</div>
                    </div>

                    {{-- ── Endurance / Driver Swap ─────────────────────────── --}}
                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="is_endurance" id="ce-is-endurance" value="1"
                                   {{ old('is_endurance', $isEdit ? $race->is_endurance : false) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="ce-is-endurance">Endurance / Driver Swap</label>
                        </div>
                        <div class="text-secondary mt-1" style="font-size:.75rem">Enables team entry registration — drivers sign up as a team with shared car number and model.</div>
                    </div>

                </div>

            </div>{{-- /step 3 --}}

            {{-- ══════════════════════════════════════════════════════════════ --}}
            {{-- STEP 4 — Details                                              --}}
            {{-- ══════════════════════════════════════════════════════════════ --}}
            <div data-step-panel="4" style="display:none">

                <div class="admin-card mb-4">

                    {{-- ── Additional ──────────────────────────────────────── --}}
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Additional</p>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <div data-tags-wrap data-config='{{ $tagsConfig }}'>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label mb-0">Event Tag <span class="text-danger">*</span></label>
                                        <button type="button" data-tags-toggle
                                                class="btn btn-sm fw-bold text-uppercase"
                                                style="font-size:.68rem;padding:2px 8px;background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.3);border-radius:6px">
                                            + New
                                        </button>
                                    </div>
                                    <select name="event_tag" class="form-select @error('event_tag') is-invalid @enderror" data-tags-select>
                                        <option value="">Select tag…</option>
                                    </select>
                                    @error('event_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div data-tags-add-panel style="display:none">
                                        <div class="mt-2 p-3 rounded-2" style="background:#f8f5ff;border:1px solid rgba(124,58,237,.2)">
                                            <div data-tags-error class="alert alert-danger py-1 px-2 mb-2" style="font-size:.8rem;display:none"></div>
                                            <div class="d-flex gap-2 align-items-end">
                                                <div class="flex-grow-1">
                                                    <label class="form-label" style="font-size:.78rem">Name</label>
                                                    <input type="text" data-tags-name placeholder="e.g. Endurance" class="form-control form-control-sm">
                                                </div>
                                                <div>
                                                    <label class="form-label" style="font-size:.78rem">Color</label>
                                                    <input type="color" data-tags-color class="form-control form-control-sm form-control-color" style="width:46px;padding:2px" value="#7B2FBE">
                                                </div>
                                                <button type="button" data-tags-save class="btn btn-sm fw-bold text-white" style="background:#7c3aed;white-space:nowrap">Add</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Description <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Additional event info…">{{ old('description', $isEdit ? $race->description : '') }}</textarea>
                        </div>
                    </div>

                    <div id="ce-media-section" class="px-4 py-3" style="border-top:1px solid #f3f4f6;{{ $isEdit && !$hasFormat ? '' : 'display:none' }}">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Media</p>
                        <x-media-picker name="image" label="Background Image" :current="$isEdit ? $race->image : null" />
                        <div class="mt-3">
                            <x-media-picker name="icon" label="Event Icon" :current="$isEdit ? $race->icon : null" currentType="icon" filterDefault="icon" />
                        </div>
                    </div>

                </div>

            </div>{{-- /step 4 --}}

            {{-- ── Stepper navigation buttons ───────────────────────────────── --}}
            <div class="d-flex align-items-center gap-2 mt-4">
                <button type="button" id="ce-step-prev" style="display:none" class="btn btn-outline-secondary fw-bold text-uppercase px-4">← Back</button>
                <button type="button" id="ce-step-next" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Next →</button>
                <button type="submit" id="ce-submit" style="display:none;background:#7c3aed" class="btn fw-black text-uppercase text-white px-4">
                    <span id="ce-btn-single">{{ $isEdit ? 'Save Changes' : 'Create Event' }}</span>
                    <span id="ce-btn-bulk" style="display:none">Create <span data-bulk-count-display>0</span> Races</span>
                </button>
                <a href="{{ route('admin.races.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
            </div>

        </div>

        {{-- ── Live Event Preview ────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-4">
            <div style="position:sticky;top:80px">

                <p style="font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:.75rem">Preview</p>

                <div id="ce-preview" style="border-radius:14px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.18);border:1px solid rgba(124,58,237,.2)">

                    <div style="position:relative;height:185px;overflow:hidden;background:#111827">
                        <img id="prev-track-img" src="" alt="" loading="lazy"
                             style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:none">
                        <div id="prev-track-placeholder"
                             style="position:absolute;inset:0;background:linear-gradient(135deg,#1e1e3a 0%,#2d1b69 100%)"></div>

                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center">
                            <img id="prev-format-img" src="" alt=""
                                 style="max-width:60%;max-height:80%;object-fit:contain;display:none;filter:drop-shadow(0 3px 18px rgba(0,0,0,.85))">
                            <div id="prev-format-text-badge"
                                 style="display:none;padding:8px 18px;background:rgba(0,0,0,.6);border-radius:8px;text-align:center">
                                <div id="prev-format-name"
                                     style="font-weight:900;font-style:italic;text-transform:uppercase;color:#fff;font-size:1rem;letter-spacing:.05em"></div>
                            </div>
                        </div>

                        <div id="prev-platforms" style="position:absolute;top:8px;right:8px;display:flex;gap:4px"></div>
                    </div>

                    <div style="background:#111827;border-top:1px solid rgba(255,255,255,.07);padding:12px 14px 14px">

                        <div id="prev-fmt-block" style="display:none;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.07)">
                            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px">
                                <span id="prev-fmt-name-label"
                                      style="font-size:.75rem;font-weight:900;font-style:italic;text-transform:uppercase;color:#7c3aed;letter-spacing:.04em"></span>
                                <span id="prev-fmt-xclr"
                                      style="font-size:.67rem;font-weight:700;color:#7c3aed;letter-spacing:.02em"></span>
                            </div>
                            <div id="prev-fmt-pills" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px"></div>
                            <div id="prev-fmt-meta" style="font-size:.67rem;color:#6b7280;line-height:1.5"></div>
                        </div>

                        <div id="prev-time"
                             style="font-size:.72rem;font-weight:900;color:#d4ee6a;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">— / — —</div>

                        <div id="prev-meta"
                             style="font-size:.72rem;color:#9ca3af;margin-bottom:10px">—</div>

                        <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;margin-bottom:10px">
                            <span id="prev-game-badge"
                                  style="display:none;font-size:.65rem;font-weight:900;text-transform:uppercase;letter-spacing:.05em;padding:2px 8px;border-radius:4px;color:#fff"></span>
                            <span id="prev-sr-badge" style="display:none;align-items:center;gap:4px">
                                <span id="prev-sr-circle"
                                      style="width:20px;height:20px;border-radius:50%;background:#1a1a2e;border:2px solid #dc2626;display:inline-flex;align-items:center;justify-content:center;color:#dc2626;font-size:.58rem;font-weight:900;flex-shrink:0">B</span>
                                <span id="prev-sr-text"
                                      style="font-size:.65rem;font-weight:900;color:#e5e7eb;white-space:nowrap">SR 5.0+</span>
                            </span>
                            <span id="prev-xcl-badge"
                                  style="display:none;font-size:.63rem;font-weight:900;text-transform:capitalize;padding:2px 8px;border-radius:4px;border:1px solid rgba(205,127,50,.4);background:rgba(205,127,50,.15);color:#cd7f32"></span>
                            <span id="prev-open-badge"
                                  style="display:none;font-size:.65rem;font-weight:900;text-transform:uppercase;padding:2px 8px;border-radius:4px;background:rgba(212,238,106,.12);color:#d4ee6a;border:1px solid rgba(212,238,106,.25)">OPEN</span>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:5px;font-size:.75rem">
                            <div style="display:flex;gap:10px">
                                <span style="color:#6b7280;width:58px;flex-shrink:0">CLASS</span>
                                <span id="prev-class" style="color:#e5e7eb;font-weight:600">Open</span>
                            </div>
                            <div style="display:flex;gap:10px">
                                <span style="color:#6b7280;width:58px;flex-shrink:0">TRACK</span>
                                <span id="prev-track-name" style="color:#e5e7eb;font-weight:600">—</span>
                            </div>
                            <div style="display:flex;gap:10px">
                                <span style="color:#6b7280;width:58px;flex-shrink:0">WEATHER</span>
                                <span id="prev-weather" style="color:#e5e7eb;font-weight:600">—</span>
                            </div>
                        </div>

                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:9px;border-top:1px solid rgba(255,255,255,.07)">
                            <span id="prev-duration"
                                  style="display:none;font-size:.68rem;font-weight:900;text-transform:uppercase;color:#111827;background:#d4ee6a;border-radius:999px;padding:2px 10px"></span>
                            <span id="prev-date" style="font-size:.7rem;color:#9ca3af;margin-left:auto">—</span>
                        </div>

                    </div>
                </div>

                <p style="font-size:.68rem;color:#9ca3af;margin-top:.6rem;text-align:center;letter-spacing:.02em">Updates as you fill in the form</p>

            </div>
        </div>

    </div>
</form>

<script>
(function () {
    // ── Mode switching ──────────────────────────────────────────────────────
    const form            = document.getElementById('ce-form');
    const singleUrl       = '{{ $isEdit ? route('admin.races.update', $race) : route('admin.races.store') }}';
    const bulkUrl         = '{{ route('admin.races.bulk-store') }}';
    const modeInput       = document.getElementById('ce-mode-input');
    const singleMaxDrivers = document.getElementById('ce-max-drivers');
    const btnSingle       = document.getElementById('ce-btn-single');
    const btnBulk         = document.getElementById('ce-btn-bulk');
    const bulkEventsSection = document.querySelector('[data-bulk-events-section]');

    function switchMode(mode) {
        const isBulk   = mode === 'bulk';
        const isCustom = mode === 'custom';
        const isFormat = !isCustom; // single or bulk

        if (form)     form.action = isBulk ? bulkUrl : singleUrl;
        if (modeInput) modeInput.value = mode;

        // Format-only sections (hidden in custom mode)
        document.querySelectorAll('[data-mode-format-only]').forEach(el => el.style.display = isCustom ? 'none' : '');
        // Custom-only sections
        document.querySelectorAll('[data-mode-custom]').forEach(el => el.style.display = isCustom ? '' : 'none');

        // Single/bulk sections (custom behaves like single for scheduling)
        document.querySelectorAll('[data-mode-single]').forEach(el => el.style.display = isBulk ? 'none' : '');
        document.querySelectorAll('[data-mode-bulk]').forEach(el => el.style.display = isBulk ? '' : 'none');

        // Disable/enable event_format_id in custom mode
        const fmtEl = document.getElementById('ce-format');
        if (fmtEl) { fmtEl.disabled = isCustom; if (isCustom) fmtEl.value = ''; }
        const fmtInfo = document.getElementById('ce-format-info');
        if (fmtInfo && isCustom) fmtInfo.style.display = 'none';

        // Swap active car_class select so only one is submitted
        const customCarClass  = document.getElementById('ce-custom-car-class');
        const formatCarClass  = document.getElementById('ce-car-class');
        if (customCarClass) customCarClass.disabled = !isCustom;
        if (formatCarClass)  formatCarClass.disabled = isCustom;

        // Disable custom session/title/multiplier inputs in non-custom mode
        ['ce-custom-title','ce-custom-practice','ce-custom-qualifying','ce-custom-race','ce-custom-multiplier']
            .forEach(id => { const el = document.getElementById(id); if (el) el.disabled = !isCustom; });

        // Show media section in custom mode
        const mediaSection = document.getElementById('ce-media-section');
        if (mediaSection) mediaSection.style.display = isCustom ? '' : 'none';

        // Disable single-only hidden inputs to prevent duplicate submissions
        if (singleMaxDrivers) singleMaxDrivers.disabled = isBulk;

        // Toggle time-of-day between H:i picker (single/custom) and enum select (bulk)
        const singleTod = document.querySelector('[data-single-tod]');
        const bulkTod   = document.querySelector('[data-bulk-tod]');
        if (singleTod) { singleTod.disabled = isBulk;  singleTod.style.display = isBulk ? 'none' : ''; }
        if (bulkTod)   { bulkTod.disabled   = !isBulk; bulkTod.style.display   = isBulk ? ''     : 'none'; }

        // Bulk events section: hide on single/custom, restore if rows exist on bulk
        if (bulkEventsSection) {
            if (!isBulk) {
                bulkEventsSection.style.display = 'none';
            } else {
                const tbody = bulkEventsSection.querySelector('[data-bulk-tbody]');
                if (tbody && tbody.children.length > 0) bulkEventsSection.style.display = '';
            }
        }

        // Submit button labels
        if (btnSingle) btnSingle.style.display = isBulk ? 'none' : '';
        if (btnBulk)   btnBulk.style.display   = isBulk ? ''     : 'none';

        // Mode buttons
        document.querySelectorAll('[data-mode-btn]').forEach(btn => {
            const active = btn.dataset.modeBtn === mode;
            btn.style.color        = active ? '#7c3aed' : '#9ca3af';
            btn.style.borderBottom = active ? '2px solid #7c3aed' : '2px solid transparent';
        });

        history.replaceState(null, '', location.pathname + '?mode=' + mode);
        if (typeof updatePreview === 'function') updatePreview();
    }

    document.querySelectorAll('[data-mode-btn]').forEach(btn => {
        btn.addEventListener('click', () => switchMode(btn.dataset.modeBtn));
    });

    const initMode = new URLSearchParams(location.search).get('mode') || '{{ old('_mode', $isEdit && !$hasFormat ? 'custom' : 'single') }}';
    switchMode(initMode);
})();

// ── Format Event JS ────────────────────────────────────────────────────────
(function () {
    const formats  = @json($formatsWithSlug);
    const tracks   = @json($accTracks);
    const oldGame  = '{{ old('game', $isEdit ? $race->game : '') }}';
    const oldFmt   = '{{ old('event_format_id', $isEdit ? $race->event_format_id : '') }}';
    const allowNoFormat = @json($isEdit && !$hasFormat);

    const gameEl       = document.getElementById('ce-game');
    const fmtEl        = document.getElementById('ce-format');
    const fmtInfo      = document.getElementById('ce-format-info');
    const trackSelect  = document.getElementById('ce-track-select');
    const trackText    = document.getElementById('ce-track-text');
    const trackHint    = document.getElementById('ce-track-hint');
    const driversWrap  = document.getElementById('ce-drivers-wrap');
    const driversDisplay = document.getElementById('ce-drivers-display');
    const driversInput = document.getElementById('ce-max-drivers');

    function buildSessionBadge(label, mins, color) {
        return `<span style="background:${color}1a;color:${color};border:1px solid ${color}33;border-radius:6px;padding:.15rem .55rem;font-weight:700;font-size:.72rem">${label} ${mins}'</span>`;
    }

    function showFormatInfo(fmt) {
        if (!fmt) { fmtInfo.style.display = 'none'; return; }
        document.getElementById('ce-fi-name').textContent = fmt.name;
        document.getElementById('ce-fi-xcl').textContent  = '×' + parseFloat(fmt.xcl_r_multiplier).toFixed(1) + ' XCL-R';
        document.getElementById('ce-fi-formation').textContent = fmt.formation_type || '—';
        document.getElementById('ce-fi-server').textContent    = fmt.server_preference || '—';

        let pitstop = 'None';
        if (fmt.pitstop_count > 0) {
            pitstop = '(' + fmt.pitstop_count + 'x';
            if (fmt.min_stop_secs) pitstop += ', ' + fmt.min_stop_secs + 's';
            pitstop += ')';
        }
        document.getElementById('ce-fi-pitstop').textContent = pitstop;

        let sessions = '';
        if (fmt.practice_mins) sessions += buildSessionBadge('P', fmt.practice_mins, '#6b7280');
        if (fmt.quali_mins)    sessions += buildSessionBadge('Q', fmt.quali_mins, '#d97706');
        sessions += buildSessionBadge('R1', fmt.race1_mins, '#7c3aed');
        if (fmt.quali2_mins)   sessions += buildSessionBadge('Q2', fmt.quali2_mins, '#d97706');
        if (fmt.race2_mins)    sessions += buildSessionBadge('R2', fmt.race2_mins, '#7c3aed');
        document.getElementById('ce-fi-sessions').innerHTML = sessions;

        fmtInfo.style.display = '';
    }

    const enduranceWrap  = document.getElementById('ce-endurance-wrap');
    const multiclassWrap = document.getElementById('ce-multiclass-wrap');

    function setEnduranceVisible(slug) {
        if (enduranceWrap) enduranceWrap.style.display = slug === 'endurance' ? '' : 'none';
    }

    function setMulticlassVisible(slug) {
        if (multiclassWrap) multiclassWrap.style.display = slug === 'multiclass' ? '' : 'none';
    }

    function updateFormats(game) {
        fmtEl.innerHTML = '<option value="">— Select format —</option>';
        showFormatInfo(null);
        setEnduranceVisible('');
        setMulticlassVisible('');
        if (!game || !formats[game]) return;
        formats[game].sort((a, b) => a.sort_order - b.sort_order).forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = f.name;
            opt.dataset.slug = f.slug;
            if (String(f.id) === oldFmt) opt.selected = true;
            fmtEl.appendChild(opt);
        });
        if (oldFmt) {
            const selected = (formats[game] || []).find(f => String(f.id) === oldFmt);
            if (selected) {
                showFormatInfo(selected);
                setEnduranceVisible(selected.slug);
                setMulticlassVisible(selected.slug);
            }
        }
    }

    function updateTrackInput(game) {
        if (game === 'acc') {
            trackSelect.name  = 'track';
            trackText.name    = '';
            trackSelect.style.display = '';
            trackText.style.display   = 'none';
            updateTrackHint(trackSelect.value);
        } else {
            trackSelect.name  = '';
            trackText.name    = 'track';
            trackSelect.style.display = 'none';
            trackText.style.display   = '';
            trackHint.style.display   = 'none';
            driversWrap.style.display = 'none';
            driversInput.value = '';
        }
    }

    function updateTrackHint(track) {
        if (!track || !tracks[track]) {
            trackHint.style.display   = 'none';
            driversWrap.style.display = 'none';
            driversInput.value = '';
            return;
        }
        const t = tracks[track];
        trackHint.textContent   = 'Recommended: ' + t.min + ' – ' + t.max + ' drivers';
        trackHint.style.display = '';
        driversDisplay.value    = t.max + ' drivers (max for ' + track + ')';
        driversInput.value      = t.max;
        driversWrap.style.display = '';
    }

    gameEl.addEventListener('change', () => {
        updateFormats(gameEl.value);
        updateTrackInput(gameEl.value);
    });
    fmtEl.addEventListener('change', () => {
        const fmt = (formats[gameEl.value] || []).find(f => String(f.id) === fmtEl.value);
        showFormatInfo(fmt || null);
        setEnduranceVisible(fmt?.slug ?? '');
        setMulticlassVisible(fmt?.slug ?? '');
    });

    const endurElFmt = document.getElementById('ce-endurance-duration');
    if (endurElFmt) {
        const endurMinsMap = { '4h': 240, '6h': 360, '8h': 480, '10h': 600, '12h': 720, '24h': 1440 };
        endurElFmt.addEventListener('change', () => {
            const fmt = (formats[gameEl.value] || []).find(f => String(f.id) === fmtEl.value);
            if (!fmt || fmt.slug !== 'endurance') return;
            const race1Mins = endurMinsMap[endurElFmt.value] || fmt.race1_mins;
            let sessions = '';
            if (fmt.practice_mins) sessions += buildSessionBadge('P', fmt.practice_mins, '#6b7280');
            if (fmt.quali_mins)    sessions += buildSessionBadge('Q', fmt.quali_mins, '#d97706');
            sessions += buildSessionBadge('R1', race1Mins, '#7c3aed');
            document.getElementById('ce-fi-sessions').innerHTML = sessions;
        });
    }

    trackSelect.addEventListener('change', () => updateTrackHint(trackSelect.value));

    [['ce-sr-toggle','ce-sr-panel'],['ce-minrating-toggle','ce-minrating-panel'],['ce-maxrating-toggle','ce-maxrating-panel']].forEach(([tid,pid]) => {
        const t = document.getElementById(tid), p = document.getElementById(pid);
        t.addEventListener('change', () => { p.style.display = t.checked ? '' : 'none'; });
    });

    if (oldGame) {
        updateFormats(oldGame);
        updateTrackInput(oldGame);
        if (oldGame === 'acc') updateTrackHint(trackSelect.value);
    }

    window.__ceAllowNoFormat = allowNoFormat;
    window.__ceTracks        = tracks;
})();

// ── Auto-select gPortal server + event tag (driven by the chosen format) ────
// Suggests a server/tag whenever the format or the scheduled time changes;
// both stay normal <select>s so an admin can still override them manually.
(function () {
    const formats  = @json($formatsWithSlug);
    const gameEl   = document.getElementById('ce-game');
    const fmtEl    = document.getElementById('ce-format');
    const serverEl = document.getElementById('gp-server');
    const tagEl    = document.querySelector('[data-tags-select]');
    if (!fmtEl || (!serverEl && !tagEl)) return;

    const schedEl     = document.querySelector('[name="scheduled_at"]');
    const bulkDateEl  = document.querySelector('[data-bulk-start-date]');
    const bulkTimeEl  = document.querySelector('[data-bulk-start-time]');

    // server_number → target group. Short = any hour, Medium splits even/odd
    // hour across two servers, Long = manual-restart server, any time.
    function serverNumberFor(group, hour) {
        if (group === 'short')  return 1;
        if (group === 'long')   return 4;
        if (group === 'medium') return hour % 2 === 0 ? 2 : 3;
        return null;
    }

    function currentHour() {
        const isBulk = document.getElementById('ce-mode-input')?.value === 'bulk';
        if (isBulk) {
            if (!bulkDateEl?.value || !bulkTimeEl?.value) return null;
            const d = new Date(bulkDateEl.value + 'T' + bulkTimeEl.value);
            return isNaN(d) ? null : d.getHours();
        }
        if (!schedEl?.value) return null;
        const d = new Date(schedEl.value);
        return isNaN(d) ? null : d.getHours();
    }

    function currentFormat() {
        return (formats[gameEl?.value] || []).find(f => String(f.id) === fmtEl.value);
    }

    function recomputeServer() {
        if (!serverEl) return;
        const fmt = currentFormat();
        if (!fmt || !fmt.server_group) return;

        const hour = currentHour();
        if (hour === null) return;

        const number = serverNumberFor(fmt.server_group, hour);
        if (number === null) return;

        const opt = Array.from(serverEl.options).find(o => o.dataset.number === String(number));
        if (!opt || serverEl.value === opt.value) return;

        serverEl.value = opt.value;
        serverEl.dispatchEvent(new Event('change'));
    }

    function recomputeTag() {
        if (!tagEl) return;
        const fmt = currentFormat();
        if (!fmt || !fmt.default_event_tag || tagEl.value === fmt.default_event_tag) return;

        const opt = Array.from(tagEl.options).find(o => o.value === fmt.default_event_tag);
        if (!opt) return;

        tagEl.value = fmt.default_event_tag;
        tagEl.dispatchEvent(new Event('change'));
    }

    function pad(n) { return String(n).padStart(2, '0'); }
    function formatForFlatpickr(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    // 0 = server needs an even hour (Server 2), 1 = odd (Server 3), null = no restriction
    function requiredParity() {
        const number = serverEl?.selectedOptions[0]?.dataset.number;
        if (number === '2') return 0;
        if (number === '3') return 1;
        return null;
    }

    // Nudges the picked time by one hour when it doesn't match the selected
    // server's even/odd requirement — e.g. picking Server 3 (odd-hour only)
    // while an even hour is set bumps it forward to the next odd hour.
    function enforceScheduleParity() {
        const parity = requiredParity();
        if (parity === null) return;

        const isBulk = document.getElementById('ce-mode-input')?.value === 'bulk';
        if (isBulk) {
            if (!bulkTimeEl?.value) return;
            const [hh] = bulkTimeEl.value.split(':').map(Number);
            if (isNaN(hh) || hh % 2 === parity) return;
            bulkTimeEl.value = pad((hh + 1) % 24) + ':00';
            return;
        }

        if (!schedEl?.value) return;
        const d = new Date(schedEl.value);
        if (isNaN(d) || d.getHours() % 2 === parity) return;

        d.setHours(d.getHours() + 1, 0, 0, 0);
        schedEl._flatpickr?.setDate(d, false);
        schedEl.value = formatForFlatpickr(d);
    }

    function recompute() {
        recomputeServer();
        enforceScheduleParity();
        recomputeTag();
    }

    fmtEl.addEventListener('change', recompute);
    schedEl?.addEventListener('change', recomputeServer);
    bulkDateEl?.addEventListener('change', recomputeServer);
    bulkTimeEl?.addEventListener('change', recomputeServer);
    serverEl?.addEventListener('change', enforceScheduleParity);
    document.querySelectorAll('[data-mode-btn]').forEach(btn => btn.addEventListener('click', recomputeServer));
})();

// ── Live Event Preview ──────────────────────────────────────────────────────
(function () {
    const formats              = @json($formatsWithSlug);
    const trackPreviewUrls     = @json($trackPreviewUrls ?? []);
    const formatPreviewUrls    = @json($formatPreviewUrls ?? []);
    const endurancePreviewUrls = @json($endurancePreviewUrls ?? []);

    const $ = id => document.getElementById(id);
    const prev = {
        trackImg:      $('prev-track-img'),
        trackPh:       $('prev-track-placeholder'),
        formatImg:     $('prev-format-img'),
        formatTextBdg: $('prev-format-text-badge'),
        formatName:    $('prev-format-name'),
        platforms:     $('prev-platforms'),
        time:          $('prev-time'),
        meta:          $('prev-meta'),
        gameBadge:     $('prev-game-badge'),
        srBadge:       $('prev-sr-badge'),
        srCircle:      $('prev-sr-circle'),
        srText:        $('prev-sr-text'),
        xclBadge:      $('prev-xcl-badge'),
        openBadge:     $('prev-open-badge'),
        classEl:       $('prev-class'),
        trackName:     $('prev-track-name'),
        weatherEl:     $('prev-weather'),
        duration:      $('prev-duration'),
        date:          $('prev-date'),
    };

    const gameColors  = { acc: '#7c3aed', lmu: '#db2877', iracing: '#2563eb', ac: '#16a34a' };
    const gameLabels  = { acc: 'ACC', lmu: 'LMU', iracing: 'iRACING', ac: 'ACC PC' };
    const wxIcons     = { dry: '☀', wet: '🌧', mixed: '⛅', random: '🎲' };
    const wxLabels    = { dry: 'Dry', wet: 'Wet', mixed: 'Mixed', random: 'Random' };
    const platBadges  = {
        acc:     [{ icon: 'fa-brands fa-playstation', label: 'PS5' }, { icon: 'fa-brands fa-xbox', label: 'Xbox' }],
        lmu:     [{ icon: 'fa-brands fa-windows', label: 'PC' }],
        iracing: [{ icon: 'fa-brands fa-windows', label: 'PC' }],
        ac:      [{ icon: 'fa-brands fa-windows', label: 'PC' }],
    };
    const srTierMap = {
        '3': ['B', '#dc2626'], '4': ['B', '#dc2626'],
        '5': ['A', '#16a34a'], '6': ['A', '#16a34a'],
        '7': ['X', '#2563eb'],
        '8': ['Y', '#eab308'],
        '9': ['Z', '#7c3aed'],
    };
    const xclColors = {
        rookie: '#ef4444', bronze: '#cd7f32', silver: '#9ca3af',
        gold: '#f59e0b', platinum: '#7c3aed', alien: '#10b981',
    };

    function updatePreview() {
        const gameEl      = $('ce-game');
        const fmtEl       = $('ce-format');
        const trackSelEl  = $('ce-track-select');
        const trackTxtEl  = $('ce-track-text');
        const carClassEl  = $('ce-car-class');
        const endurEl     = $('ce-endurance-duration');
        const srToggle    = $('ce-sr-toggle');
        const srSelect    = $('ce-sr-select');
        const minToggle   = $('ce-minrating-toggle');
        const minSelect   = $('ce-minrating-select');
        const maxToggle   = $('ce-maxrating-toggle');
        const weatherSel  = document.querySelector('[name="weather"]');
        const schedEl     = document.querySelector('[name="scheduled_at"]');

        const game      = gameEl  ? gameEl.value  : '';
        const fmtId     = fmtEl   ? fmtEl.value   : '';
        const fmtOpt    = (fmtEl && fmtId) ? fmtEl.options[fmtEl.selectedIndex] : null;
        const fmtSlug   = fmtOpt  ? (fmtOpt.dataset.slug || '') : '';
        const fmtName   = fmtOpt  ? fmtOpt.textContent.trim() : '';
        const fmtData   = (formats[game] || []).find(f => String(f.id) === fmtId);
        const track     = (game === 'acc' && trackSelEl && trackSelEl.style.display !== 'none')
                            ? (trackSelEl.value || '')
                            : (trackTxtEl ? trackTxtEl.value : '');
        const weather   = weatherSel  ? weatherSel.value  : '';
        const carClass  = carClassEl  ? carClassEl.value  : '';
        const schedVal  = schedEl     ? schedEl.value     : '';
        const endurDur  = endurEl     ? endurEl.value     : '';
        const srOn      = srToggle    ? srToggle.checked  : false;
        const srVal     = (srOn && srSelect) ? srSelect.value : '';
        const minOn     = minToggle   ? minToggle.checked : false;
        const minVal    = (minOn && minSelect) ? minSelect.value : '';
        const maxOn     = maxToggle   ? maxToggle.checked : false;

        // Track background
        const trackUrl = trackPreviewUrls[track] || '';
        if (trackUrl && prev.trackImg) {
            prev.trackImg.src           = trackUrl;
            prev.trackImg.style.display = '';
            if (prev.trackPh) prev.trackPh.style.display = 'none';
        } else {
            if (prev.trackImg) prev.trackImg.style.display = 'none';
            if (prev.trackPh) prev.trackPh.style.display  = '';
        }

        // Format overlay image / text fallback
        let fmtUrl = '';
        if (fmtSlug === 'endurance' && endurDur) {
            fmtUrl = endurancePreviewUrls[endurDur] || formatPreviewUrls[fmtId] || '';
        } else if (fmtId) {
            fmtUrl = formatPreviewUrls[fmtId] || '';
        }
        if (fmtUrl && prev.formatImg) {
            prev.formatImg.src           = fmtUrl;
            prev.formatImg.style.display = '';
            if (prev.formatTextBdg) prev.formatTextBdg.style.display = 'none';
        } else if (fmtName && prev.formatTextBdg) {
            if (prev.formatImg) prev.formatImg.style.display = 'none';
            if (prev.formatName) prev.formatName.textContent = fmtName;
            prev.formatTextBdg.style.display = '';
        } else {
            if (prev.formatImg)     prev.formatImg.style.display     = 'none';
            if (prev.formatTextBdg) prev.formatTextBdg.style.display = 'none';
        }

        // Platform badges
        if (prev.platforms) {
            prev.platforms.innerHTML = '';
            (platBadges[game] || []).forEach(p => {
                const s = document.createElement('span');
                s.style.cssText = 'display:inline-flex;align-items:center;gap:3px;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:4px;background:rgba(255,255,255,.12);color:#e5e7eb';
                s.innerHTML = `<i class="${p.icon}" style="font-size:.7rem"></i>${p.label}`;
                prev.platforms.appendChild(s);
            });
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

        // Game badge
        if (game && prev.gameBadge) {
            prev.gameBadge.textContent      = gameLabels[game] || game.toUpperCase();
            prev.gameBadge.style.background = gameColors[game] || '#374151';
            prev.gameBadge.style.display    = '';
        } else if (prev.gameBadge) {
            prev.gameBadge.style.display = 'none';
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
                prev.xclBadge.textContent        = minVal.charAt(0).toUpperCase() + minVal.slice(1) + '+';
                prev.xclBadge.style.color        = color;
                prev.xclBadge.style.background   = color + '22';
                prev.xclBadge.style.borderColor  = color + '66';
                prev.xclBadge.style.display      = '';
            } else {
                prev.xclBadge.style.display = 'none';
            }
        }

        // OPEN badge
        if (prev.openBadge) prev.openBadge.style.display = (!srOn && !minOn && !maxOn) ? '' : 'none';

        // Car class / track / weather — multiclass selection (if any) wins over the single Car Class field
        const mcClasses = Array.from(document.querySelectorAll('[data-mc-class]:checked')).map(cb => cb.dataset.mcClass);
        if (prev.classEl) prev.classEl.textContent = mcClasses.length ? mcClasses.join(' + ') : (carClass || 'Open');
        if (prev.trackName) prev.trackName.textContent = track    || '—';
        if (prev.weatherEl) {
            const icon  = wxIcons[weather]  || '';
            const label = wxLabels[weather] || (weather ? weather : '—');
            prev.weatherEl.textContent = icon ? icon + ' ' + label : label;
        }

        // Custom mode: pick up title and race duration from custom inputs
        const modeInput    = document.getElementById('ce-mode-input');
        const isCustomMode = modeInput && modeInput.value === 'custom';
        const customTitle  = $('ce-custom-title');
        const customRace   = $('ce-custom-race');
        const customMult   = $('ce-custom-multiplier');
        const customClass  = $('ce-custom-car-class');

        // Duration badge
        if (prev.duration) {
            if (fmtSlug === 'endurance' && endurDur) {
                prev.duration.textContent   = endurDur.toUpperCase();
                prev.duration.style.display = '';
            } else if (isCustomMode && customRace && customRace.value) {
                const mins = parseInt(customRace.value);
                prev.duration.textContent   = mins + ' MIN';
                prev.duration.style.display = '';
            } else if (fmtData && fmtData.race1_mins) {
                prev.duration.textContent   = fmtData.race1_mins + ' MIN';
                prev.duration.style.display = '';
            } else {
                prev.duration.style.display = 'none';
            }
        }

        // In custom mode: show custom format block with title + sessions + multiplier
        if (isCustomMode) {
            if (fmtBlock) {
                const nameLabel = $('prev-fmt-name-label');
                const xclrEl   = $('prev-fmt-xclr');
                const pillsEl  = $('prev-fmt-pills');
                const metaEl   = $('prev-fmt-meta');
                if (nameLabel) nameLabel.textContent = (customTitle && customTitle.value) ? customTitle.value : 'Custom Race';
                const mult = customMult && customMult.value ? parseFloat(customMult.value) : 1.0;
                if (xclrEl) xclrEl.textContent = '×' + mult.toFixed(1) + ' XCL-R';
                if (pillsEl) {
                    pillsEl.innerHTML = '';
                    const pracEl  = $('ce-custom-practice');
                    const qualiEl = $('ce-custom-qualifying');
                    const customSessions = [
                        { key: 'P', mins: pracEl  && pracEl.value  ? parseInt(pracEl.value)  : 0, bg: '#1f2937', color: '#9ca3af' },
                        { key: 'Q', mins: qualiEl && qualiEl.value ? parseInt(qualiEl.value) : 0, bg: '#292524', color: '#f59e0b' },
                        { key: 'R', mins: customRace && customRace.value ? parseInt(customRace.value) : 0, bg: '#2e1065', color: '#a78bfa' },
                    ];
                    customSessions.forEach(function (s) {
                        if (!s.mins) return;
                        const pill = document.createElement('span');
                        pill.style.cssText = 'font-size:.7rem;font-weight:600;border-radius:6px;padding:3px 8px;background:' + s.bg + ';color:' + s.color;
                        pill.textContent   = s.key + ' ' + s.mins + "'";
                        pillsEl.appendChild(pill);
                    });
                }
                if (metaEl) metaEl.textContent = '';
                if (prev.classEl && customClass) prev.classEl.textContent = customClass.value || 'Open';
                fmtBlock.style.display = '';
            }
            return;
        }

        // Format info block
        const fmtBlock = $('prev-fmt-block');
        if (fmtBlock) {
            if (fmtData) {
                const nameLabel = $('prev-fmt-name-label');
                const xclrEl   = $('prev-fmt-xclr');
                const pillsEl  = $('prev-fmt-pills');
                const metaEl   = $('prev-fmt-meta');

                if (nameLabel) nameLabel.textContent = fmtName;
                if (xclrEl)   xclrEl.textContent = fmtData.xcl_r_multiplier
                    ? '×' + parseFloat(fmtData.xcl_r_multiplier).toFixed(1) + ' XCL-R'
                    : '';

                if (pillsEl) {
                    pillsEl.innerHTML = '';
                    const endurMinsMap = { '4h': 240, '6h': 360, '8h': 480, '10h': 600, '12h': 720, '24h': 1440 };
                    const race1Mins = (fmtSlug === 'endurance' && endurDur && endurMinsMap[endurDur])
                        ? endurMinsMap[endurDur]
                        : fmtData.race1_mins;
                    const sessions = [
                        { key: 'P',  mins: fmtData.practice_mins, bg: '#1f2937', color: '#9ca3af' },
                        { key: 'Q',  mins: fmtData.quali_mins,    bg: '#292524', color: '#f59e0b' },
                        { key: fmtData.race2_mins ? 'R1' : 'R', mins: race1Mins, bg: '#2e1065', color: '#a78bfa' },
                        { key: 'Q2', mins: fmtData.quali2_mins,   bg: '#292524', color: '#f59e0b' },
                        { key: 'R2', mins: fmtData.race2_mins,    bg: '#2e1065', color: '#a78bfa' },
                    ];
                    sessions.forEach(function (s) {
                        if (!s.mins) return;
                        const pill = document.createElement('span');
                        pill.style.cssText = 'font-size:.7rem;font-weight:600;border-radius:6px;padding:3px 8px;background:' + s.bg + ';color:' + s.color;
                        pill.textContent   = s.key + ' ' + s.mins + "'";
                        pillsEl.appendChild(pill);
                    });
                }

                if (metaEl) {
                    const formation = fmtData.formation_type
                        ? fmtData.formation_type.charAt(0).toUpperCase() + fmtData.formation_type.slice(1)
                        : '—';
                    const pitstop = fmtData.pitstop_count > 0
                        ? '(' + fmtData.pitstop_count + 'x)'
                        : 'Not required';
                    metaEl.textContent = 'Formation: ' + formation + ' Pitstop: ' + pitstop;
                }

                fmtBlock.style.display = '';
            } else {
                fmtBlock.style.display = 'none';
            }
        }
    }

    // Bind change/input listeners
    ['ce-game','ce-format','ce-car-class','ce-sr-toggle','ce-sr-select',
     'ce-minrating-toggle','ce-minrating-select','ce-maxrating-toggle',
     'ce-endurance-duration','ce-track-select',
     'ce-custom-car-class','ce-custom-multiplier']
        .forEach(id => { const el = $(id); if (el) el.addEventListener('change', updatePreview); });
    ['ce-custom-title','ce-custom-practice','ce-custom-qualifying','ce-custom-race']
        .forEach(id => { const el = $(id); if (el) el.addEventListener('input', updatePreview); });
    const wEl = document.querySelector('[name="weather"]');
    const sEl = document.querySelector('[name="scheduled_at"]');
    const tEl = $('ce-track-text');
    if (wEl) wEl.addEventListener('change', updatePreview);
    if (sEl) sEl.addEventListener('change', updatePreview);
    if (tEl) tEl.addEventListener('input', updatePreview);
    document.querySelectorAll('[data-mc-class]').forEach(cb => cb.addEventListener('change', updatePreview));

    updatePreview();
})();
</script>

<script>
// ── Rain & Weather pills ──────────────────────────────────────────────────────
(function () {
    function initPills(containerSelector, inputSelector) {
        const container = document.querySelector(containerSelector);
        if (!container) return;
        const radios = container.querySelectorAll(inputSelector);

        function applyStyles() {
            radios.forEach(radio => {
                const label = radio.closest('label');
                if (!label) return;
                const color = radio.dataset.wrColor || radio.dataset.rainColor || '#7c3aed';
                if (radio.checked) {
                    label.style.borderColor = color;
                    label.style.background  = color + '18';
                    label.style.color       = color;
                } else {
                    label.style.borderColor = '#e5e7eb';
                    label.style.background  = '#fff';
                    label.style.color       = '#374151';
                }
            });
        }

        radios.forEach(r => r.addEventListener('change', applyStyles));
        applyStyles();
    }

    initPills('#ce-weather-pills', 'input[type="radio"]');
})();

// ── Stepper ──────────────────────────────────────────────────────────────────
(function () {
    const panels   = document.querySelectorAll('[data-step-panel]');
    const navItems = document.querySelectorAll('[data-step-nav]');
    const prevBtn  = document.getElementById('ce-step-prev');
    const nextBtn  = document.getElementById('ce-step-next');
    const submitBtn = document.getElementById('ce-submit');
    let current = 1;

    function showStep(n) {
        panels.forEach(p => p.style.display = p.dataset.stepPanel == n ? '' : 'none');
        navItems.forEach(ni => {
            const step   = parseInt(ni.dataset.stepNav);
            const isDone = step < n;
            ni.classList.toggle('active', step === n);
            ni.classList.toggle('done', isDone);
            const circle = ni.querySelector('.ce-step-circle');
            if (circle) circle.textContent = isDone ? '✓' : step;
        });
        current = n;
        if (prevBtn)   prevBtn.style.display   = n === 1 ? 'none' : '';
        if (nextBtn)   nextBtn.style.display   = n === panels.length ? 'none' : '';
        if (submitBtn) submitBtn.style.display = n === panels.length ? '' : 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function canAdvance() {
        if (current === 1) {
            const game = document.getElementById('ce-game');
            const fmt  = document.getElementById('ce-format');
            const mode = document.getElementById('ce-mode-input')?.value;
            if (mode === 'custom') {
                const title = document.getElementById('ce-custom-title');
                const race  = document.getElementById('ce-custom-race');
                return !!(game?.value && title?.value && race?.value);
            }
            return game?.value && (fmt?.value || window.__ceAllowNoFormat);
        }
        if (current === 2) {
            const mode = document.getElementById('ce-mode-input')?.value;
            if (mode === 'bulk') {
                // Need at least 1 generated event
                return document.querySelectorAll('[data-bulk-tbody] tr').length > 0;
            }
            const track = document.querySelector('[name="track"]');
            const dt    = document.querySelector('[name="scheduled_at"]');
            return track?.value && dt?.value;
        }
        return true; // steps 3+ always can advance
    }

    nextBtn?.addEventListener('click', () => {
        if (!canAdvance()) {
            // Highlight missing fields briefly
            const panel = document.querySelector(`[data-step-panel="${current}"]`);
            panel?.querySelectorAll(':required:invalid').forEach(el => {
                el.style.outline = '2px solid #dc2626';
                setTimeout(() => el.style.outline = '', 2000);
            });
            return;
        }
        showStep(current + 1);
    });

    prevBtn?.addEventListener('click', () => showStep(current - 1));

    navItems.forEach(ni => {
        ni.style.cursor = 'pointer';
        ni.addEventListener('click', () => {
            const step = parseInt(ni.dataset.stepNav);
            if (step < current) showStep(step); // can go back freely
        });
    });

    showStep(1);
})();

// ── Custom Race Pitstop Toggle ───────────────────────────────────────────────
(function () {
    const toggle = document.getElementById('ce-custom-pitstop-toggle');
    const panel  = document.getElementById('ce-custom-pitstop-panel');
    const minToggle = document.getElementById('ce-custom-minstop-toggle');
    const minVal    = document.getElementById('ce-custom-minstop-val');
    if (!toggle || !panel) return;
    toggle.addEventListener('change', () => { panel.style.display = toggle.checked ? '' : 'none'; });
    if (minToggle && minVal) {
        minToggle.addEventListener('change', () => { minVal.value = minToggle.checked ? '25' : ''; });
    }
})();

// ── Rain Level Control ───────────────────────────────────────────────────────
(function () {
    const weatherSelect  = document.getElementById('ce-weather-select');
    const rainWrap       = document.getElementById('ce-rain-level-wrap');
    const rainSlider     = document.getElementById('ce-rain-level');
    const rainDisplay    = document.getElementById('ce-rain-level-val');
    const presets        = { wet: 0.8, mixed: 0.3 };

    function updateWrap() {
        const w = weatherSelect?.value;
        const show = w === 'wet' || w === 'mixed';
        if (rainWrap) rainWrap.style.display = show ? '' : 'none';
        if (show && rainSlider && !rainSlider.value) {
            rainSlider.value = presets[w] ?? 0.5;
            if (rainDisplay) rainDisplay.textContent = parseFloat(rainSlider.value).toFixed(1);
        }
    }

    rainSlider?.addEventListener('input', () => {
        if (rainDisplay) rainDisplay.textContent = parseFloat(rainSlider.value).toFixed(1);
    });

    weatherSelect?.addEventListener('change', updateWrap);
    updateWrap();
})();
</script>

@endsection