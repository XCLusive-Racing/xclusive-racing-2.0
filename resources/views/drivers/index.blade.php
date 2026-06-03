@extends('layouts.app')

@section('title', 'Driver Standings - XCLusive Racing')

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
    <div class="container" style="max-width:1100px">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">Driver Standings</h1>
                <p class="text-secondary mb-0">{{ number_format($drivers->total()) }} drivers ranked by XCL Rating</p>
            </div>
            <form method="GET" action="{{ route('drivers.index') }}" class="d-flex gap-2">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search gamertag…"
                       class="form-control form-control-sm"
                       style="width:220px;border-color:#e5e7eb;font-size:.85rem">
                <button class="btn btn-sm fw-bold text-white px-3" style="background:#7c3aed">Search</button>
                @if(request('q'))
                <a href="{{ route('drivers.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
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
                            <th class="fw-bold text-uppercase text-secondary py-3 text-end" style="font-size:.7rem;letter-spacing:.06em">XCL Rating</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-center" style="font-size:.7rem;letter-spacing:.06em">SR</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 d-none d-md-table-cell" style="font-size:.7rem;letter-spacing:.06em">Team</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-center d-none d-lg-table-cell" style="font-size:.7rem;letter-spacing:.06em">Races</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-center d-none d-lg-table-cell" style="font-size:.7rem;letter-spacing:.06em">Wins</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-center d-none d-lg-table-cell" style="font-size:.7rem;letter-spacing:.06em">Podiums</th>
                            <th class="py-3" style="width:48px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drivers as $i => $driver)
                        @php
                            $rank     = ($drivers->currentPage() - 1) * $drivers->perPage() + $i + 1;
                            $class    = $driver->class;
                            $meta     = $classMeta[$class] ?? $classMeta['rookie'];
                            $srClass  = $driver->sr_class;
                            $stats    = $driver->stats;
                        @endphp
                        <tr style="border-bottom:1px solid #f9fafb;transition:background .12s" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                            <td class="ps-4 fw-bold text-secondary" style="font-size:.85rem">{{ $rank }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($driver->country_code)
                                    <span class="text-secondary" style="font-size:.8rem">{{ $driver->country_code }}</span>
                                    @endif
                                    <a href="{{ route('drivers.show', $driver) }}"
                                       class="fw-bold text-dark text-decoration-none"
                                       style="font-size:.9rem">{{ $driver->gamertag }}</a>
                                    @if($driver->number)
                                    <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.7rem">#{{ $driver->number }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge fw-bold text-uppercase" style="background:{{ $meta['color'] }}20;color:{{ $meta['color'] }};border:1px solid {{ $meta['color'] }}40;font-size:.7rem">
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="text-end fw-black" style="font-size:.95rem;color:#111827">
                                {{ number_format($driver->xcl_rating, 0) }}
                            </td>
                            <td class="text-center">
                                <span class="fw-black" style="color:{{ $srClass['color'] }};font-size:.9rem">
                                    {{ $srClass['grade'] }}
                                </span>
                                <span class="text-secondary ms-1" style="font-size:.75rem">{{ number_format($driver->safety_rating, 2) }}</span>
                            </td>
                            <td class="text-secondary d-none d-md-table-cell" style="font-size:.82rem;max-width:160px">
                                <span class="text-truncate d-block">{{ $driver->team ?? '—' }}</span>
                            </td>
                            <td class="text-center fw-bold d-none d-lg-table-cell">{{ $stats?->total_races ?? '—' }}</td>
                            <td class="text-center fw-bold d-none d-lg-table-cell" style="color:#f59e0b">{{ $stats?->wins ?? '—' }}</td>
                            <td class="text-center fw-bold d-none d-lg-table-cell">{{ $stats?->podiums ?? '—' }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('drivers.show', $driver) }}"
                                   class="text-secondary" style="font-size:.8rem;text-decoration:none">→</a>
                            </td>
                        </tr>
                        @endforeach

                        @if($drivers->isEmpty())
                        <tr>
                            <td colspan="10" class="text-center py-5 text-secondary">No drivers found.</td>
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