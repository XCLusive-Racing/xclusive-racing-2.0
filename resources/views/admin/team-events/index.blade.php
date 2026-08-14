@extends('layouts.admin')

@section('title', 'Team Events')
@section('page-title', 'Team Events')

@section('content')

<div class="row g-4">

    {{-- ── Create form ──────────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="admin-form-card p-4">
            <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1rem">+ Create Team Event</h2>

            <form action="{{ route('admin.team-events.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Subject --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Driver / Team <span class="text-danger">*</span></label>
                    <select name="subject" data-driver-picker-select class="form-select @error('subject') is-invalid @enderror" style="font-size:.9rem" required>
                        <option value="">— Select —</option>
                        <optgroup label="Professional Drivers">
                            @foreach(['dirk-schouten' => 'Dirk Schouten', 'mats-van-rooijen' => 'Mats van Rooijen', 'jesse-aalbregt' => 'Jesse Aalbregt'] as $val => $label)
                            <option value="{{ $val }}" {{ old('subject') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Esports Teams">
                            @foreach(['acc-team' => 'ACC Team', 'lmu-team' => 'LMU Team', 'iracing-team' => 'iRacing Team'] as $val => $label)
                            <option value="{{ $val }}" {{ old('subject') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @include('admin.team-events._driver-picker', ['esportsDriversByGame' => $esportsDriversByGame, 'selectedDriverIds' => old('participating_drivers') ? json_decode(old('participating_drivers'), true) : []])

                {{-- Title --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Main Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="e.g. Lausitzring: Race 1" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Subtitle --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">
                        Championship / Series
                        <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
                    </label>
                    <input type="text" name="subtitle" value="{{ old('subtitle') }}"
                           class="form-control @error('subtitle') is-invalid @enderror"
                           placeholder="e.g. Porsche Sixt Carrera Cup Deutschland">
                    @error('subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Date / Time --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Race Start <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                           class="form-control @error('starts_at') is-invalid @enderror" required>
                    <div class="form-text">Enter in your local time zone.</div>
                    @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Event image --}}
                <div class="mb-2">
                    <label class="form-label fw-bold" style="font-size:.82rem">
                        Event Image
                        <span class="text-secondary fw-normal" style="text-transform:none">(optional · JPG/PNG/WebP)</span>
                    </label>
                    <input type="file" name="image" accept="image/*"
                           class="form-control @error('image') is-invalid @enderror">
                    <div class="form-text">Upload a file. Max 10 MB.</div>
                    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-normal text-secondary" style="font-size:.78rem">Or paste a URL from the Media Library</label>
                    <input type="url" name="image_url" value="{{ old('image_url') }}"
                           class="form-control @error('image_url') is-invalid @enderror"
                           placeholder="https://…">
                    @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Watch link --}}
                <div class="mb-4">
                    <label class="form-label fw-bold" style="font-size:.82rem">
                        Watch Link
                        <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
                    </label>
                    <input type="url" name="watch_url" value="{{ old('watch_url') }}"
                           class="form-control @error('watch_url') is-invalid @enderror"
                           placeholder="https://www.youtube.com/...">
                    @error('watch_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit"
                        class="btn fw-black text-uppercase text-white px-4"
                        style="background:#7c3aed">
                    Create Event
                </button>
            </form>
        </div>
    </div>

    {{-- ── Event list ────────────────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="admin-form-card p-0" data-tabs data-default-tab="upcoming">

            {{-- Tab nav --}}
            <div class="d-flex border-bottom px-2" style="background:#f9fafb">
                <button data-tab-btn="upcoming"
                        class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                        style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent">
                    Upcoming
                    @if($upcomingEvents->isNotEmpty())
                    <span class="badge ms-1" style="background:#7c3aed;color:white;font-size:.65rem;padding:2px 7px;border-radius:10px">
                        {{ $upcomingEvents->count() }}
                    </span>
                    @endif
                </button>
                <button data-tab-btn="past"
                        class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                        style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent">
                    Past Events
                    @if($pastEvents->isNotEmpty())
                    <span class="badge ms-1" style="background:#9ca3af;color:white;font-size:.65rem;padding:2px 7px;border-radius:10px">
                        {{ $pastEvents->count() }}
                    </span>
                    @endif
                </button>
            </div>

            {{-- UPCOMING TAB --}}
            <div data-tab-panel="upcoming" class="p-4" style="display:none">
                @include('admin.team-events._event-list', [
                    'events'       => $upcomingEvents,
                    'subjects'     => $subjects,
                    'emptyMessage' => 'No upcoming team events. Create one on the left.',
                ])
            </div>

            {{-- PAST TAB --}}
            <div data-tab-panel="past" class="p-4" style="display:none">
                @include('admin.team-events._event-list', [
                    'events'       => $pastEvents,
                    'subjects'     => $subjects,
                    'emptyMessage' => 'No past team events yet.',
                ])
            </div>

        </div>
    </div>

</div>

@endsection
