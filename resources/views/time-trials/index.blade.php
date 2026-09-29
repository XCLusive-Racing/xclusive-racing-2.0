@extends('layouts.app')

@section('title', 'Time Trials - ' . config('xcl.name'))

@section('content')
<main class="xcl-page xcl-tt pb-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">
        <div class="pt-4 mb-4">
            <h1 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">TIME TRIALS</h1>
            <div class="section-divider mb-3" style="margin-left:0"></div>
            <p class="xcl-tt__lead mb-0">A new track every week, and the all time records of every track.</p>
        </div>

        {{-- This week's event --}}
        @if($event)
        <a href="{{ route('time-trials.events.show', $event) }}" class="xcl-tt__weekly mb-4">
            <div>
                <span class="xcl-tt__status xcl-tt__status--{{ $event->status() }}">{{ $event->status() === 'live' ? 'Live now' : 'Coming up' }}</span>
                <h2 class="xcl-tt__weekly-title">{{ $event->displayTitle() }}</h2>
                <p class="xcl-tt__muted mb-0">
                    {{ $event->classLabel() }} ·
                    @if($event->status() === 'live')
                    closes <x-local-time :at="$event->ends_at" format="dm-time" />
                    @else
                    opens <x-local-time :at="$event->starts_at" format="dm-time" />
                    @endif
                    · {{ $event->registrations_count }} {{ \Illuminate\Support\Str::plural('driver', $event->registrations_count) }}
                </p>
            </div>
            <span class="btn fw-black text-uppercase text-white xcl-tt__cta">Sign up and standings</span>
        </a>
        @endif
        @if($lastEvent)
        <p class="xcl-tt__muted small mb-4">
            Last week: <a href="{{ route('time-trials.events.show', $lastEvent) }}" class="xcl-tt__link">{{ $lastEvent->displayTitle() }} results</a>
        </p>
        @endif

        <h2 id="records" class="xcl-tt__section-title">All Time Records</h2>
        <p class="xcl-tt__muted mb-3">One row per driver per car. Every counting lap from a weekly Time Trial is added when the week ends.</p>

        @include('time-trials._boards', ['url' => fn ($key) => route('time-trials.index', ['platform' => $key]) . '#records'])

        @include('time-trials._bop-note')

        @if($tracks->isEmpty())
        <div class="xcl-tt__empty">No {{ $boards[$board]['label'] }} lap times yet.</div>
        @else
        <div class="xcl-tt__tracks">
            @foreach($tracks as $t)
            <a href="{{ route('time-trials.show', ['track' => $t['key'], 'platform' => $board]) }}" class="xcl-tt__track">
                <div class="xcl-tt__track-banner">
                    @if($t['image'])
                    <img src="{{ $t['image'] }}" alt="" loading="lazy">
                    @endif
                </div>
                <div class="xcl-tt__track-body">
                    <h2 class="xcl-tt__track-name">{{ $t['name'] }}</h2>
                    <div class="xcl-tt__track-record">
                        <span class="xcl-tt__time">{{ \App\Models\TimeTrialLap::formatLap($t['record']->lap_time_ms) }}</span>
                        <span class="xcl-tt__muted">{{ $t['record']->driver_name }}</span>
                    </div>
                    <div class="xcl-tt__muted small">
                        {{ $t['record']->car?->label() ?? 'Car #' . $t['record']->car_id }}
                        · {{ number_format($t['drivers']) }} {{ \Illuminate\Support\Str::plural('driver', $t['drivers']) }}
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</main>
@endsection
