@extends('layouts.admin')

@section('title', 'Points Schemes')
@section('page-title', 'Points Schemes')

@section('content')

@php
    $psPreview = function ($scheme, $count = 6) {
        $table = collect($scheme->points_table ?? [])->sortKeys();
        return $table->take($count)->map(fn ($pts, $pos) => $pos . ':' . rtrim(rtrim((string) $pts, '0'), '.'))->implode(', ');
    };
@endphp

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
                <tr>
                    <td class="ps-0">
                        <div class="fw-bold text-dark">{{ $scheme->name }}</div>
                        <div class="text-secondary" style="font-size:.75rem">
                            {{ $psPreview($scheme) }}{{ count($scheme->points_table ?? []) > 6 ? '…' : '' }}
                        </div>
                        @if($scheme->scope_note)
                        <div class="text-secondary fst-italic" style="font-size:.72rem">{{ $scheme->scope_note }}</div>
                        @endif
                    </td>
                    <td class="text-center d-none d-md-table-cell text-secondary text-capitalize" style="width:90px">{{ $scheme->type }}</td>
                    <td class="text-center d-none d-md-table-cell text-secondary" style="width:150px">
                        FL {{ $scheme->fastest_lap_points }} · Pole {{ $scheme->pole_points }} · Lead {{ $scheme->leading_lap_points }}
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
