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
                @php
                    $fmt      = $race->eventFormat;
                    $pracMins = $fmt ? $fmt->practice_mins  : $race->practice_duration;
                    $qualiMins = $fmt ? $fmt->quali_mins    : $race->qualifying_duration;
                    $race1Mins = ($race->is_endurance && $race->race_duration)
                        ? $race->race_duration
                        : ($fmt ? $fmt->race1_mins : $race->race_duration);
                    $quali2Mins = $fmt ? $fmt->quali2_mins  : null;
                    $race2Mins  = $fmt ? $fmt->race2_mins   : null;

                    // Pitstop info: format-based first, then race-level for custom events
                    if ($fmt) {
                        $hasPitstop   = $fmt->pitstop_count > 0;
                        $pitstopCount = $fmt->pitstop_count;
                        $minStopSecs  = $fmt->min_stop_secs;
                    } else {
                        $hasPitstop   = (int) ($race->pitstop_count ?? 0) > 0;
                        $pitstopCount = $race->pitstop_count ?? 0;
                        $minStopSecs  = $race->min_stop_secs ?? null;
                    }
                    $pitstopLabel = !$hasPitstop
                        ? 'None'
                        : ($minStopSecs
                            ? $pitstopCount . 'x, ' . $minStopSecs . 's waiting time'
                            : $pitstopCount . 'x, fuel only');
                @endphp
                @if($pracMins || $qualiMins || $race1Mins || $fmt)
                <div class="xcl-event-card mb-4">
                    <h2 class="xcl-event-card__heading">SESSION SCHEDULE</h2>
                    <div class="xcl-session-schedule">
                        @if($pracMins)
                        <div class="xcl-session-schedule__step">
                            <div class="xcl-session-schedule__dot"></div>
                            <div class="xcl-session-schedule__info">
                                <span class="xcl-session-schedule__label">PRACTICE</span>
                                <span class="xcl-session-schedule__dur">{{ $pracMins }} min</span>
                            </div>
                        </div>
                        @endif
                        @if($qualiMins)
                        <div class="xcl-session-schedule__step">
                            <div class="xcl-session-schedule__dot"></div>
                            <div class="xcl-session-schedule__info">
                                <span class="xcl-session-schedule__label">{{ $race2Mins ? 'QUALIFYING 1' : 'QUALIFYING' }}</span>
                                <span class="xcl-session-schedule__dur">{{ $qualiMins }} min</span>
                            </div>
                        </div>
                        @endif
                        @if($race1Mins)
                        <div class="xcl-session-schedule__step xcl-session-schedule__step--race">
                            <div class="xcl-session-schedule__dot xcl-session-schedule__dot--race" style="border-color:{{ $race->gameColor() }};background:{{ $race->gameColor() }}22"></div>
                            <div class="xcl-session-schedule__info">
                                <span class="xcl-session-schedule__label xcl-session-schedule__label--race" style="color:{{ $race->gameColor() }}">{{ $race2Mins ? 'RACE 1' : 'RACE' }}</span>
                                <span class="xcl-session-schedule__dur xcl-session-schedule__dur--race">
                                    @if($race->is_endurance && $race1Mins % 60 === 0)
                                        {{ $race1Mins / 60 }}h
                                    @else
                                        {{ $race1Mins }} min
                                    @endif
                                </span>
                            </div>
                        </div>
                        @endif
                        @if($quali2Mins)
                        <div class="xcl-session-schedule__step">
                            <div class="xcl-session-schedule__dot"></div>
                            <div class="xcl-session-schedule__info">
                                <span class="xcl-session-schedule__label">QUALIFYING 2</span>
                                <span class="xcl-session-schedule__dur">{{ $quali2Mins }} min</span>
                            </div>
                        </div>
                        @endif
                        @if($race2Mins)
                        <div class="xcl-session-schedule__step xcl-session-schedule__step--race">
                            <div class="xcl-session-schedule__dot xcl-session-schedule__dot--race" style="border-color:{{ $race->gameColor() }};background:{{ $race->gameColor() }}22"></div>
                            <div class="xcl-session-schedule__info">
                                <span class="xcl-session-schedule__label xcl-session-schedule__label--race" style="color:{{ $race->gameColor() }}">RACE 2</span>
                                <span class="xcl-session-schedule__dur xcl-session-schedule__dur--race">{{ $race2Mins }} min</span>
                            </div>
                        </div>
                        @endif
                    </div>
                    @php
                        $customMultiplier = $race->xcl_r_multiplier
                            ?? ($race->duration_key ? (['15'=>0.6,'20'=>0.8,'30'=>1.0,'30+'=>1.2,'30++'=>1.3,'45'=>1.5,'45+'=>1.6,'60'=>2.0,'60+'=>2.1,'90'=>2.5,'90+'=>2.6][$race->duration_key] ?? null) : null);
                    @endphp
                    @if($fmt || $hasPitstop || $customMultiplier)
                    <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap">
                        @if($fmt || $hasPitstop)
                        <span style="font-size:.72rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">
                            <i class="fa-solid fa-screwdriver-wrench" style="color:#f59e0b;margin-right:6px"></i>Pit Stops
                            <span style="font-weight:700;color:{{ $hasPitstop ? '#f59e0b' : '#6b7280' }};text-transform:none;letter-spacing:normal;margin-left:6px">{{ $pitstopLabel }}</span>
                        </span>
                        @if($fmt || $customMultiplier)
                        <span style="width:1px;height:18px;background:rgba(219,39,119,.4);flex-shrink:0"></span>
                        @endif
                        @endif
                        @if($fmt)
                        <span style="font-size:.72rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">
                            <i class="fa-solid fa-gauge-high" style="color:#c084fc;margin-right:6px"></i>XCL Rating
                            <span style="font-weight:700;color:#c084fc;text-transform:none;letter-spacing:normal;margin-left:6px">{{ $fmt->xclRLabel() }}</span>
                        </span>
                        @elseif($customMultiplier)
                        <span style="font-size:.72rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">
                            <i class="fa-solid fa-gauge-high" style="color:#c084fc;margin-right:6px"></i>XCL Rating
                            <span style="font-weight:700;color:#c084fc;text-transform:none;letter-spacing:normal;margin-left:6px">×{{ number_format($customMultiplier, 1) }} XCL-R</span>
                        </span>
                        @endif
                    </div>
                    @endif
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
                                @php
                                    $groupedRaceResults = \App\Models\RaceResult::groupedByCar($race->raceResults->where('dns', false), $race);
                                    $classifiedPositions = \App\Models\RaceResult::classifiedPositions($groupedRaceResults->pluck('result'));
                                @endphp
                                @foreach($groupedRaceResults as $row)
                                @php $result = $row->result; $pos = $classifiedPositions->get($result->id); @endphp
                                <tr>
                                    <td>
                                        @if($pos === 1)
                                            <span class="xcl-results-table__pos xcl-results-table__pos--gold">P{{ $pos }}</span>
                                        @elseif($pos === 2)
                                            <span class="xcl-results-table__pos xcl-results-table__pos--silver">P{{ $pos }}</span>
                                        @elseif($pos === 3)
                                            <span class="xcl-results-table__pos xcl-results-table__pos--bronze">P{{ $pos }}</span>
                                        @elseif($pos !== null)
                                            <span class="xcl-results-table__pos">P{{ $pos }}</span>
                                        @else
                                            <span class="xcl-results-table__pos">—</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-white">
                                        {{ $row->label }}
                                        @if($row->sub)
                                        <span class="d-block fw-normal" style="font-size:.72rem;opacity:.65">{{ $row->sub }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($result->fastest_lap)
                                            <span class="xcl-results-table__fl">FL</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($result->dsq)
                                            <span class="xcl-results-table__status xcl-results-table__status--dsq">DSQ</span>
                                        @elseif($result->dc)
                                            <span class="xcl-results-table__status xcl-results-table__status--dc">DC</span>
                                        @elseif($result->dns)
                                            <span class="xcl-results-table__status xcl-results-table__status--dns">DNS</span>
                                        @elseif($result->dnf)
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

                @php $isEndurance = (bool) $race->is_endurance; @endphp

                {{-- Team Entry (endurance races only) --}}
                @auth
                @if($isEndurance && $userTeam)
                <div class="xcl-event-card mb-4">
                    <h3 class="xcl-event-card__heading">TEAM ENTRY</h3>

                    @if($myTeamEntry)
                        <div class="xcl-event-reg-status xcl-event-reg-status--registered mb-3">
                            Registered!
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                            @if($userTeam->logoUrl())
                            <img src="{{ $userTeam->logoUrl() }}" width="44" height="44"
                                 style="border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.15);flex-shrink:0">
                            @else
                            <div style="width:44px;height:44px;border-radius:50%;background:#374151;display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:900;color:#e5e7eb;flex-shrink:0">
                                {{ strtoupper(substr($userTeam->name, 0, 1)) }}
                            </div>
                            @endif
                            <div>
                                <div style="font-size:.95rem;font-weight:800;color:#e5e7eb">[{{ $userTeam->tag }}] {{ $userTeam->name }}</div>
                                @if($myTeamEntry->car_number || $myTeamEntry->car_model)
                                <div style="font-size:.78rem;color:#9ca3af;margin-top:2px">
                                    @if($myTeamEntry->car_number)<span style="color:#e5e7eb;font-weight:700">#{{ $myTeamEntry->car_number }}</span>@endif
                                    @if($myTeamEntry->car_model)<span> — {{ $myTeamEntry->car_model }}</span>@endif
                                </div>
                                @endif
                            </div>
                        </div>
                        @if($race->status === 'open')
                        <form action="{{ route('events.unregister-team', $race) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="xcl-event-unreg-btn w-100">UNREGISTER TEAM</button>
                        </form>
                        @endif
                    @elseif($race->registrationOpen())
                        <p class="xcl-event-card__text mb-3" style="font-size:.82rem">
                            Register your team <strong style="color:#e5e7eb">{{ $userTeam->name }}</strong>. Select which drivers will participate:
                        </p>
                        <form action="{{ route('events.register-team', $race) }}" method="POST">
                            @csrf

                            {{-- Car details --}}
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="xcl-event-card__text d-block mb-1" style="font-size:.75rem">Car Number</label>
                                    <input type="number" name="car_number" min="0" max="999"
                                           class="form-control form-control-sm"
                                           style="background:#1f2937;border-color:#374151;color:#e5e7eb"
                                           placeholder="e.g. 7" required>
                                </div>
                                <div class="col-6">
                                    <label class="xcl-event-card__text d-block mb-1" style="font-size:.75rem">Car Model</label>
                                    @php
                                        $carList = \App\Models\Car::where('game', $race->game)
                                            ->when($race->car_class, fn($q) => $q->where('car_class', $race->car_class))
                                            ->orderBy('name')
                                            ->pluck('name');
                                    @endphp
                                    @if($carList->isNotEmpty())
                                    <select name="car_model" class="form-select form-select-sm"
                                            style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                                        <option value="">— Select car —</option>
                                        @foreach($carList as $car)
                                        <option value="{{ $car }}">{{ $car }}</option>
                                        @endforeach
                                    </select>
                                    @else
                                    <input type="text" name="car_model" maxlength="60"
                                           class="form-control form-control-sm"
                                           style="background:#1f2937;border-color:#374151;color:#e5e7eb"
                                           placeholder="e.g. Ferrari 296">
                                    @endif
                                </div>
                            </div>

                            {{-- Driver selection --}}
                            <div style="display:grid;grid-template-columns:1fr auto;align-items:center;gap:4px 8px;margin-bottom:8px">
                                <span style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Driver</span>
                                <span style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;text-align:center">Starts</span>

                                {{-- Owner --}}
                                <div class="d-flex align-items-center gap-2">
                                    <input class="form-check-input m-0" type="checkbox" name="driver_ids[]"
                                           value="{{ $userTeam->owner_id }}" id="driver_{{ $userTeam->owner_id }}"
                                           checked onchange="syncStarter(this)">
                                    <label for="driver_{{ $userTeam->owner_id }}" class="d-flex align-items-center gap-2" style="color:#e5e7eb;font-size:.85rem;cursor:pointer">
                                        @if(auth()->user()->avatarUrl())
                                        <img src="{{ auth()->user()->avatarUrl() }}" width="22" height="22"
                                             style="border-radius:50%;object-fit:cover;flex-shrink:0">
                                        @else
                                        <div style="width:22px;height:22px;border-radius:50%;background:#374151;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;color:#e5e7eb;flex-shrink:0">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        </div>
                                        @endif
                                        {{ auth()->user()->displayName() }}
                                        <span style="color:#6b7280;font-size:.75rem">(you)</span>
                                    </label>
                                </div>
                                <div class="text-center">
                                    <input type="radio" name="starting_driver_id" value="{{ $userTeam->owner_id }}"
                                           id="starter_{{ $userTeam->owner_id }}" checked
                                           style="width:15px;height:15px;cursor:pointer;accent-color:{{ $race->gameColor() }}">
                                </div>

                                @foreach($userTeam->members as $member)
                                <div class="d-flex align-items-center gap-2">
                                    <input class="form-check-input m-0" type="checkbox" name="driver_ids[]"
                                           value="{{ $member->id }}" id="driver_{{ $member->id }}"
                                           onchange="syncStarter(this)">
                                    <label for="driver_{{ $member->id }}" class="d-flex align-items-center gap-2" style="color:#e5e7eb;font-size:.85rem;cursor:pointer">
                                        @if($member->avatarUrl())
                                        <img src="{{ $member->avatarUrl() }}" width="22" height="22"
                                             style="border-radius:50%;object-fit:cover;flex-shrink:0">
                                        @else
                                        <div style="width:22px;height:22px;border-radius:50%;background:#374151;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;color:#e5e7eb;flex-shrink:0">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                        @endif
                                        {{ $member->displayName() }}
                                    </label>
                                </div>
                                <div class="text-center">
                                    <input type="radio" name="starting_driver_id" value="{{ $member->id }}"
                                           id="starter_{{ $member->id }}"
                                           style="width:15px;height:15px;cursor:pointer;accent-color:{{ $race->gameColor() }}">
                                </div>
                                @endforeach
                            </div>
                            <script>
                            function syncStarter(checkbox) {
                                const val   = checkbox.value;
                                const radio = document.getElementById('starter_' + val);
                                if (!radio) return;
                                if (!checkbox.checked) {
                                    // If this driver was the selected starter, move to first checked driver
                                    if (radio.checked) {
                                        const first = document.querySelector('input[name="driver_ids[]"]:checked');
                                        if (first) document.getElementById('starter_' + first.value)?.click();
                                    }
                                    radio.disabled = true;
                                } else {
                                    radio.disabled = false;
                                }
                            }
                            </script>

                            <button type="submit" class="xcl-event-reg-btn w-100 mt-3"
                                    style="background:{{ $race->gameColor() }}">
                                REGISTER TEAM →
                            </button>
                        </form>
                    @else
                        <p class="xcl-event-card__text mb-0">Team registration is closed.</p>
                    @endif
                </div>
                @endif
                @endauth

                {{-- Registration (solo — hidden for endurance races) --}}
                @if($race->status !== 'finished' && !$isEndurance)
                <div class="xcl-event-card mb-4">
                    <h3 class="xcl-event-card__heading">REGISTRATION</h3>

                    @auth
                        @if($isRegistered)
                            <div class="xcl-event-reg-status xcl-event-reg-status--registered mb-3">
                                You are registered for this race!
                                @if($myRegistration?->teamEntry)
                                <span class="d-block mt-1" style="font-size:.78rem;opacity:.8">
                                    Team: {{ $myRegistration->teamEntry->team->name }}
                                </span>
                                @endif
                                @if($race->is_multiclass && $myRegistration?->raceClass)
                                <span class="d-block mt-1" style="font-size:.78rem;font-weight:700;color:{{ $myRegistration->raceClass->color }}">
                                    Class: {{ $myRegistration->raceClass->name }}
                                </span>
                                @endif
                            </div>
                            @if($race->status === 'open' && !$myRegistration?->teamEntry)
                            <form action="{{ route('events.unregister', $race) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="xcl-event-unreg-btn w-100">UNREGISTER</button>
                            </form>
                            @endif
                        @elseif($race->registrationOpen())
                            @if($race->isFull())
                                <div class="xcl-event-reg-status xcl-event-reg-status--full">This race is full.</div>
                            @else
                                <form action="{{ route('events.register', $race) }}" method="POST">
                                    @csrf
                                    @if($race->is_multiclass && $race->raceClasses->isNotEmpty())
                                    <div class="mb-3">
                                        <label class="xcl-event-card__text mb-1 d-block" style="font-size:.82rem">Select Class</label>
                                        <select name="race_class_id" class="form-select form-select-sm" required
                                                style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                                            <option value="">Choose your class...</option>
                                            @foreach($race->raceClasses as $cls)
                                            @php
                                                $clsReqs = array_filter([
                                                    $cls->sr_requirement ? 'SR ' . $cls->sr_requirement . '.0+' : null,
                                                    $cls->min_rating ? ($cls->xclTierInfo()[0] ?: $cls->min_rating) . '+' : null,
                                                ]);
                                            @endphp
                                            <option value="{{ $cls->id }}" {{ $cls->isFull() ? 'disabled' : '' }}>
                                                {{ $cls->name }}{{ $cls->car_class ? ' (' . $cls->car_class . ')' : '' }}{{ $clsReqs ? ' — ' . implode(' · ', $clsReqs) : '' }}{{ $cls->isFull() ? ' — Full' : '' }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                    <button type="submit" class="xcl-event-reg-btn w-100"
                                            style="background:{{ $race->gameColor() }}">
                                        REGISTER NOW →
                                    </button>
                                </form>
                                <p class="xcl-event-card__text mt-2 mb-0" style="font-size:.72rem;opacity:.7">
                                    Registration closes 5 minutes before the start.
                                </p>
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
                @php
                    $classesWithReqs = $race->is_multiclass
                        ? $race->raceClasses->filter(fn($c) => $c->sr_requirement || $c->min_rating)
                        : collect();
                @endphp
                @if($race->car_class || $race->sr_requirement || $race->min_rating || $classesWithReqs->isNotEmpty())
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
                        @php [$srLetter, $srColor] = $race->srTier(); @endphp
                        <div class="xcl-event-req-row">
                            <span class="xcl-event-req-label">Min. SR</span>
                            <span class="xcl-event-req-value">
                                <span style="display:inline-flex;align-items:center;gap:5px">
                                    <span style="width:20px;height:20px;border-radius:50%;background:#0f0f1a;border:2px solid {{ $srColor }};display:inline-flex;align-items:center;justify-content:center;color:{{ $srColor }};font-size:.58rem;font-weight:900;flex-shrink:0">{{ $srLetter }}</span>
                                    <span style="font-weight:700;color:#e5e7eb">SR {{ $race->sr_requirement }}.0+</span>
                                </span>
                            </span>
                        </div>
                        @endif
                        @if($race->min_rating)
                        @php [$xclName, $xclColor] = $race->xclTierInfo(); @endphp
                        <div class="xcl-event-req-row">
                            <span class="xcl-event-req-label">Min. XCL Rating</span>
                            <span class="xcl-event-req-value">
                                @if($xclName)
                                <span style="font-size:.75rem;font-weight:900;text-transform:capitalize;padding:2px 10px;border-radius:4px;border:1px solid {{ $xclColor }}66;background:{{ $xclColor }}22;color:{{ $xclColor }}">{{ $xclName }}+</span>
                                @else
                                {{ $race->min_rating }}
                                @endif
                            </span>
                        </div>
                        @endif

                        @if($classesWithReqs->isNotEmpty())
                        <div class="mt-2 pt-2" style="border-top:1px solid rgba(255,255,255,.08)">
                            <span class="xcl-event-req-label d-block mb-2">Per Class</span>
                            @foreach($classesWithReqs as $cls)
                            @php [$clsSrLetter, $clsSrColor] = $cls->srTier(); [$clsXclName, $clsXclColor] = $cls->xclTierInfo(); @endphp
                            <div class="xcl-event-req-row">
                                <span class="xcl-event-req-label" style="color:{{ $cls->color }}">{{ $cls->name }}</span>
                                <span class="xcl-event-req-value d-flex gap-2 align-items-center">
                                    @if($cls->sr_requirement)
                                    <span style="display:inline-flex;align-items:center;gap:4px">
                                        <span style="width:16px;height:16px;border-radius:50%;background:#0f0f1a;border:2px solid {{ $clsSrColor }};display:inline-flex;align-items:center;justify-content:center;color:{{ $clsSrColor }};font-size:.5rem;font-weight:900;flex-shrink:0">{{ $clsSrLetter }}</span>
                                        <span style="font-size:.75rem;font-weight:700;color:#e5e7eb">SR {{ $cls->sr_requirement }}.0+</span>
                                    </span>
                                    @endif
                                    @if($cls->min_rating)
                                    <span style="font-size:.68rem;font-weight:900;text-transform:capitalize;padding:1px 8px;border-radius:4px;border:1px solid {{ $clsXclColor }}66;background:{{ $clsXclColor }}22;color:{{ $clsXclColor }}">{{ $clsXclName ?: $cls->min_rating }}+</span>
                                    @endif
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Drivers --}}
                <div class="xcl-event-card">
                    @php
                        $sofRatings = $race->registrations->pluck('user')->filter()
                            ->map(fn($u) => (int) ($u->{"elo_{$race->game}"} ?? 0))
                            ->filter(fn($r) => $r > 0);
                        $sof = $sofRatings->isNotEmpty() ? $sofRatings->avg() : null;
                    @endphp
                    <h3 class="xcl-event-card__heading">
                        {{ $isEndurance ? 'TEAMS' : 'DRIVERS' }}
                        <span class="xcl-event-card__heading-sub">
                            @if($isEndurance)
                                {{ $race->teamEntries->count() }}{{ $race->max_drivers ? '/' . $race->max_drivers : '' }}
                            @else
                                {{ $race->registrations->count() }}{{ $race->max_drivers ? '/' . $race->max_drivers : '' }}
                            @endif
                        </span>
                        @if($sof !== null)
                        <span class="xcl-event-card__heading-sub" style="margin-left:auto;margin-right:14px;color:#c084fc;font-weight:800">
                            <i class="fa-solid fa-chart-line" style="margin-right:4px"></i>SoF {{ number_format($sof, 0) }}
                        </span>
                        @endif
                    </h3>

                    @if($race->registrations->isEmpty())
                        <p class="xcl-event-card__text mb-0">No drivers registered yet. Be the first!</p>
                    @else
                        @php $driverCount = $race->registrations->count(); @endphp
                        <div class="xcl-drivers-grid-wrap {{ $driverCount <= 8 ? 'no-overflow' : '' }}">
                            <div class="xcl-drivers-grid">
                                @foreach($race->registrations as $reg)
                                @php $driverRecord = $driverMap->get($reg->user->platform_id ?? '') @endphp
                                @if($driverRecord)
                                <a href="{{ route('drivers.show', $driverRecord) }}" class="xcl-drivers-grid__item text-decoration-none">
                                @else
                                <div class="xcl-drivers-grid__item">
                                @endif
                                    <div class="xcl-drivers-grid__avatar" style="{{ !$reg->user->avatarUrl() ? 'background:' . $race->gameColor() : '' }}">
                                        @if($reg->user->avatarUrl())
                                            <img src="{{ $reg->user->avatarUrl() }}" alt="{{ $reg->user->name }}">
                                        @else
                                            {{ strtoupper(substr($reg->user->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div class="xcl-drivers-grid__info">
                                        <span class="xcl-drivers-grid__name">{{ $reg->user->displayName() }}</span>
                                        @if($reg->teamEntry)
                                        <span class="xcl-drivers-grid__class-badge" style="background:#374151;color:#9ca3af;border:1px solid #4b5563">
                                            {{ $reg->teamEntry->team->name }}
                                        </span>
                                        @elseif($race->is_multiclass && $reg->raceClass)
                                        <span class="xcl-drivers-grid__class-badge" style="background:{{ $reg->raceClass->color }}22;color:{{ $reg->raceClass->color }};border:1px solid {{ $reg->raceClass->color }}44">
                                            {{ $reg->raceClass->name }}
                                        </span>
                                        @endif
                                    </div>
                                @if($driverRecord)
                                </a>
                                @else
                                </div>
                                @endif
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