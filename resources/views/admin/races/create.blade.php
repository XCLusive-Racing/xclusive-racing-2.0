@extends('layouts.admin')

@section('title', 'Create Event')
@section('page-title', 'Create Event')

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
    'selectedTag' => old('event_tag', ''),
]);

// Attach derived slug to each format so JS can detect endurance
$formatsWithSlug = $formats->groupBy('game')->map(
    fn($g) => $g->map(fn($f) => array_merge($f->toArray(), [
        'slug' => \Illuminate\Support\Str::slug($f->name, '_'),
    ]))->values()
);
@endphp

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
</div>

<form id="ce-form" action="{{ route('admin.races.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="_mode" id="ce-mode-input" value="{{ old('_mode', 'single') }}">

    <div class="row g-4 align-items-start">

        {{-- ── Left column ──────────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-8" data-bulk-wrap>

            <div class="admin-card mb-4">

                {{-- ── Event ─────────────────────────────────────────────────── --}}
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Event</p>

                    <div class="row g-3">
                        <div class="col-sm-5">
                            <label class="form-label">Game <span class="text-danger">*</span></label>
                            <select name="game" id="ce-game" class="form-select @error('game') is-invalid @enderror">
                                <option value="">Select game…</option>
                                <option value="acc"     {{ old('game') === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                                <option value="lmu"     {{ old('game') === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                                <option value="iracing" {{ old('game') === 'iracing' ? 'selected' : '' }}>iRacing</option>
                                <option value="ac"      {{ old('game') === 'ac'      ? 'selected' : '' }}>AC Rally</option>
                            </select>
                            @error('game')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-sm-7">
                            <label class="form-label">Format <span class="text-danger">*</span></label>
                            <select name="event_format_id" id="ce-format" class="form-select @error('event_format_id') is-invalid @enderror">
                                <option value="">— Select game first —</option>
                            </select>
                            @error('event_format_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Format info: both modes --}}
                    <div id="ce-format-info" class="mt-3 p-3 rounded-3" style="display:none;background:#f8f5ff;border:1px solid rgba(124,58,237,.2)">
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

                    {{-- Endurance: single only --}}
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
                </div>

                {{-- ── Track & Conditions ────────────────────────────────────── --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
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
                                            {{ old('track') === $track ? 'selected' : '' }}>
                                        {{ $track }}
                                    </option>
                                @endforeach
                            </select>
                            <input id="ce-track-text" type="text" name="track" value="{{ old('track') }}"
                                   class="form-control @error('track') is-invalid @enderror"
                                   placeholder="e.g. Monza">
                            <div id="ce-track-hint" class="form-text" style="display:none"></div>
                            @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Weather</label>
                            <select name="weather" class="form-select">
                                <option value="">— Not set —</option>
                                <option value="dry"    {{ old('weather') === 'dry'    ? 'selected' : '' }}>Dry</option>
                                <option value="wet"    {{ old('weather') === 'wet'    ? 'selected' : '' }}>Wet</option>
                                <option value="mixed"  {{ old('weather') === 'mixed'  ? 'selected' : '' }}>Mixed</option>
                                <option value="random" {{ old('weather') === 'random' ? 'selected' : '' }}>Random</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">In-game Time</label>
                            <select name="time_of_day" class="form-select">
                                <option value="">— Not set —</option>
                                <option value="day"     {{ old('time_of_day') === 'day'     ? 'selected' : '' }}>Day</option>
                                <option value="dusk"    {{ old('time_of_day') === 'dusk'    ? 'selected' : '' }}>Dusk</option>
                                <option value="night"   {{ old('time_of_day') === 'night'   ? 'selected' : '' }}>Night</option>
                                <option value="dynamic" {{ old('time_of_day') === 'dynamic' ? 'selected' : '' }}>Dynamic</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- ── Schedule ────────────────────────────────────────────────── --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule</p>

                    {{-- Single schedule panel --}}
                    <div data-mode-single>
                        <div class="row g-3">
                            <div class="col-sm-7">
                                <label class="form-label">Date & Time (BST) <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="scheduled_at"
                                       value="{{ old('scheduled_at', $prefillDate ? $prefillDate . 'T20:00' : '') }}"
                                       class="form-control @error('scheduled_at') is-invalid @enderror">
                                @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-5" id="ce-drivers-wrap" style="display:none">
                                <label class="form-label">Max Drivers</label>
                                <input type="text" id="ce-drivers-display" class="form-control" readonly
                                       style="background:#f9fafb;color:#374151;cursor:default">
                                <input type="hidden" name="max_drivers" id="ce-max-drivers">
                                <div class="form-text">Determined by track</div>
                            </div>
                        </div>
                    </div>

                    {{-- Bulk schedule panel --}}
                    <div data-mode-bulk style="display:none">

                        {{-- Regular/Week mode toggle --}}
                        <div class="d-flex gap-2 mb-4">
                            <button type="button" data-bulk-mode="regular"
                                    class="btn btn-sm fw-bold text-uppercase px-3"
                                    style="font-size:.72rem;background:#7c3aed;color:#fff;border:1px solid #7c3aed;border-radius:6px">
                                Regular
                            </button>
                            <button type="button" data-bulk-mode="week"
                                    class="btn btn-sm fw-bold text-uppercase px-3"
                                    style="font-size:.72rem;background:transparent;color:#9ca3af;border:1px solid #e5e7eb;border-radius:6px">
                                Week Schedule
                            </button>
                        </div>

                        {{-- Regular interval panel --}}
                        <div data-bulk-regular-panel>
                            <div class="row g-3 mb-3">
                                <div class="col-sm-4">
                                    <label class="form-label">Number of Events</label>
                                    <input type="number" data-bulk-count value="8" min="1" max="20" class="form-control">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" data-bulk-start-date class="form-control">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">Start Time (BST/GMT)</label>
                                    <input type="time" data-bulk-start-time value="20:00" class="form-control">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-sm-4">
                                    <label class="form-label">Interval</label>
                                    <select data-bulk-interval class="form-select">
                                        <option value="7">Weekly (7 days)</option>
                                        <option value="14">Bi-weekly (14 days)</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                                <div class="col-sm-4" data-bulk-custom-interval-wrap style="display:none">
                                    <label class="form-label">Days between events</label>
                                    <input type="number" data-bulk-custom-interval value="7" min="1" class="form-control">
                                </div>
                            </div>
                        </div>

                        {{-- Week schedule panel --}}
                        <div data-bulk-week-panel style="display:none">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-4">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" data-bulk-week-start class="form-control">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">Time (BST/GMT)</label>
                                    <input type="time" data-bulk-week-time value="20:00" class="form-control">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">Number of Weeks</label>
                                    <input type="number" data-bulk-week-count value="1" min="1" max="12" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
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
                        </div>

                        {{-- Shared generator inputs --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label class="form-label">Base Name</label>
                                <input type="text" data-bulk-base-name value="Round" class="form-control" placeholder="e.g. Round">
                            </div>
                        </div>

                        <div>
                            <button type="button" data-bulk-generate disabled
                                    class="btn fw-black text-uppercase text-white px-4"
                                    style="background:#7c3aed">
                                Generate Schedule
                            </button>
                            <span data-bulk-no-date class="text-secondary ms-2" style="font-size:.78rem">Pick a start date first</span>
                        </div>

                    </div>
                </div>

                {{-- ── Requirements ──────────────────────────────────────────── --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Requirements <span class="fw-normal" style="text-transform:none">(optional — all off by default)</span></p>

                    <div class="d-flex flex-column gap-3">
                        <div>
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="ce-sr-toggle" {{ old('sr_requirement') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="ce-sr-toggle">Safety Rating (SR)</label>
                            </div>
                            <div id="ce-sr-panel" style="{{ old('sr_requirement') ? '' : 'display:none' }}">
                                <select name="sr_requirement" id="ce-sr-select" class="form-select form-select-sm" style="max-width:300px">
                                    <option value="">— No requirement —</option>
                                    <option value="3" {{ old('sr_requirement') === '3' ? 'selected' : '' }}>SR 3.0+  ·  Grade B</option>
                                    <option value="4" {{ old('sr_requirement') === '4' ? 'selected' : '' }}>SR 4.0+  ·  Grade B</option>
                                    <option value="5" {{ old('sr_requirement') === '5' ? 'selected' : '' }}>SR 5.0+  ·  Grade A</option>
                                    <option value="6" {{ old('sr_requirement') === '6' ? 'selected' : '' }}>SR 6.0+  ·  Grade A</option>
                                    <option value="7" {{ old('sr_requirement') === '7' ? 'selected' : '' }}>SR 7.0+  ·  Grade X</option>
                                    <option value="8" {{ old('sr_requirement') === '8' ? 'selected' : '' }}>SR 8.0+  ·  Grade Y</option>
                                    <option value="9" {{ old('sr_requirement') === '9' ? 'selected' : '' }}>SR 9.0+  ·  Grade Z</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="ce-minrating-toggle" {{ old('min_rating') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="ce-minrating-toggle">XCL Rating (Min)</label>
                            </div>
                            <div id="ce-minrating-panel" style="{{ old('min_rating') ? '' : 'display:none' }}">
                                <select name="min_rating" id="ce-minrating-select" class="form-select form-select-sm" style="max-width:280px">
                                    <option value="">— No minimum —</option>
                                    @foreach(['rookie','bronze','silver','gold','platinum','alien'] as $r)
                                        <option value="{{ $r }}" {{ old('min_rating') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}+</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="ce-maxrating-toggle" {{ old('max_rating') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="ce-maxrating-toggle">XCL Rating (Max)</label>
                            </div>
                            <div id="ce-maxrating-panel" style="{{ old('max_rating') ? '' : 'display:none' }}">
                                <select name="max_rating" class="form-select form-select-sm" style="max-width:280px">
                                    <option value="">— No maximum —</option>
                                    @foreach(['rookie','bronze','silver','gold','platinum','alien'] as $r)
                                        <option value="{{ $r }}" {{ old('max_rating') === $r ? 'selected' : '' }}>{{ ucfirst($r) }} max</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Additional ──────────────────────────────────────────────── --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
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

                        <div class="col-sm-6">
                            <label class="form-label">Car Class <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                            <select name="car_class" id="ce-car-class" class="form-select">
                                <option value="" {{ !old('car_class') ? 'selected' : '' }}>Open</option>
                                @foreach(['GT2', 'GT3', 'GT4', 'M2'] as $cls)
                                    <option value="{{ $cls }}" {{ old('car_class') === $cls ? 'selected' : '' }}>{{ $cls }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Description <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Additional event info…">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- ── Multiclass (single only) ────────────────────────────────── --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6" data-mode-single data-multiclass-wrap>
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Multiclass <span class="fw-normal" style="text-transform:none">(optional)</span></p>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_multiclass_race"
                                   data-multiclass-checkbox {{ old('is_multiclass') ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="is_multiclass_race">Enable Multiclass</label>
                        </div>
                        <input type="hidden" name="is_multiclass" data-multiclass-flag value="{{ old('is_multiclass') ? '1' : '0' }}">
                    </div>
                    <div data-multiclass-section style="{{ old('is_multiclass') ? '' : 'display:none' }}">
                        <div data-multiclass-list></div>
                        <button type="button" data-multiclass-add
                                class="btn btn-sm fw-bold text-uppercase"
                                style="background:rgba(219,39,119,.1);color:#db2777;border:1px solid rgba(219,39,119,.3);font-size:.72rem">
                            + Add Class
                        </button>
                        <input type="hidden" name="classes_json" data-multiclass-json value="[]">
                    </div>
                </div>

            </div>

            {{-- ── gPortal Server & Slot (single only) ─────────────────────── --}}
            @if($servers->isNotEmpty())
            <div class="admin-card mb-4" data-mode-single>
                <div class="px-4 py-3">
                    <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">gPortal Server <span class="fw-normal" style="text-transform:none">(optional)</span></p>
                    <p class="text-secondary mb-3" style="font-size:.75rem">Assign a server slot — config will be auto-pushed 10 minutes before the reset.</p>

                    <div class="mb-3">
                        <label class="form-label">Server</label>
                        <select name="ftp_server_id" id="gp-server" class="form-select">
                            <option value="">— No server assigned —</option>
                            @foreach($servers as $srv)
                                <option value="{{ $srv->id }}"
                                        data-type="{{ $srv->server_type }}"
                                        {{ old('ftp_server_id') == $srv->id ? 'selected' : '' }}>
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

                    <div id="gp-slot-picker" style="display:none">
                        <label class="form-label">Race Slot (UTC)</label>
                        <div id="gp-slot-grid" class="d-flex gap-2 flex-wrap mb-2" style="max-height:260px;overflow-y:auto"></div>
                        <input type="hidden" name="slot_time" id="gp-slot-value" value="{{ old('slot_time') }}">
                        <div id="gp-slot-selected" class="small fw-bold" style="color:#7c3aed;display:none"></div>
                        @error('slot_time') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div id="gp-scheduled-picker" style="display:none">
                        <label class="form-label">Race Slot (UTC)</label>
                        <input type="datetime-local" name="slot_time" id="gp-scheduled-value"
                               value="{{ old('slot_time') }}"
                               class="form-control" style="max-width:240px">
                        @error('slot_time') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Bulk events table (managed by bulk JS) ───────────────────── --}}
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
                                    <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Title</th>
                                    <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Track</th>
                                    <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:200px">Date & Time (BST/GMT)</th>
                                    <th class="pe-4" style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody data-bulk-tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Submit ───────────────────────────────────────────────────── --}}
            <div class="d-flex gap-2">
                <button type="submit" id="ce-submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    <span id="ce-btn-single">Create Event</span>
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
    const singleUrl       = '{{ route('admin.races.store') }}';
    const bulkUrl         = '{{ route('admin.races.bulk-store') }}';
    const modeInput       = document.getElementById('ce-mode-input');
    const singleMaxDrivers = document.getElementById('ce-max-drivers');
    const btnSingle       = document.getElementById('ce-btn-single');
    const btnBulk         = document.getElementById('ce-btn-bulk');
    const bulkEventsSection = document.querySelector('[data-bulk-events-section]');

    function switchMode(mode) {
        const isBulk = mode === 'bulk';
        if (form)     form.action = isBulk ? bulkUrl : singleUrl;
        if (modeInput) modeInput.value = mode;

        document.querySelectorAll('[data-mode-single]').forEach(el => el.style.display = isBulk ? 'none' : '');
        document.querySelectorAll('[data-mode-bulk]').forEach(el => el.style.display = isBulk ? '' : 'none');

        // Disable single-only hidden inputs to prevent duplicate submissions
        if (singleMaxDrivers) singleMaxDrivers.disabled = isBulk;

        // Bulk events section: hide on single, restore if rows exist on bulk
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
    }

    document.querySelectorAll('[data-mode-btn]').forEach(btn => {
        btn.addEventListener('click', () => switchMode(btn.dataset.modeBtn));
    });

    const initMode = new URLSearchParams(location.search).get('mode') || '{{ old('_mode', 'single') }}';
    switchMode(initMode);
})();

// ── Format Event JS ────────────────────────────────────────────────────────
(function () {
    const formats  = @json($formatsWithSlug);
    const tracks   = @json($accTracks);
    const oldGame  = '{{ old('game') }}';
    const oldFmt   = '{{ old('event_format_id') }}';

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
            pitstop = 'Required (' + fmt.pitstop_count + 'x';
            if (fmt.min_stop_secs) pitstop += ', min ' + fmt.min_stop_secs + 's';
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

    const enduranceWrap = document.getElementById('ce-endurance-wrap');

    function setEnduranceVisible(slug) {
        if (enduranceWrap) enduranceWrap.style.display = slug === 'endurance' ? '' : 'none';
    }

    function updateFormats(game) {
        fmtEl.innerHTML = '<option value="">— Select format —</option>';
        showFormatInfo(null);
        setEnduranceVisible('');
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
    });
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
})();

// ── gPortal Slot Picker ───────────────────────────────────────────────────────
(function () {
    const serverEl    = document.getElementById('gp-server');
    if (!serverEl) return;

    const slotPicker  = document.getElementById('gp-slot-picker');
    const schedPicker = document.getElementById('gp-scheduled-picker');
    const slotGrid    = document.getElementById('gp-slot-grid');
    const slotValue   = document.getElementById('gp-slot-value');
    const slotLabel   = document.getElementById('gp-slot-selected');

    const serverSlots = @json($serverSlots ?? []);
    const DAYS        = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    function toLocalTime(utcSlot) {
        const d = new Date(utcSlot.replace(' ', 'T') + ':00Z');
        return d.toLocaleTimeString('en-GB', { timeZone: 'Europe/London', hour: '2-digit', minute: '2-digit' });
    }

    function toLocalDateLabel(utcSlot) {
        const d = new Date(utcSlot.replace(' ', 'T') + ':00Z');
        return DAYS[d.toLocaleDateString('en-GB', { timeZone: 'Europe/London', weekday: 'short' }).slice(0,3).indexOf(d.toLocaleDateString('en-GB', { timeZone: 'Europe/London', weekday: 'short' }))]
            || d.toLocaleDateString('en-GB', { timeZone: 'Europe/London', weekday: 'short' });
    }

    function toLocalDayKey(utcSlot) {
        const d = new Date(utcSlot.replace(' ', 'T') + ':00Z');
        return d.toLocaleDateString('en-CA', { timeZone: 'Europe/London' });
    }

    function toLocalDayHeader(utcSlot) {
        const d = new Date(utcSlot.replace(' ', 'T') + ':00Z');
        const weekday = d.toLocaleDateString('en-GB', { timeZone: 'Europe/London', weekday: 'short' });
        const day     = d.toLocaleDateString('en-GB', { timeZone: 'Europe/London', day: '2-digit', month: '2-digit' });
        return weekday + ' ' + day;
    }

    function buildSlotGrid(serverId) {
        slotGrid.innerHTML = '';
        const data = serverSlots[serverId];
        if (!data || !data.slots.length) return;

        const taken    = data.takenSlots || [];
        const selected = slotValue ? slotValue.value : '';

        const byDate = {};
        const headers = {};
        data.slots.forEach(slot => {
            const key = toLocalDayKey(slot);
            if (!byDate[key]) { byDate[key] = []; headers[key] = toLocalDayHeader(slot); }
            byDate[key].push(slot);
        });

        Object.entries(byDate).forEach(([dateKey, slots]) => {
            const col = document.createElement('div');
            col.style.cssText = 'min-width:90px';

            const dayLabel = document.createElement('div');
            dayLabel.className = 'fw-black text-uppercase mb-1';
            dayLabel.style.cssText = 'font-size:.68rem;letter-spacing:.06em;color:#9ca3af';
            dayLabel.textContent = headers[dateKey];
            col.appendChild(dayLabel);

            slots.forEach(slot => {
                const displayTime = toLocalTime(slot);
                const isTaken     = taken.includes(slot);
                const isSelected  = selected === slot;

                const btn = document.createElement('button');
                btn.type        = 'button';
                btn.textContent = displayTime;
                btn.className   = 'btn btn-sm fw-bold mb-1 d-block w-100';
                btn.style.cssText = 'font-size:.72rem;border-radius:6px;padding:3px 6px;' + (
                    isTaken    ? 'background:#f3f4f6;color:#d1d5db;cursor:not-allowed;border:1px solid #e5e7eb' :
                    isSelected ? 'background:#7c3aed;color:#fff;border:1px solid #7c3aed' :
                                 'background:#f8f5ff;color:#7c3aed;border:1px solid rgba(124,58,237,.3)'
                );

                if (!isTaken) {
                    btn.addEventListener('click', () => {
                        slotValue.value = slot;
                        slotLabel.textContent  = '✓ ' + displayTime + ' (GMT/BST)';
                        slotLabel.style.display = '';
                        buildSlotGrid(serverId);
                    });
                } else {
                    btn.disabled = true;
                    btn.title    = 'Already taken';
                }

                col.appendChild(btn);
            });

            slotGrid.appendChild(col);
        });
    }

    function onServerChange() {
        const opt    = serverEl.options[serverEl.selectedIndex];
        const type   = opt ? opt.dataset.type : null;
        const id     = serverEl.value;

        slotPicker.style.display  = '';
        schedPicker.style.display = 'none';
        slotGrid.innerHTML        = '';

        if (!id) {
            slotPicker.style.display = 'none';
            return;
        }

        if (type === 'scheduled') {
            slotPicker.style.display  = 'none';
            schedPicker.style.display = '';
        } else {
            slotPicker.style.display  = '';
            buildSlotGrid(id);
        }
    }

    serverEl.addEventListener('change', onServerChange);
    onServerChange();
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
    const gameLabels  = { acc: 'ACC', lmu: 'LMU', iracing: 'iRACING', ac: 'AC RALLY' };
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

        // Car class / track / weather
        if (prev.classEl)   prev.classEl.textContent  = carClass || 'Open';
        if (prev.trackName) prev.trackName.textContent = track    || '—';
        if (prev.weatherEl) {
            const icon  = wxIcons[weather]  || '';
            const label = wxLabels[weather] || (weather ? weather : '—');
            prev.weatherEl.textContent = icon ? icon + ' ' + label : label;
        }

        // Duration badge
        if (prev.duration) {
            if (fmtSlug === 'endurance' && endurDur) {
                prev.duration.textContent   = endurDur.toUpperCase();
                prev.duration.style.display = '';
            } else if (fmtData && fmtData.race1_mins) {
                prev.duration.textContent   = fmtData.race1_mins + ' MIN';
                prev.duration.style.display = '';
            } else {
                prev.duration.style.display = 'none';
            }
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
                    const sessions = [
                        { key: 'P',  mins: fmtData.practice_mins, bg: '#1f2937', color: '#9ca3af' },
                        { key: 'Q',  mins: fmtData.quali_mins,    bg: '#292524', color: '#f59e0b' },
                        { key: fmtData.race2_mins ? 'R1' : 'R', mins: fmtData.race1_mins, bg: '#2e1065', color: '#a78bfa' },
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
                        ? 'Required (' + fmtData.pitstop_count + 'x)'
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
     'ce-endurance-duration','ce-track-select']
        .forEach(id => { const el = $(id); if (el) el.addEventListener('change', updatePreview); });
    const wEl = document.querySelector('[name="weather"]');
    const sEl = document.querySelector('[name="scheduled_at"]');
    const tEl = $('ce-track-text');
    if (wEl) wEl.addEventListener('change', updatePreview);
    if (sEl) sEl.addEventListener('change', updatePreview);
    if (tEl) tEl.addEventListener('input', updatePreview);

    updatePreview();
})();
</script>

@endsection
