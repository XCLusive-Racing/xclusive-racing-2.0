@extends('layouts.app')

@section('title', $race->title . ' - XCLusive Racing')

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="container-xl">

        <div class="mb-4 pt-3">
            <a href="{{ route('race') }}" class="btn btn-link fw-bold text-uppercase text-xcl-purple text-decoration-none ps-0">
                ← BACK TO EVENTS
            </a>
        </div>

        @if(session('success'))
        <div class="alert border-0 text-white fw-bold mb-4" style="background:#22c55e">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert border-0 text-white fw-bold mb-4" style="background:#ef4444">
            {{ session('error') }}
        </div>
        @endif

        <div class="row g-4">

            {{-- Race info --}}
            <div class="col-lg-8">
                <div class="bg-white rounded-3 shadow-sm overflow-hidden mb-4">
                    <div class="p-2" style="background:{{ $race->gameColor() }}"></div>
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <span class="badge text-white fw-bold text-uppercase fs-6"
                                  style="background:{{ $race->gameColor() }}">
                                {{ $race->gameLabel() }}
                            </span>
                            <span class="badge text-uppercase fs-6
                                {{ $race->status === 'open' ? 'bg-success' : ($race->status === 'finished' ? 'bg-dark' : 'bg-secondary') }}">
                                {{ strtoupper($race->status) }}
                            </span>
                        </div>

                        <h1 class="display-5 fw-black text-uppercase fst-italic text-dark mb-3">{{ $race->title }}</h1>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2 text-secondary">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                    </svg>
                                    <span class="fw-bold">{{ $race->track }}</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2 text-secondary">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>
                                    </svg>
                                    <span class="fw-bold">{{ $race->scheduledAtUk()->format('D d M Y · H:i T') }}</span>
                                </div>
                            </div>
                        </div>

                        @if($race->description)
                        <p class="text-secondary fs-6">{{ $race->description }}</p>
                        @endif
                    </div>
                </div>

                {{-- Results (if finished) --}}
                @if($race->status === 'finished' && $race->raceResults->isNotEmpty())
                <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                    <div class="p-4 border-bottom">
                        <h2 class="fw-black text-uppercase fst-italic text-dark fs-4 mb-0">RACE RESULTS</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold text-uppercase small ps-4">Pos</th>
                                    <th class="fw-bold text-uppercase small">Driver</th>
                                    <th class="fw-bold text-uppercase small text-center">Fastest Lap</th>
                                    <th class="fw-bold text-uppercase small text-center pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($race->raceResults as $result)
                                <tr>
                                    <td class="ps-4">
                                        @if($result->position === 1)
                                            <span class="fw-black" style="color:#f59e0b">P{{ $result->position }}</span>
                                        @elseif($result->position === 2)
                                            <span class="fw-black text-secondary">P{{ $result->position }}</span>
                                        @elseif($result->position === 3)
                                            <span class="fw-black" style="color:#92400e">P{{ $result->position }}</span>
                                        @else
                                            <span class="fw-bold text-secondary">P{{ $result->position }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold">{{ $result->displayName() }}</td>
                                    <td class="text-center">
                                        @if($result->fastest_lap)
                                            <span class="badge" style="background:#7c3aed">FL</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-4">
                                        @if($result->dnf)
                                            <span class="badge bg-danger">DNF</span>
                                        @else
                                            <span class="badge bg-success">FIN</span>
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

            {{-- Sidebar --}}
            <div class="col-lg-4">

                {{-- Register card --}}
                @if($race->status !== 'finished')
                <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
                    <h3 class="fw-black text-uppercase fst-italic text-dark fs-5 mb-3">REGISTRATION</h3>

                    @auth
                        @if($isRegistered)
                            <div class="alert border-0 text-white fw-bold mb-3" style="background:#22c55e; font-size:.9rem">
                                You are registered for this race!
                            </div>
                            @if($race->status === 'open')
                            <form action="{{ route('race.unregister', $race) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger fw-bold text-uppercase w-100">
                                    UNREGISTER
                                </button>
                            </form>
                            @endif
                        @elseif($race->status === 'open')
                            @if($race->isFull())
                                <p class="text-danger fw-bold">This race is full.</p>
                            @else
                                <form action="{{ route('race.register', $race) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="btn fw-black text-uppercase text-white w-100"
                                            style="background:{{ $race->gameColor() }}">
                                        REGISTER NOW
                                    </button>
                                </form>
                            @endif
                        @else
                            <p class="text-secondary mb-0">Registration is closed.</p>
                        @endif
                    @else
                        <p class="text-secondary mb-3">You need an account to register for events.</p>
                        <a href="{{ route('login') }}" class="btn fw-black text-uppercase text-white w-100 mb-2"
                           style="background:{{ $race->gameColor() }}">
                            LOGIN TO REGISTER
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-outline-secondary fw-bold text-uppercase w-100">
                            CREATE ACCOUNT
                        </a>
                    @endauth
                </div>
                @endif

                {{-- Drivers list --}}
                <div class="bg-white rounded-3 shadow-sm p-4">
                    <h3 class="fw-black text-uppercase fst-italic text-dark fs-5 mb-3">
                        DRIVERS
                        <span class="fs-6 fw-normal text-secondary ms-1">
                            ({{ $race->registrations->count() }}{{ $race->max_drivers ? '/' . $race->max_drivers : '' }})
                        </span>
                    </h3>

                    @if($race->registrations->isEmpty())
                        <p class="text-secondary small mb-0">No drivers registered yet. Be the first!</p>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($race->registrations as $reg)
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black"
                                     style="width:32px;height:32px;font-size:.8rem;background:{{ $race->gameColor() }};flex-shrink:0">
                                    {{ strtoupper(substr($reg->user->name, 0, 1)) }}
                                </div>
                                <span class="fw-bold small">{{ $reg->user->name }}</span>
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