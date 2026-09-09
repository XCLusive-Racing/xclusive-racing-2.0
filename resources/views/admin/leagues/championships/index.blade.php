@extends('layouts.admin')

@section('title', $league->name . ' — Championships')
@section('page-title', $league->name . ' — Championships')

@section('page-actions')
    <form action="{{ route('admin.leagues.championships.store', $league) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-sm fw-black text-uppercase text-white px-3" style="background:#7c3aed;font-size:.78rem">
            + New Championship
        </button>
    </form>
@endsection

@section('content')

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">{{ $league->name }} Championships</div>
        </div>
        <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
            {{ $championships->count() }} {{ Str::plural('championship', $championships->count()) }}
        </span>
    </div>

    @if($championships->isEmpty())
    <div class="p-5 text-center">
        <div style="font-size:2.5rem;margin-bottom:.75rem">🏆</div>
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">No championships yet</div>
        <div class="text-secondary mt-2 mb-4" style="font-size:.82rem">Start the setup wizard to configure your first one.</div>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Name</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Game</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:120px">Status</th>
                    <th class="fw-bold text-uppercase text-center d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:110px">XCL Rating</th>
                    <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:100px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($championships as $c)
                @php
                    $sc = ['draft' => '#f59e0b', 'published' => '#2563eb', 'registration_open' => '#16a34a', 'registration_closed' => '#6b7280', 'running' => '#7c3aed', 'completed' => '#6b7280', 'cancelled' => '#dc2626'][$c->status] ?? '#6b7280';
                @endphp
                <tr>
                    <td class="ps-4 fw-bold text-dark">{{ $c->name }}</td>
                    <td class="d-none d-md-table-cell text-secondary">{{ strtoupper($c->game) }}</td>
                    <td class="text-center">
                        <span class="badge" style="background:{{ $sc }}22;color:{{ $sc }};font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">
                            {{ ucfirst(str_replace('_', ' ', $c->status)) }}
                        </span>
                    </td>
                    <td class="text-center d-none d-lg-table-cell">
                        @if($c->xcl_rating_enabled)
                        <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.65rem;padding:3px 7px;border-radius:5px;font-weight:700">On</span>
                        @else
                        <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.65rem;padding:3px 7px;border-radius:5px;font-weight:700">Off</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.leagues.championships.wizard', [$league, $c, 'basics']) }}" class="fw-bold" style="color:#7c3aed;font-size:.8rem">
                            Edit
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
