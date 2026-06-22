@extends('layouts.admin')

@section('title', 'Bulk Create Races')
@section('page-title', 'Bulk Create Races')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<div data-bulk-wrap>

<form action="{{ route('admin.races.bulk-store') }}" method="POST">
@csrf

<div class="row g-4 align-items-start">

    {{-- Left: generator + events --}}
    <div class="col-12 col-lg-8">

        {{-- Schedule Generator --}}
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-2">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule Generator</p>

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
                        <input type="number" data-bulk-custom-interval value="7" min="1" class="form-control" placeholder="e.g. 10">
                    </div>
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

            {{-- Submit --}}
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
                    $tagsConfig = json_encode([
                        'tags'        => $tags->map(fn($t) => ['slug' => $t->slug, 'name' => $t->name, 'color' => $t->color]),
                        'storeUrl'    => route('admin.event-tags.store'),
                        'csrfToken'   => csrf_token(),
                        'selectedTag' => old('event_tag', ''),
                    ]);
                @endphp
                <div data-tags-wrap data-config='{{ $tagsConfig }}'>
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

@endsection
