@extends('layouts.admin')

@section('title', 'Custom Race')
@section('page-title', 'Custom Race')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<form action="{{ route('admin.races.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4 align-items-start">

        {{-- Left --}}
        <div class="col-12 col-lg-8">

            {{-- Event --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Event</p>

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="e.g. Open Practice — Spa">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Game <span class="text-danger">*</span></label>
                            <select name="game" class="form-select @error('game') is-invalid @enderror">
                                <option value="">Select game…</option>
                                <option value="acc"     {{ old('game') === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                                <option value="lmu"     {{ old('game') === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                                <option value="iracing" {{ old('game') === 'iracing' ? 'selected' : '' }}>iRacing</option>
                                <option value="ac"      {{ old('game') === 'ac'      ? 'selected' : '' }}>AC Rally</option>
                            </select>
                            @error('game')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Track <span class="text-danger">*</span></label>
                            <input type="text" name="track" value="{{ old('track') }}"
                                   class="form-control @error('track') is-invalid @enderror"
                                   placeholder="e.g. Spa">
                            @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Car Class <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                            <select name="car_class" class="form-select">
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
                                <input type="number" name="practice_duration" value="{{ old('practice_duration') }}"
                                       class="form-control @error('practice_duration') is-invalid @enderror"
                                       min="1" max="999" placeholder="—">
                                <span class="input-group-text" style="font-size:.78rem">min</span>
                            </div>
                            @error('practice_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Qualifying</label>
                            <div class="input-group">
                                <input type="number" name="qualifying_duration" value="{{ old('qualifying_duration') }}"
                                       class="form-control @error('qualifying_duration') is-invalid @enderror"
                                       min="1" max="999" placeholder="—">
                                <span class="input-group-text" style="font-size:.78rem">min</span>
                            </div>
                            @error('qualifying_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Race 1 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="race_duration" value="{{ old('race_duration') }}"
                                       class="form-control @error('race_duration') is-invalid @enderror"
                                       min="1" max="999" placeholder="e.g. 30" required>
                                <span class="input-group-text" style="font-size:.78rem">min</span>
                            </div>
                            @error('race_duration')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">XCL-R Multiplier</label>
                            <select name="duration_key" class="form-select">
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
                            <select name="weather" class="form-select">
                                <option value="">— Not set —</option>
                                <option value="dry"    {{ old('weather') === 'dry'    ? 'selected' : '' }}>Dry</option>
                                <option value="wet"    {{ old('weather') === 'wet'    ? 'selected' : '' }}>Wet</option>
                                <option value="mixed"  {{ old('weather') === 'mixed'  ? 'selected' : '' }}>Mixed</option>
                                <option value="random" {{ old('weather') === 'random' ? 'selected' : '' }}>Random</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">In-game Time</label>
                            <select name="time_of_day" class="form-select">
                                <option value="">— Not set —</option>
                                <option value="day"     {{ old('time_of_day') === 'day'     ? 'selected' : '' }}>Day</option>
                                <option value="dusk"    {{ old('time_of_day') === 'dusk'    ? 'selected' : '' }}>Dusk</option>
                                <option value="night"   {{ old('time_of_day') === 'night'   ? 'selected' : '' }}>Night</option>
                                <option value="dynamic" {{ old('time_of_day') === 'dynamic' ? 'selected' : '' }}>Dynamic</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Max Drivers <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                            <input type="number" name="max_drivers" value="{{ old('max_drivers') }}"
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
                        <input type="datetime-local" name="scheduled_at"
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
                                <select name="sr_requirement" class="form-select form-select-sm" style="max-width:280px">
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

                {{-- Additional --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Additional</p>

                    @php
                        $tagsConfig = json_encode([
                            'tags'        => $tags->map(fn($t) => ['slug' => $t->slug, 'name' => $t->name, 'color' => $t->color]),
                            'storeUrl'    => route('admin.event-tags.store'),
                            'csrfToken'   => csrf_token(),
                            'selectedTag' => old('event_tag', ''),
                        ]);
                    @endphp
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
                                            <input type="text" data-tags-name placeholder="e.g. Practice" class="form-control form-control-sm">
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

                {{-- Multiclass --}}
                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6" data-multiclass-wrap>
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Multiclass <span class="fw-normal" style="text-transform:none">(optional)</span></p>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="cr_multiclass"
                                   data-multiclass-checkbox {{ old('is_multiclass') ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="cr_multiclass">Enable Multiclass</label>
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
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Create Race</button>
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

<script>
(function () {
    [['cr-sr-toggle','cr-sr-panel'],['cr-minrating-toggle','cr-minrating-panel'],['cr-maxrating-toggle','cr-maxrating-panel']].forEach(([tid,pid]) => {
        const t = document.getElementById(tid), p = document.getElementById(pid);
        t?.addEventListener('change', () => { p.style.display = t.checked ? '' : 'none'; });
    });
})();
</script>

@endsection