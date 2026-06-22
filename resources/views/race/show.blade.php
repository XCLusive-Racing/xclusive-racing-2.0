@extends('layouts.app')

@section('title', $race->title . ' - XCLusive Racing')

@section('content')
<main class="events-page xcl-page pb-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        {{-- Back button --}}
        <div class="pt-4 mb-4">
            <a href="{{ route('events.index') }}" class="events-back-btn text-decoration-none">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/>
                </svg>
                BACK TO EVENTS
            </a>
        </div>

        @if(session('success'))
        <div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#dc2626">
            {{ session('error') }}
        </div>
        @endif

        {{-- Hero banner --}}
        <div class="xcl-event-hero mb-4">
            @if($race->image)
                <img src="{{ $race->image_url }}" alt="{{ $race->title }}" class="xcl-event-hero__img">
            @endif
            <div class="xcl-event-hero__gradient" style="background:linear-gradient(160deg,{{ $race->gameColor() }}44 0%,rgba(0,0,0,.85) 100%)"></div>
            <div class="xcl-event-hero__top-bar" style="background:{{ $race->gameColor() }}"></div>

            {{-- Badges top-right --}}
            <div class="xcl-event-hero__badges">
                <span class="xcl-event-hero__badge" style="background:{{ $race->gameColor() }}">
                    {{ $race->gameLabel() }}
                </span>
                <span class="xcl-event-hero__badge {{ $race->status === 'open' ? 'xcl-event-hero__badge--open' : ($race->status === 'finished' ? 'xcl-event-hero__badge--finished' : 'xcl-event-hero__badge--closed') }}">
                    {{ strtoupper($race->status) }}
                </span>
            </div>

            {{-- Icon centered --}}
            @if($race->icon)
            <div class="xcl-event-hero__icon">
                <img src="{{ $race->icon_url }}" alt="">
            </div>
            @endif

            {{-- Title + meta --}}
            <div class="xcl-event-hero__body">
                <h1 class="xcl-event-hero__title">{{ $race->title }}</h1>
                <div class="xcl-event-hero__meta-row">
                    @if($race->track)
                    <span class="xcl-event-hero__meta-item">
                        <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        {{ $race->track }}
                    </span>
                    @endif
                    <span class="xcl-event-hero__meta-item">
                        <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>
                        </svg>
                        {{ $race->scheduledAtUk()->format('D d M Y · H:i T') }}
                    </span>
                    @if($race->weather)
                    <span class="xcl-event-hero__meta-item">
                        @if($race->weather === 'dry')
                            <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.79 1.42-1.41zM4 10.5H1v2h3v-2zm9-9.95h-2V3.5h2V.55zm7.45 3.91l-1.41-1.41-1.79 1.79 1.41 1.41 1.79-1.79zm-3.21 13.7l1.79 1.8 1.41-1.41-1.8-1.79-1.4 1.4zM20 10.5v2h3v-2h-3zm-8-5c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm-1 16.95h2V19.5h-2v2.95zm-7.45-3.91l1.41 1.41 1.79-1.8-1.41-1.41-1.79 1.8z"/></svg>
                        @elseif($race->weather === 'wet')
                            <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M17.66 8L12 2.35 6.34 8C4.78 9.56 4 11.64 4 13.64s.78 4.11 2.34 5.67 3.61 2.35 5.66 2.35 4.1-.79 5.66-2.35S20 15.64 20 13.64 19.22 9.56 17.66 8z"/></svg>
                        @else
                            <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.79 1.42-1.41zM4 10.5H1v2h3v-2zm9-9.95h-2V3.5h2V.55zm7.45 3.91l-1.41-1.41-1.79 1.79 1.41 1.41 1.79-1.79zm-3.21 13.7l1.79 1.8 1.41-1.41-1.8-1.79-1.4 1.4zM20 10.5v2h3v-2h-3zm-8-5c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm-1 16.95h2V19.5h-2v2.95zm-7.45-3.91l1.41 1.41 1.79-1.8-1.41-1.41-1.79 1.8z"/></svg>
                        @endif
                        {{ ucfirst($race->weather) }}
                    </span>
                    @endif
                    @if($race->time_of_day)
                    <span class="xcl-event-hero__meta-item">
                        @if($race->time_of_day === 'night')
                            <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 0 1-4.4 2.26 5.403 5.403 0 0 1-3.14-9.8c-.44-.06-.9-.1-1.36-.1z"/></svg>
                        @else
                            <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.79 1.42-1.41zM4 10.5H1v2h3v-2zm9-9.95h-2V3.5h2V.55zm7.45 3.91l-1.41-1.41-1.79 1.79 1.41 1.41 1.79-1.79zm-3.21 13.7l1.79 1.8 1.41-1.41-1.8-1.79-1.4 1.4zM20 10.5v2h3v-2h-3zm-8-5c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm-1 16.95h2V19.5h-2v2.95zm-7.45-3.91l1.41 1.41 1.79-1.8-1.41-1.41-1.79 1.8z"/></svg>
                        @endif
                        {{ ucfirst($race->time_of_day) }}
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4">

            {{-- Left: about + schedule + results --}}
            <div class="col-12 col-lg-8">

                {{-- Description --}}
                @if($race->description)
                <div class="xcl-event-card mb-4">
                    <h2 class="xcl-event-card__heading">ABOUT THIS EVENT</h2>
                    <p class="xcl-event-card__text">{{ $race->description }}</p>
                </div>
                @endif

                {{-- Session Schedule --}}
                @if($race->practice_duration || $race->qualifying_duration || $race->race_duration)
                <div class="xcl-event-card mb-4">
                    <h2 class="xcl-event-card__heading">SESSION SCHEDULE</h2>
                    <div class="xcl-session-schedule">
                        @if($race->practice_duration)
                        <div class="xcl-session-schedule__step">
                            <div class="xcl-session-schedule__dot"></div>
                            <div class="xcl-session-schedule__info">
                                <span class="xcl-session-schedule__label">PRACTICE</span>
                                <span class="xcl-session-schedule__dur">{{ $race->practice_duration }} min</span>
                            </div>
                        </div>
                        @endif
                        @if($race->qualifying_duration)
                        <div class="xcl-session-schedule__step">
                            <div class="xcl-session-schedule__dot"></div>
                            <div class="xcl-session-schedule__info">
                                <span class="xcl-session-schedule__label">QUALIFYING</span>
                                <span class="xcl-session-schedule__dur">{{ $race->qualifying_duration }} min</span>
                            </div>
                        </div>
                        @endif
                        @if($race->race_duration)
                        <div class="xcl-session-schedule__step xcl-session-schedule__step--race">
                            <div class="xcl-session-schedule__dot xcl-session-schedule__dot--race" style="border-color:{{ $race->gameColor() }};background:{{ $race->gameColor() }}22"></div>
                            <div class="xcl-session-schedule__info">
                                <span class="xcl-session-schedule__label xcl-session-schedule__label--race" style="color:{{ $race->gameColor() }}">RACE</span>
                                <span class="xcl-session-schedule__dur xcl-session-schedule__dur--race">{{ $race->race_duration }} min</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Race Results --}}
                @if($race->status === 'finished' && $race->raceResults->isNotEmpty())
                <div class="xcl-event-card">
                    <h2 class="xcl-event-card__heading">RACE RESULTS</h2>
                    <div class="table-responsive">
                        <table class="xcl-results-table">
                            <thead>
                                <tr>
                                    <th>Pos</th>
                                    <th>Driver</th>
                                    <th class="text-center">Fastest Lap</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($race->raceResults as $result)
                                <tr>
                                    <td>
                                        @if($result->position === 1)
                                            <span class="xcl-results-table__pos xcl-results-table__pos--gold">P{{ $result->position }}</span>
                                        @elseif($result->position === 2)
                                            <span class="xcl-results-table__pos xcl-results-table__pos--silver">P{{ $result->position }}</span>
                                        @elseif($result->position === 3)
                                            <span class="xcl-results-table__pos xcl-results-table__pos--bronze">P{{ $result->position }}</span>
                                        @else
                                            <span class="xcl-results-table__pos">P{{ $result->position }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-white">{{ $result->displayName() }}</td>
                                    <td class="text-center">
                                        @if($result->fastest_lap)
                                            <span class="xcl-results-table__fl">FL</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($result->dnf)
                                            <span class="xcl-results-table__status xcl-results-table__status--dnf">DNF</span>
                                        @else
                                            <span class="xcl-results-table__status xcl-results-table__status--fin">FIN</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

            </div>

            {{-- Right: sidebar --}}
            <div class="col-12 col-lg-4">

                {{-- Registration --}}
                @if($race->status !== 'finished')
                <div class="xcl-event-card mb-4">
                    <h3 class="xcl-event-card__heading">REGISTRATION</h3>

                    @auth
                        @if($isRegistered)
                            <div class="xcl-event-reg-status xcl-event-reg-status--registered mb-3">
                                You are registered for this race!
                            </div>
                            @if($race->status === 'open')
                            <form action="{{ route('events.unregister', $race) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="xcl-event-unreg-btn w-100">UNREGISTER</button>
                            </form>
                            @endif
                        @elseif($race->status === 'open')
                            @if($race->isFull())
                                <div class="xcl-event-reg-status xcl-event-reg-status--full">This race is full.</div>
                            @else
                                <form action="{{ route('events.register', $race) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="xcl-event-reg-btn w-100"
                                            style="background:{{ $race->gameColor() }}">
                                        REGISTER NOW →
                                    </button>
                                </form>
                            @endif
                        @else
                            <p class="xcl-event-card__text mb-0">Registration is closed.</p>
                        @endif
                    @else
                        <p class="xcl-event-card__text mb-3">You need an account to register for events.</p>
                        <a href="{{ route('login') }}" class="xcl-event-reg-btn w-100 d-block text-center text-decoration-none mb-2"
                           style="background:{{ $race->gameColor() }}">
                            LOGIN TO REGISTER →
                        </a>
                        <a href="{{ route('register') }}" class="xcl-event-unreg-btn w-100 d-block text-center text-decoration-none">
                            CREATE ACCOUNT
                        </a>
                    @endauth
                </div>
                @endif

                {{-- Requirements --}}
                @if($race->car_class || $race->sr_requirement || $race->min_rating)
                <div class="xcl-event-card mb-4">
                    <h3 class="xcl-event-card__heading">REQUIREMENTS</h3>
                    <div class="xcl-event-reqs">
                        @if($race->car_class)
                        <div class="xcl-event-req-row">
                            <span class="xcl-event-req-label">Car Class</span>
                            <span class="xcl-event-req-value">{{ $race->car_class }}</span>
                        </div>
                        @endif
                        @if($race->sr_requirement)
                        <div class="xcl-event-req-row">
                            <span class="xcl-event-req-label">Min. SR</span>
                            <span class="xcl-event-req-value">{{ $race->sr_requirement }}</span>
                        </div>
                        @endif
                        @if($race->min_rating)
                        <div class="xcl-event-req-row">
                            <span class="xcl-event-req-label">Min. Rating</span>
                            <span class="xcl-event-req-value">{{ number_format((int) $race->min_rating) }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Drivers --}}
                <div class="xcl-event-card">
                    <h3 class="xcl-event-card__heading">
                        DRIVERS
                        <span class="xcl-event-card__heading-sub">
                            {{ $race->registrations->count() }}{{ $race->max_drivers ? '/' . $race->max_drivers : '' }}
                        </span>
                    </h3>

                    @if($race->registrations->isEmpty())
                        <p class="xcl-event-card__text mb-0">No drivers registered yet. Be the first!</p>
                    @else
                        @php $driverCount = $race->registrations->count(); @endphp
                        <div class="xcl-drivers-grid-wrap {{ $driverCount <= 8 ? 'no-overflow' : '' }}">
                            <div class="xcl-drivers-grid">
                                @foreach($race->registrations as $reg)
                                <a href="{{ route('drivers.show', $reg->user) }}" class="xcl-drivers-grid__item text-decoration-none">
                                    <div class="xcl-drivers-grid__avatar" style="{{ !$reg->user->avatarUrl() ? 'background:' . $race->gameColor() : '' }}">
                                        @if($reg->user->avatarUrl())
                                            <img src="{{ $reg->user->avatarUrl() }}" alt="{{ $reg->user->name }}">
                                        @else
                                            {{ strtoupper(substr($reg->user->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <span class="xcl-drivers-grid__name">{{ $reg->user->displayName() }}</span>
                                </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</main>
@endsection