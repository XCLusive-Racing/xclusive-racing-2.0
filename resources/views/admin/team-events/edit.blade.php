@extends('layouts.admin')

@section('title', 'Edit Team Event')
@section('page-title', 'Edit Team Event')

@section('content')

<div class="row g-4 justify-content-center">
    <div class="col-lg-6">
        <div class="admin-form-card p-4">

            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="{{ route('admin.team-events.index') }}"
                   class="btn btn-sm fw-bold text-uppercase"
                   style="font-size:.68rem;padding:4px 10px;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe">
                    ← Back
                </a>
                <h2 class="fw-black text-uppercase fst-italic text-dark mb-0" style="font-size:1rem">
                    Edit Team Event
                </h2>
            </div>

            <form action="{{ route('admin.team-events.update', $event) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Subject --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Driver / Team <span class="text-danger">*</span></label>
                    <select name="subject" class="form-select @error('subject') is-invalid @enderror" style="font-size:.9rem" required>
                        <option value="">— Select —</option>
                        <optgroup label="Professional Drivers">
                            @foreach(['dirk-schouten' => 'Dirk Schouten', 'mats-van-rooijen' => 'Mats van Rooijen', 'jesse-aalbregt' => 'Jesse Aalbregt'] as $val => $label)
                            <option value="{{ $val }}" {{ (old('subject', $event->subject) === $val) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Esports Teams">
                            @foreach(['acc-team' => 'ACC Team', 'lmu-team' => 'LMU Team', 'iracing-team' => 'iRacing Team'] as $val => $label)
                            <option value="{{ $val }}" {{ (old('subject', $event->subject) === $val) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Title --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Main Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $event->title) }}"
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
                    <input type="text" name="subtitle" value="{{ old('subtitle', $event->subtitle) }}"
                           class="form-control @error('subtitle') is-invalid @enderror"
                           placeholder="e.g. Porsche Sixt Carrera Cup Deutschland">
                    @error('subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Date / Time --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Race Start <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="starts_at"
                           value="{{ old('starts_at', $event->starts_at->format('Y-m-d\TH:i')) }}"
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

                    @if($event->image_url)
                    <div class="mb-2 rounded-2 overflow-hidden" style="height:80px;width:160px;background:#111">
                        <img src="{{ $event->image_url }}" alt="Current image"
                             style="width:100%;height:100%;object-fit:cover">
                    </div>
                    <div class="form-text mb-2" style="font-size:.75rem">Current image shown above — upload a file or paste a URL to replace it.</div>
                    @endif

                    <input type="file" name="image" accept="image/*"
                           class="form-control @error('image') is-invalid @enderror">
                    <div class="form-text">Upload a file. Max 10 MB.</div>
                    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-normal text-secondary" style="font-size:.78rem">Or paste a URL from the Media Library</label>
                    <input type="url" name="image_url" value="{{ old('image_url', str_starts_with($event->image ?? '', 'http') ? $event->image : '') }}"
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
                    <input type="url" name="watch_url" value="{{ old('watch_url', $event->watch_url) }}"
                           class="form-control @error('watch_url') is-invalid @enderror"
                           placeholder="https://www.youtube.com/...">
                    @error('watch_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit"
                            class="btn fw-black text-uppercase text-white px-4"
                            style="background:#7c3aed">
                        Save Changes
                    </button>
                    <a href="{{ route('admin.team-events.index') }}"
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
