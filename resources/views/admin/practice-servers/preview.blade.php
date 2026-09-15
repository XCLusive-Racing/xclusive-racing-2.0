@extends('layouts.admin')

@section('title', 'Preview — ' . ($race->title ?? 'Practice Session'))
@section('page-title', 'Practice Server Preview')

@section('page-actions')
    <a href="{{ route('admin.practice-servers.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

@php
$statusStyles = [
    'scheduled' => ['#eff6ff', '#1d4ed8', '#bfdbfe'],
    'pushing'   => ['#fefce8', '#a16207', '#fde68a'],
    'live'      => ['#f0fdf4', '#16a34a', '#bbf7d0'],
    'completed' => ['#f9fafb', '#6b7280', '#e5e7eb'],
    'failed'    => ['#fef2f2', '#dc2626', '#fecaca'],
    'cancelled' => ['#f9fafb', '#9ca3af', '#e5e7eb'],
    'too_late'  => ['#fff7ed', '#c2410c', '#fed7aa'],
];
[$bg, $color, $border] = $statusStyles[$session->status] ?? ['#f9fafb', '#374151', '#e5e7eb'];
@endphp

<div class="admin-card mb-4">
    <div class="px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:.95rem">
                {{ $race->title ?? 'Deleted event' }}
            </div>
            <p class="text-secondary mb-0" style="font-size:.78rem">
                {{ $session->window_start->timezone('Europe/London')->format('d M H:i') }}
                &rarr;
                {{ $session->window_end->timezone('Europe/London')->format('H:i T') }}
                &nbsp;&middot;&nbsp;
                {{ $practiceServer->name ?? 'Practice server' }}
                @if($ftpServer)
                    &nbsp;&middot;&nbsp; <span style="font-family:monospace">{{ $ftpServer->host }}{{ $cfgPath }}</span>
                @else
                    &nbsp;&middot;&nbsp; <span class="text-danger">no FTP server assigned</span>
                @endif
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge fw-bold text-uppercase" style="background:{{ $bg }};color:{{ $color }};border:1px solid {{ $border }};font-size:.68rem;padding:4px 10px">
                {{ strtoupper($session->status) }}
            </span>
            @if(in_array($session->status, ['scheduled', 'failed', 'live'], true))
            <form action="{{ route('admin.practice-servers.push', $session) }}" method="POST"
                  onsubmit="return confirm('Push this practice config now?')">
                @csrf
                <button type="submit" class="btn btn-sm fw-black text-uppercase text-white" style="background:#7c3aed;font-size:.72rem;padding:6px 14px">
                    Push Now →
                </button>
            </form>
            @endif
        </div>
    </div>

    @if($session->last_error)
    <div class="px-4 py-2" style="border-top:1px solid #fecaca;background:#fef2f2">
        <span class="fw-bold text-uppercase" style="font-size:.65rem;letter-spacing:.05em;color:#dc2626">Last error:</span>
        <span style="font-size:.78rem;color:#7f1d1d">{{ $session->last_error }}</span>
    </div>
    @endif

    <div class="px-4 py-3 d-flex flex-wrap gap-4" style="border-top:1px solid #f3f4f6">
        <div>
            <div class="fw-bold text-uppercase" style="font-size:.65rem;letter-spacing:.05em;color:#9ca3af">Entries in this preview</div>
            <div class="fw-black" style="font-size:1.1rem;color:{{ $entryListResult->entryCount > $practiceServer->max_car_slots ? '#dc2626' : '#111827' }}">
                {{ $entryListResult->entryCount }} / {{ $practiceServer->max_car_slots }} car slots
            </div>
        </div>
        @if($entryListResult->skippedCount > 0)
        <div>
            <div class="fw-bold text-uppercase" style="font-size:.65rem;letter-spacing:.05em;color:#9ca3af">Skipped (no platform ID)</div>
            <div class="fw-black" style="font-size:1.1rem;color:#c2410c">{{ $entryListResult->skippedCount }}</div>
        </div>
        @endif
        @php $overflow = max(0, $entryListResult->entryCount - $practiceServer->max_car_slots); @endphp
        @if($overflow > 0)
        <div>
            <div class="fw-bold text-uppercase" style="font-size:.65rem;letter-spacing:.05em;color:#9ca3af">Overflow vs. connection headroom</div>
            <div class="fw-black" style="font-size:1.1rem;color:{{ $overflow > $gap ? '#dc2626' : '#111827' }}">
                {{ $overflow }} / {{ $gap }}
            </div>
        </div>
        @endif
    </div>
    <p class="text-secondary px-4 pb-3 mb-0" style="font-size:.72rem">
        This is a live dry run — it recomputes the files from current data but never contacts the FTP server. The actual push may differ if entries or race config change before then.
        @if($overflow > 0)
            {{ $overflow }} {{ \Illuminate\Support\Str::plural('entry', $overflow) }} won't get a car (over the {{ $practiceServer->max_car_slots }} car-slot limit){{ $overflow > $gap ? ' and may not even be able to connect — signups exceed the connection headroom too' : '' }}.
        @endif
    </p>
</div>

<div class="admin-card p-0 overflow-hidden">
    <div data-tabs data-default-tab="event.json">
        <div class="d-flex border-bottom px-2 flex-wrap" style="background:#f9fafb">
            @foreach($files as $filename => $content)
            <button data-tab-btn="{{ $filename }}"
                    class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                    style="font-size:.75rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent;font-family:monospace">
                {{ $filename }}
            </button>
            @endforeach
        </div>

        @foreach($files as $filename => $content)
        <div data-tab-panel="{{ $filename }}" style="display:none">
            <pre class="mb-0" style="font-family:monospace;font-size:.8rem;line-height:1.5;padding:1.25rem;background:white;max-height:70vh;overflow:auto">{{ $content }}</pre>
        </div>
        @endforeach
    </div>
</div>

@endsection
