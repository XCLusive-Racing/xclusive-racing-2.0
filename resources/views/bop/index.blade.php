@extends('layouts.app')

@section('title', 'Balance of Performance - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:1100px;position:relative;z-index:1">

        {{-- Header --}}
        <div class="mb-4">
            <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">Balance of Performance</h1>
            <p class="text-secondary mb-0">Official BOP settings per game and track</p>
        </div>

        {{-- Game tabs --}}
        <div class="d-flex gap-2 flex-wrap mb-4">
            @foreach($games as $key => $label)
            <a href="{{ route('bop.index', ['game' => $key]) }}"
               class="btn btn-sm fw-bold text-uppercase px-4"
               style="font-size:.75rem;border-radius:20px;
                      {{ $activeGame === $key
                          ? 'background:#7c3aed;color:#fff;border:1px solid #7c3aed'
                          : 'background:#fff;color:#374151;border:1px solid #e5e7eb' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        {{-- Track filter --}}
        @if($tracks->isNotEmpty())
        <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
            <span class="fw-bold text-uppercase text-secondary" style="font-size:.72rem;letter-spacing:.06em">Track</span>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('bop.index', ['game' => $activeGame]) }}"
                   class="btn btn-sm fw-bold text-uppercase px-3"
                   style="font-size:.72rem;border-radius:20px;
                          {{ !$activeTrack
                              ? 'background:#1f2937;color:#fff;border:1px solid #1f2937'
                              : 'background:#fff;color:#374151;border:1px solid #e5e7eb' }}">
                    All tracks
                </a>
                @foreach($tracks as $track)
                <a href="{{ route('bop.index', ['game' => $activeGame, 'track' => $track]) }}"
                   class="btn btn-sm fw-bold text-uppercase px-3"
                   style="font-size:.72rem;border-radius:20px;
                          {{ $activeTrack === $track
                              ? 'background:#1f2937;color:#fff;border:1px solid #1f2937'
                              : 'background:#fff;color:#374151;border:1px solid #e5e7eb' }}">
                    {{ $track }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- BOP table --}}
        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
            @if($bops->isEmpty())
            <div class="text-center py-5 text-secondary" style="font-size:.85rem">
                No BOP data available for {{ $games[$activeGame] }} yet.
            </div>
            @else
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.875rem">
                    <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                        <tr>
                            <th class="fw-bold text-uppercase text-secondary ps-4 py-3" style="font-size:.7rem;letter-spacing:.06em">Car Model</th>
                            <th class="fw-bold text-uppercase text-secondary py-3" style="font-size:.7rem;letter-spacing:.06em">Track</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-end" style="font-size:.7rem;letter-spacing:.06em">Ballast (kg)</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-end" style="font-size:.7rem;letter-spacing:.06em">Restrictor</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 d-none d-md-table-cell pe-4" style="font-size:.7rem;letter-spacing:.06em">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bops as $bop)
                        <tr style="border-bottom:1px solid #f9fafb">
                            <td class="ps-4 fw-bold text-dark" style="font-size:.88rem">{{ $bop->car_model }}</td>
                            <td class="text-secondary" style="font-size:.82rem">{{ $bop->track ?? 'All tracks' }}</td>
                            <td class="text-end fw-bold" style="font-size:.88rem;color:{{ $bop->ballast_kg > 0 ? '#ef4444' : ($bop->ballast_kg < 0 ? '#10b981' : '#374151') }}">
                                {{ $bop->ballast_kg > 0 ? '+' : '' }}{{ $bop->ballast_kg }} kg
                            </td>
                            <td class="text-end fw-bold" style="font-size:.88rem;color:{{ $bop->restrictor > 0 ? '#f59e0b' : '#374151' }}">
                                {{ $bop->restrictor > 0 ? $bop->restrictor . '%' : '—' }}
                            </td>
                            <td class="text-secondary d-none d-md-table-cell pe-4" style="font-size:.8rem">{{ $bop->notes ?? '—' }}</td>
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