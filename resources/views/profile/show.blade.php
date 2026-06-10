@extends('layouts.app')

@section('title', 'Profile - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:900px;position:relative;z-index:1">

        {{-- Profile header --}}
        <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <div class="d-flex align-items-start gap-4">
                <div class="flex-shrink-0">
                    <x-rank-avatar :user="$user" :size="88" />
                </div>
                <div class="flex-grow-1">
                    <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">{{ $user->name }}</h1>
                    <p class="text-secondary text-uppercase mb-1">
                        {{ $user->country }} &bull; {{ strtoupper($user->platform) }}
                    </p>
                    @if($user->team)
                    <p class="fw-bold text-uppercase text-xcl-purple mb-1">{{ $user->team }}</p>
                    @endif
                    <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                        @if($user->game)
                        <x-game-badge :game="$user->game" />
                        @endif
                        @if($user->car_model)
                        <span class="text-secondary" style="font-size:.8rem">
                            <span class="fw-bold text-dark">{{ $user->car_model }}</span>
                        </span>
                        @endif
                        @if($user->car_number)
                        <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.75rem">#{{ $user->car_number }}</span>
                        @endif
                    </div>
                    @if($user->connectedAccounts->isNotEmpty())
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        @foreach($user->connectedAccounts as $account)
                        <span class="badge d-inline-flex align-items-center gap-1 fw-bold"
                              style="background:{{ $account->providerColor() }};color:#fff;font-size:.7rem;padding:3px 8px">
                            {!! $account->providerIcon() !!}
                            {{ $account->username }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <a href="{{ route('profile.edit') }}"
                       class="btn btn-sm fw-bold text-uppercase text-white"
                       style="background:#7c3aed;font-size:.75rem">EDIT</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold text-uppercase" style="font-size:.75rem">
                            LOGOUT
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ELO ratings --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="bg-white rounded-3 shadow-sm p-4 elo-card elo-acc">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wide mb-2">ACC CONSOLE</div>
                    <div class="elo-value">{{ $user->elo_acc }}</div>
                    <p class="text-secondary small mb-0">Current Rating</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white rounded-3 shadow-sm p-4 elo-card elo-lmu">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wide mb-2">LE MANS ULTIMATE</div>
                    <div class="elo-value">{{ $user->elo_lmu }}</div>
                    <p class="text-secondary small mb-0">Current Rating</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white rounded-3 shadow-sm p-4 elo-card elo-iracing">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wide mb-2">iRACING</div>
                    <div class="elo-value">{{ $user->elo_iracing }}</div>
                    <p class="text-secondary small mb-0">Current Rating</p>
                </div>
            </div>
        </div>

        {{-- My registered events --}}
        <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-4">MY EVENTS</h2>
            @if($myEvents->isEmpty())
            <a href="{{ url('/events') }}" class="next-step-card">
                <div class="next-step-title mb-2">NO UPCOMING EVENTS</div>
                <p class="mb-0">You haven't registered for any upcoming races yet. Browse events to join one.</p>
            </a>
            @else
            <div class="row g-3">
                @foreach($myEvents as $event)
                <div class="col-md-6">
                    <a href="{{ route('events.show', $event) }}" class="next-step-card">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge fw-bold" style="background:{{ $event->gameColor() }};color:#fff;font-size:.65rem;padding:2px 7px;border-radius:4px">
                                {{ $event->gameLabel() }}
                            </span>
                            @if($event->is_championship)
                            <span class="badge fw-bold" style="background:#fef3c7;color:#92400e;font-size:.65rem;padding:2px 7px;border-radius:4px">Championship</span>
                            @endif
                        </div>
                        <div class="next-step-title mb-1">{{ $event->title }}</div>
                        <p class="mb-1 text-secondary" style="font-size:.85rem">{{ $event->track }}</p>
                        <p class="mb-0 text-secondary" style="font-size:.78rem">
                            {{ $event->scheduledAtUk()->format('d M Y · H:i T') }}
                        </p>
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Stats --}}
        @php
            $ds      = ($driver && $driver->stats) ? $driver->stats : null;
            $dsRaces = $ds?->total_races  ?? $stats['totalRaces'];
            $dsWins  = $ds?->wins         ?? $stats['wins'];
            $dsPods  = $ds?->podiums      ?? $stats['podiums'];
            $dsRate  = $dsRaces > 0 ? round(($dsWins / $dsRaces) * 100) : ($stats['winRate'] ?? 0);
        @endphp
        <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-4">YOUR STATS</h2>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-num">{{ $dsRaces }}</div>
                        <div class="stat-label">Races</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-num">{{ $dsWins }}</div>
                        <div class="stat-label">Wins</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-num">{{ $dsPods }}</div>
                        <div class="stat-label">Podiums</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-num">{{ $dsRate }}%</div>
                        <div class="stat-label">Win Rate</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Race history --}}
        <div class="bg-white rounded-3 shadow-sm p-4">
            <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-4">RACE HISTORY</h2>

            @if($results->isEmpty())
            <div class="text-center py-4">
                <div style="font-size:2rem;margin-bottom:.5rem">🏁</div>
                <p class="text-secondary mb-0">No race results yet. Enter a race to see your history here.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.875rem">
                    <thead style="border-bottom:2px solid #f3f4f6">
                        <tr>
                            <th class="fw-bold text-uppercase text-secondary pb-2" style="font-size:.72rem;letter-spacing:.06em;width:55px">Pos</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2" style="font-size:.72rem;letter-spacing:.06em;width:55px">No</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2" style="font-size:.72rem;letter-spacing:.06em">Event</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2" style="font-size:.72rem;letter-spacing:.06em">Vehicle</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2 text-center" style="font-size:.72rem;letter-spacing:.06em;width:60px">Laps</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2 text-center" style="font-size:.72rem;letter-spacing:.06em;width:110px">Time/Retired</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2 text-center" style="font-size:.72rem;letter-spacing:.06em;width:105px">Best Lap</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2 text-center" style="font-size:.72rem;letter-spacing:.06em;width:90px">Consistency</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2 text-center" style="font-size:.72rem;letter-spacing:.06em;width:50px">Led</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                        <tr style="border-bottom:1px solid #f9fafb">
                            <td><x-race-position :position="$result->position" /></td>
                            <td>
                                @if($result->car_number !== null)
                                <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.72rem">#{{ $result->car_number }}</span>
                                @else
                                <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size:.85rem">{{ $result->race_title ?? '—' }}</div>
                                <div class="text-secondary" style="font-size:.72rem">
                                    {{ $result->race_track ?? '' }}
                                    @if($result->race_scheduled_at)
                                    · {{ \Carbon\Carbon::parse($result->race_scheduled_at)->timezone('Europe/London')->format('d M Y') }}
                                    @endif
                                    @if($result->race_game)
                                    <x-game-badge :game="$result->race_game" class="ms-1" />
                                    @endif
                                </div>
                            </td>
                            <td class="text-secondary" style="font-size:.82rem">{{ $result->vehicle ?? '—' }}</td>
                            <td class="text-center fw-bold">{{ $result->lap_count ?? '—' }}</td>
                            <td class="text-center" style="font-family:monospace;font-size:.82rem">
                                @if($result->dnf)
                                    <span class="badge" style="background:#fef2f2;color:#dc2626;font-size:.7rem;padding:3px 8px;border-radius:5px;font-weight:700">DNF</span>
                                @else
                                    {{ \App\Models\RaceResult::formatMs($result->total_time) }}
                                @endif
                            </td>
                            <td class="text-center fw-bold" style="font-family:monospace;font-size:.82rem">
                                {{ \App\Models\RaceResult::formatMs($result->best_lap) }}
                                @if($result->fastest_lap)
                                <span class="badge ms-1" style="background:#7c3aed;font-size:.6rem;padding:2px 5px">FL</span>
                                @endif
                            </td>
                            <td class="text-center" style="font-size:.82rem">
                                {{ $result->consistency !== null ? $result->consistency . '%' : '—' }}
                            </td>
                            <td class="text-center fw-bold">{{ $result->laps_led ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</main>
@endsection