@extends('layouts.app')

@section('title', 'Calendar ' . $current->format('F Y') . ' - XCLusive Racing')

@section('content')
<main class="events-page xcl-page pb-5 px-3"
      data-cal
      data-my-ids='@json($myRaceIds->keys()->values())'>
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        {{-- Header --}}
        <div class="pt-4 mb-5">
            <h1 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">RACE CALENDAR</h1>
            <div class="section-divider" style="margin-left:0"></div>
        </div>

        {{-- Controls --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">

            {{-- Left: view toggle + game filter --}}
            <div class="d-flex gap-2 flex-wrap align-items-center">

                @auth
                {{-- View toggle --}}
                <div class="xcl-cal-toggle me-2">
                    <button data-cal-view="all" class="xcl-cal-toggle__btn xcl-cal-toggle__btn--active">All Events</button>
                    <button data-cal-view="mine" class="xcl-cal-toggle__btn">My Schedule</button>
                </div>
                @endauth

                {{-- Game filter --}}
                <button data-cal-filter="all" class="xcl-filter-btn fw-bold text-uppercase xcl-filter-btn--active">All</button>
                @foreach([['acc','ACC'],['lmu','LMU'],['iracing','iRacing'],['ac','AC Rally']] as [$g,$l])
                <button data-cal-filter="{{ $g }}" class="xcl-filter-btn fw-bold text-uppercase">{{ $l }}</button>
                @endforeach
            </div>

            {{-- Month nav --}}
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('calendar', ['year' => $prev->year, 'month' => $prev->month]) }}"
                   class="xcl-cal-nav-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <span class="fw-black text-uppercase fst-italic text-white" style="min-width:170px;text-align:center;font-size:1.1rem;letter-spacing:.03em">
                    {{ $current->format('F Y') }}
                </span>
                <a href="{{ route('calendar', ['year' => $next->year, 'month' => $next->month]) }}"
                   class="xcl-cal-nav-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

        </div>

        {{-- Legend --}}
        <div class="d-flex gap-4 flex-wrap mb-4">
            @foreach([['acc','#7c3aed','ACC Console'],['lmu','#db2777','Le Mans Ultimate'],['iracing','#2563eb','iRacing'],['ac','#16a34a','AC Rally']] as [$g,$c,$l])
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-1 flex-shrink-0" style="width:10px;height:10px;background:{{ $c }};display:inline-block"></span>
                <span class="fw-bold text-uppercase" style="font-size:.68rem;color:rgba(255,255,255,.45);letter-spacing:.06em">{{ $l }}</span>
            </div>
            @endforeach
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-1 flex-shrink-0" style="width:10px;height:10px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);display:inline-block"></span>
                <span class="fw-bold text-uppercase" style="font-size:.68rem;color:rgba(255,255,255,.45);letter-spacing:.06em">Championship</span>
            </div>
        </div>

        {{-- Desktop: calendar grid --}}
        <div class="xcl-cal d-none d-md-block mb-5">

            {{-- Day headers --}}
            <div class="xcl-cal__header">
                @foreach(['MON','TUE','WED','THU','FRI','SAT','SUN'] as $dh)
                <div class="xcl-cal__daylabel">{{ $dh }}</div>
                @endforeach
            </div>

            {{-- Grid --}}
            @php
                $gridStart = $current->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::MONDAY);
                $gridEnd   = $current->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
                $today     = \Carbon\Carbon::today('Europe/London');
                $weeks     = $gridStart->diffInWeeks($gridEnd) + 1;
                $cursor    = $gridStart->copy();
            @endphp

            @for($w = 0; $w < $weeks; $w++)
            <div class="xcl-cal__week">
                @for($d = 0; $d < 7; $d++)
                @php
                    $day         = $cursor->copy();
                    $dateKey     = $day->format('Y-m-d');
                    $isThisMonth = $day->month === $current->month;
                    $isToday     = $day->isSameDay($today);
                    $dayRaces    = $grouped->get($dateKey, collect());
                    $cursor->addDay();
                @endphp

                <div class="xcl-cal__cell {{ !$isThisMonth ? 'xcl-cal__cell--other' : '' }} {{ $isToday ? 'xcl-cal__cell--today' : '' }}">
                    <div class="xcl-cal__daynr {{ $isToday ? 'xcl-cal__daynr--today' : '' }}">{{ $day->day }}</div>

                    @foreach($dayRaces as $race)
                    <div data-cal-pill
                         data-cal-game="{{ $race->game }}"
                         data-cal-race-id="{{ $race->id }}"
                         class="xcl-cal__pill {{ $race->championship_id ? 'xcl-cal__pill--championship' : '' }} {{ $myRaceIds->has($race->id) ? 'xcl-cal__pill--mine' : '' }}"
                         style="--game-color:{{ $race->gameColor() }}"
                         title="{{ $race->title }} · {{ $race->scheduledAtUk()->format('H:i') }} UK">
                        <a href="{{ route('events.show', $race) }}" class="xcl-cal__pill-link">
                            <span class="xcl-cal__pill-time">{{ $race->scheduledAtUk()->format('H:i') }}</span>
                            <span class="xcl-cal__pill-title">{{ $race->title }}</span>
                        </a>
                        @if($race->championship_id)
                        <span class="xcl-cal__pill-champ" title="Championship round">C</span>
                        @endif
                    </div>
                    @endforeach
                </div>

                @endfor
            </div>
            @endfor

        </div>

        {{-- Mobile: list view --}}
        <div class="d-md-none mb-5">
            @php
                $mobileDays = collect();
                $mStart = $current->copy()->startOfMonth();
                $mEnd   = $current->copy()->endOfMonth();
                $mc = $mStart->copy();
                while ($mc->lte($mEnd)) {
                    $dk = $mc->format('Y-m-d');
                    if ($grouped->has($dk)) $mobileDays[$dk] = $grouped[$dk];
                    $mc->addDay();
                }
            @endphp

            @if($mobileDays->isEmpty())
            <div class="xcl-cal__empty">
                <p class="fw-bold text-uppercase" style="color:rgba(255,255,255,.35);font-size:.88rem">No events this month</p>
            </div>
            @else
            @foreach($mobileDays as $dateKey => $dayRaces)
            @php $dayDate = \Carbon\Carbon::parse($dateKey); @endphp
            <div class="xcl-cal__mobile-day mb-3">
                <div class="xcl-cal__mobile-date">
                    <span class="xcl-cal__mobile-daynum">{{ $dayDate->format('d') }}</span>
                    <span class="xcl-cal__mobile-dayname">{{ strtoupper($dayDate->format('D, M')) }}</span>
                </div>
                <div class="d-flex flex-column gap-2">
                    @foreach($dayRaces as $race)
                    <div data-cal-pill
                         data-cal-game="{{ $race->game }}"
                         data-cal-race-id="{{ $race->id }}">
                        <a href="{{ route('events.show', $race) }}" class="xcl-cal__mobile-event text-decoration-none d-flex align-items-center gap-3 {{ $myRaceIds->has($race->id) ? 'xcl-cal__mobile-event--mine' : '' }}">
                            <div class="xcl-cal__mobile-bar" style="background:{{ $race->gameColor() }}"></div>
                            <div class="flex-grow-1">
                                <div class="xcl-cal__mobile-title">{{ $race->title }}</div>
                                <div class="xcl-cal__mobile-meta">
                                    {{ $race->scheduledAtUk()->format('H:i T') }}
                                    @if($race->track) · {{ $race->track }} @endif
                                    @if($race->championship_id) · <span style="color:rgba(255,255,255,.5)">Championship</span> @endif
                                </div>
                            </div>
                            <div class="xcl-cal__mobile-badge" style="background:{{ $race->gameColor() }}22;border-color:{{ $race->gameColor() }}44">
                                <span style="color:{{ $race->gameColor() }}">{{ strtoupper($race->game === 'iracing' ? 'iR' : $race->game) }}</span>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
            @endif
        </div>

    </div>
</main>
@endsection
