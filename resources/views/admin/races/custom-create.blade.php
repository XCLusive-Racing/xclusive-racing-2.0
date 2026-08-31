@extends('layouts.admin')

@section('title', 'Custom Race')
@section('page-title', 'Custom Race')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

@php
$tagsConfig = json_encode([
    'tags'        => $tags->map(fn($t) => ['slug' => $t->slug, 'name' => $t->name, 'color' => $t->color]),
    'storeUrl'    => route('admin.event-tags.store'),
    'csrfToken'   => csrf_token(),
    'selectedTag' => old('event_tag', ''),
]);
@endphp

<style>
[data-cr-step-nav].active .cr-step-circle { background:#7c3aed; border-color:#7c3aed; color:#fff; }
[data-cr-step-nav].active > div > span { color:#7c3aed; }
[data-cr-step-nav].done .cr-step-circle { background:#7c3aed; border-color:#7c3aed; color:#fff; }
[data-cr-step-nav].done > div > span { color:#374151; }
[data-cr-step-nav].done .cr-step-connector,
[data-cr-step-nav].active .cr-step-connector { background:#7c3aed; }
</style>

<form id="cr-form" action="{{ route('admin.races.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4 align-items-start">

        {{-- ── Left column ──────────────────────────────────────────────────── --}}
        <div class="col-12 col-lg-8">

            {{-- ── Stepper nav ──────────────────────────────────────────────── --}}
            <div id="cr-stepper-nav" class="d-flex align-items-center mb-4">
                @foreach([1 => 'Event', 2 => 'Schedule', 3 => 'Drivers', 4 => 'Details'] as $n => $label)
                <div data-cr-step-nav="{{ $n }}" class="d-flex align-items-center {{ $n < 4 ? 'flex-grow-1' : '' }}">
                    <div class="d-flex align-items-center gap-2 flex-shrink-0" style="white-space:nowrap">
                        <div class="cr-step-circle fw-black" style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;border:2px solid #e5e7eb;background:#fff;color:#9ca3af;transition:all .2s">{{ $n }}</div>
                        <span class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;transition:color .2s">{{ $label }}</span>
                    </div>
                    @if($n < 4)
                    <div class="cr-step-connector" style="flex:1;height:2px;background:#e5e7eb;margin:0 12px;min-width:12px;transition:background .2s"></div>
                    @endif
                </div>
                @endforeach
            </div>

            {{-- ══════════════════════════════════════════════════════════════ --}}
            {{-- STEP 1 — Event                                                 --}}
            {{-- ══════════════════════════════════════════════════════════════ --}}
            <div data-cr-step-panel="1">

                <div class="admin-card mb-4">

                    {{-- Event --}}
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Event</p>

                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" id="cr-title" name="title" value="{{ old('title') }}"
                                   class="form-control @error('title') is-invalid @enderror"
                                   placeholder="e.g. Nordschleife Thursday GT3" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-4">
                                <label class="form-label">Game <span class="text-danger">*</span></label>
                                <select id="cr-game" name="game" class="form-select @error('game') is-invalid @enderror">
                                    <option value="">Select game…</option>
                                    <option value="acc"     {{ old('game') === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                                    <option value="lmu"     {{ old('game') === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                                    <option value="iracing" {{ old('game') === 'iracing' ? 'selected' : '' }}>iRacing</option>
                                    <option value="ac"      {{ old('game') === 'ac'      ? 'selected' : '' }}>ACC PC</option>
                                </select>
                                @error('game')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Track <span class="text-danger">*</span></label>
                                <select id="cr-track-select" name="track"
                                        class="form-select @error('track') is-invalid @enderror"
                                        style="display:none">
                                    <option value="">Select track…</option>
                                    @foreach($accTracks as $t)
                                        <option value="{{ $t }}" {{ old('track') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                    @endforeach
                                </select>
                                <input type="text" id="cr-track-text" name="track" value="{{ old('track') }}"
                                       class="form-control @error('track') is-invalid @enderror"
                                       placeholder="e.g. Monza">
                                @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Car Class <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                                <select id="cr-car-class" name="car_class" class="form-select">
                                    <option value="">Open</option>
                                    @foreach(['GT2', 'GT3', 'GT4', 'TCX', 'GTC'] as $cls)
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
                                    <input type="number" id="cr-practice" name="practice_duration" value="{{ old('practice_duration') }}"
                                           class="form-control @error('practice_duration') is-invalid @enderror"
                                           min="1" max="999" placeholder="—">
                                    <span class="input-group-text" style="font-size:.78rem">min</span>
                                </div>
                                @error('practice_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">Qualifying</label>
                                <div class="input-group">
                                    <input type="number" id="cr-qualifying" name="qualifying_duration" value="{{ old('qualifying_duration') }}"
                                           class="form-control @error('qualifying_duration') is-invalid @enderror"
                                           min="1" max="999" placeholder="—">
                                    <span class="input-group-text" style="font-size:.78rem">min</span>
                                </div>
                                @error('qualifying_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">Race <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" id="cr-race" name="race_duration" value="{{ old('race_duration') }}"
                                           class="form-control @error('race_duration') is-invalid @enderror"
                                           min="1" max="999" placeholder="e.g. 60" required>
                                    <span class="input-group-text" style="font-size:.78rem">min</span>
                                </div>
                                @error('race_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">XCL-R Multiplier</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="font-size:.78rem">×</span>
                                    <input type="number" id="cr-xcl-multiplier" name="xcl_r_multiplier"
                                           value="{{ old('xcl_r_multiplier') }}"
                                           class="form-control @error('xcl_r_multiplier') is-invalid @enderror"
                                           min="0.1" max="10" step="0.1" placeholder="1.0">
                                </div>
                                @error('xcl_r_multiplier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Pit Stop --}}
                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Pit Stop</p>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="cr-pitstop-toggle"
                                   {{ old('pitstop_count', 0) > 0 ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="cr-pitstop-toggle">Mandatory Pit Stop</label>
                        </div>
                        <div id="cr-pitstop-panel" style="{{ old('pitstop_count', 0) > 0 ? '' : 'display:none' }}">
                            <div class="d-flex gap-3 flex-wrap align-items-end">
                                <div>
                                    <label class="form-label" style="font-size:.82rem">Stops</label>
                                    <input type="number" name="pitstop_count" id="cr-pitstop-count"
                                           value="{{ old('pitstop_count', 1) }}"
                                           class="form-control form-control-sm" min="0" max="9" style="width:70px">
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="cr-minstop-toggle"
                                           {{ old('min_stop_secs') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="cr-minstop-toggle" style="font-size:.85rem">25s minimum waiting time</label>
                                    <input type="hidden" name="min_stop_secs" id="cr-minstop-val" value="{{ old('min_stop_secs', '') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>{{-- /step 1 --}}

            {{-- ══════════════════════════════════════════════════════════════ --}}
            {{-- STEP 2 — Schedule                                              --}}
            {{-- ══════════════════════════════════════════════════════════════ --}}
            <div data-cr-step-panel="2" style="display:none">

                <div class="admin-card mb-4">

                    {{-- Date/Time & Server --}}
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule</p>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Date & Time (BST) <span class="text-danger">*</span></label>
                                <input type="text" id="cr-scheduled-at" name="scheduled_at" data-flatpickr data-min-today="true" data-minute-increment="1"
                                       value="{{ old('scheduled_at') }}"
                                       placeholder="Select date & time…"
                                       class="form-control @error('scheduled_at') is-invalid @enderror">
                                @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Server <span class="text-danger">*</span></label>
                                <select name="ftp_server_id" id="cr-server" class="form-select @error('ftp_server_id') is-invalid @enderror" required>
                                    <option value="">— Select server —</option>
                                    @foreach($servers as $srv)
                                        <option value="{{ $srv->id }}"
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
                                @error('ftp_server_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Configuration --}}
                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Configuration</p>

                        <div class="row g-3">
                            <div class="col-sm-4">
                                <label class="form-label">Max Drivers/Teams</label>
                                <input type="number" name="max_drivers" value="{{ old('max_drivers') }}"
                                       class="form-control @error('max_drivers') is-invalid @enderror"
                                       min="1">
                                @error('max_drivers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                </div>

            </div>{{-- /step 2 --}}

            {{-- ══════════════════════════════════════════════════════════════ --}}
            {{-- STEP 3 — Drivers                                               --}}
            {{-- ══════════════════════════════════════════════════════════════ --}}
            <div data-cr-step-panel="3" style="display:none">

                <div class="admin-card mb-4">

                    {{-- Requirements --}}
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Requirements <span class="fw-normal" style="text-transform:none">(optional)</span></p>

                        <div class="d-flex flex-column gap-3">
                            <div>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="cr-sr-toggle" {{ old('sr_requirement') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="cr-sr-toggle">Safety Rating (SR)</label>
                                </div>
                                <div id="cr-sr-panel" style="{{ old('sr_requirement') ? '' : 'display:none' }}">
                                    <select name="sr_requirement" id="cr-sr-select" class="form-select form-select-sm" style="max-width:280px">
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

                    {{-- Multiclass --}}
                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6" data-multiclass-wrap>
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Multiclass <span class="fw-normal" style="text-transform:none">(optional)</span></p>

                        <input type="hidden" name="is_multiclass" data-multiclass-flag value="{{ old('is_multiclass', '0') }}">
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
                                       {{ in_array($cls, old('selected_classes', [])) ? 'checked' : '' }}>
                                <span style="width:10px;height:10px;border-radius:50%;background:{{ $meta['color'] }};flex-shrink:0"></span>
                                {{ $meta['label'] }}
                            </label>
                            @endforeach
                        </div>

                        <div data-mc-drivers-wrap class="d-flex flex-wrap gap-3" style="display:none"></div>
                        <div class="text-secondary mt-2" style="font-size:.75rem" data-mc-hint>Select one or more classes to enable multiclass</div>
                    </div>

                </div>

            </div>{{-- /step 3 --}}

            {{-- ══════════════════════════════════════════════════════════════ --}}
            {{-- STEP 4 — Details                                               --}}
            {{-- ══════════════════════════════════════════════════════════════ --}}
            <div data-cr-step-panel="4" style="display:none">

                <div class="admin-card mb-4">

                    {{-- Tag & Description --}}
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Additional</p>

                        <div class="mb-3">
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
                                                <input type="text" data-tags-name placeholder="e.g. Special Event" class="form-control form-control-sm">
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

                        <div>
                            <label class="form-label">Description <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Additional event info…">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    {{-- Media --}}
                    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Media</p>
                        <x-media-picker name="image" label="Background Image" />
                        <div class="mt-3">
                            <x-media-picker name="icon" label="Event Icon" />
                        </div>
                    </div>

                </div>

            </div>{{-- /step 4 --}}

            {{-- ── Navigation buttons ──────────────────────────────────────── --}}
            <div class="d-flex align-items-center gap-2 mt-4">
                <button type="button" id="cr-step-prev" style="display:none" class="btn btn-outline-secondary fw-bold text-uppercase px-4">← Back</button>
                <button type="button" id="cr-step-next" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Next →</button>
                <button type="submit" id="cr-submit" style="display:none;background:#7c3aed" class="btn fw-black text-uppercase text-white px-4">Create Race</button>
                <a href="{{ route('admin.races.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
            </div>

        </div>

        {{-- ── Right: preview ───────────────────────────────────────────────── --}}
        {{-- Outside the sticky wrapper so the media picker fixed modal isn't clipped --}}
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
                                <span style="font-size:.75rem;font-weight:900;font-style:italic;text-transform:uppercase;color:#7c3aed;letter-spacing:.04em">Custom Race</span>
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
                                  style="display:none;font-size:.65rem;font-weight:900;text-transform:uppercase;letter-spacing:.05em;padding:2px 8px;border-radius:4px;color:#fff"></span>
                            <span id="crp-sr-badge" style="display:none;align-items:center;gap:4px">
                                <span id="crp-sr-circle"
                                      style="width:20px;height:20px;border-radius:50%;background:#1a1a2e;border:2px solid #dc2626;display:inline-flex;align-items:center;justify-content:center;color:#dc2626;font-size:.58rem;font-weight:900;flex-shrink:0">B</span>
                                <span id="crp-sr-text"
                                      style="font-size:.65rem;font-weight:900;color:#e5e7eb;white-space:nowrap">SR 5.0+</span>
                            </span>
                            <span id="crp-xcl-badge"
                                  style="display:none;font-size:.63rem;font-weight:900;text-transform:capitalize;padding:2px 8px;border-radius:4px;border:1px solid rgba(205,127,50,.4);background:rgba(205,127,50,.15);color:#cd7f32"></span>
                            <span id="crp-open-badge"
                                  style="display:none;font-size:.65rem;font-weight:900;text-transform:uppercase;padding:2px 8px;border-radius:4px;background:rgba(212,238,106,.12);color:#d4ee6a;border:1px solid rgba(212,238,106,.25)">OPEN</span>
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

            </div>
        </div>

    </div>
</form>

<script>
// ── Stepper ─────────────────────────────────────────────────────────────────
(function () {
    const TOTAL   = 4;
    let current   = 1;

    const prevBtn = document.getElementById('cr-step-prev');
    const nextBtn = document.getElementById('cr-step-next');
    const subBtn  = document.getElementById('cr-submit');

    function goTo(n) {
        document.querySelectorAll('[data-cr-step-panel]').forEach(p => {
            p.style.display = (parseInt(p.dataset.crStepPanel) === n) ? '' : 'none';
        });

        document.querySelectorAll('[data-cr-step-nav]').forEach(nav => {
            const s = parseInt(nav.dataset.crStepNav);
            nav.classList.toggle('active', s === n);
            nav.classList.toggle('done',   s < n);
        });

        prevBtn.style.display = (n > 1)      ? '' : 'none';
        nextBtn.style.display = (n < TOTAL)  ? '' : 'none';
        subBtn.style.display  = (n === TOTAL) ? '' : 'none';

        current = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    prevBtn.addEventListener('click', () => goTo(current - 1));
    nextBtn.addEventListener('click', () => goTo(current + 1));

    goTo(1);
})();

// ── Toggle panels ────────────────────────────────────────────────────────────
(function () {
    [['cr-sr-toggle','cr-sr-panel'],['cr-minrating-toggle','cr-minrating-panel'],['cr-maxrating-toggle','cr-maxrating-panel']].forEach(([tid,pid]) => {
        const t = document.getElementById(tid), p = document.getElementById(pid);
        t?.addEventListener('change', () => { p.style.display = t.checked ? '' : 'none'; });
    });

    const pitstopToggle = document.getElementById('cr-pitstop-toggle');
    const pitstopPanel  = document.getElementById('cr-pitstop-panel');
    const pitstopCount  = document.getElementById('cr-pitstop-count');
    const minstopToggle = document.getElementById('cr-minstop-toggle');
    const minstopVal    = document.getElementById('cr-minstop-val');

    if (pitstopToggle) {
        pitstopToggle.addEventListener('change', () => {
            pitstopPanel.style.display = pitstopToggle.checked ? '' : 'none';
            if (pitstopCount) pitstopCount.name = pitstopToggle.checked ? 'pitstop_count' : '';
        });
        if (!pitstopToggle.checked && pitstopCount) pitstopCount.name = '';
    }

    if (minstopToggle) {
        const sync = () => { if (minstopVal) minstopVal.value = minstopToggle.checked ? '25' : ''; };
        minstopToggle.addEventListener('change', sync);
        sync();
    }
})();

// ── Live Event Preview ───────────────────────────────────────────────────────
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
        time:          $('crp-time'),
        meta:          $('crp-meta'),
        gameBadge:     $('crp-game-badge'),
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

    const gameColors = { acc: '#7c3aed', lmu: '#db2877', iracing: '#2563eb', ac: '#16a34a' };
    const gameLabels = { acc: 'ACC', lmu: 'LMU', iracing: 'iRACING', ac: 'ACC PC' };
    const wxIcons    = { dry: '☀', wet: '🌧', mixed: '⛅', random: '🎲' };
    const wxLabels   = { dry: 'Dry', wet: 'Wet', mixed: 'Mixed', random: 'Random' };
    const platBadges = {
        acc:     [{ icon: 'fa-brands fa-playstation', label: 'PS5' }, { icon: 'fa-brands fa-xbox', label: 'Xbox' }],
        lmu:     [{ icon: 'fa-brands fa-windows', label: 'PC' }],
        iracing: [{ icon: 'fa-brands fa-windows', label: 'PC' }],
        ac:      [{ icon: 'fa-brands fa-windows', label: 'PC' }],
    };
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
        const game     = ($('cr-game')         || {}).value || '';
        const _tSel = $('cr-track-select'), _tTxt = $('cr-track-text');
        const track    = (game === 'acc' ? (_tSel && _tSel.value) : (_tTxt && _tTxt.value)) || '';
        const carClass = ($('cr-car-class')    || {}).value || '';
        const pracMin  = ($('cr-practice')     || {}).value || '';
        const qualiMin = ($('cr-qualifying')   || {}).value || '';
        const raceMin  = ($('cr-race')         || {}).value || '';
        const dkVal    = '';
        const weather  = '';
        const schedVal = ($('cr-scheduled-at') || {}).value || '';
        const title    = ($('cr-title')        || {}).value || '';
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

        const bgUrl = customBgUrl || trackPreviewUrls[track] || '';
        if (bgUrl && prev.trackImg) {
            prev.trackImg.src = bgUrl;
            prev.trackImg.style.display = '';
            if (prev.trackPh) prev.trackPh.style.display = 'none';
        } else {
            if (prev.trackImg) prev.trackImg.style.display = 'none';
            if (prev.trackPh) prev.trackPh.style.display  = '';
        }

        if (prev.titleText) prev.titleText.textContent = title;
        if (prev.titleOverlay) prev.titleOverlay.style.display = title ? '' : 'none';

        if (prev.platforms) {
            prev.platforms.innerHTML = '';
            (platBadges[game] || []).forEach(p => {
                const s = document.createElement('span');
                s.style.cssText = 'display:inline-flex;align-items:center;gap:3px;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:4px;background:rgba(255,255,255,.12);color:#e5e7eb';
                s.innerHTML = `<i class="${p.icon}" style="font-size:.7rem"></i>${p.label}`;
                prev.platforms.appendChild(s);
            });
        }

        const hasSessions = pracMin || qualiMin || raceMin;
        if (prev.sessionsBlock) {
            if (hasSessions || dkVal) {
                if (prev.sessionsPills) {
                    prev.sessionsPills.innerHTML = '';
                    [
                        { key: 'P',  mins: pracMin,  bg: '#1f2937', color: '#9ca3af' },
                        { key: 'Q',  mins: qualiMin, bg: '#292524', color: '#f59e0b' },
                        { key: 'R',  mins: raceMin,  bg: '#2e1065', color: '#a78bfa' },
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

        if (game && prev.gameBadge) {
            prev.gameBadge.textContent      = gameLabels[game] || game.toUpperCase();
            prev.gameBadge.style.background = gameColors[game] || '#374151';
            prev.gameBadge.style.display    = '';
        } else if (prev.gameBadge) {
            prev.gameBadge.style.display = 'none';
        }

        if (prev.srBadge) {
            if (srOn && srVal && srTierMap[srVal]) {
                const [letter, color] = srTierMap[srVal];
                if (prev.srCircle) { prev.srCircle.textContent = letter; prev.srCircle.style.borderColor = color; prev.srCircle.style.color = color; }
                if (prev.srText)   prev.srText.textContent = 'SR ' + srVal + '.0+';
                prev.srBadge.style.display = 'inline-flex';
            } else {
                prev.srBadge.style.display = 'none';
            }
        }

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

        if (prev.openBadge) prev.openBadge.style.display = (!srOn && !minOn && !maxOn) ? '' : 'none';

        const mcClasses = Array.from(document.querySelectorAll('[data-mc-class]:checked')).map(cb => cb.dataset.mcClass);
        if (prev.classEl) prev.classEl.textContent = mcClasses.length ? mcClasses.join(' + ') : (carClass || 'Open');
        if (prev.trackName) prev.trackName.textContent = track    || '—';
        if (prev.weatherEl) {
            const icon  = wxIcons[weather]  || '';
            const label = wxLabels[weather] || (weather ? weather : '—');
            prev.weatherEl.textContent = icon ? icon + ' ' + label : label;
        }

        if (prev.duration) {
            if (raceMin) {
                prev.duration.textContent   = raceMin + ' MIN';
                prev.duration.style.display = '';
            } else {
                prev.duration.style.display = 'none';
            }
        }
    }

    // Track input: ACC → select, other games → text
    const trackSelect = $('cr-track-select');
    const trackText   = $('cr-track-text');
    const gameEl      = $('cr-game');

    function updateTrackInput() {
        const game = gameEl ? gameEl.value : '';
        if (game === 'acc') {
            trackSelect.name  = 'track';
            trackText.name    = '';
            trackSelect.style.display = '';
            trackText.style.display   = 'none';
        } else {
            trackSelect.name  = '';
            trackText.name    = 'track';
            trackSelect.style.display = 'none';
            trackText.style.display   = '';
        }
        updatePreview();
    }

    function getTrack() {
        const game = gameEl ? gameEl.value : '';
        return game === 'acc'
            ? (trackSelect ? trackSelect.value : '')
            : (trackText  ? trackText.value   : '');
    }

    if (gameEl) gameEl.addEventListener('change', updateTrackInput);
    if (trackSelect) trackSelect.addEventListener('change', updatePreview);

    // Patch updatePreview to use the correct track source
    const _origUpdate = updatePreview;

    ['cr-game','cr-car-class','cr-xcl-multiplier',
     'cr-sr-toggle','cr-sr-select','cr-minrating-toggle','cr-minrating-select','cr-maxrating-toggle']
        .forEach(id => { const el = $(id); if (el) el.addEventListener('change', updatePreview); });
    ['cr-title','cr-practice','cr-qualifying','cr-race']
        .forEach(id => { const el = $(id); if (el) el.addEventListener('input', updatePreview); });
    if (trackText) trackText.addEventListener('input', updatePreview);
    const schedEl = $('cr-scheduled-at');
    if (schedEl) schedEl.addEventListener('change', updatePreview);

    updateTrackInput();
    document.querySelectorAll('[data-mc-class]').forEach(cb => cb.addEventListener('change', updatePreview));

    document.addEventListener('mp:change', e => {
        const { name, url } = e.detail;
        if (name === 'image') {
            customBgUrl = url || '';
            updatePreview();
        } else if (name === 'icon') {
            if (prev.iconImg) {
                prev.iconImg.src = url || '';
                prev.iconImg.style.display = url ? '' : 'none';
            }
        }
    });

    updatePreview();
})();
</script>

@endsection
