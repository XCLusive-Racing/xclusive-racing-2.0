@props([
    'events'      => collect(),
    'teamDrivers' => collect(),
    'limit'       => 2,
    'title'       => 'UPCOMING EVENTS',
])

@php
    $events        = $events->take($limit);
    $teamDriverIds = $teamDrivers->pluck('id')->all();
@endphp

@if($events->isNotEmpty())
<section class="pro-upcoming-races">
    <div class="pro-section-label">{{ $title }}</div>

    <div class="pro-upcoming-list">
        @foreach($events as $event)
        @php
            $eventTeamDrivers = $event->participatingDrivers->whereIn('id', $teamDriverIds);
        @endphp
        <div class="pro-upcoming-card"
             data-countdown="{{ $event->starts_at->toIso8601String() }}"
             @if($event->image_url) style="background-image:url('{{ $event->image_url }}');background-size:cover;background-position:center" @endif>
            @if($event->image_url)<div class="pro-upcoming-card__img-overlay"></div>@endif

            <div class="pro-upcoming-card__info">
                <div class="pro-upcoming-card__title">
                    {{ $event->title }}
                    @if($event->watch_url)
                    <span class="xcl-sb-live-badge">LIVE</span>
                    @endif
                </div>
                @if($event->subtitle)
                <div class="pro-upcoming-card__sub">{{ $event->subtitle }}</div>
                @endif
                <div class="pro-upcoming-card__date">
                    {{ $event->starts_at->timezone('Europe/London')->format('d M Y · H:i T') }}
                </div>

                @if($eventTeamDrivers->isNotEmpty())
                <div class="xcl-sb-drivers-row" style="margin-top:.45rem"
                     title="{{ $eventTeamDrivers->pluck('name')->join(', ') }}">
                    @foreach($eventTeamDrivers->take(4) as $pd)
                    <span class="xcl-sb-drivers-row__avatar">
                        @if($pd->photo_url)
                        <img src="{{ $pd->photo_url }}" alt="{{ $pd->name }}">
                        @else
                        {{ $pd->initials() }}
                        @endif
                    </span>
                    @endforeach
                    @if($eventTeamDrivers->count() > 4)
                    <span class="xcl-sb-drivers-row__more">+{{ $eventTeamDrivers->count() - 4 }} more</span>
                    @endif
                </div>
                @endif
            </div>

            <div class="pro-upcoming-card__right">
                <div class="pro-upcoming-countdown">
                    <span data-cd-d>00</span><span class="pro-upcoming-countdown__sep">d</span>
                    <span data-cd-h>00</span><span class="pro-upcoming-countdown__sep">h</span>
                    <span data-cd-m>00</span><span class="pro-upcoming-countdown__sep">m</span>
                </div>
                @if($event->watch_url)
                <a href="{{ $event->watch_url }}" target="_blank" rel="noopener" class="pro-upcoming-watch">
                    ▶ WATCH LIVE
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
