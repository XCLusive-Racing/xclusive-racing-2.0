@extends('layouts.app')

@section('title', $event->displayTitle() . ' - ' . config('xcl.name'))

@use('App\Models\TimeTrialLap')

@php
    $status = $event->status();
    $statusLabel = ['upcoming' => 'Upcoming', 'live' => 'Live now', 'closed' => 'Results coming', 'finished' => 'Final results'][$status] ?? ucfirst($status);
@endphp

@section('content')
<main class="xcl-page xcl-tt pb-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">
        <div class="pt-4 mb-4">
            <h1 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">{{ $event->displayTitle() }}</h1>
            <div class="section-divider mb-3" style="margin-left:0"></div>
            <a href="{{ route('time-trials.index') }}" class="xcl-tt__back">&larr; Time Trials</a>
        </div>

        <div class="xcl-tt__event-grid mb-4">
            {{-- Event info + signup --}}
            <section class="xcl-tt__event-card">
                @if($trackImage)
                <div class="xcl-tt__track-banner"><img src="{{ $trackImage }}" alt=""></div>
                @endif
                <div class="xcl-tt__event-body">
                    <span class="xcl-tt__status xcl-tt__status--{{ $status }}">{{ $statusLabel }}</span>
                    <dl class="xcl-tt__facts">
                        <dt>Track</dt><dd>{{ $event->trackName() }}</dd>
                        <dt>Class</dt><dd>{{ $event->classLabel() }}</dd>
                        <dt>Opens</dt><dd><x-local-time :at="$event->starts_at" format="dm-time" /></dd>
                        <dt>Closes</dt><dd><x-local-time :at="$event->ends_at" format="dm-time" /></dd>
                        <dt>Drivers</dt><dd>{{ $event->registrations_count }}</dd>
                    </dl>

                    @if($event->isOpenForSignup())
                        @guest
                        <a href="{{ route('login') }}" class="btn fw-black text-uppercase text-white w-100 xcl-tt__cta">Log in to sign up</a>
                        @else
                            @if($registered)
                            <p class="xcl-tt__signed-up mb-2"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> You are signed up</p>
                            <p class="xcl-tt__muted small mb-3">
                                Join <strong class="text-white">{{ $event->server?->ingame_name ?: $event->server?->name }}</strong>
                                from the in game server list. New signups can join from the next full hour.
                            </p>
                            <form method="POST" action="{{ route('time-trials.events.withdraw', $event) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase w-100">Withdraw</button>
                            </form>
                            @elseif(! $hasPlayerId)
                            <p class="xcl-tt__muted small mb-2">Add your Xbox or PlayStation account to your profile to sign up.</p>
                            <a href="{{ route('profile.edit') }}" class="btn fw-black text-uppercase text-white w-100 xcl-tt__cta">Edit profile</a>
                            @else
                            <form method="POST" action="{{ route('time-trials.events.register', $event) }}">
                                @csrf
                                <button type="submit" class="btn fw-black text-uppercase text-white w-100 xcl-tt__cta">Sign up</button>
                            </form>
                            @endif
                        @endguest
                    @endif
                </div>
            </section>

            {{-- Rules --}}
            <section class="xcl-tt__panel xcl-tt__rules">
                <h2 class="xcl-tt__panel-title">How it works</h2>
                <ul>
                    <li>The server runs one practice session every hour. Drive as many laps as you like, your fastest valid lap counts.</li>
                    <li>A lap only counts when it beats your own All Time Record in that car on this track. Already set a faster time in a car? Pick another car to improve.</li>
                    <li>The standings update every hour. A faster lap replaces your old one.</li>
                    <li>When the week ends, points go to every classified driver: {{ \App\Services\TimeTrials\TimeTrialRating::FIRST }} for the winner down to {{ \App\Services\TimeTrials\TimeTrialRating::LAST }} for the last driver. Your counting laps join the All Time Records.</li>
                    <li>No custom Balance of Performance, the standard in game ACC balance applies.</li>
                </ul>

                @if($timesToBeat->isNotEmpty())
                <h3 class="xcl-tt__panel-subtitle">Your times to beat</h3>
                <ul class="xcl-tt__to-beat">
                    @foreach($timesToBeat as $lap)
                    <li>
                        <span>{{ $lap->car?->label() ?? 'Car #' . $lap->car_id }}</span>
                        <span class="xcl-tt__time">{{ TimeTrialLap::formatLap($lap->lap_time_ms) }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </section>
        </div>

        {{-- Standings --}}
        <section class="xcl-tt__panel">
            <div class="xcl-tt__panel-head">
                <h2 class="xcl-tt__panel-title mb-0">{{ $finished ? 'Final results' : 'Standings' }}</h2>
                @if(! $finished && $event->results_checked_at)
                <span class="xcl-tt__muted small">Updated <x-local-time :at="$event->results_checked_at" format="time-tz" /></span>
                @endif
            </div>

            @if($rows->isEmpty())
            <div class="xcl-tt__empty">No counting laps yet.</div>
            @else
            <div class="table-responsive">
                <table class="table align-middle mb-0 xcl-tt__table">
                    <thead>
                        <tr>
                            <th class="ps-3 ps-sm-4">Pos</th>
                            <th>Driver</th>
                            <th class="xcl-tt__col-sm">Car</th>
                            <th class="text-end">Lap</th>
                            <th class="text-end xcl-tt__col-sm">Gap</th>
                            @unless($finished)
                            <th class="text-end xcl-tt__col-sm">S1</th>
                            <th class="text-end xcl-tt__col-sm">S2</th>
                            <th class="text-end xcl-tt__col-sm">S3</th>
                            @endunless
                            <th class="text-end pe-3 pe-sm-4">Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        @php $carLabel = $row['car']?->label() ?? 'Car #' . $row['car_id']; @endphp
                        <tr>
                            <td class="ps-3 ps-sm-4 xcl-tt__pos xcl-tt__pos--{{ min($row['position'], 4) }}">{{ $row['position'] }}</td>
                            <td>
                                @if($profileId = $profiles[$row['identifier']] ?? null)
                                <a href="{{ route('drivers.show', $profileId) }}" class="xcl-tt__driver">{{ $row['driver_name'] }}</a>
                                @else
                                <span class="xcl-tt__driver">{{ $row['driver_name'] }}</span>
                                @endif
                                <span class="xcl-tt__sub xcl-tt__xs-only">{{ $carLabel }}</span>
                            </td>
                            <td class="xcl-tt__col-sm xcl-tt__muted">{{ $carLabel }}</td>
                            <td class="text-end">
                                <span class="xcl-tt__time">{{ TimeTrialLap::formatLap($row['lap_time_ms']) }}</span>
                                <span class="xcl-tt__sub xcl-tt__xs-only">{{ TimeTrialLap::formatGap($row['gap_ms']) }}</span>
                            </td>
                            <td class="text-end xcl-tt__col-sm xcl-tt__gap">{{ TimeTrialLap::formatGap($row['gap_ms']) }}</td>
                            @unless($finished)
                            @foreach($row['sectors'] as $sector)
                            <td class="text-end xcl-tt__col-sm xcl-tt__sector">{{ TimeTrialLap::formatSector($sector) }}</td>
                            @endforeach
                            @endunless
                            <td class="text-end pe-3 pe-sm-4 xcl-tt__points">+{{ $row['points'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </section>
    </div>
</main>
@endsection
