@extends('layouts.admin')

@section('title', 'Championships')
@section('page-title', 'Championships')

@section('page-actions')
    <a href="{{ route('admin.championships.create') }}"
       class="btn btn-sm fw-bold text-uppercase text-white"
       style="background:#db2777;font-size:.78rem">
        + New Championship
    </a>
@endsection

@section('content')

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Championship</th>
                    <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Game</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Season</th>
                    <th class="fw-bold text-uppercase text-center d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Rounds</th>
                    <th class="fw-bold text-uppercase text-center d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Drivers</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Status</th>
                    <th class="pe-4"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($championships as $c)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $c->name }}</div>
                        @if($c->is_multiclass)
                            <span style="font-size:.7rem;color:#db2777;font-weight:700">MULTICLASS</span>
                        @endif
                    </td>
                    <td class="d-none d-sm-table-cell">
                        <span class="badge text-white fw-bold"
                              style="background:{{ $c->gameColor() }};font-size:.7rem;padding:5px 10px;border-radius:6px">
                            {{ $c->gameLabel() }}
                        </span>
                    </td>
                    <td class="d-none d-md-table-cell text-secondary" style="font-size:.82rem">{{ $c->season }}</td>
                    <td class="d-none d-lg-table-cell text-center fw-bold">{{ $c->rounds_count }}</td>
                    <td class="d-none d-lg-table-cell text-center fw-bold">
                        {{ $c->registrations_count }}{{ $c->max_drivers ? ' / ' . $c->max_drivers : '' }}
                    </td>
                    <td class="text-center">
                        @php
                            $colors = ['draft' => '#6b7280', 'active' => '#16a34a', 'finished' => '#2563eb'];
                            $color  = $colors[$c->status] ?? '#6b7280';
                        @endphp
                        <span class="badge fw-bold" style="background:{{ $color }}1a;color:{{ $color }};font-size:.7rem;padding:4px 10px;border-radius:20px">
                            {{ ucfirst($c->status) }}
                        </span>
                    </td>
                    <td class="pe-4 text-end">
                        <a href="{{ route('admin.championships.show', $c) }}"
                           class="btn btn-sm fw-bold text-uppercase text-white"
                           style="background:#7c3aed;font-size:.72rem;padding:5px 12px;border-radius:6px">
                            Open
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-secondary py-5" style="font-size:.875rem">
                        No championships yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
