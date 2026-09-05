@extends('layouts.admin')

@section('title', 'Broadcasts')
@section('page-title', 'Broadcasts')

@section('content')

<div class="team-event-page-grid">

    {{-- ── Create form ──────────────────────────────────────────────────── --}}
    <div>
        <div class="admin-form-card p-4">
            <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1rem">+ Schedule Broadcast</h2>

            <form action="{{ route('admin.broadcasts.store') }}" method="POST">
                @csrf

                {{-- Title --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">What are you broadcasting? <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="e.g. XCLusive GT3 Championship — Round 4" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Subtitle --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">
                        Details
                        <span class="text-secondary fw-normal" style="text-transform:none">(optional — track, game, etc.)</span>
                    </label>
                    <input type="text" name="subtitle" value="{{ old('subtitle') }}"
                           class="form-control @error('subtitle') is-invalid @enderror"
                           placeholder="e.g. Nürburgring GP · ACC">
                    @error('subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Series badge --}}
                <div class="row g-3 mb-3">
                    <div class="col-7">
                        <label class="form-label fw-bold" style="font-size:.82rem">
                            Series Badge
                            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
                        </label>
                        <input type="text" name="series" value="{{ old('series') }}"
                               class="form-control @error('series') is-invalid @enderror"
                               placeholder="e.g. GT3, ENDURANCE">
                        @error('series') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-5">
                        <label class="form-label fw-bold" style="font-size:.82rem">Badge Colour</label>
                        <input type="color" name="color" value="{{ old('color', '#cc0000') }}"
                               class="form-control form-control-color @error('color') is-invalid @enderror" style="width:100%">
                        @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Date / Time --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Date &amp; Time (BST — British Summer Time, UTC+1) <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                           class="form-control @error('starts_at') is-invalid @enderror" required>
                    @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Duration --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Length <span class="text-danger">*</span></label>
                    <select name="duration_minutes" class="form-select @error('duration_minutes') is-invalid @enderror" required>
                        @foreach(\App\Models\Broadcast::durationOptions() as $minutes => $label)
                        <option value="{{ $minutes }}" {{ (int) old('duration_minutes', 60) === $minutes ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">How long the broadcast stays listed as live/upcoming.</div>
                    @error('duration_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Watch link --}}
                <div class="mb-4">
                    <label class="form-label fw-bold" style="font-size:.82rem">Broadcast Link <span class="text-danger">*</span></label>
                    <input type="url" name="watch_url" value="{{ old('watch_url') }}"
                           class="form-control @error('watch_url') is-invalid @enderror"
                           placeholder="https://www.twitch.tv/trueracingrevival" required>
                    @error('watch_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit"
                        class="btn fw-black text-uppercase text-white px-4"
                        style="background:#7c3aed">
                    Schedule Broadcast
                </button>
            </form>
        </div>
    </div>

    {{-- ── Broadcast list ────────────────────────────────────────────────── --}}
    <div>
        <div class="admin-form-card p-0" data-tabs data-default-tab="upcoming">

            {{-- Tab nav --}}
            <div class="d-flex border-bottom px-2" style="background:#f9fafb">
                <button data-tab-btn="upcoming"
                        class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                        style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent">
                    Upcoming
                    @if($upcomingBroadcasts->isNotEmpty())
                    <span class="badge ms-1" style="background:#7c3aed;color:white;font-size:.65rem;padding:2px 7px;border-radius:10px">
                        {{ $upcomingBroadcasts->count() }}
                    </span>
                    @endif
                </button>
                <button data-tab-btn="past"
                        class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                        style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent">
                    Past Broadcasts
                    @if($pastBroadcasts->isNotEmpty())
                    <span class="badge ms-1" style="background:#9ca3af;color:white;font-size:.65rem;padding:2px 7px;border-radius:10px">
                        {{ $pastBroadcasts->count() }}
                    </span>
                    @endif
                </button>
            </div>

            {{-- UPCOMING TAB --}}
            <div data-tab-panel="upcoming" class="p-4" style="display:none">
                @include('admin.broadcasts._list', [
                    'broadcasts'   => $upcomingBroadcasts,
                    'emptyMessage' => 'No upcoming broadcasts. Schedule one on the left.',
                ])
            </div>

            {{-- PAST TAB --}}
            <div data-tab-panel="past" class="p-4" style="display:none">
                @include('admin.broadcasts._list', [
                    'broadcasts'   => $pastBroadcasts,
                    'emptyMessage' => 'No past broadcasts yet.',
                ])
            </div>

        </div>
    </div>

</div>

@endsection
