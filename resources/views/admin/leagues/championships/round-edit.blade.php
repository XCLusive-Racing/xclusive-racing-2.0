@extends('layouts.admin')

@php
    // ACC's real track roster — reused from admin.races.form so a typo here can
    // never produce a track name gPortal's event.json doesn't recognise.
    $accTracks = [
        'Barcelona', 'Brands Hatch', 'COTA', 'Donington', 'Hungaroring', 'Imola',
        'Indianapolis', 'Kyalami', 'Laguna Seca', 'Misano', 'Monza', 'Mount Panorama',
        'Nürburgring', 'Nordschleife', 'Oulton Park', 'Paul Ricard', 'Red Bull Ring',
        'Silverstone', 'Snetterton', 'Spa', 'Suzuka', 'Valencia', 'Watkins Glen',
        'Zandvoort', 'Zolder',
    ];

    // Resolved starting values for _round-shared-fields -- an existing round
    // already has its own saved values for every field, unlike a brand new one
    // (round-create.blade.php builds the same shape from the championship's
    // Sessions-step defaults instead).
    $defaults = [
        'ftp_server_id'               => $race->ftp_server_id,
        'description'                 => $race->description,
        'practice_duration'           => $race->practice_duration,
        'qualifying_duration'         => $race->qualifying_duration,
        'race_duration'               => $race->race_duration,
        'time_of_day'                 => $race->time_of_day,
        'ambient_temp'                => $race->ambient_temp,
        'weather'                     => $race->weather,
        'weather_randomness'          => $race->weather_randomness,
        'rain_level'                  => $race->rain_level ?? 0.0,
        'xcl_r_multiplier'            => $race->xcl_r_multiplier,
        'pitstop_count'               => $race->pitstop_count,
        'fixed_stop_time'             => $race->min_stop_secs !== null,
        'driver_stint_time_mins'      => $race->driver_stint_time_mins,
        'max_total_driving_time_mins' => $race->max_total_driving_time_mins,
        'mandatory_driver_swap'       => $race->mandatory_driver_swap,
    ];
@endphp

@section('title', 'Edit Round — ' . $championship->name)
@section('page-title', $league->name . ' — Edit Round ' . $race->round_number)

@section('page-actions')
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back to Rounds
    </a>
@endsection

@section('content')

<form action="{{ route('admin.leagues.championships.rounds.update', [$league, $championship, $race]) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="admin-card mb-4">
        <div class="px-4 pt-4 pb-2">
            <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">
                Editing {{ $race->title }}
            </p>
        </div>

        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <div class="row g-3">
                <div class="col-sm-3">
                    <label class="form-label">Round #</label>
                    <input type="number" name="round_number" value="{{ old('round_number', $race->round_number) }}"
                           class="form-control @error('round_number') is-invalid @enderror" min="1">
                    @error('round_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-5">
                    <label class="form-label">Track <span class="text-danger">*</span></label>
                    @if($championship->game === 'acc')
                    <select name="track" class="form-select @error('track') is-invalid @enderror" required>
                        <option value="">Select track…</option>
                        @foreach($accTracks as $trackName)
                        <option value="{{ $trackName }}" {{ old('track', $race->track) === $trackName ? 'selected' : '' }}>{{ $trackName }}</option>
                        @endforeach
                    </select>
                    @else
                    <input type="text" name="track" value="{{ old('track', $race->track) }}"
                           class="form-control @error('track') is-invalid @enderror" placeholder="e.g. Le Mans" required>
                    @endif
                    @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Date &amp; Time <span class="text-danger">*</span> <span class="fw-normal text-secondary" style="text-transform:none">(on the hour or half hour, BST)</span></label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', $race->scheduledAtUk()->format('Y-m-d\TH:i')) }}" step="1800"
                           class="form-control @error('scheduled_at') is-invalid @enderror">
                    @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        @include('admin.leagues.championships._round-shared-fields', ['idPrefix' => 're', 'defaults' => $defaults, 'openByDefault' => true])
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Save Changes</button>
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
    </div>
</form>

@endsection
