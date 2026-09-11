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
            <option value="ac" {{ old('game', $championship->game) === 'ac' ? 'selected' : '' }}>ACC PC</option>
            <option value="lmu" {{ old('game', $championship->game) === 'lmu' ? 'selected' : '' }}>Le Mans Ultimate</option>
            <option value="iracing" {{ old('game', $championship->game) === 'iracing' ? 'selected' : '' }}>iRacing</option>
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
        <x-media-picker name="image" label="Banner" :current="$championship->image" />
        <div class="form-text mt-n2" style="font-size:.72rem;color:#9ca3af">Shown as the hero image on the public championship page.</div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12">
        <label class="form-label">Description <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
        <textarea name="description" rows="3" class="form-control rich-editor @error('description') is-invalid @enderror"
                  placeholder="Shown as intro copy on the public championship page…">{{ old('description', $championship->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@if($canApproveRating)
<hr class="my-4">
<p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">XCL Rating</p>
<p class="text-secondary mb-3" style="font-size:.8rem">Unlocks XCL-R Multiplier on the Sessions step.</p>

<div class="row g-3 mb-1">
    <div class="col-12">
        {{-- formaction/formmethod send just this button's click to
             approve-rating/revoke-rating instead of this step's own Save &
             Continue action — the same routes the Review step's card already
             used (open to this championship's own league manager too, not
             XCL-admin-only any more), just placed as an option here instead
             of stranded below Save & Continue. Can't be a nested <form>
             (this is already inside the Basics one), so this is how it
             stays a real, separately-submitted action while living among
             the other fields. The enclosing form also carries @method('PUT')
             as a hidden _method field for its own normal save — that field
             rides along on every submit regardless of formmethod, so
             Laravel would otherwise still treat this as a PUT and 405 on
             these POST-only routes; the onclick blanks it out first. --}}
        @if($championship->xcl_rating_enabled)
        <button type="submit" formaction="{{ route('admin.leagues.championships.revoke-rating', [$league, $championship]) }}" formmethod="POST" formnovalidate
                class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-bold"
                style="cursor:pointer;font-size:.82rem;border:2px solid #7c3aed;background:#7c3aed18;color:#7c3aed"
                onclick="if(!confirm('Disable XCL Rating for {{ addslashes($championship->name) }}?')) return false; this.form.querySelector('input[name=_method]').value=''; return true;">
            Enabled — click to disable
        </button>
        @else
        <button type="submit" formaction="{{ route('admin.leagues.championships.approve-rating', [$league, $championship]) }}" formmethod="POST" formnovalidate
                class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-bold"
                style="cursor:pointer;font-size:.82rem;border:2px solid #e5e7eb;background:#fff;color:#374151"
                onclick="this.form.querySelector('input[name=_method]').value=''; return true;">
            Disabled — click to enable
        </button>
        @endif
    </div>
</div>
@endif

<hr class="my-4">
<p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Server <span class="fw-normal" style="text-transform:none">(optional)</span></p>

@if($servers->isEmpty())
<p class="text-secondary mb-0" style="font-size:.82rem">
    No servers assigned to {{ $league->name }} yet — an XCL admin needs to assign one from the League page before rounds can auto-push.
</p>
@else
<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label class="form-label">Default Server</label>
        <select name="ftp_server_id" class="form-select @error('ftp_server_id') is-invalid @enderror">
            <option value="">— No server assigned —</option>
            @foreach($servers as $srv)
            <option value="{{ $srv->id }}" {{ (string) old('ftp_server_id', $championship->ftp_server_id) === (string) $srv->id ? 'selected' : '' }}>{{ $srv->name }}</option>
            @endforeach
        </select>
        <div class="form-text" style="font-size:.72rem;color:#9ca3af">Pre-selected on every new round — still overridable per round in Add Round.</div>
        @error('ftp_server_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>
@endif

<hr class="my-4">
<p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule</p>
<p class="text-secondary mb-3" style="font-size:.8rem">
    Set the pattern rounds normally follow — Add Round will suggest each round's date and time from this, and you can still change any single round by hand.
</p>

<div class="row g-3">
@foreach(\App\Settings\ChampionshipSettingsSchema::fieldsForGroup('schedule') as $field)
    @include('admin.leagues.championships._field', ['field' => $field])
@endforeach
</div>

<script>
(function () {
    var recurrence = document.getElementById('f-schedule-recurrence');
    var dayWrap    = document.getElementById('f-schedule-day_of_week')?.closest('.mb-3');

    function updateDayVisibility() {
        if (!recurrence || !dayWrap) return;
        dayWrap.style.display = ['weekly', 'biweekly'].includes(recurrence.value) ? '' : 'none';
    }
    if (recurrence) {
        recurrence.addEventListener('change', updateDayVisibility);
        updateDayVisibility();
    }
})();
</script>
