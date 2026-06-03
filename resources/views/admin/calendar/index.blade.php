@extends('layouts.admin')

@section('title', $current->format('F Y') . ' — Calendar')
@section('page-title', 'Calendar')

@section('page-actions')
    <a href="{{ route('admin.races.create') }}"
       class="btn btn-sm btn-outline-secondary fw-bold text-uppercase">
        + Single Race
    </a>
@endsection

@section('content')

{{-- Month navigation --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">

    <div class="d-flex gap-3 flex-wrap" style="font-size:.8rem">
        @foreach([['acc','#7c3aed','ACC'],['lmu','#db2777','LMU'],['iracing','#2563eb','iRacing']] as [$g,$c,$l])
        <div class="d-flex align-items-center gap-2">
            <span class="rounded" style="width:10px;height:10px;background:{{ $c }};display:inline-block;flex-shrink:0"></span>
            <span class="fw-bold text-secondary text-uppercase" style="font-size:.68rem">{{ $l }}</span>
        </div>
        @endforeach
    </div>

    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.calendar', ['year' => $prev->year, 'month' => $prev->month]) }}"
           class="btn btn-sm btn-outline-secondary fw-bold px-3">‹</a>
        <span class="fw-black text-uppercase fst-italic text-dark" style="min-width:160px;text-align:center;font-size:1rem">
            {{ $current->format('F Y') }}
        </span>
        <a href="{{ route('admin.calendar', ['year' => $next->year, 'month' => $next->month]) }}"
           class="btn btn-sm btn-outline-secondary fw-bold px-3">›</a>
    </div>
</div>

{{-- Calendar grid --}}
<div class="admin-card overflow-hidden">

    {{-- Day headers --}}
    <div class="d-grid" style="grid-template-columns:repeat(7,1fr);border-bottom:1px solid #e5e7eb">
        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dayLabel)
        <div class="text-center py-3 fw-black text-uppercase"
             style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af;
                    border-right:{{ $loop->last ? 'none' : '1px solid #f3f4f6' }}">
            {{ $dayLabel }}
        </div>
        @endforeach
    </div>

    @php
        $gridStart = $current->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::MONDAY);
        $gridEnd   = $current->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $today     = \Carbon\Carbon::today();
        $weeks     = $gridStart->diffInWeeks($gridEnd) + 1;
        $cursor    = $gridStart->copy();
    @endphp

    @for($w = 0; $w < $weeks; $w++)
    <div class="d-grid" style="grid-template-columns:repeat(7,1fr)">
        @for($d = 0; $d < 7; $d++)
        @php
            $day         = $cursor->copy();
            $dateKey     = $day->format('Y-m-d');
            $isThisMonth = $day->month === $current->month;
            $isToday     = $day->isSameDay($today);
            $dayRaces    = $races->get($dateKey, collect());
            $cursor->addDay();
        @endphp

        <div style="min-height:110px;
                    border-right:{{ $d === 6       ? 'none' : '1px solid #f3f4f6' }};
                    border-bottom:{{ $w === $weeks-1 ? 'none' : '1px solid #f3f4f6' }};
                    background:{{ !$isThisMonth ? '#fafafa' : 'white' }};
                    padding:.5rem .4rem .4rem">

            {{-- Day number + quick-add button --}}
            <div class="d-flex align-items-center justify-content-between mb-1">
                @if($isToday)
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white fw-black"
                          style="width:24px;height:24px;font-size:.75rem;background:#7c3aed;flex-shrink:0">
                        {{ $day->day }}
                    </span>
                @else
                    <span class="fw-bold" style="font-size:.78rem;color:{{ $isThisMonth ? '#111827' : '#d1d5db' }};line-height:24px;padding:0 2px">
                        {{ $day->day }}
                    </span>
                @endif

                @if($isThisMonth)
                <a href="{{ route('admin.races.create', ['date' => $dateKey]) }}"
                   class="text-decoration-none d-flex align-items-center justify-content-center rounded"
                   style="width:18px;height:18px;color:#9ca3af;flex-shrink:0;transition:all .15s"
                   title="Add race on {{ $day->format('d M') }}"
                   onmouseover="this.style.background='#7c3aed';this.style.color='white'"
                   onmouseout="this.style.background='transparent';this.style.color='#9ca3af'">
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </a>
                @endif
            </div>

            {{-- Events --}}
            @foreach($dayRaces as $race)
                @include('admin.calendar._event_pill', ['race' => $race])
            @endforeach

        </div>
        @endfor
    </div>
    @endfor

</div>

{{-- Month summary --}}
@php $totalThisMonth = $races->flatten()->count(); @endphp
@if($totalThisMonth > 0)
<div class="mt-3 text-secondary" style="font-size:.8rem">
    {{ $totalThisMonth }} event{{ $totalThisMonth !== 1 ? 's' : '' }} in {{ $current->format('F Y') }}
</div>
@endif

@endsection