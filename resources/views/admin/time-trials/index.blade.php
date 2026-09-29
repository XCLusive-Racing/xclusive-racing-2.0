@extends('layouts.admin')

@section('title', 'Time Trials')
@section('page-title', 'Time Trials')

@section('page-actions')
    <a href="{{ route('admin.time-trials.create') }}" class="btn btn-sm fw-black text-uppercase text-white" style="background:#7c3aed;font-size:.78rem">
        + New Time Trial
    </a>
@endsection

@section('content')

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#dc2626">{{ session('error') }}</div>
@endif

<div class="admin-card mb-4 px-4 py-3 text-secondary" style="font-size:.82rem">
    Each week's Time Trial runs on its server as 2 minutes of practice and 30 minutes of qualifying, on a loop.
    The config and an open entry list with every member are uploaded every hour; the server picks them up at its
    own next restart (an upload never kicks anyone). Results are collected automatically, and only drivers who
    signed up on the website count. When the week ends, the classification is stored, rating points are awarded
    (+50 for the winner down to +1 for the last driver) and the counting laps are added to the All Time Records.
    Tip: publish the next week's event in time, the server switches track at its first restart after the start.
</div>

<div class="admin-card p-0 overflow-hidden">
    @if($events->isEmpty())
    <p class="text-secondary text-center py-4 mb-0" style="font-size:.85rem">No Time Trials yet.</p>
    @else
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.85rem">
            <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                <tr>
                    <th class="fw-bold text-uppercase ps-4 py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Event</th>
                    <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Window (UK)</th>
                    <th class="fw-bold text-uppercase py-3 d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Server</th>
                    <th class="fw-bold text-uppercase py-3 text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Drivers</th>
                    <th class="fw-bold text-uppercase py-3 d-none d-lg-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Last push</th>
                    <th class="fw-bold text-uppercase pe-4 py-3 text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                @php
                    $status = $event->status();
                    $statusColor = ['draft' => '#9ca3af', 'upcoming' => '#2563eb', 'live' => '#16a34a', 'closed' => '#f59e0b', 'finished' => '#6b7280'][$status];
                @endphp
                <tr style="border-bottom:1px solid #f9fafb">
                    <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $event->displayTitle() }}</div>
                        <div class="d-flex gap-2 align-items-center mt-1">
                            <span class="badge fw-bold text-uppercase" style="background:{{ $statusColor }};font-size:.62rem">{{ $status }}</span>
                            <span class="badge fw-bold" style="background:#f3e8ff;color:#7c3aed;font-size:.62rem">{{ $event->classLabel() }}</span>
                            <span class="text-secondary" style="font-size:.75rem">{{ $event->laps_count }} lap rows</span>
                        </div>
                    </td>
                    <td class="text-secondary" style="font-size:.8rem">
                        {{ $event->starts_at->timezone('Europe/London')->format('D d M H:i') }}<br>
                        {{ $event->ends_at->timezone('Europe/London')->format('D d M H:i') }}
                    </td>
                    <td class="text-secondary d-none d-md-table-cell" style="font-size:.8rem">{{ $event->server?->name ?? 'None' }}</td>
                    <td class="text-center fw-bold">{{ $event->registrations_count }}</td>
                    <td class="d-none d-lg-table-cell" style="font-size:.78rem">
                        @if($event->last_push_error)
                        <span class="fw-bold" style="color:#dc2626">{{ \Illuminate\Support\Str::limit($event->last_push_error, 60) }}</span>
                        @elseif($event->last_pushed_at)
                        <span class="text-secondary">{{ $event->last_pushed_at->timezone('Europe/London')->format('d M H:i') }} · {{ $event->last_entry_count }} entries</span>
                        @else
                        <span class="text-secondary">Not yet</span>
                        @endif
                        @if($event->results_error)
                        <div class="fw-bold" style="color:#dc2626">Results: {{ \Illuminate\Support\Str::limit($event->results_error, 60) }}</div>
                        @endif
                    </td>
                    <td class="pe-4 text-end">
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                            @if($event->is_published)
                            <a href="{{ route('time-trials.events.show', $event) }}" target="_blank" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.68rem;padding:4px 10px">View</a>
                            @endif
                            @if(! $event->finalized_at)
                            <a href="{{ route('admin.time-trials.edit', $event) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.68rem;padding:4px 10px">Edit</a>
                            <form action="{{ route('admin.time-trials.push', $event) }}" method="POST"
                                  onsubmit="return confirm('Push this Time Trial config and entry list to the server now?')">
                                @csrf
                                <button type="submit" class="btn btn-sm fw-bold text-uppercase text-white" style="font-size:.68rem;padding:4px 10px;background:#7c3aed">Push now</button>
                            </form>
                            <form action="{{ route('admin.time-trials.collect', $event) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.68rem;padding:4px 10px">Collect results</button>
                            </form>
                            @endif
                            @if(! $event->finalized_at && $event->laps_count === 0)
                            <form action="{{ route('admin.time-trials.destroy', $event) }}" method="POST" onsubmit="return confirm('Delete this Time Trial?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm fw-bold text-uppercase" style="font-size:.68rem;padding:4px 10px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($events->hasPages())
    <div class="px-4 py-3 border-top">{{ $events->links('pagination::bootstrap-5') }}</div>
    @endif
    @endif
</div>

@endsection
