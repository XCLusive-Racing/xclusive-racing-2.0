@php $bop = $bop ?? null; @endphp

<div class="mb-3">
    <label class="form-label fw-bold" style="font-size:.8rem">Game <span class="text-danger">*</span></label>
    <select name="game" class="form-select form-select-sm @error('game') is-invalid @enderror">
        @foreach($games as $key => $label)
        <option value="{{ $key }}" {{ old('game', $bop?->game) === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error('game')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" style="font-size:.8rem">Car Model <span class="text-danger">*</span></label>
    <input type="text" name="car_model"
           value="{{ old('car_model', $bop?->car_model) }}"
           class="form-control form-control-sm @error('car_model') is-invalid @enderror"
           placeholder="e.g. Ferrari 296 GT3">
    @error('car_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" style="font-size:.8rem">Track <span class="text-secondary fw-normal">(leave empty = all tracks)</span></label>
    <input type="text" name="track"
           value="{{ old('track', $bop?->track) }}"
           class="form-control form-control-sm @error('track') is-invalid @enderror"
           placeholder="e.g. Spa-Francorchamps">
    @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-6">
        <label class="form-label fw-bold" style="font-size:.8rem">Ballast (kg) <span class="text-danger">*</span></label>
        <input type="number" name="ballast_kg"
               value="{{ old('ballast_kg', $bop?->ballast_kg ?? 0) }}"
               class="form-control form-control-sm @error('ballast_kg') is-invalid @enderror"
               min="-100" max="200">
        @error('ballast_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-6">
        <label class="form-label fw-bold" style="font-size:.8rem">Restrictor (%) <span class="text-danger">*</span></label>
        <input type="number" name="restrictor"
               value="{{ old('restrictor', $bop?->restrictor ?? 0) }}"
               class="form-control form-control-sm @error('restrictor') is-invalid @enderror"
               min="0" max="20">
        @error('restrictor')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold" style="font-size:.8rem">Notes</label>
    <textarea name="notes" rows="2"
              class="form-control form-control-sm @error('notes') is-invalid @enderror"
              placeholder="Optional remarks...">{{ old('notes', $bop?->notes) }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>