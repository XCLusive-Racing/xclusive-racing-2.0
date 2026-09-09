<div class="row g-3 mb-3">
    <div class="col-sm-8">
        <label class="form-label">Name</label>
        <input type="text" name="name" value="{{ old('name', $championship->name) }}"
               class="form-control @error('name') is-invalid @enderror">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-sm-4">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $championship->slug) }}"
               class="form-control @error('slug') is-invalid @enderror" style="font-family:monospace">
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <label class="form-label">Game</label>
        <select name="game" class="form-select @error('game') is-invalid @enderror">
            <option value="acc" {{ old('game', $championship->game) === 'acc' ? 'selected' : '' }}>Assetto Corsa Competizione</option>
            <option value="lmu" {{ old('game', $championship->game) === 'lmu' ? 'selected' : '' }}>Le Mans Ultimate</option>
        </select>
        @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-sm-4">
        <label class="form-label">Platform</label>
        <select name="platform" class="form-select @error('platform') is-invalid @enderror">
            <option value="pc" {{ old('platform', $championship->platform) === 'pc' ? 'selected' : '' }}>PC</option>
            <option value="console" {{ old('platform', $championship->platform) === 'console' ? 'selected' : '' }}>Console</option>
            <option value="cross" {{ old('platform', $championship->platform) === 'cross' ? 'selected' : '' }}>Cross-Platform</option>
        </select>
        @error('platform') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-sm-4">
        <label class="form-label">Visibility</label>
        <select name="visibility" class="form-select @error('visibility') is-invalid @enderror">
            <option value="public" {{ old('visibility', $championship->visibility) === 'public' ? 'selected' : '' }}>Public</option>
            <option value="unlisted" {{ old('visibility', $championship->visibility) === 'unlisted' ? 'selected' : '' }}>Unlisted</option>
        </select>
        @error('visibility') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-sm-8">
        <label class="form-label">Banner</label>
        @if($championship->image_url)
        <div class="mb-2">
            <img src="{{ $championship->image_url }}" alt="" style="max-height:80px;border-radius:8px">
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="image_remove" value="1" class="form-check-input" id="image_remove">
            <label class="form-check-label" for="image_remove" style="font-size:.82rem">Remove current banner</label>
        </div>
        @endif
        <input type="file" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror">
        <div class="form-text" style="font-size:.72rem;color:#9ca3af">Shown as the hero image on the public championship page.</div>
        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
