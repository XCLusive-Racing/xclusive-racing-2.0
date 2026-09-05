@extends('layouts.admin')

@section('title', 'Edit Broadcast')
@section('page-title', 'Edit Broadcast')

@section('content')

<div class="row g-4 justify-content-center">
    <div class="col-lg-6">
        <div class="admin-form-card p-4">

            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="{{ route('admin.broadcasts.index') }}"
                   class="btn btn-sm fw-bold text-uppercase"
                   style="font-size:.68rem;padding:4px 10px;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe">
                    ← Back
                </a>
                <h2 class="fw-black text-uppercase fst-italic text-dark mb-0" style="font-size:1rem">
                    Edit Broadcast
                </h2>
            </div>

            <form action="{{ route('admin.broadcasts.update', $broadcast) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Title --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">What are you broadcasting? <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $broadcast->title) }}"
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
                    <input type="text" name="subtitle" value="{{ old('subtitle', $broadcast->subtitle) }}"
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
                        <input type="text" name="series" value="{{ old('series', $broadcast->series) }}"
                               class="form-control @error('series') is-invalid @enderror"
                               placeholder="e.g. GT3, ENDURANCE">
                        @error('series') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-5">
                        <label class="form-label fw-bold" style="font-size:.82rem">Badge Colour</label>
                        <input type="color" name="color" value="{{ old('color', $broadcast->color) }}"
                               class="form-control form-control-color @error('color') is-invalid @enderror" style="width:100%">
                        @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Date / Time --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Date &amp; Time (BST — British Summer Time, UTC+1) <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="starts_at"
                           value="{{ old('starts_at', $broadcast->starts_at->timezone('Europe/London')->format('Y-m-d\TH:i')) }}"
                           class="form-control @error('starts_at') is-invalid @enderror" required>
                    @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Duration --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Length <span class="text-danger">*</span></label>
                    <select name="duration_minutes" class="form-select @error('duration_minutes') is-invalid @enderror" required>
                        @foreach(\App\Models\Broadcast::durationOptions() as $minutes => $label)
                        <option value="{{ $minutes }}" {{ (int) old('duration_minutes', $broadcast->duration_minutes) === $minutes ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">How long the broadcast stays listed as live/upcoming.</div>
                    @error('duration_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Watch link --}}
                <div class="mb-4">
                    <label class="form-label fw-bold" style="font-size:.82rem">Broadcast Link <span class="text-danger">*</span></label>
                    <input type="url" name="watch_url" value="{{ old('watch_url', $broadcast->watch_url) }}"
                           class="form-control @error('watch_url') is-invalid @enderror"
                           placeholder="https://www.twitch.tv/trueracingrevival" required>
                    @error('watch_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit"
                            class="btn fw-black text-uppercase text-white px-4"
                            style="background:#7c3aed">
                        Save Changes
                    </button>
                    <a href="{{ route('admin.broadcasts.index') }}"
                       class="btn fw-bold text-uppercase px-4"
                       style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection
