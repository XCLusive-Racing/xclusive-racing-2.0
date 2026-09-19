@extends('layouts.admin')

@section('title', 'Edit Result')
@section('page-title', 'Edit Result')

@section('content')

@php
    $isPro = in_array($result->subject, ['dirk-schouten', 'mats-van-rooijen'], true);
    $firstRace = $result->races->first();
    $existingPositions = $isPro ? [] : ($firstRace?->positions->pluck('position', 'esports_driver_id')->all() ?? []);
@endphp

<div class="row g-4 justify-content-center">
    <div class="col-lg-6">
        <div class="admin-form-card p-4">

            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="{{ route('admin.results.index') }}"
                   class="btn btn-sm fw-bold text-uppercase"
                   style="font-size:.68rem;padding:4px 10px;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe">
                    ← Back
                </a>
                <h2 class="fw-black text-uppercase fst-italic text-dark mb-0" style="font-size:1rem">
                    Edit Result
                </h2>
            </div>

            <form data-result-form action="{{ route('admin.results.update', $result) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Subject --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Driver / Team <span class="text-danger">*</span></label>
                    <select name="subject" data-driver-picker-select class="form-select @error('subject') is-invalid @enderror" style="font-size:.9rem" required>
                        <option value="">— Select —</option>
                        <optgroup label="Professional Drivers">
                            @foreach(['dirk-schouten' => 'Dirk Schouten', 'mats-van-rooijen' => 'Mats van Rooijen'] as $val => $label)
                            <option value="{{ $val }}" {{ old('subject', $result->subject) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Esports Teams">
                            @foreach(['acc-team' => 'ACC Team', 'lmu-team' => 'LMU Team', 'iracing-team' => 'iRacing Team'] as $val => $label)
                            <option value="{{ $val }}" {{ old('subject', $result->subject) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Pro fields --}}
                <div data-result-fields="pro" style="display:none">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">Year <span class="text-danger">*</span></label>
                        <input type="number" name="year" value="{{ old('year', $result->year) }}" min="2000" max="2100"
                               class="form-control @error('year') is-invalid @enderror">
                        @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">Championship / Series <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $result->title) }}"
                               class="form-control @error('title') is-invalid @enderror">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">
                            Season Standing
                            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
                        </label>
                        <input type="text" name="standing" value="{{ old('standing', $result->standing) }}"
                               class="form-control @error('standing') is-invalid @enderror">
                        @error('standing') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold" style="font-size:.82rem">Races <span class="text-danger">*</span></label>
                        <div data-race-repeater>
                            @forelse($isPro ? $result->races : [] as $i => $race)
                            <div data-race-row class="d-flex gap-2 align-items-start mb-2">
                                <input type="text" name="races[{{ $i }}][track]" value="{{ $race->track }}" placeholder="Track" class="form-control" style="flex:1.4">
                                <input type="text" name="races[{{ $i }}][class]" value="{{ $race->car_class }}" placeholder="Class (optional)" class="form-control" style="flex:1">
                                <input type="text" name="races[{{ $i }}][positions]" value="{{ $race->positions->pluck('position')->implode(', ') }}" placeholder="Positions, e.g. P3, P3" class="form-control" style="flex:1">
                                <button type="button" data-race-remove class="btn btn-sm" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca">&times;</button>
                            </div>
                            @empty
                            <div data-race-row class="d-flex gap-2 align-items-start mb-2">
                                <input type="text" name="races[0][track]" placeholder="Track" class="form-control" style="flex:1.4">
                                <input type="text" name="races[0][class]" placeholder="Class (optional)" class="form-control" style="flex:1">
                                <input type="text" name="races[0][positions]" placeholder="Positions, e.g. P3, P3" class="form-control" style="flex:1">
                                <button type="button" data-race-remove class="btn btn-sm" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca">&times;</button>
                            </div>
                            @endforelse
                        </div>
                        <button type="button" data-race-add class="btn btn-sm fw-bold text-uppercase mt-1"
                                style="font-size:.7rem;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe">
                            + Add Race
                        </button>
                        <template data-race-template>
                            <div data-race-row class="d-flex gap-2 align-items-start mb-2">
                                <input type="text" name="races[__INDEX__][track]" placeholder="Track" class="form-control" style="flex:1.4">
                                <input type="text" name="races[__INDEX__][class]" placeholder="Class (optional)" class="form-control" style="flex:1">
                                <input type="text" name="races[__INDEX__][positions]" placeholder="Positions, e.g. P3, P3" class="form-control" style="flex:1">
                                <button type="button" data-race-remove class="btn btn-sm" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca">&times;</button>
                            </div>
                        </template>
                        @error('races') <div class="text-danger" style="font-size:.78rem">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Esports fields --}}
                <div data-result-fields="esports" style="display:none">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">Event Date <span class="text-danger">*</span></label>
                        <input type="date" name="event_date" value="{{ old('event_date', $firstRace?->race_date?->format('Y-m-d')) }}"
                               class="form-control @error('event_date') is-invalid @enderror">
                        @error('event_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">
                            Event / Series Name
                            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
                        </label>
                        <input type="text" name="title" value="{{ old('title', $result->title) }}"
                               class="form-control @error('title') is-invalid @enderror">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">Track <span class="text-danger">*</span></label>
                        <input type="text" name="track" value="{{ old('track', $firstRace?->track) }}"
                               class="form-control @error('track') is-invalid @enderror">
                        @error('track') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">
                            Car Class
                            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
                        </label>
                        <input type="text" name="car_class" value="{{ old('car_class', $firstRace?->car_class) }}"
                               class="form-control @error('car_class') is-invalid @enderror">
                        @error('car_class') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @include('admin.results._driver-positions', ['esportsDriversByGame' => $esportsDriversByGame, 'existingPositions' => $existingPositions])

                    <div class="mb-4">
                        <label class="form-label fw-bold" style="font-size:.82rem">
                            Notes
                            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
                        </label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $result->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit"
                            class="btn fw-black text-uppercase text-white px-4"
                            style="background:#7c3aed">
                        Save Changes
                    </button>
                    <a href="{{ route('admin.results.index') }}"
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
