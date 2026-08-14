@php
    $selectedDriverIds = $selectedDriverIds ?? [];
@endphp

<div class="mb-4" data-driver-picker style="display:none">
    <label class="form-label fw-bold" style="font-size:.82rem">Participating Drivers</label>

    @foreach(\App\Models\TeamEvent::teamSubjectGames() as $subject => $game)
    <div data-driver-group="{{ $subject }}" class="xcl-driver-picker__grid" style="display:none">
        @forelse(($esportsDriversByGame[$game] ?? collect()) as $driver)
        <div class="xcl-driver-card {{ in_array($driver->id, $selectedDriverIds) ? 'is-selected' : '' }}"
             data-driver-card
             data-driver-id="{{ $driver->id }}">
            <span class="xcl-driver-card__check"><i class="fa-solid fa-check"></i></span>
            <span class="xcl-driver-card__avatar">
                @if($driver->photo_url)
                <img src="{{ $driver->photo_url }}" alt="">
                @else
                {{ $driver->initials() }}
                @endif
            </span>
            <span class="xcl-driver-card__body">
                <span class="xcl-driver-card__name">{{ $driver->name }}</span>
                <span class="xcl-driver-card__badge">{{ \App\Models\EsportsDriver::gameLabel($driver->game) }}</span>
            </span>
        </div>
        @empty
        <p class="text-secondary" style="font-size:.8rem;grid-column:1/-1;margin:0">No drivers found for this game yet.</p>
        @endforelse
    </div>
    @endforeach

    <input type="hidden" name="participating_drivers" data-driver-picker-input
           value="{{ json_encode(array_values($selectedDriverIds)) }}">
</div>
