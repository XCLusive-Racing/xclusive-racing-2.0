@extends('layouts.admin')

@php
    $accTracks = \App\Models\Race::ACC_TRACKS;

    // Starting values for _round-shared-fields: the round's own saved values
    // (round-create.blade.php builds the same shape from the Sessions defaults).
    $defaults = [
        'ftp_server_id'               => $race->ftp_server_id,
        'round_type'                  => $race->round_type,
        'description'                 => $race->description,
        'practice_duration'           => $race->practice_duration,
        'qualifying_duration'         => $race->qualifying_duration,
        'race_lengths'                => implode(', ', $race->raceLengths()),
        'session_format_id'           => $race->session_format_id,
        'time_of_day'                 => $race->time_of_day,
        'ambient_temp'                => $race->ambient_temp,
        'weather'                     => $race->weather,
        'weather_randomness'          => $race->weather_randomness,
        'rain_level'                  => $race->rain_level ?? 0.0,
        'xcl_r_multiplier'            => $race->xcl_r_multiplier,
        'points_multiplier'           => $race->points_multiplier,
        'pitstop_count'               => $race->pitstop_count,
        'tyre_set_count'              => $race->tyre_set_count,
        'fixed_stop_time'             => $race->min_stop_secs !== null,
        'mandatory_refuelling'        => $race->mandatory_refuelling,
        'mandatory_tyre_change'       => $race->mandatory_tyre_change,
        'driver_stint_time_mins'      => $race->driver_stint_time_mins,
        'max_total_driving_time_mins' => $race->max_total_driving_time_mins,
        'mandatory_driver_swap'       => $race->mandatory_driver_swap,
    ];
@endphp

@section('title', 'Edit Round · ' . $championship->name)
@section('page-title', $league->name . ' · Edit Round ' . $race->round_number)

@section('page-actions')
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back to Rounds
    </a>
@endsection

@section('content')

<div class="row g-4">
<div class="col-12 col-xl-9">

<form action="{{ route('admin.leagues.championships.rounds.update', [$league, $championship, $race]) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="cw-card">
        <div class="cw-card__head">
            <h2 class="cw-card__title">Round {{ $race->round_number }}</h2>
            <span class="cw-help mt-0">{{ $race->title }}</span>
        </div>

        <div class="cw-section">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="cw-label">Round #</label>
                    <input type="number" name="round_number" value="{{ old('round_number', $race->round_number) }}"
                           class="form-control @error('round_number') is-invalid @enderror" min="1">
                    @error('round_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="cw-label">Track <span class="text-danger">*</span></label>
                    @if(in_array($championship->game, ['acc', 'ac'], true))
                    <select name="track" class="form-select @error('track') is-invalid @enderror" required data-track-randomize>
                        <option value="">Select track…</option>
                        <option value="{{ \App\Models\Race::RANDOM_TRACK }}">🎲 Randomize</option>
                        @foreach($accTracks as $trackName)
                        <option value="{{ $trackName }}" @selected(old('track', $race->track) === $trackName)>{{ $trackName }}</option>
                        @endforeach
                    </select>
                    @else
                    <input type="text" name="track" value="{{ old('track', $race->track) }}"
                           class="form-control @error('track') is-invalid @enderror" placeholder="Le Mans" required>
                    @endif
                    @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="cw-label">Date &amp; Time (UK) <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', $race->scheduledAtUk()->format('Y-m-d\TH:i')) }}" step="1800"
                           class="form-control @error('scheduled_at') is-invalid @enderror">
                    <div class="cw-help">On the hour or half hour.</div>
                    @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        @include('admin.leagues.championships._round-shared-fields', ['idPrefix' => 're', 'defaults' => $defaults, 'openByDefault' => true])
    </div>

    <div class="cw-actions">
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}" class="cw-btn-secondary text-decoration-none">Cancel</a>
        <button type="submit" class="cw-btn-primary">Save Changes</button>
    </div>
</form>

</div>
</div>

@endsection
