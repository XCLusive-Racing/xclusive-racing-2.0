@extends('layouts.app')

@section('title', $trackName . ' Time Trials - ' . config('xcl.name'))

@use('App\Models\TimeTrialLap')

@php
    $query = fn (array $changes = []) => array_filter(array_merge(
        ['platform' => $board, 'class' => $class, 'car' => $car?->id],
        $changes
    ), fn ($v) => $v !== null);
@endphp

@section('content')
<main class="xcl-page xcl-tt pb-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">
        <div class="pt-4 mb-4">
            <h1 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">{{ $trackName }}</h1>
            <div class="section-divider mb-3" style="margin-left:0"></div>
            <a href="{{ route('time-trials.index', ['platform' => $board]) }}" class="xcl-tt__back">&larr; All tracks</a>
            <p class="xcl-tt__lead mb-0">All time Time Trials leaderboard. Each driver's best lap per car.</p>
        </div>

        @include('time-trials._boards', ['url' => fn ($key) => route('time-trials.show', ['track' => $track, 'platform' => $key])])

        {{-- Filters: plain links and a GET form, no JavaScript needed. --}}
        <div class="xcl-tt__filters mb-3">
            @if($classes->count() > 1)
            <div class="xcl-tt__chips" aria-label="Car class">
                <a href="{{ route('time-trials.show', ['track' => $track] + $query(['class' => null, 'car' => null])) }}"
                   class="xcl-tt__chip {{ ! $class ? 'xcl-tt__chip--active' : '' }}">All classes</a>
                @foreach($classes as $c)
                <a href="{{ route('time-trials.show', ['track' => $track] + $query(['class' => $c, 'car' => null])) }}"
                   class="xcl-tt__chip {{ $class === $c ? 'xcl-tt__chip--active' : '' }}">{{ $c }}</a>
                @endforeach
            </div>
            @endif

            @if($cars->isNotEmpty())
            <form method="GET" action="{{ route('time-trials.show', $track) }}" class="xcl-tt__car-form">
                <input type="hidden" name="platform" value="{{ $board }}">
                @if($class)<input type="hidden" name="class" value="{{ $class }}">@endif
                <label for="tt-car" class="visually-hidden">Car</label>
                <select id="tt-car" name="car" class="form-select form-select-sm xcl-tt__select">
                    <option value="">All cars</option>
                    @foreach($cars as $c)
                    <option value="{{ $c->id }}" @selected($car?->id === $c->id)>{{ $c->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm fw-bold text-uppercase xcl-tt__apply">Show</button>
            </form>
            @endif
        </div>

        @include('time-trials._bop-note')

        <section class="xcl-tt__panel">
            @if($laps->isEmpty())
            <div class="xcl-tt__empty">No {{ $boards[$board]['label'] }} lap times on {{ $trackName }} yet.</div>
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
                            <th class="text-end xcl-tt__col-sm">S1</th>
                            <th class="text-end xcl-tt__col-sm">S2</th>
                            <th class="text-end xcl-tt__col-sm">S3</th>
                            <th class="text-end pe-3 pe-sm-4 xcl-tt__col-sm">Patch</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($theoretical && $laps->onFirstPage())
                        <tr class="xcl-tt__theoretical">
                            <td class="ps-3 ps-sm-4" title="Theoretical best"><i class="fa-solid fa-bolt" aria-hidden="true"></i></td>
                            <td>
                                <span class="fw-bold">Theoretical best</span>
                                <span class="xcl-tt__sub">Fastest S1 + S2 + S3 set on this track</span>
                            </td>
                            <td class="xcl-tt__col-sm xcl-tt__muted">{{ $class || $car ? 'Current filter' : 'All cars' }}</td>
                            <td class="text-end">
                                <span class="xcl-tt__time">{{ TimeTrialLap::formatLap($theoretical['total']) }}</span>
                                <span class="xcl-tt__sub xcl-tt__xs-only">
                                    {{ collect($theoretical['sectors'])->map(fn ($s) => TimeTrialLap::formatSector($s['ms']))->implode(' / ') }}
                                </span>
                            </td>
                            <td class="text-end xcl-tt__col-sm xcl-tt__gap">{{ TimeTrialLap::formatGap($theoretical['total'] - $leaderMs) }}</td>
                            @foreach($theoretical['sectors'] as $s)
                            <td class="text-end xcl-tt__col-sm xcl-tt__sector" title="{{ $s['driver'] }}">{{ TimeTrialLap::formatSector($s['ms']) }}</td>
                            @endforeach
                            <td class="pe-3 pe-sm-4 xcl-tt__col-sm"></td>
                        </tr>
                        @endif

                        @foreach($laps as $i => $lap)
                        @php $pos = $laps->firstItem() + $i; @endphp
                        <tr>
                            <td class="ps-3 ps-sm-4 xcl-tt__pos xcl-tt__pos--{{ min($pos, 4) }}">{{ $pos }}</td>
                            <td>
                                @if($profileId = $profiles[$lap->platform_identifier] ?? null)
                                <a href="{{ route('drivers.show', $profileId) }}" class="xcl-tt__driver">{{ $lap->driver_name }}</a>
                                @else
                                <span class="xcl-tt__driver">{{ $lap->driver_name }}</span>
                                @endif
                                <span class="xcl-tt__sub xcl-tt__xs-only">{{ $lap->car?->label() ?? 'Car #' . $lap->car_id }}</span>
                            </td>
                            <td class="xcl-tt__col-sm xcl-tt__muted">
                                {{ $lap->car?->label() ?? 'Car #' . $lap->car_id }}
                                <span class="xcl-tt__class">{{ $lap->car_class }}</span>
                            </td>
                            <td class="text-end">
                                <span class="xcl-tt__time">{{ TimeTrialLap::formatLap($lap->lap_time_ms) }}</span>
                                <span class="xcl-tt__sub xcl-tt__xs-only">
                                    {{ TimeTrialLap::formatGap($lap->lap_time_ms - $leaderMs) }}
                                    @if($lap->sector1_ms !== null)
                                    <span class="d-block">{{ TimeTrialLap::formatSector($lap->sector1_ms) }} / {{ TimeTrialLap::formatSector($lap->sector2_ms) }} / {{ TimeTrialLap::formatSector($lap->sector3_ms) }}</span>
                                    @endif
                                </span>
                            </td>
                            <td class="text-end xcl-tt__col-sm xcl-tt__gap">{{ TimeTrialLap::formatGap($lap->lap_time_ms - $leaderMs) }}</td>
                            <td class="text-end xcl-tt__col-sm xcl-tt__sector">{{ TimeTrialLap::formatSector($lap->sector1_ms) }}</td>
                            <td class="text-end xcl-tt__col-sm xcl-tt__sector">{{ TimeTrialLap::formatSector($lap->sector2_ms) }}</td>
                            <td class="text-end xcl-tt__col-sm xcl-tt__sector">{{ TimeTrialLap::formatSector($lap->sector3_ms) }}</td>
                            <td class="text-end pe-3 pe-sm-4 xcl-tt__col-sm xcl-tt__muted">{{ $lap->game_patch }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($laps->hasPages())
            <div class="xcl-tt__pager">
                {{ $laps->links('pagination::bootstrap-5') }}
            </div>
            @endif
            @endif
        </section>
    </div>
</main>
@endsection
