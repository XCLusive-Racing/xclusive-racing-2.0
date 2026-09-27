@extends('layouts.admin')

@section('title', 'Server Status')
@section('page-title', 'Server Status')

@section('page-actions')
    <a href="{{ route('admin.servers.index') }}" class="btn btn-sm fw-bold text-uppercase"
       style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-size:.78rem">
        ← Servers
    </a>
@endsection

@php
    $uk = fn ($time) => $time?->copy()->tz('Europe/London')->format('D d M, H:i');
    $health = [
        'warning'  => ['label' => 'Needs a look', 'bg' => '#fef3c7', 'fg' => '#92400e'],
        'ok'       => ['label' => 'OK',           'bg' => '#d1fae5', 'fg' => '#065f46'],
        'inactive' => ['label' => 'Inactive',     'bg' => '#f3f4f6', 'fg' => '#6b7280'],
    ];
    $pushColour = ['pushed' => '#16a34a', 'pending' => '#d97706', 'failed' => '#dc2626'];
    $warnings = $rows->where('health', 'warning')->count();
@endphp

@section('content')

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">All servers</div>
            <div class="text-secondary mt-1" style="font-size:.75rem">
                From what the scheduled pushes and result imports recorded — the servers themselves aren't contacted. Times are UK time.
            </div>
        </div>
        <span class="badge" style="background:{{ $warnings ? '#fef3c7' : '#d1fae5' }};color:{{ $warnings ? '#92400e' : '#065f46' }};font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
            {{ $warnings ? $warnings.' need a look' : 'All OK' }}
        </span>
    </div>

    @if($rows->isEmpty())
    <div class="p-5 text-center text-secondary" style="font-size:.82rem">No servers configured.</div>
    @else
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.82rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    @foreach(['Server', 'Last config push', 'Next race', 'Last results', 'Practice'] as $heading)
                    <th class="fw-bold text-uppercase {{ $loop->first ? 'ps-4' : '' }}" style="font-size:.7rem;letter-spacing:.06em;color:#9ca3af">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                @php $server = $row['server']; $h = $health[$row['health']]; @endphp
                <tr data-server-status="{{ $row['health'] }}">
                    <td class="ps-4" style="min-width:210px">
                        <div class="fw-bold text-dark">{{ $server->name }}</div>
                        <div class="text-secondary" style="font-size:.72rem">
                            {{ $server->league?->name ?? 'No league' }} · {{ strtoupper($server->platform ?? '') }} ·
                            {{ $server->server_type === 'rolling' ? 'Rolling restart' : 'Manual restart' }}
                        </div>
                        <span class="badge mt-1" style="background:{{ $h['bg'] }};color:{{ $h['fg'] }};font-size:.65rem;padding:3px 7px;border-radius:5px;font-weight:700">{{ $h['label'] }}</span>
                        @foreach($row['problems'] as $problem)
                        <div style="color:#b45309;font-size:.72rem;font-weight:700">⚠ {{ $problem }}</div>
                        @endforeach
                    </td>
                    <td style="min-width:190px">
                        @if($row['last_push'])
                        <a href="{{ route('admin.races.show', $row['last_push']) }}" class="fw-bold text-dark text-decoration-none">{{ $row['last_push']->title }}</a>
                        <div class="text-secondary" style="font-size:.72rem">{{ $uk($row['last_push']->config_pushed_at) }}</div>
                        @else
                        <span class="text-secondary">Never</span>
                        @endif
                        @foreach($row['failed_pushes'] as $race)
                        <div style="font-size:.72rem;color:#dc2626">
                            Failed: <a href="{{ route('admin.races.show', $race) }}" style="color:#dc2626">{{ $race->title }}</a>
                            @if($race->config_push_error)<span title="{{ $race->config_push_error }}">— {{ \Illuminate\Support\Str::limit($race->config_push_error, 60) }}</span>@endif
                        </div>
                        @endforeach
                    </td>
                    <td style="min-width:180px">
                        @if($row['next_race'])
                        <a href="{{ route('admin.races.show', $row['next_race']) }}" class="fw-bold text-dark text-decoration-none">{{ $row['next_race']->title }}</a>
                        <div class="text-secondary" style="font-size:.72rem">
                            {{ $uk($row['next_race']->scheduled_at) }}
                            @if($row['next_race']->config_push_status)
                            · <span style="color:{{ $pushColour[$row['next_race']->config_push_status] ?? '#6b7280' }};font-weight:700">config {{ $row['next_race']->config_push_status }}</span>
                            @endif
                        </div>
                        @else
                        <span class="text-secondary">Nothing scheduled</span>
                        @endif
                    </td>
                    <td style="min-width:180px">
                        @if($row['last_import'])
                        <div class="fw-bold text-dark">{{ $row['last_import']->race?->title ?? $row['last_import']->filename }}</div>
                        <div class="text-secondary" style="font-size:.72rem">{{ $uk($row['last_import']->created_at) }}</div>
                        @else
                        <span class="text-secondary">None imported yet</span>
                        @endif
                        @foreach($row['overdue_results'] as $race)
                        <div style="font-size:.72rem;color:#b45309">
                            No results: <a href="{{ route('admin.races.show', $race) }}" style="color:#b45309">{{ $race->title }}</a> ({{ $uk($race->scheduled_at) }})
                        </div>
                        @endforeach
                    </td>
                    <td style="min-width:180px">
                        @if($row['championship_practice'])
                        @php $cp = $row['championship_practice']; @endphp
                        <div class="fw-bold text-dark">24h: {{ $cp->name }}</div>
                        <div class="text-secondary" style="font-size:.72rem">
                            {{ $cp->practiceRace?->track }} · pushed {{ $uk($cp->practice_pushed_at) }}
                        </div>
                        @endif
                        @if($row['practice_session'])
                        @php $ps = $row['practice_session']; @endphp
                        <div class="{{ $row['championship_practice'] ? 'mt-1' : '' }} fw-bold text-dark">Event practice: {{ str_replace('_', ' ', $ps->status) }}</div>
                        <div class="text-secondary" style="font-size:.72rem">{{ $uk($ps->window_start) }} – {{ $uk($ps->window_end) }}</div>
                        @endif
                        @if($row['practice_error'])
                        <div style="font-size:.72rem;color:#dc2626" title="{{ $row['practice_error'] }}">{{ \Illuminate\Support\Str::limit($row['practice_error'], 70) }}</div>
                        @endif
                        @if(! $row['championship_practice'] && ! $row['practice_session'])
                        <span class="text-secondary">None</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
