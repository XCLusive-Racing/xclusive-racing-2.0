<div class="cw-section">
    <p class="cw-section__title">Name</p>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="cw-label" for="b-name">Name</label>
            <input type="text" name="name" id="b-name" value="{{ old('name', $championship->name) }}"
                   class="form-control @error('name') is-invalid @enderror">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="cw-label" for="b-slug">URL Slug</label>
            <input type="text" name="slug" id="b-slug" value="{{ old('slug', $championship->slug) }}"
                   class="form-control @error('slug') is-invalid @enderror" style="font-family:monospace">
            @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        {{-- Stored as `tagline` (the line under the name) and `slogan` (the small line under that). --}}
        <div class="col-md-6">
            <label class="cw-label" for="b-subtitle">Subtitle <span class="cw-optional">(optional)</span></label>
            <input type="text" name="tagline" id="b-subtitle" value="{{ old('tagline', $championship->tagline) }}" maxlength="100"
                   class="form-control @error('tagline') is-invalid @enderror" placeholder="Season 1">
            <div class="cw-help">The line under the name.</div>
            @error('tagline') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="cw-label" for="b-slogan">Slogan <span class="cw-optional">(optional)</span></label>
            <input type="text" name="slogan" id="b-slogan" value="{{ old('slogan', $championship->slogan) }}" maxlength="150"
                   class="form-control @error('slogan') is-invalid @enderror" placeholder="Where real drivers are born">
            <div class="cw-help">The small line under the subtitle.</div>
            @error('slogan') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="cw-section">
    <p class="cw-section__title">Game &amp; Visibility</p>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="cw-label" for="b-game">Game</label>
            <select name="game" id="b-game" data-championship-game class="form-select @error('game') is-invalid @enderror">
                @foreach(['acc' => 'ACC Console', 'ac' => 'ACC PC', 'lmu' => 'Le Mans Ultimate', 'iracing' => 'iRacing'] as $value => $label)
                <option value="{{ $value }}" @selected(old('game', $championship->game) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="cw-label" for="b-platform">Platform</label>
            <select name="platform" id="b-platform" data-championship-platform class="form-select @error('platform') is-invalid @enderror">
                @foreach(['pc' => 'PC', 'console' => 'Console', 'cross' => 'Cross-Platform'] as $value => $label)
                <option value="{{ $value }}" @selected(old('platform', $championship->platform) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('platform') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="cw-label" for="b-visibility">Visibility</label>
            <select name="visibility" id="b-visibility" class="form-select @error('visibility') is-invalid @enderror">
                <option value="public" @selected(old('visibility', $championship->visibility) === 'public')>Public</option>
                <option value="unlisted" @selected(old('visibility', $championship->visibility) === 'unlisted')>Unlisted</option>
            </select>
            <div class="cw-help">Unlisted: only reachable by link.</div>
            @error('visibility') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="cw-section">
    <p class="cw-section__title">Page</p>
    <div class="row g-3">
        <div class="col-md-8">
            <x-media-picker name="image" label="Banner" :current="$championship->image" />
            <div class="cw-help mt-n2">Hero image on the championship page.</div>
        </div>
        <div class="col-md-4">
            <x-media-picker name="icon" label="Icon" :current="$championship->icon" />
            <div class="cw-help mt-n2">On the page and every round. Blank = league logo.</div>
        </div>
        <div class="col-12">
            <label class="cw-label">Description <span class="cw-optional">(optional)</span></label>
            <textarea name="description" rows="3" class="form-control rich-editor @error('description') is-invalid @enderror"
                      placeholder="Intro text on the championship page">{{ old('description', $championship->description) }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

@if($canApproveRating)
<div class="cw-section">
    <p class="cw-section__title">XCL Rating</p>
    <div class="d-flex flex-wrap align-items-center gap-3">
        {{-- A background fetch, not a submit: this sits inside the Basics form, and
             submitting it to approve-rating/revoke-rating would drop every unsaved
             field on the page. --}}
        <button type="button" id="xcl-rating-toggle-btn"
                data-enabled="{{ $championship->xcl_rating_enabled ? '1' : '0' }}"
                data-enable-url="{{ route('admin.leagues.championships.approve-rating', [$league, $championship]) }}"
                data-disable-url="{{ route('admin.leagues.championships.revoke-rating', [$league, $championship]) }}"
                data-name="{{ addslashes($championship->name) }}"
                class="cw-toggle {{ $championship->xcl_rating_enabled ? 'is-on' : '' }}">
            <span class="cw-toggle__dot"></span>
            <span data-label>{{ $championship->xcl_rating_enabled ? 'Enabled, click to disable' : 'Disabled, click to enable' }}</span>
        </button>
        <span class="cw-help mt-0">Rounds count for XCL rating. Unlocks the XCL-R Multiplier on Sessions.</span>
    </div>
    <div id="xcl-rating-toggle-error" class="text-danger mt-2" style="font-size:.78rem;display:none"></div>
</div>
@endif

<script>
// ACC's platform follows from the game (Championship::platformForGame(), also
// applied server-side): ACC PC is always PC, ACC Console never is.
(function () {
    var game     = document.querySelector('[data-championship-game]');
    var platform = document.querySelector('[data-championship-platform]');
    if (!game || !platform) return;

    function allowed(value) {
        if (game.value === 'ac') return value === 'pc';
        if (game.value === 'acc') return value !== 'pc';
        return true;
    }

    function updatePlatform() {
        Array.from(platform.options).forEach(function (o) {
            o.hidden = o.disabled = !allowed(o.value);
        });
        if (platform.selectedOptions[0]?.disabled) {
            platform.value = Array.from(platform.options).find(function (o) { return !o.disabled; }).value;
        }
    }
    game.addEventListener('change', updatePlatform);
    updatePlatform();
})();

(function () {
    var btn = document.getElementById('xcl-rating-toggle-btn');
    if (!btn) return;
    var errEl = document.getElementById('xcl-rating-toggle-error');

    function render(enabled) {
        btn.dataset.enabled = enabled ? '1' : '0';
        btn.classList.toggle('is-on', enabled);
        btn.querySelector('[data-label]').textContent = enabled ? 'Enabled, click to disable' : 'Disabled, click to enable';
    }

    btn.addEventListener('click', function () {
        var enabled = btn.dataset.enabled === '1';
        if (enabled && !confirm('Disable XCL Rating for ' + btn.dataset.name + '?')) return;

        btn.disabled = true;
        errEl.style.display = 'none';

        fetch(enabled ? btn.dataset.disableUrl : btn.dataset.enableUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        })
            .then(function (r) { if (!r.ok) throw new Error('Request failed'); return r.json(); })
            .then(function () { render(!enabled); })
            .catch(function () {
                errEl.textContent   = 'Could not update XCL Rating. Try again.';
                errEl.style.display = '';
            })
            .finally(function () { btn.disabled = false; });
    });
})();
</script>
