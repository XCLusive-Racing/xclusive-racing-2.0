<div class="admin-form-card mb-4">
    <div class="px-4 pt-4 pb-3">
        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Format Info</p>

        <div class="row g-3 mb-3">
            <div class="col-sm-4">
                <label class="form-label">Game <span class="text-danger">*</span></label>
                <select name="game" class="form-select @error('game') is-invalid @enderror">
                    <option value="">Select game...</option>
                    @foreach(['acc' => 'ACC Console', 'lmu' => 'Le Mans Ultimate', 'iracing' => 'iRacing', 'ac' => 'AC Rally'] as $val => $label)
                        <option value="{{ $val }}" {{ old('game', $eventFormat->game ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('game')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-5">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $eventFormat->name ?? '') }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="e.g. Sprint Race">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-3">
                <label class="form-label">Sort Order <span class="text-danger">*</span></label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $eventFormat->sort_order ?? 0) }}"
                       class="form-control" min="0">
            </div>
        </div>

        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label">Formation Type</label>
                <input type="text" name="formation_type" value="{{ old('formation_type', $eventFormat->formation_type ?? '') }}"
                       class="form-control" placeholder="e.g. Short, Full - Nords (short)">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Server Preference</label>
                <input type="text" name="server_preference" value="{{ old('server_preference', $eventFormat->server_preference ?? '') }}"
                       class="form-control" placeholder="e.g. 1/2, 3, 4">
            </div>
            <div class="col-sm-3">
                <label class="form-label">XCL-R Multiplier <span class="text-danger">*</span></label>
                <input type="number" name="xcl_r_multiplier" value="{{ old('xcl_r_multiplier', $eventFormat->xcl_r_multiplier ?? 1.0) }}"
                       class="form-control @error('xcl_r_multiplier') is-invalid @enderror"
                       step="0.1" min="0.1" max="10" placeholder="1.0">
                @error('xcl_r_multiplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Sessions (minutes)</p>

        <div class="row g-3 mb-3">
            <div class="col">
                <label class="form-label">Practice <span class="text-danger">*</span></label>
                <input type="number" name="practice_mins" value="{{ old('practice_mins', $eventFormat->practice_mins ?? 0) }}"
                       class="form-control" min="0" max="999">
            </div>
            <div class="col">
                <label class="form-label">Qualifying <span class="text-danger">*</span></label>
                <input type="number" name="quali_mins" value="{{ old('quali_mins', $eventFormat->quali_mins ?? 0) }}"
                       class="form-control" min="0" max="999">
            </div>
            <div class="col">
                <label class="form-label">Race 1 <span class="text-danger">*</span></label>
                <input type="number" name="race1_mins" value="{{ old('race1_mins', $eventFormat->race1_mins ?? 0) }}"
                       class="form-control" min="1" max="999">
            </div>
            <div class="col">
                <label class="form-label">Quali 2 <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                <input type="number" name="quali2_mins" value="{{ old('quali2_mins', $eventFormat->quali2_mins ?? '') }}"
                       class="form-control" min="1" max="999" placeholder="—">
            </div>
            <div class="col">
                <label class="form-label">Race 2 <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                <input type="number" name="race2_mins" value="{{ old('race2_mins', $eventFormat->race2_mins ?? '') }}"
                       class="form-control" min="1" max="999" placeholder="—">
            </div>
        </div>
    </div>

    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Pitstop</p>

        <div class="row g-3">
            <div class="col-sm-4">
                <label class="form-label">Type <span class="text-danger">*</span></label>
                <select name="pitstop_type" class="form-select">
                    <option value="none"      {{ old('pitstop_type', $eventFormat->pitstop_type ?? 'none') === 'none'      ? 'selected' : '' }}>None</option>
                    <option value="fuel_only" {{ old('pitstop_type', $eventFormat->pitstop_type ?? 'none') === 'fuel_only' ? 'selected' : '' }}>Fuel Only</option>
                </select>
            </div>
            <div class="col-sm-4">
                <label class="form-label">Count <span class="text-danger">*</span></label>
                <input type="number" name="pitstop_count" value="{{ old('pitstop_count', $eventFormat->pitstop_count ?? 0) }}"
                       class="form-control" min="0" max="10">
            </div>
            <div class="col-sm-4">
                <label class="form-label">Min Stop Time <span class="fw-normal text-secondary" style="text-transform:none">(seconds)</span></label>
                <input type="number" name="min_stop_secs" value="{{ old('min_stop_secs', $eventFormat->min_stop_secs ?? '') }}"
                       class="form-control" min="1" placeholder="—">
            </div>
        </div>
    </div>
</div>
