{{-- One event card (the .xcl-ec2 box) — used by the event list and the Popular Today row. --}}
@php
    // Shown when the event has no icon of its own. From the event's real
    // settings, not its title (an "Endurance" title used to read MULTICLASS).
    if ($race->championship_id) {
        $badge = strtoupper($race->round_type ?: 'Championship');
    } elseif (count($race->displayCarClasses()) > 1) {
        $badge = 'MULTICLASS';
    } else {
        $tagObj = $eventTags->firstWhere('slug', $race->event_tag);
        $badge  = strtoupper($tagObj?->name ?? 'Race');
    }
    $gameShort = match($race->game) {
        'acc'     => 'ACC',
        'lmu'     => 'LMU',
        'iracing' => 'iRACING',
        'ac'      => 'ACC PC',
        default   => strtoupper($race->game),
    };
    $ecPlatIcons = match($race->game) {
        'acc'     => [['fa-brands fa-playstation', 'PS5'], ['fa-brands fa-xbox', 'Xbox']],
        'lmu'     => [['fa-brands fa-steam', 'Steam'], ['fa-solid fa-desktop', 'PC']],
        'iracing' => [['fa-brands fa-steam', 'Steam'], ['fa-solid fa-desktop', 'PC']],
        'ac'      => [['fa-brands fa-steam', 'Steam'], ['fa-solid fa-desktop', 'PC']],
        default   => [['fa-solid fa-desktop', 'PC']],
    };
@endphp
<div class="xcl-ec2">
    <div class="xcl-ec2__img-wrap">
        {{-- Track image: full-bleed background --}}
        @if($race->image_url)
            <img src="{{ $race->image_url }}" alt="{{ $race->track ?? '' }}" loading="lazy" class="xcl-ec2__img">
        @else
            <div class="xcl-ec2__img-placeholder"></div>
        @endif
        {{-- Format image: centered overlay (max 60% of card width) --}}
        <div class="xcl-ec2__badge-wrap">
            @if($race->icon_url)
            <div class="xcl-ec2__icon-badge">
                <img src="{{ $race->icon_url }}" alt="{{ $race->title }}" class="xcl-ec2__icon-badge-img">
            </div>
            @else
            <div class="xcl-ec2__badge">
                <div class="xcl-ec2__badge-main">{{ $badge }}</div>
                <div class="xcl-ec2__badge-sub">{{ $gameShort }}</div>
            </div>
            @endif
        </div>

        {{-- Car class(es) + driver swap — top-left --}}
        @php $cardClasses = $race->displayCarClasses(); @endphp
        @if($cardClasses || $race->isDriverSwap())
        <div class="xcl-ec2__top-left-row">
            @foreach($cardClasses as $cardClass)
            @php [$classBg, $classText] = \App\Models\Race::classStyle($cardClass); @endphp
            <div class="xcl-ec2__class-badge" style="background:{{ $classBg }};color:{{ $classText }}">{{ $cardClass }}</div>
            @endforeach
            @if($race->isDriverSwap())
            <div class="xcl-ec2__class-badge" style="background:#7c3aed;color:#fff" title="Drivers share a car"><i class="fa-solid fa-people-arrows me-1"></i>DRIVER SWAP</div>
            @endif
        </div>
        @endif

        {{-- Registrations count — top-right --}}
        <div class="xcl-ec2__lobby">
            <x-icon-helmet />
            <span>{{ $race->is_endurance ? $race->team_entries_count : $race->displayedSignupCount() }} / {{ $race->max_drivers ?? '∞' }}</span>
        </div>

        {{-- Platform badges — bottom-left --}}
        <div class="xcl-sb-next__hero-platforms">
            @foreach($ecPlatIcons as [$icon, $label])
            <span class="xcl-sb-next__hero-platform-icon">
                <i class="{{ $icon }}"></i> {{ $label }}
            </span>
            @endforeach
        </div>

        {{-- Race length — bottom-right --}}
        @if($race->raceDurationMinutes())
        <div class="xcl-sb-next__hero-duration">
            <span class="xcl-sb-next__duration-badge">
                <i class="fa-solid fa-clock"></i> {{ $race->durationLabel() }}
            </span>
        </div>
        @endif
    </div>
    <div class="xcl-ec2__body">
        @php
            [$xclName,  $xclColor] = $race->xclTierInfo();
            [$xclMaxName, $xclMaxColor] = $race->xclMaxTierInfo();
            $weatherIcon = match($race->weather) {
                'dry'   => 'fa-sun',
                'wet'   => 'fa-cloud-rain',
                'mixed' => 'fa-cloud-sun-rain',
                'random' => 'fa-dice',
                default => null,
            };
        @endphp
        <div class="xcl-ec2__time-row">
            <div class="xcl-ec2__time">
                <x-local-time :at="$race->scheduled_at" format="weekday" upper /> /
                <x-local-time :at="$race->scheduled_at" format="time-tz" upper />
            </div>
        </div>
        <div class="xcl-ec2__badges-row">
            <span class="xcl-sb-badge xcl-sb-badge--game">{{ $gameShort }}</span>
            @if($race->sr_requirement)
            <span class="xcl-sb-badge xcl-sb-badge--sr">{{ number_format($race->sr_requirement, 1) }} SR</span>
            @endif
            @if($race->status === 'open')
                <span class="xcl-sb-badge xcl-sb-badge--open">OPEN</span>
            @else
                <span class="xcl-sb-badge xcl-sb-badge--closed">CLOSED</span>
            @endif
            @if($xclName)
            <span style="font-size:.6rem;font-weight:900;text-transform:capitalize;padding:2px 7px;border-radius:4px;border:1px solid {{ $xclColor }}66;background:{{ $xclColor }}22;color:{{ $xclColor }}">{{ $xclName }}+</span>
            @endif
            @if($xclMaxName)
            <span style="font-size:.6rem;font-weight:900;text-transform:capitalize;padding:2px 7px;border-radius:4px;border:1px solid {{ $xclMaxColor }}66;background:{{ $xclMaxColor }}22;color:{{ $xclMaxColor }}">{{ $xclMaxName }} max</span>
            @endif
        </div>
        <div class="xcl-ec2__meta">
            <x-local-time :at="$race->scheduled_at" format="date-short" />
            @if($race->track) | {{ $race->track }} @endif
            @if($weatherIcon)
                | <i class="fa-solid {{ $weatherIcon }}"></i> {{ ucfirst($race->weather) }}
            @endif
        </div>
        <a href="{{ route('events.show', $race) }}" class="xcl-see-event-btn">SEE EVENT</a>
    </div>
</div>
