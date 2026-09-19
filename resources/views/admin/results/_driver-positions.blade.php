@php
    $existingPositions = $existingPositions ?? [];
@endphp

<div class="mb-4" data-result-fields="esports" style="display:none">
    <label class="form-label fw-bold" style="font-size:.82rem">Driver Results</label>
    <p style="font-size:.78rem;color:#6b7280;margin-bottom:12px">
        Enter a finishing position for each driver who competed (e.g. P4, DNF). Leave blank for drivers who didn't take part.
    </p>

    @foreach(\App\Models\TeamEvent::teamSubjectGames() as $subject => $game)
    <div data-driver-group="{{ $subject }}" style="display:none">
        @forelse(($esportsDriversByGame[$game] ?? collect()) as $driver)
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="xcl-driver-card__avatar" style="width:32px;height:32px;flex-shrink:0">
                @if($driver->photo_url)
                <img src="{{ $driver->photo_url }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
                @else
                {{ $driver->initials() }}
                @endif
            </span>
            <span style="flex:1;font-size:.85rem">{{ $driver->name }}</span>
            <input type="text" name="driver_positions[{{ $driver->id }}]"
                   value="{{ old('driver_positions.' . $driver->id, $existingPositions[$driver->id] ?? '') }}"
                   class="form-control form-control-sm" style="max-width:140px"
                   placeholder="e.g. P4, DNF">
        </div>
        @empty
        <p class="text-secondary" style="font-size:.8rem">No drivers found for this game yet.</p>
        @endforelse
    </div>
    @endforeach

    @error('driver_positions')
    <div class="text-danger" style="font-size:.78rem">{{ $message }}</div>
    @enderror
</div>
