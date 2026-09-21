@php
    $result = $result ?? null;
    $firstRace = $result?->races->first();
    $existing = $existing ?? [];
    $currentType = old('type', $result->type ?? 'race');
    // A driver is checked when saved on this result, or was checked before a validation error.
    $isAdded = fn ($id) => in_array((string) $id, array_map('strval', old('selected_drivers', array_keys($existing))), true);
@endphp

<div data-result-fields="esports" style="display:none">

    {{-- Type --}}
    <div class="mb-3">
        <label class="form-label fw-bold" style="font-size:.82rem">What are you adding? <span class="text-danger">*</span></label>
        <select name="type" data-result-type class="form-select @error('type') is-invalid @enderror">
            @foreach(\App\Models\Result::TYPES as $val => $label)
            <option value="{{ $val }}" {{ $currentType === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold" style="font-size:.82rem">
            <span data-type-fields="race">Race Date</span>
            <span data-type-fields="standings">Standings As Of</span>
            <span data-type-fields="final">Season End Date</span>
            <span class="text-danger">*</span>
        </label>
        <input type="date" name="event_date" value="{{ old('event_date', $firstRace?->race_date?->format('Y-m-d')) }}"
               class="form-control @error('event_date') is-invalid @enderror">
        @error('event_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold" style="font-size:.82rem">
            <span data-type-fields="race">Event / Series Name <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span></span>
            <span data-type-fields="standings final">Championship Name <span class="text-danger">*</span></span>
        </label>
        <input type="text" name="title" value="{{ old('title', $result->title ?? '') }}"
               class="form-control @error('title') is-invalid @enderror"
               placeholder="e.g. XCL Endurance Series">
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3" data-type-fields="standings">
        <label class="form-label fw-bold" style="font-size:.82rem">
            Round
            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
        </label>
        <input type="text" name="round_label" value="{{ old('round_label', $result->round_label ?? '') }}"
               class="form-control @error('round_label') is-invalid @enderror"
               placeholder="e.g. After round 4 of 8">
        @error('round_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3" data-type-fields="race">
        <label class="form-label fw-bold" style="font-size:.82rem">Track <span class="text-danger">*</span></label>
        <input type="text" name="track" value="{{ old('track', $firstRace?->track) }}"
               class="form-control @error('track') is-invalid @enderror"
               placeholder="e.g. Spa-Francorchamps">
        @error('track') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold" style="font-size:.82rem">
            Car Class
            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
        </label>
        <input type="text" name="car_class" value="{{ old('car_class', $firstRace?->car_class) }}"
               class="form-control @error('car_class') is-invalid @enderror"
               placeholder="e.g. GT3">
        @error('car_class') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Driver picker: a checkbox per driver (inside its card). The rows underneath are
         shown by the CSS block below, purely from the checkbox state. --}}
    @php $cssRules = []; @endphp
    <div class="mb-4">
        <label class="form-label fw-bold" style="font-size:.82rem">Drivers who competed <span class="text-danger">*</span></label>

        @foreach(\App\Models\TeamEvent::teamSubjectGames() as $subject => $game)
        @php $gameDrivers = $esportsDriversByGame[$game] ?? collect(); @endphp
        <div data-driver-group="{{ $subject }}" style="display:none">
            <div class="participating-drivers-panel">
                <p style="font-size:.78rem;color:#6b7280;margin-bottom:12px">Select every driver who took part</p>
                <div class="xcl-driver-picker__grid">
                    @forelse($gameDrivers as $driver)
                    <label class="xcl-driver-card" for="driver-cb-{{ $driver->id }}" data-driver-card>
                        <input type="checkbox" id="driver-cb-{{ $driver->id }}" name="selected_drivers[]"
                               value="{{ $driver->id }}" {{ $isAdded($driver->id) ? 'checked' : '' }}>
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
                    </label>
                    @empty
                    <p class="text-secondary" style="font-size:.8rem;grid-column:1/-1;margin:0">No drivers found for this game yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Result rows: hidden by default, one CSS rule per driver reveals its row --}}
            <div class="mt-3">
                <p data-result-empty="{{ $subject }}" style="font-size:.8rem;color:#9ca3af;margin:0">Select drivers above to enter their results</p>
                @foreach($gameDrivers as $driver)
                <div data-result-row="{{ $driver->id }}" class="mb-2" style="align-items:center;gap:.5rem">
                    <span style="flex:1;font-size:.85rem;font-weight:700">{{ $driver->name }}</span>
                    <input type="text" name="driver_positions[{{ $driver->id }}]"
                           value="{{ old('driver_positions.'.$driver->id, $existing[$driver->id]['position'] ?? '') }}"
                           class="form-control form-control-sm" style="max-width:110px"
                           placeholder="Pos, e.g. P4">
                    <input type="text" name="driver_points[{{ $driver->id }}]" data-type-fields="standings final"
                           value="{{ old('driver_points.'.$driver->id, $existing[$driver->id]['points'] ?? '') }}"
                           class="form-control form-control-sm" style="max-width:100px"
                           placeholder="Points">
                </div>
                @php
                    $cssRules[] = 'form:has(#driver-cb-'.$driver->id.':checked) [data-result-row="'.$driver->id.'"]{display:flex}';
                @endphp
                @endforeach
            </div>
        </div>
        @php
            $cssRules[] = 'form:has([data-driver-group="'.$subject.'"] input[name="selected_drivers[]"]:checked) [data-result-empty="'.$subject.'"]{display:none}';
        @endphp
        @endforeach

        @error('selected_drivers')
        <div class="text-danger" style="font-size:.78rem">{{ $message }}</div>
        @enderror
        @error('driver_positions')
        <div class="text-danger" style="font-size:.78rem">{{ $message }}</div>
        @enderror
    </div>

    <style>
        [data-result-row]{display:none}
        {!! implode("\n        ", $cssRules) !!}
    </style>

    <div class="mb-4">
        <label class="form-label fw-bold" style="font-size:.82rem">
            Notes
            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
        </label>
        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $result->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
