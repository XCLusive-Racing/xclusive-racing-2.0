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
                    <select name="subject" class="form-select @error('subject') is-invalid @enderror" style="font-size:.9rem" required>
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
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">
                        Event Image
                        <span class="text-secondary fw-normal" style="text-transform:none">(optional · JPG/PNG/WebP)</span>
                    </label>
                    <input type="file" name="image" accept="image/*"
                           class="form-control @error('image') is-invalid @enderror">
                    <div class="form-text">Landscape banner recommended. Max 10 MB.</div>
                    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
        <div class="admin-form-card p-4">
            <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1rem">All Team Events</h2>

            @if($events->isEmpty())
                <p class="text-secondary" style="font-size:.88rem">No team events yet. Create one on the left.</p>
            @else
                <div style="display:flex;flex-direction:column;gap:.75rem">
                    @foreach($events as $ev)
                    <div class="d-flex align-items-start gap-3 p-3 rounded-2"
                         style="background:#f9fafb;border:1px solid #e5e7eb">

                        {{-- Thumbnail --}}
                        @if($ev->image_url)
                        <div class="flex-shrink-0" style="width:64px;height:48px;border-radius:6px;overflow:hidden;background:#111">
                            <img src="{{ $ev->image_url }}" alt="{{ $ev->title }}"
                                 style="width:100%;height:100%;object-fit:cover">
                        </div>
                        @endif

                        {{-- Date block --}}
                        <div class="text-center flex-shrink-0"
                             style="width:48px;background:#7c3aed;border-radius:8px;padding:6px 4px;color:white">
                            <div style="font-size:.6rem;font-weight:800;letter-spacing:.08em;opacity:.8">
                                {{ strtoupper($ev->starts_at->format('M')) }}
                            </div>
                            <div style="font-size:1.2rem;font-weight:900;line-height:1">
                                {{ $ev->starts_at->format('d') }}
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="fw-black text-dark text-truncate" style="font-size:.88rem">
                                {{ $ev->title }}
                            </div>
                            @if($ev->subtitle)
                            <div class="text-secondary text-truncate" style="font-size:.75rem">
                                {{ $ev->subtitle }}
                            </div>
                            @endif
                            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                <span class="badge fw-bold text-uppercase"
                                      style="background:#ede9fe;color:#6d28d9;font-size:.6rem">
                                    {{ $subjects[$ev->subject] ?? $ev->subject }}
                                </span>
                                <span style="font-size:.72rem;color:#6b7280">
                                    {{ $ev->starts_at->format('d M Y · H:i') }}
                                </span>
                                @if($ev->watch_url)
                                <a href="{{ $ev->watch_url }}" target="_blank"
                                   style="font-size:.7rem;color:#7c3aed;font-weight:700;text-decoration:none">
                                    ▶ Watch
                                </a>
                                @endif
                                @if($ev->starts_at->isPast())
                                <span style="font-size:.65rem;color:#9ca3af;font-weight:700">PAST</span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex flex-column gap-1">
                            <a href="{{ route('admin.team-events.edit', $ev) }}"
                               class="btn btn-sm fw-bold text-uppercase"
                               style="font-size:.68rem;padding:4px 10px;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe;white-space:nowrap">
                                Edit
                            </a>
                            <form action="{{ route('admin.team-events.destroy', $ev) }}" method="POST"
                                  onsubmit="return confirm('Delete this event?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm fw-bold text-uppercase w-100"
                                        style="font-size:.68rem;padding:4px 10px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;white-space:nowrap">
                                    Delete
                                </button>
                            </form>
                        </div>

                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

@endsection
