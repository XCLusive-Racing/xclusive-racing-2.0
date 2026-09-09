@extends('layouts.admin')

@section('title', 'Points Schemes')
@section('page-title', 'Points Schemes')

@section('content')

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">All Points Schemes</div>
            <div class="text-secondary mt-1" style="font-size:.8rem">Read-only — see what every league runs, across every league. To create or copy one for your own league, go to Championships → Scoring → Manage points schemes.</div>
        </div>
    </div>

    @if($schemes->isEmpty())
    <div class="p-5 text-center">
        <div style="font-size:2.5rem;margin-bottom:.75rem">🏆</div>
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">No points schemes yet</div>
    </div>
    @else
    @foreach($schemes as $groupName => $group)
    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
        <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.75rem;letter-spacing:.06em;color:#7c3aed">{{ $groupName }}</p>
        <table class="table table-hover align-middle mb-0" style="font-size:.85rem">
            <tbody>
                @foreach($group as $scheme)
                @php $drivers = $scheme->points_map['drivers'] ?? []; ksort($drivers); @endphp
                <tr>
                    <td class="ps-0">
                        <div class="fw-bold text-dark">{{ $scheme->name }}</div>
                        <div class="text-secondary" style="font-size:.75rem">
                            {{ implode(', ', array_slice($drivers, 0, 8)) }}{{ count($drivers) > 8 ? '…' : '' }}
                        </div>
                    </td>
                    <td class="text-center d-none d-md-table-cell text-secondary" style="width:140px">
                        FL {{ $scheme->fastest_lap_points }} · Pole {{ $scheme->pole_points }}
                        @if(!empty($scheme->points_map['teams']))
                        <br><span style="color:#7c3aed">has team points</span>
                        @endif
                    </td>
                    <td class="text-end pe-0" style="width:90px">
                        <a href="{{ route('admin.points-schemes.export', $scheme) }}" class="fw-bold" style="color:#7c3aed;font-size:.8rem">CSV →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @endif
</div>

@endsection
