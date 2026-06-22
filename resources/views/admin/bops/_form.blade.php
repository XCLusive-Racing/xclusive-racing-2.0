@php $bop = $bop ?? null; @endphp

{{-- Game & Car --}}
<div class="px-4 pt-4 pb-3 border-bottom">
    <div class="fw-black text-uppercase mb-3" style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af">Car Identity</div>
    <div class="row g-3">
        <div class="col-sm-5">
            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Game <span class="text-danger">*</span></label>
            <select name="game" class="form-select @error('game') is-invalid @enderror" style="font-size:.85rem">
                @foreach($games as $key => $label)
                <option value="{{ $key }}" {{ old('game', $bop?->game) === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('game')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-sm-7">
            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Car Model <span class="text-danger">*</span></label>
            <input type="text" name="car_model"
                   value="{{ old('car_model', $bop?->car_model) }}"
                   class="form-control @error('car_model') is-invalid @enderror"
                   style="font-size:.85rem"
                   placeholder="e.g. Ferrari 296 GT3">
            @error('car_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- Track --}}
<div class="px-4 py-3 border-bottom">
    <div class="fw-black text-uppercase mb-3" style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af">Track</div>
    <div>
        <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">
            Track
            <span class="fw-normal text-secondary ms-1" style="font-size:.75rem">— leave empty to apply to all tracks</span>
        </label>
        <input type="text" name="track"
               value="{{ old('track', $bop?->track) }}"
               class="form-control @error('track') is-invalid @enderror"
               style="font-size:.85rem;max-width:320px"
               placeholder="e.g. spa">
        @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Ballast & Restrictor --}}
<div class="px-4 py-3 border-bottom" data-ballast-wrap>
    <div class="fw-black text-uppercase mb-3" style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af">Performance Adjustment</div>
    <div class="row g-3 align-items-start">
        <div class="col-sm-5">
            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Ballast (kg) <span class="text-danger">*</span></label>
            <input type="number" name="ballast_kg"
                   data-ballast-input
                   value="{{ old('ballast_kg', $bop?->ballast_kg ?? 0) }}"
                   class="form-control @error('ballast_kg') is-invalid @enderror"
                   style="font-size:.85rem"
                   min="-100" max="200">
            @error('ballast_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="mt-2 fw-black" style="font-size:1.1rem" data-ballast-display></div>
        </div>
        <div class="col-sm-5">
            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Restrictor (%) <span class="text-danger">*</span></label>
            <input type="number" name="restrictor"
                   value="{{ old('restrictor', $bop?->restrictor ?? 0) }}"
                   class="form-control @error('restrictor') is-invalid @enderror"
                   style="font-size:.85rem"
                   min="0" max="20">
            @error('restrictor')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- Notes --}}
<div class="px-4 pt-3 pb-4">
    <div class="fw-black text-uppercase mb-3" style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af">Notes</div>
    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Notes <span class="fw-normal text-secondary">(optional)</span></label>
    <textarea name="notes" rows="2"
              class="form-control @error('notes') is-invalid @enderror"
              style="font-size:.85rem"
              placeholder="Optional remarks...">{{ old('notes', $bop?->notes) }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>