@extends('layouts.admin')

@php $isEdit = $format->exists; @endphp

@section('title', ($isEdit ? 'Edit' : 'New') . ' Race Format — ' . $league->name)
@section('page-title', $league->name . ' — ' . ($isEdit ? 'Edit ' . $format->name : 'New Race Format'))

@section('page-actions')
    <a href="{{ route('admin.leagues.session-formats.index', $league) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back to Race Formats
    </a>
@endsection

@section('content')

<form action="{{ $isEdit ? route('admin.leagues.session-formats.update', [$league, $format]) : route('admin.leagues.session-formats.store', $league) }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="admin-card mb-4">
        <div class="p-4">
            <div class="row g-3 mb-3">
                <div class="col-sm-5">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $format->name) }}" maxlength="100" required placeholder="e.g. Sprint Double Header"
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-7">
                    <label class="form-label">Description <span class="fw-normal text-secondary">(optional)</span></label>
                    <input type="text" name="description" value="{{ old('description', $format->description) }}" maxlength="255"
                           class="form-control @error('description') is-invalid @enderror">
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <p class="fw-black text-uppercase fst-italic mb-2 mt-4" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Sessions</p>
            <div class="row g-3 mb-3">
                <div class="col-6 col-sm-3">
                    <label class="form-label">Practice <span class="fw-normal text-secondary">(min)</span></label>
                    <input type="number" name="practice_duration" value="{{ old('practice_duration', $format->practice_duration) }}" min="1" max="999" placeholder="None"
                           class="form-control @error('practice_duration') is-invalid @enderror">
                    @error('practice_duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label">Qualifying <span class="fw-normal text-secondary">(min)</span></label>
                    <input type="number" name="qualifying_duration" value="{{ old('qualifying_duration', $format->qualifying_duration) }}" min="1" max="999" placeholder="None"
                           class="form-control @error('qualifying_duration') is-invalid @enderror">
                    @error('qualifying_duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Races <span class="fw-normal text-secondary">(min each)</span></label>
                    <input type="text" name="race_lengths" value="{{ old('race_lengths', $format->raceLengthsText()) }}" required placeholder="e.g. 25 or 25, 25"
                           class="form-control @error('race_lengths') is-invalid @enderror">
                    <div class="form-text" style="font-size:.72rem;color:#9ca3af">One length per race, comma-separated — "25, 25" is two 25-minute races in the same round (max 4).</div>
                    @error('race_lengths')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-text mb-3" style="font-size:.72rem;color:#9ca3af">Leave Practice or Qualifying blank to skip that session.</div>

            <p class="fw-black text-uppercase fst-italic mb-2 mt-4" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Pitstops &amp; Tyres</p>
            <div class="row g-3 align-items-end">
                <div class="col-6 col-sm-3">
                    <label class="form-label">Mandatory Pitstops</label>
                    <input type="number" name="pitstop_count" value="{{ old('pitstop_count', $format->pitstop_count) }}" min="0" max="9"
                           class="form-control @error('pitstop_count') is-invalid @enderror">
                    @error('pitstop_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label">Tyre Sets</label>
                    <input type="number" name="tyre_set_count" value="{{ old('tyre_set_count', $format->tyre_set_count) }}" min="1" max="50" placeholder="Unlimited"
                           class="form-control @error('tyre_set_count') is-invalid @enderror">
                    @error('tyre_set_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6 pb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="fixed_stop_time" id="sf-fixed-stop" value="1"
                               {{ old('fixed_stop_time', $format->fixed_stop_time) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="sf-fixed-stop">Fixed Stop Time</label>
                    </div>
                    <div class="form-text mt-0" style="font-size:.72rem;color:#9ca3af">Off = game default (dynamic). On = a fixed 25 seconds.</div>
                </div>
            </div>

            <p class="fw-black text-uppercase fst-italic mb-2 mt-4" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Driver Swaps</p>
            <div class="row g-3">
                <div class="col-6 col-sm-3">
                    <label class="form-label">Max. Stint Time <span class="fw-normal text-secondary">(min)</span></label>
                    <input type="number" name="driver_stint_time_mins" value="{{ old('driver_stint_time_mins', $format->driver_stint_time_mins) }}" min="1" max="1440" placeholder="No limit"
                           class="form-control @error('driver_stint_time_mins') is-invalid @enderror">
                    @error('driver_stint_time_mins')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label">Max Driving Time / Driver <span class="fw-normal text-secondary">(min)</span></label>
                    <input type="number" name="max_total_driving_time_mins" value="{{ old('max_total_driving_time_mins', $format->max_total_driving_time_mins) }}" min="1" max="1440" placeholder="No limit"
                           class="form-control @error('max_total_driving_time_mins') is-invalid @enderror">
                    @error('max_total_driving_time_mins')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-text mb-0" style="font-size:.72rem;color:#9ca3af">Only used by championships with driver swaps enabled.</div>
        </div>
    </div>

    <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
        {{ $isEdit ? 'Save Format' : 'Create Format' }}
    </button>
</form>

@endsection
