{{-- One event card of the dashboard (events-sidebar): the Upcoming Events column and the
     Featured Events row. $event needs registrations_count (and team_entries_count for a
     team event). --}}
@php
    $upPlatLabel = match($event->game) {
        'acc'             => 'PS5 / XBOX',
        'lmu','iracing','ac' => 'PC / STEAM',
        default           => 'PC',
    };
    $upGameLabel = match($event->game) {
        'acc' => 'ACC', 'lmu' => 'LMU',
        'iracing' => 'iRACING', 'ac' => 'ACC PC',
        default => strtoupper($event->game),
    };
@endphp
<div class="xcl-sb-up-card"
     data-sb-game-card="{{ $event->game }}"
     data-countdown="{{ $event->scheduled_at->toIso8601String() }}">

    <div class="xcl-sb-up-card__img-wrap">
        @php
            $upPlaceholder = match($event->game) {
                'lmu'     => '/images/home/teams/XCLusive_Placeholder_lmu.png',
                'iracing' => '/images/home/teams/XCLusive_Placeholder_iRacing.png',
                default   => '/images/home/teams/XCLusive_Placeholder_ACC.png',
            };
        @endphp
        <img src="{{ $event->image_url ?? $upPlaceholder }}"
             alt="{{ $event->title }}" loading="lazy"
             class="xcl-sb-up-card__img">
        <div class="xcl-sb-up-card__img-gradient"></div>

        @if($event->icon_url)
        <div class="xcl-sb-up-card__icon-overlay">
            <img src="{{ $event->icon_url }}" alt="{{ $event->title }}">
        </div>
        @endif

        <div class="xcl-sb-up-card__meta-row">
            <div class="xcl-sb-countdown xcl-sb-countdown--small">
                <span data-cd-d>00</span>D&nbsp;<span data-cd-h>00</span>H&nbsp;<span data-cd-m>00</span>M
            </div>
            <div class="xcl-sb-lobby xcl-sb-lobby--small">
                <x-icon-helmet />
                <span>{{ $event->is_endurance ? $event->team_entries_count : $event->displayedSignupCount() }} / {{ $event->max_drivers ?? '∞' }}</span>
            </div>
        </div>
    </div>

    <div class="xcl-sb-up-card__footer">
        <div class="d-flex gap-1 flex-wrap align-items-center">
            <span class="xcl-sb-badge xcl-sb-badge--platform">{{ $upPlatLabel }}</span>
            <span class="xcl-sb-badge xcl-sb-badge--game">{{ $upGameLabel }}</span>
        </div>
        <a href="{{ route('events.show', $event) }}" class="xcl-sb-up-card__join">JOIN EVENT</a>
    </div>
</div>
