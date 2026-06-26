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
@endphp

{{-- Tab bar --}}
<div class="d-flex mb-4" style="border-bottom:2px solid #e5e7eb">
    <button type="button" data-tab-btn="single"
            class="btn fw-black text-uppercase rounded-0 border-0 px-4 py-2"
            style="font-size:.76rem;letter-spacing:.08em;margin-bottom:-2px">
        Format Event
    </button>
    <button type="button" data-tab-btn="bulk"
            class="btn fw-black text-uppercase rounded-0 border-0 px-4 py-2"
            style="font-size:.76rem;letter-spacing:.08em;margin-bottom:-2px">
        Bulk Schedule
    </button>
</div>

{{-- ═══════════════════════════════════════════════════════════
     TAB: FORMAT EVENT (single event, format-based)
════════════════════════════════════════════════════════════════ --}}
<div data-tab-content="single">
<form action="{{ route('admin.races.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4 align-items-start">

        {{-- Left --}}
        <div class="col-12 col-lg-8">

            {{-- Section 1: Event --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Event</p>

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="e.g. Round 1 — Monza Sprint">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

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

                    {{-- Format info card --}}
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
                </div>

                {{-- Track / Weather / Time --}}
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

                {{-- Schedule --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule</p>

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

                {{-- Requirements --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Requirements <span class="fw-normal" style="text-transform:none">(optional — all off by default)</span></p>

                    <div class="d-flex flex-column gap-3">
                        <div>
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="ce-sr-toggle" {{ old('sr_requirement') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="ce-sr-toggle">Safety Rating (SR)</label>
                            </div>
                            <div id="ce-sr-panel" style="{{ old('sr_requirement') ? '' : 'display:none' }}">
                                <select name="sr_requirement" class="form-select form-select-sm" style="max-width:280px">
                                    <option value="">— No requirement —</option>
                                    <option value="5" {{ old('sr_requirement') === '5' ? 'selected' : '' }}>SR ≥ 5.0 (grade B+)</option>
                                    <option value="7" {{ old('sr_requirement') === '7' ? 'selected' : '' }}>SR ≥ 7.0 (grade X+)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="ce-minrating-toggle" {{ old('min_rating') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="ce-minrating-toggle">XCL Rating (Min)</label>
                            </div>
                            <div id="ce-minrating-panel" style="{{ old('min_rating') ? '' : 'display:none' }}">
                                <select name="min_rating" class="form-select form-select-sm" style="max-width:280px">
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

                {{-- Additional --}}
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
                            <select name="car_class" class="form-select">
                                <option value="">— Not set —</option>
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

                {{-- Multiclass --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6" data-multiclass-wrap>
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

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Create Event</button>
                <a href="{{ route('admin.races.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
            </div>
        </div>

        {{-- Right: media --}}
        <div class="col-12 col-lg-4">
            <div class="admin-card mb-4">
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
</form>
</div>

{{-- ═══════════════════════════════════════════════════════════
     TAB: BULK SCHEDULE
════════════════════════════════════════════════════════════════ --}}
<div data-tab-content="bulk" style="display:none">
<div data-bulk-wrap>
<form action="{{ route('admin.races.bulk-store') }}" method="POST">
@csrf
<input type="hidden" name="_tab" value="bulk">

<div class="row g-4 align-items-start">

    {{-- Left: generator + events --}}
    <div class="col-12 col-lg-8">

        {{-- Schedule Generator --}}
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-2">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule Generator</p>

                {{-- Mode toggle --}}
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

                {{-- Shared: base name + default track --}}
                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <label class="form-label">Base Name</label>
                        <input type="text" data-bulk-base-name value="Round" class="form-control" placeholder="e.g. Round">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Default Track</label>
                        <input type="text" data-bulk-default-track class="form-control" placeholder="e.g. Monza">
                    </div>
                </div>
            </div>

            <div class="px-4 pb-4">
                <button type="button" data-bulk-generate disabled
                        class="btn fw-black text-uppercase text-white px-4"
                        style="background:#7c3aed">
                    Generate Schedule
                </button>
                <span data-bulk-no-date class="text-secondary ms-2" style="font-size:.78rem">Pick a start date first</span>
            </div>
        </div>

        {{-- Events list --}}
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

            <div class="d-flex gap-2">
                <button type="submit"
                        class="btn fw-black text-uppercase text-white px-4"
                        style="background:#7c3aed">
                    Create <span data-bulk-count-display>0</span> Races
                </button>
                <a href="{{ route('admin.races.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>
        </div>

    </div>

    {{-- Right: shared settings --}}
    <div class="col-12 col-lg-4">
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-2">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Shared Settings</p>

                <div class="mb-3">
                    <label class="form-label">Game</label>
                    <select name="game" class="form-select @error('game') is-invalid @enderror" required>
                        <option value="">Select game...</option>
                        <option value="acc"     {{ old('game') === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                        <option value="lmu"     {{ old('game') === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                        <option value="iracing" {{ old('game') === 'iracing' ? 'selected' : '' }}>iRacing</option>
                        <option value="ac"      {{ old('game') === 'ac'      ? 'selected' : '' }}>AC Rally</option>
                    </select>
                    @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Multiplier</label>
                    <select name="duration_key" class="form-select @error('duration_key') is-invalid @enderror">
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
                    @error('duration_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Max Drivers <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                    <input type="number" name="max_drivers" value="{{ old('max_drivers') }}"
                           class="form-control @error('max_drivers') is-invalid @enderror"
                           min="1">
                    @error('max_drivers') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Event Tag</p>

                @php
                    $bulkTagsConfig = json_encode([
                        'tags'        => $tags->map(fn($t) => ['slug' => $t->slug, 'name' => $t->name, 'color' => $t->color]),
                        'storeUrl'    => route('admin.event-tags.store'),
                        'csrfToken'   => csrf_token(),
                        'selectedTag' => old('event_tag', ''),
                    ]);
                @endphp
                <div data-tags-wrap data-config='{{ $bulkTagsConfig }}'>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label mb-0">Tag</label>
                        <button type="button" data-tags-toggle
                                class="btn btn-sm fw-bold text-uppercase"
                                style="font-size:.68rem;padding:2px 8px;background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.3);border-radius:6px">
                            + New
                        </button>
                    </div>
                    <select name="event_tag" class="form-select @error('event_tag') is-invalid @enderror" data-tags-select required>
                        <option value="">Select tag...</option>
                    </select>
                    @error('event_tag') <div class="invalid-feedback">{{ $message }}</div> @enderror

                    <div data-tags-add-panel style="display:none">
                        <div class="mt-2 p-3 rounded-2" style="background:#f8f5ff;border:1px solid rgba(124,58,237,.2)">
                            <div data-tags-error class="alert alert-danger py-1 px-2 mb-2" style="font-size:.8rem;display:none"></div>
                            <div class="d-flex gap-2 align-items-end">
                                <div class="flex-grow-1">
                                    <label class="form-label" style="font-size:.78rem">Name</label>
                                    <input type="text" data-tags-name class="form-control form-control-sm">
                                </div>
                                <div>
                                    <label class="form-label" style="font-size:.78rem">Color</label>
                                    <input type="color" data-tags-color class="form-control form-control-sm form-control-color" style="width:46px;padding:2px" value="#7B2FBE">
                                </div>
                                <button type="button" data-tags-save
                                        class="btn btn-sm fw-bold text-white" style="background:#7c3aed;white-space:nowrap">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Description <span class="fw-normal" style="text-transform:none">(optional)</span></p>
                <textarea name="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Applies to all events...">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

        </div>
    </div>

</div>
</form>
</div>
</div>

<script>
(function () {
    // ── Tab switching ──────────────────────────────────────────────────────
    function switchTab(tab) {
        document.querySelectorAll('[data-tab-btn]').forEach(btn => {
            const active = btn.dataset.tabBtn === tab;
            btn.style.color        = active ? '#7c3aed' : '#9ca3af';
            btn.style.borderBottom = active ? '2px solid #7c3aed' : '2px solid transparent';
        });
        document.querySelectorAll('[data-tab-content]').forEach(el => {
            el.style.display = el.dataset.tabContent === tab ? '' : 'none';
        });
        history.replaceState(null, '', location.pathname + '?tab=' + tab);
    }

    document.querySelectorAll('[data-tab-btn]').forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tabBtn));
    });

    const initTab = new URLSearchParams(location.search).get('tab') || '{{ old('_tab', 'single') }}';
    switchTab(initTab);
})();

// ── Format Event JS ────────────────────────────────────────────────────────
(function () {
    const formats  = @json($formats->groupBy('game'));
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
        if (fmt.pitstop_type === 'fuel_only' && fmt.pitstop_count > 0) {
            pitstop = 'Fuel Only (' + fmt.pitstop_count + 'x';
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

    function updateFormats(game) {
        fmtEl.innerHTML = '<option value="">— Select format —</option>';
        showFormatInfo(null);
        if (!game || !formats[game]) return;
        formats[game].sort((a, b) => a.sort_order - b.sort_order).forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = f.name;
            if (String(f.id) === oldFmt) opt.selected = true;
            fmtEl.appendChild(opt);
        });
        if (oldFmt) {
            const selected = (formats[game] || []).find(f => String(f.id) === oldFmt);
            if (selected) showFormatInfo(selected);
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
</script>

@endsection