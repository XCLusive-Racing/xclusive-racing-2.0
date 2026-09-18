{{-- Single driver/team entry in a xcl-drivers-grid. $waitlistPosition (optional) renders
     a "Waiting #N" badge instead of the usual team/class badge, for the Waiting List box. --}}
@php
    $driverRecord = $driverMap->get($reg->user->platform_id ?? '');
    $rankColor = $reg->user->rank($race->game)['color'];
@endphp
@if($driverRecord)
<a href="{{ route('drivers.show', $driverRecord) }}" class="xcl-drivers-grid__item text-decoration-none">
@else
<div class="xcl-drivers-grid__item">
@endif
    <div class="xcl-drivers-grid__avatar" style="{{ !$reg->user->avatarUrl() ? 'background:' . $race->gameColor() : '' }}">
        @if($reg->user->avatarUrl())
            <img src="{{ $reg->user->avatarUrl() }}" alt="{{ $reg->user->name }}">
        @else
            {{ strtoupper(substr($reg->user->name, 0, 1)) }}
        @endif
    </div>
    <div class="xcl-drivers-grid__info">
        <span class="xcl-drivers-grid__name" style="color:{{ $rankColor }}">{{ $reg->user->displayName() }}</span>
        @if($reg->teamEntry)
        <span class="xcl-drivers-grid__class-badge" style="background:#374151;color:#9ca3af;border:1px solid #4b5563">
            {{ $reg->teamEntry->team->name }}
        </span>
        @elseif($race->is_multiclass && $reg->raceClass)
        <span class="xcl-drivers-grid__class-badge" style="background:{{ $reg->raceClass->color }}22;color:{{ $reg->raceClass->color }};border:1px solid {{ $reg->raceClass->color }}44">
            {{ $reg->raceClass->name }}
        </span>
        @endif
        @isset($waitlistPosition)
        <span class="xcl-drivers-grid__class-badge" style="background:#f59e0b22;color:#fbbf24;border:1px solid #f59e0b44">
            #{{ $waitlistPosition }}
        </span>
        @endisset
    </div>
@if($driverRecord)
</a>
@else
</div>
@endif
