@extends('layouts.admin')

@section('title', 'Server Schedule')
@section('page-title', 'Server Schedule')

@section('page-actions')
    <a href="{{ route('admin.servers.index') }}" class="btn btn-sm fw-bold text-uppercase"
       style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-size:.78rem">
        ← Servers
    </a>
@endsection

@section('content')

@if($servers->isEmpty())
<div class="admin-card p-5 text-center">
    <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">No servers configured</div>
    <div class="text-secondary mt-2" style="font-size:.82rem">Add a server first before viewing the schedule.</div>
</div>
@else

<div class="row g-4">
    @foreach($servers as $server)
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">
                            {{ $server->name }}
                        </div>
                        <div class="text-secondary mt-1" style="font-size:.75rem">
                            @if($server->server_type === 'rolling')
                                Resets every {{ $server->reset_interval_minutes }}min from {{ str_pad($server->reset_start_hour, 2, '0', STR_PAD_LEFT) }}:00 UTC
                            @else
                                Manual restart
                            @endif
                        </div>
                    </div>
                    @if(!$server->active)
                    <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">Inactive</span>
                    @endif
                </div>
                <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
                    {{ $server->races->count() }} upcoming
                </span>
            </div>

            @if($server->races->isEmpty())
            <div class="px-4 py-5 text-center">
                <div class="text-secondary" style="font-size:.82rem">No upcoming races scheduled on this server.</div>
                <a href="{{ route('admin.races.create') }}" class="btn btn-sm fw-bold text-uppercase mt-3"
                   style="background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.3);font-size:.72rem">
                    + Schedule Race
                </a>
            </div>
            @else
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.875rem">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <tr>
                            <th class="fw-bold text-uppercase ps-4" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:160px">Date & Time (BST)</th>
                            <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Track</th>
                            <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:80px">Game</th>
                            <th class="fw-bold text-uppercase d-none d-lg-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:120px">Config</th>
                            <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:90px">Status</th>
                            <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:80px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($server->races as $race)
                        @php $bst = $race->scheduledAtUk(); @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark" style="font-size:.82rem">{{ $bst->format('D d M') }}</div>
                                <div class="text-secondary" style="font-size:.72rem">{{ $bst->format('H:i') }} BST</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $race->title ?: $race->track }}</div>
                                @if($race->title && $race->track !== $race->title)
                                <div class="text-secondary" style="font-size:.72rem">{{ $race->track }}</div>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell">
                                <span class="badge text-uppercase" style="background:#f3f4f6;color:#374151;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">
                                    {{ strtoupper($race->game) }}
                                </span>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                @if($race->config_push_status === 'pushed')
                                <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">Pushed</span>
                                @elseif($race->config_push_status === 'pending')
                                <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">Pending</span>
                                @elseif($race->config_push_status === 'failed')
                                <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">Failed</span>
                                @else
                                <span class="text-secondary" style="font-size:.72rem">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($race->status === 'open')
                                <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">Open</span>
                                @elseif($race->status === 'closed')
                                <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">Closed</span>
                                @else
                                <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">{{ ucfirst($race->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.races.show', $race) }}"
                                   class="btn btn-sm fw-bold"
                                   style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-size:.72rem;padding:3px 10px">
                                    View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

@endif

@endsection
