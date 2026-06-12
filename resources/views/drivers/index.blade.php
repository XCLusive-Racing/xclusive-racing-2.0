@extends('layouts.app')

@section('title', 'Leaderboard - ' . config('xcl.name'))

@php
$classMeta = [
    'alien'    => ['label' => 'Alien',    'color' => '#10b981'],
    'platinum' => ['label' => 'Platinum', 'color' => '#7c3aed'],
    'gold'     => ['label' => 'Gold',     'color' => '#f59e0b'],
    'silver'   => ['label' => 'Silver',   'color' => '#9ca3af'],
    'bronze'   => ['label' => 'Bronze',   'color' => '#cd7f32'],
    'rookie'   => ['label' => 'Rookie',   'color' => '#ef4444'],
];
@endphp

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:1100px;position:relative;z-index:1">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">Leaderboard</h1>
                <p class="text-secondary mb-0">{{ number_format($drivers->total()) }} drivers ranked · {{ $gameInfo['label'] }}</p>
            </div>
            <form method="GET" action="{{ route('drivers.index') }}" class="d-flex gap-2 xcl-search-form">
                <input type="hidden" name="game" value="{{ $game }}">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search name or gamertag…"
                       class="form-control form-control-sm flex-fill"
                       style="border-color:#e5e7eb;font-size:.85rem;min-width:0">
                <button class="btn btn-sm fw-bold text-white px-3" style="background:#7c3aed">Search</button>
                @if(request('q'))
                <a href="{{ route('drivers.index', ['game' => $game]) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>

        {{-- Game tabs --}}
        <div class="d-flex gap-2 mb-4 flex-wrap">
            @foreach($games as $key => $info)
            <a href="{{ route('drivers.index', ['game' => $key] + (request('q') ? ['q' => request('q')] : [])) }}"
               class="btn btn-sm fw-bold text-uppercase"
               style="font-size:.75rem;border-radius:8px;{{ $game === $key
                   ? 'background:' . $info['color'] . ';color:#fff;border:2px solid ' . $info['color']
                   : 'background:#fff;color:#374151;border:2px solid #e5e7eb' }}">
                {{ $info['label'] }}
            </a>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.875rem">
                    <thead style="border-bottom:2px solid #f3f4f6;background:#fafafa">
                        <tr>
                            <th class="fw-bold text-uppercase text-secondary ps-4 py-3" style="font-size:.7rem;letter-spacing:.06em;width:60px">#</th>
                            <th class="fw-bold text-uppercase text-secondary py-3" style="font-size:.7rem;letter-spacing:.06em">Driver</th>
                            <th class="fw-bold text-uppercase text-secondary py-3" style="font-size:.7rem;letter-spacing:.06em">Class</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-end" style="font-size:.7rem;letter-spacing:.06em">Rating</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-center" style="font-size:.7rem;letter-spacing:.06em">SR</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 d-none d-md-table-cell" style="font-size:.7rem;letter-spacing:.06em">Team</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drivers as $i => $user)
                        @php
                            $rank    = ($drivers->currentPage() - 1) * $drivers->perPage() + $i + 1;
                            $rClass  = $user->rank($game);
                            $meta    = $classMeta[$rClass['slug']] ?? $classMeta['rookie'];
                            $srInfo  = $user->srGrade($game);
                            $elo     = (int) ($user->{$eloCol} ?? 0);
                            $sr      = (float) ($user->{$srCol} ?? 0);
                        @endphp
                        <tr style="border-bottom:1px solid #f9fafb;transition:background .12s" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                            <td class="ps-4 fw-bold text-secondary" style="font-size:.85rem">{{ $rank }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($user->country)
                                    <span class="text-secondary" style="font-size:.8rem">{{ strtoupper($user->country) }}</span>
                                    @endif
                                    @php $driverRecord = $driverMap->get($user->platform_id); @endphp
                                    @if($driverRecord)
                                    <a href="{{ route('drivers.show', $driverRecord->id) }}"
                                       class="fw-bold text-decoration-none"
                                       style="font-size:.9rem;color:#111827;transition:color .15s"
                                       onmouseover="this.style.color='#7c3aed'" onmouseout="this.style.color='#111827'">{{ $user->displayName() }}</a>
                                    @else
                                    <span class="fw-bold text-dark" style="font-size:.9rem">{{ $user->displayName() }}</span>
                                    @endif
                                    @if($user->is_supporter)
                                    <span title="Supporter" style="font-size:.75rem;color:#f59e0b;line-height:1">★</span>
                                    @endif
                                    @if($user->car_number)
                                    <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.7rem">#{{ $user->car_number }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge fw-bold text-uppercase" style="background:{{ $meta['color'] }}20;color:{{ $meta['color'] }};border:1px solid {{ $meta['color'] }}40;font-size:.7rem">
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="text-end fw-black" style="font-size:.95rem;color:#111827">
                                {{ number_format($elo) }}
                            </td>
                            <td class="text-center">
                                <span class="fw-black" style="color:{{ $srInfo['color'] }};font-size:.9rem">
                                    {{ $srInfo['grade'] }}
                                </span>
                                <span class="text-secondary ms-1" style="font-size:.75rem">{{ number_format($sr, 2) }}</span>
                            </td>
                            <td class="text-secondary d-none d-md-table-cell" style="font-size:.82rem;max-width:160px">
                                <span class="text-truncate d-block">{{ $user->team ?? '—' }}</span>
                            </td>
                        </tr>
                        @endforeach

                        @if($drivers->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">No drivers found.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($drivers->hasPages())
            <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between" style="background:#fafafa">
                <span class="text-secondary" style="font-size:.8rem">
                    Showing {{ $drivers->firstItem() }}–{{ $drivers->lastItem() }} of {{ number_format($drivers->total()) }}
                </span>
                {{ $drivers->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
</main>

@endsection