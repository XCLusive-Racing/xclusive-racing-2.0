{{-- Shared by the linear and curved panels in form.blade.php. Only the active
     panel's fields are enabled at submit time (see the JS syncActivePanel())
     so "depth_type"/"depth_value"/"reference_field_size" — same names in both
     panels — never collide on the wire. --}}
<div class="row g-3">
    <div class="col-sm-5">
        <label class="form-label">Scoring Depth</label>
        <select data-depth-type name="depth_type" class="form-select">
            <option value="fixed" {{ old('depth_type', $config['depth_type'] ?? 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed number of positions</option>
            <option value="percentage" {{ old('depth_type', $config['depth_type'] ?? 'fixed') === 'percentage' ? 'selected' : '' }}>Percentage of the field</option>
        </select>
    </div>
    <div class="col-sm-3">
        <label class="form-label">&nbsp;</label>
        <input type="number" data-depth-value name="depth_value" value="{{ old('depth_value', $config['depth_value'] ?? 10) }}" class="form-control" min="1">
    </div>
    <div class="col-sm-4">
        <label class="form-label">Reference Field Size</label>
        <input type="number" data-reference-field-size name="reference_field_size" value="{{ old('reference_field_size', $config['reference_field_size'] ?? 30) }}" class="form-control" min="1" max="100">
    </div>
</div>
<div class="form-text mb-3" style="font-size:.72rem;color:#9ca3af">
    A percentage always resolves against that round's classified finishers, not the starting grid, so a retirement
    elsewhere in the field never changes what a finishing driver scores. Reference Field Size only sizes the stored
    preview table here — it has no effect on live scoring.
</div>
