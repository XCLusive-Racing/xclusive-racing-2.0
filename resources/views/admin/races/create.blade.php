@extends('layouts.admin')

@section('title', 'Create Race')
@section('page-title', 'Create Race')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<form action="{{ route('admin.races.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4 align-items-start">

        {{-- Left: form fields --}}
        <div class="col-12 col-lg-8">

            {{-- Race Info --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-2">
                    <p class="fw-black text-uppercase fst-italic text-dark mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Race Info</p>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="e.g. Round 1 — Monza Sprint">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Game</label>
                            <select name="game" class="form-select @error('game') is-invalid @enderror">
                                <option value="">Select game...</option>
                                <option value="acc"     {{ old('game') === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                                <option value="lmu"     {{ old('game') === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                                <option value="iracing" {{ old('game') === 'iracing' ? 'selected' : '' }}>iRacing</option>
                                <option value="ac"      {{ old('game') === 'ac'      ? 'selected' : '' }}>AC Rally</option>
                            </select>
                            @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Track</label>
                            <input type="text" name="track" value="{{ old('track') }}"
                                   class="form-control @error('track') is-invalid @enderror"
                                   placeholder="e.g. Monza">
                            @error('track') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6;margin-top:.5rem">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule</p>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Date & Time (BST)</label>
                            <input type="datetime-local" name="scheduled_at"
                                   value="{{ old('scheduled_at', $prefillDate ? $prefillDate . 'T20:00' : '') }}"
                                   class="form-control @error('scheduled_at') is-invalid @enderror">
                            @error('scheduled_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Max Drivers <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                            <input type="number" name="max_drivers" value="{{ old('max_drivers') }}"
                                   class="form-control @error('max_drivers') is-invalid @enderror"
                                   min="1" placeholder="No limit">
                            @error('max_drivers') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Settings</p>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Rating Multiplier</label>
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
                        <div class="col-sm-6">
                            {{-- Event Tag --}}
                            @php
                                $tagsConfig = json_encode([
                                    'tags'        => $tags->map(fn($t) => ['slug' => $t->slug, 'name' => $t->name, 'color' => $t->color]),
                                    'storeUrl'    => route('admin.event-tags.store'),
                                    'csrfToken'   => csrf_token(),
                                    'selectedTag' => old('event_tag', ''),
                                ]);
                            @endphp
                            <div data-tags-wrap data-config='{{ $tagsConfig }}'>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label mb-0">Event Tag</label>
                                    <button type="button" data-tags-toggle
                                            class="btn btn-sm fw-bold text-uppercase"
                                            style="font-size:.68rem;padding:2px 8px;background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.3);border-radius:6px">
                                        + New
                                    </button>
                                </div>
                                <select name="event_tag" class="form-select @error('event_tag') is-invalid @enderror" data-tags-select>
                                    <option value="">Select tag...</option>
                                </select>
                                @error('event_tag') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                <div data-tags-add-panel style="display:none">
                                    <div class="mt-2 p-3 rounded-2" style="background:#f8f5ff;border:1px solid rgba(124,58,237,.2)">
                                        <div data-tags-error class="alert alert-danger py-1 px-2 mb-2" style="font-size:.8rem;display:none"></div>
                                        <div class="d-flex gap-2 align-items-end">
                                            <div class="flex-grow-1">
                                                <label class="form-label" style="font-size:.78rem">Name</label>
                                                <input type="text" data-tags-name placeholder="e.g. Endurance"
                                                       class="form-control form-control-sm">
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
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Event Format <span class="fw-normal" style="text-transform:none">(optional)</span></p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <label class="form-label">Practice <span class="fw-normal text-secondary">(min)</span></label>
                            <input type="number" name="practice_duration" value="{{ old('practice_duration') }}"
                                   class="form-control" min="1" max="999" placeholder="e.g. 15">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Qualifying <span class="fw-normal text-secondary">(min)</span></label>
                            <input type="number" name="qualifying_duration" value="{{ old('qualifying_duration') }}"
                                   class="form-control" min="1" max="999" placeholder="e.g. 10">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Race <span class="fw-normal text-secondary">(min)</span></label>
                            <input type="number" name="race_duration" value="{{ old('race_duration') }}"
                                   class="form-control" min="1" max="999" placeholder="e.g. 20">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Car Class</label>
                            <input type="text" name="car_class" value="{{ old('car_class') }}"
                                   class="form-control" placeholder="e.g. GT3, GT4, LMP2">
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
                        <div class="col-sm-4">
                            <label class="form-label">Time of Day</label>
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

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Requirements <span class="fw-normal" style="text-transform:none">(optional)</span></p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Safety Rating (SR)</label>
                            <select name="sr_requirement" class="form-select">
                                <option value="">— No requirement —</option>
                                <option value="none"  {{ old('sr_requirement') === 'none'  ? 'selected' : '' }}>None (grade D allowed)</option>
                                <option value="5"     {{ old('sr_requirement') === '5'     ? 'selected' : '' }}>SR ≥ 5.0 (grade B+)</option>
                                <option value="7"     {{ old('sr_requirement') === '7'     ? 'selected' : '' }}>SR ≥ 7.0 (grade X+)</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Minimum Rating Class</label>
                            <select name="min_rating" class="form-select">
                                <option value="">— No requirement —</option>
                                <option value="all"      {{ old('min_rating') === 'all'      ? 'selected' : '' }}>All classes</option>
                                <option value="rookie"   {{ old('min_rating') === 'rookie'   ? 'selected' : '' }}>Rookie+</option>
                                <option value="bronze"   {{ old('min_rating') === 'bronze'   ? 'selected' : '' }}>Bronze+</option>
                                <option value="silver"   {{ old('min_rating') === 'silver'   ? 'selected' : '' }}>Silver+</option>
                                <option value="gold"     {{ old('min_rating') === 'gold'     ? 'selected' : '' }}>Gold+</option>
                                <option value="platinum" {{ old('min_rating') === 'platinum' ? 'selected' : '' }}>Platinum+</option>
                                <option value="alien"    {{ old('min_rating') === 'alien'    ? 'selected' : '' }}>Alien only</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Description <span class="fw-normal" style="text-transform:none">(optional)</span></p>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Additional race info...">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

            {{-- Actions --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Create Race
                </button>
                <a href="{{ route('admin.races.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
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

@endsection

