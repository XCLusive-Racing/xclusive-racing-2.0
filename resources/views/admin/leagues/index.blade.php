@extends('layouts.admin')

@section('title', 'Leagues')
@section('page-title', 'Leagues')

@section('page-actions')
    @if(auth()->user()->canManage())
    <a href="{{ route('admin.leagues.create') }}" class="btn btn-sm fw-black text-uppercase text-white px-3"
       style="background:#7c3aed;font-size:.78rem">
        + Add League
    </a>
    @endif
@endsection

@section('content')

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Leagues</div>
            <div class="text-secondary mt-1" style="font-size:.8rem">
                @if(auth()->user()->canManage())
                    Every league running on XCLusive, across every organiser.
                @else
                    Leagues you manage or steward.
                @endif
            </div>
        </div>
        <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
            {{ $leagues->count() }} {{ Str::plural('league', $leagues->count()) }}
        </span>
    </div>

    @if($leagues->isEmpty())
    <div class="p-5 text-center">
        <div style="font-size:2.5rem;margin-bottom:.75rem">🏁</div>
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">No leagues yet</div>
        <div class="text-secondary mt-2 mb-4" style="font-size:.82rem">
            @if(auth()->user()->canManage())
                Add the first league to give an external organiser their own space.
            @else
                You have not been added to a league yet.
            @endif
        </div>
        @if(auth()->user()->canManage())
        <a href="{{ route('admin.leagues.create') }}" class="btn fw-black text-uppercase text-white px-4"
           style="background:#7c3aed;font-size:.8rem">
            + Add League
        </a>
        @endif
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">League</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Slug</th>
                    <th class="fw-bold text-uppercase text-center d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Members</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:100px">Status</th>
                    <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leagues as $league)
                @php
                    $sc = ['active' => '#16a34a', 'draft' => '#f59e0b', 'archived' => '#6b7280'][$league->status] ?? '#6b7280';
                @endphp
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $league->primary_color }};flex-shrink:0"></span>
                            <div class="fw-bold text-dark">{{ $league->name }}</div>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span style="font-family:monospace;font-size:.8rem;color:#374151">{{ $league->slug }}</span>
                    </td>
                    <td class="text-center d-none d-lg-table-cell">
                        <span class="text-secondary">{{ $league->members_count ?? $league->memberships()->count() }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge" style="background:{{ $sc }}22;color:{{ $sc }};font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">
                            {{ ucfirst($league->status) }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="dropdown">
                            <button class="btn btn-sm fw-bold" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false"
                                    style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-size:.78rem;padding:4px 10px;line-height:1.2">
                                ···
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:150px;border-color:#e5e7eb">
                                <li>
                                    <a class="dropdown-item fw-bold" href="{{ route('admin.leagues.edit', $league) }}" style="color:#7c3aed">
                                        {{ auth()->user()->canManage() || auth()->user()->managesLeague($league) ? 'Edit' : 'View' }}
                                    </a>
                                </li>
                                @if(auth()->user()->canManage())
                                <li><hr class="dropdown-divider" style="border-color:#f3f4f6"></li>
                                <li>
                                    @if($league->status === 'archived')
                                    <form action="{{ route('admin.leagues.restore', $league) }}" method="POST" onsubmit="return false">
                                        @csrf
                                        <button type="button" class="dropdown-item fw-bold" style="color:#16a34a"
                                                onclick="this.closest('form').submit()">
                                            Restore to Draft
                                        </button>
                                    </form>
                                    @elseif(auth()->user()->isOwner())
                                    <form action="{{ route('admin.leagues.archive', $league) }}" method="POST" onsubmit="return false">
                                        @csrf
                                        <button type="button" class="dropdown-item fw-bold" style="color:#dc2626"
                                                onclick="xcDeleteSubmit(this.closest('form'), 'Archive {{ addslashes($league->name) }}? Its championships will no longer accept entries.')">
                                            Archive
                                        </button>
                                    </form>
                                    @endif
                                </li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
