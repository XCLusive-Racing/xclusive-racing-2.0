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

            {{-- Title overlay --}}
            <div class="xcl-event-hero__body">
                <h1 class="xcl-event-hero__title">{{ $race->title }}</h1>
                <div class="xcl-event-hero__meta-row">
                    @if($race->track)
                    <span class="xcl-event-hero__meta-item">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        {{ $race->track }}
                    </span>
                    @endif
                    <span class="xcl-event-hero__meta-item">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>
                        </svg>
                        {{ $race->scheduledAtUk()->format('D d M Y · H:i T') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4">

            {{-- Left: info + results --}}
            <div class="col-12 col-lg-8">

                {{-- Description --}}
                @if($race->description)
                <div class="xcl-event-card mb-4">
                    <h2 class="xcl-event-card__heading">ABOUT THIS EVENT</h2>
                    <p class="xcl-event-card__text">{{ $race->description }}</p>
                </div>
                @endif

                {{-- Results --}}
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
                        <div class="xcl-drivers-list">
                            @foreach($race->registrations as $reg)
                            <div class="xcl-drivers-list__item">
                                <div class="xcl-drivers-list__avatar" style="background:{{ $race->gameColor() }}">
                                    {{ strtoupper(substr($reg->user->name, 0, 1)) }}
                                </div>
                                <span class="xcl-drivers-list__name">{{ $reg->user->name }}</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</main>
@endsection