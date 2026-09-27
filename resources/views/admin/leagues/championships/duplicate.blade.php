@extends('layouts.admin')

@section('title', 'Duplicate — ' . $championship->name)
@section('page-title', $league->name . ' — Duplicate Championship')

@section('page-actions')
    <a href="{{ route('admin.leagues.championships.index', $league) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back to Championships
    </a>
@endsection

@section('content')

<form action="{{ route('admin.leagues.championships.duplicate.store', [$league, $championship]) }}" method="POST">
    @csrf

    <div class="admin-card mb-4">
        <div class="px-4 pt-4 pb-2">
            <p class="fw-black text-uppercase fst-italic mb-1" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">
                New season of {{ $championship->name }}
            </p>
            <p class="text-secondary mb-0" style="font-size:.78rem">
                Copies every setting (sessions, format, scoring, requirements, penalties, success ballast), the classes and the default server.
                Entries, results, penalties, per-driver ballast adjustments and the XCL rating approval start over. The copy starts as a draft.
            </p>
        </div>

        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <div class="row g-3">
                <div class="col-sm-8">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $championship->name) }}"
                           class="form-control @error('name') is-invalid @enderror" required maxlength="255">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Season <span class="text-danger">*</span></label>
                    <input type="number" name="season" value="{{ old('season', (int) $championship->season + 1) }}"
                           class="form-control @error('season') is-invalid @enderror" required min="2000" max="2100">
                    @error('season')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            @if($rounds->isEmpty())
            <p class="text-secondary mb-0" style="font-size:.78rem">This championship has no rounds to copy.</p>
            @else
            <div class="form-check mb-3">
                <input type="hidden" name="copy_rounds" value="0">
                <input type="checkbox" name="copy_rounds" value="1" id="dup-copy-rounds" class="form-check-input"
                       {{ old('copy_rounds', '1') === '1' ? 'checked' : '' }}>
                <label for="dup-copy-rounds" class="form-check-label fw-bold" style="font-size:.85rem">
                    Copy the {{ $rounds->count() }} {{ Str::plural('round', $rounds->count()) }}
                </label>
                <div class="form-text" style="font-size:.72rem;color:#9ca3af">
                    Same tracks, sessions, weather and servers. The gaps between rounds stay the same, and start times stay the same in UK time.
                </div>
            </div>
            <div class="row g-3" id="dup-first-round">
                <div class="col-sm-5">
                    <label class="form-label">Round 1 starts <span class="fw-normal text-secondary" style="text-transform:none">(UK time)</span></label>
                    <input type="datetime-local" name="first_round_at" step="1800"
                           value="{{ old('first_round_at', $suggestedStart) }}"
                           class="form-control @error('first_round_at') is-invalid @enderror">
                    <div class="form-text" style="font-size:.72rem;color:#9ca3af">Suggested: a week after the last round.</div>
                    @error('first_round_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Duplicate</button>
        <a href="{{ route('admin.leagues.championships.index', $league) }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
    </div>
</form>

<script>
    (function () {
        var box = document.getElementById('dup-copy-rounds');
        var dates = document.getElementById('dup-first-round');
        if (!box || !dates) return;
        var sync = function () { dates.style.display = box.checked ? '' : 'none'; };
        box.addEventListener('change', sync);
        sync();
    })();
</script>

@endsection
