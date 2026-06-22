@extends('layouts.app')

@section('title', $driver->gamertag . ' - XCLusive Racing')

@php
$classMeta = [
    'alien'    => ['label' => 'Alien',    'color' => '#10b981'],
    'platinum' => ['label' => 'Platinum', 'color' => '#7c3aed'],
    'gold'     => ['label' => 'Gold',     'color' => '#f59e0b'],
    'silver'   => ['label' => 'Silver',   'color' => '#9ca3af'],
    'bronze'   => ['label' => 'Bronze',   'color' => '#cd7f32'],
    'rookie'   => ['label' => 'Rookie',   'color' => '#ef4444'],
];
$class   = $driver->class;
$meta    = $classMeta[$class] ?? $classMeta['rookie'];
$srClass = $driver->sr_class;
$stats   = $driver->stats;
@endphp

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:960px;position:relative;z-index:1">

        {{-- Back --}}
        <div class="mb-3">
            <a href="{{ route('drivers.index') }}" class="text-secondary fw-bold text-decoration-none" style="font-size:.85rem">
                ← Back to Standings
            </a>
        </div>

        {{-- Header card --}}
        <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <div class="d-flex align-items-start gap-4 flex-wrap">
                {{-- Class badge ring --}}
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle fw-black"
                     style="width:80px;height:80px;background:{{ $meta['color'] }}18;border:3px solid {{ $meta['color'] }};font-size:1.6rem;color:{{ $meta['color'] }}">
                    {{ strtoupper(substr($meta['label'], 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-0">{{ $driver->gamertag }}</h1>
                        @if($driver->number)
                        <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.8rem">#{{ $driver->number }}</span>
                        @endif
                        @if($isSupporter)
                        <span class="badge fw-bold d-inline-flex align-items-center gap-1"
                              style="background:#fef9c3;color:#854d0e;border:1px solid #fde047;font-size:.72rem">
                            ★ Supporter
                        </span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                        <span class="badge fw-bold text-uppercase"
                              style="background:{{ $meta['color'] }}20;color:{{ $meta['color'] }};border:1px solid {{ $meta['color'] }}40">
                            {{ $meta['label'] }}
                        </span>
                        <span class="fw-black" style="color:{{ $srClass['color'] }}">SR {{ $srClass['grade'] }}</span>
                        @if($driver->country_code)
                        <span class="text-secondary fw-bold" style="font-size:.85rem">{{ $driver->country_code }}</span>
                        @endif
                        <span class="badge text-uppercase fw-bold" style="background:#f3f4f6;color:#374151;font-size:.72rem">
                            {{ strtoupper($driver->platform) }}
                        </span>
                    </div>
                    @if($driver->team)
                    <p class="fw-bold text-uppercase mb-0" style="color:#7c3aed;font-size:.9rem">{{ $driver->team }}</p>
                    @endif
                    @if($driver->discord)
                    <p class="text-secondary mb-0" style="font-size:.82rem">
                        <i class="fab fa-discord me-1"></i>{{ $driver->discord }}
                    </p>
                    @endif
                </div>
                {{-- Rating block --}}
                <div class="text-end flex-shrink-0">
                    <div class="fw-black" style="font-size:2.8rem;line-height:1;color:#111827">
                        {{ number_format($driver->xcl_rating, 0) }}
                    </div>
                    <div class="text-secondary fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:.08em">XCL Rating</div>
                    <div class="mt-1">
                        <span style="color:{{ $srClass['color'] }};font-weight:900">{{ number_format($driver->safety_rating, 2) }}</span>
                        <span class="text-secondary" style="font-size:.75rem"> SR</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats grid --}}
        @if($stats)
        <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <h2 class="fs-5 fw-black text-uppercase fst-italic text-dark mb-3">Career Stats</h2>
            <div class="row g-3">
                @foreach([
                    ['label' => 'Races',      'value' => $stats->total_races],
                    ['label' => 'Wins',        'value' => $stats->wins,       'color' => '#f59e0b'],
                    ['label' => 'Podiums',     'value' => $stats->podiums],
                    ['label' => 'Top 5s',      'value' => $stats->top5s],
                    ['label' => 'Top 10s',     'value' => $stats->top10s],
                    ['label' => 'Fastest Laps','value' => $stats->fastest_race_laps, 'color' => '#7c3aed'],
                    ['label' => 'Avg. Rating', 'value' => $avgRating ? number_format($avgRating, 0) : '—'],
                ] as $stat)
                <div class="col-6 col-sm-4 col-lg-3">
                    <div class="stat-box">
                        <div class="stat-num" @if(!empty($stat['color'])) style="color:{{ $stat['color'] }}" @endif>
                            {{ $stat['value'] }}
                        </div>
                        <div class="stat-label">{{ $stat['label'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
        @endif

        {{-- Track times --}}
        @if($trackTimes->isNotEmpty())
        <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <h2 class="fs-5 fw-black text-uppercase fst-italic text-dark mb-3">Track Times</h2>
            <div class="row g-2">
                @foreach($trackTimes as $time)
                @if($time->best_race_lap || $time->best_qualifying_lap)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="p-2 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
                        <div class="fw-bold text-dark mb-1" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">
                            {{ $time->track }}
                        </div>
                        @if($time->best_race_lap)
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge" style="background:#7c3aed20;color:#7c3aed;font-size:.65rem;font-weight:700">R</span>
                            <span style="font-family:monospace;font-size:.82rem;font-weight:700">{{ $time->best_race_lap }}</span>
                        </div>
                        @endif
                        @if($time->best_qualifying_lap)
                        <div class="d-flex align-items-center gap-1 mt-1">
                            <span class="badge" style="background:#f59e0b20;color:#f59e0b;font-size:.65rem;font-weight:700">Q</span>
                            <span style="font-family:monospace;font-size:.82rem;font-weight:700">{{ $time->best_qualifying_lap }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Hotlaps --}}
        @if($driver->hotlaps->isNotEmpty())
        <div class="bg-white rounded-3 shadow-sm p-4">
            <h2 class="fs-5 fw-black text-uppercase fst-italic text-dark mb-3">Hotlap History</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.85rem">
                    <thead style="border-bottom:2px solid #f3f4f6">
                        <tr>
                            <th class="fw-bold text-uppercase text-secondary pb-2" style="font-size:.7rem">Car</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2 text-center" style="font-size:.7rem">Best Lap</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2 text-center" style="font-size:.7rem">Laps</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2 text-end" style="font-size:.7rem">Rating</th>
                            <th class="fw-bold text-uppercase text-secondary pb-2 text-end" style="font-size:.7rem">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($driver->hotlaps->sortBy('best_lap') as $hotlap)
                        <tr style="border-bottom:1px solid #f9fafb">
                            <td class="fw-bold">{{ $hotlap->car_name ?? '—' }}</td>
                            <td class="text-center" style="font-family:monospace;font-weight:700">{{ $hotlap->best_lap }}</td>
                            <td class="text-center text-secondary">{{ $hotlap->laps_driven }}</td>
                            <td class="text-end fw-bold">{{ number_format($hotlap->xcl_rating_at_time, 0) }}</td>
                            <td class="text-end fw-bold">
                                @if($hotlap->rating_change !== null)
                                <span style="color:{{ $hotlap->rating_change >= 0 ? '#10b981' : '#ef4444' }}">
                                    {{ $hotlap->rating_change >= 0 ? '+' : '' }}{{ number_format($hotlap->rating_change, 0) }}
                                </span>
                                @else
                                <span class="text-secondary">—</span>
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
</main>
@endsection