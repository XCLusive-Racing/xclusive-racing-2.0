@extends('layouts.app')

@section('title', 'Hotlap Leaderboard - XCLusive Racing')

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="container" style="max-width:900px">

        <div class="mb-4">
            <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">Hotlap Leaderboard</h1>
            <p class="text-secondary mb-0">Fastest lap times submitted by XCL drivers</p>
        </div>

        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.875rem">
                    <thead style="border-bottom:2px solid #f3f4f6;background:#fafafa">
                        <tr>
                            <th class="fw-bold text-uppercase text-secondary ps-4 py-3" style="font-size:.7rem;letter-spacing:.06em;width:50px">#</th>
                            <th class="fw-bold text-uppercase text-secondary py-3" style="font-size:.7rem;letter-spacing:.06em">Driver</th>
                            <th class="fw-bold text-uppercase text-secondary py-3" style="font-size:.7rem;letter-spacing:.06em">Car</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-center" style="font-size:.7rem;letter-spacing:.06em">Best Lap</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-center d-none d-md-table-cell" style="font-size:.7rem;letter-spacing:.06em">Laps</th>
                            <th class="fw-bold text-uppercase text-secondary py-3 text-end pe-4 d-none d-md-table-cell" style="font-size:.7rem;letter-spacing:.06em">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hotlaps as $i => $hotlap)
                        @php $rank = ($hotlaps->currentPage() - 1) * $hotlaps->perPage() + $i + 1; @endphp
                        <tr style="border-bottom:1px solid #f9fafb">
                            <td class="ps-4 fw-bold text-secondary" style="font-size:.85rem">{{ $rank }}</td>
                            <td>
                                @if($hotlap->driver)
                                <a href="{{ route('drivers.show', $hotlap->driver) }}"
                                   class="fw-bold text-dark text-decoration-none">{{ $hotlap->driver_name }}</a>
                                @else
                                <span class="fw-bold text-dark">{{ $hotlap->driver_name }}</span>
                                @endif
                            </td>
                            <td class="text-secondary" style="font-size:.82rem">{{ $hotlap->car_name ?? '—' }}</td>
                            <td class="text-center fw-black" style="font-family:monospace;color:#7c3aed">{{ $hotlap->best_lap }}</td>
                            <td class="text-center text-secondary d-none d-md-table-cell">{{ $hotlap->laps_driven }}</td>
                            <td class="text-end pe-4 fw-bold d-none d-md-table-cell">{{ number_format($hotlap->xcl_rating_at_time, 0) }}</td>
                        </tr>
                        @endforeach

                        @if($hotlaps->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">No hotlaps recorded yet.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @if($hotlaps->hasPages())
            <div class="px-4 py-3 border-top" style="background:#fafafa">
                {{ $hotlaps->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
</main>
@endsection