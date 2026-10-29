@extends('layouts.admin')

@section('title', $championship->name . ' · ' . ($steps[$step] ?? ''))
@section('page-title', $league->name . ' · ' . $championship->name)

@section('page-actions')
    @php $pendingEntries = $championship->registrations()->whereNull('approved_at')->count(); @endphp
    <a href="{{ route('admin.leagues.championships.entries.index', [$league, $championship]) }}"
       class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        Entries
        @if($pendingEntries > 0)
        <span class="badge ms-1" style="background:#f59e0b;color:#fff;font-size:.65rem">{{ $pendingEntries }} pending</span>
        @endif
    </a>
    <a href="{{ route('championships.show', $championship) }}" target="_blank" rel="noopener"
       class="btn btn-sm fw-black text-uppercase text-white" style="font-size:.78rem;background:#7c3aed">
        Preview →
    </a>
    <a href="{{ route('admin.leagues.championships.index', $league) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@php
    $stepKeys    = array_keys($steps);
    $stepIndex   = array_search($step, $stepKeys, true);
    $prevStepKey = $stepIndex > 0 ? $stepKeys[$stepIndex - 1] : null;

    // One short line under a section title, where a whole section shares a rule.
    $sectionNotes = [
        'Session Lengths' => 'Defaults for every new round. Each round can still change them.',
        'Weather' => 'Air, track, clouds and rain only apply when Weather is Fixed.',
        'Rating & Pitstops' => 'Defaults for every new round. Each round can still change them.',
        'Registration' => null,
    ];
@endphp

@section('content')

{{-- League + step bar --}}
<div class="cw-card">
    <div class="d-flex align-items-center gap-2 px-4 py-2" style="background:linear-gradient(90deg, {{ $league->primary_color }}18, {{ $league->accent_color }}10);border-bottom:1px solid #f3f4f6">
        <span style="width:10px;height:10px;border-radius:50%;background:{{ $league->primary_color }}"></span>
        <span class="fw-black text-uppercase fst-italic text-dark" style="font-size:.78rem">{{ $league->name }}</span>
        <span class="cw-tag cw-tag--muted">{{ $championship->statusLabel() }}</span>
        @if($championship->visibility === 'unlisted')
        <span class="cw-tag cw-tag--warn">Hidden</span>
        @endif
    </div>
    {{-- Each step is its own saved page, so these are plain links. --}}
    <nav class="cw-stepper">
        @foreach($steps as $key => $label)
        @php $i = array_search($key, $stepKeys, true); @endphp
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, $key]) }}"
           class="cw-step {{ $key === $step ? 'is-active' : '' }} {{ $i < $stepIndex ? 'is-done' : '' }}">
            <span class="cw-step__num">{{ $i + 1 }}</span>{{ $label }}
        </a>
        @if(!$loop->last)
        <span class="cw-step-line {{ $i < $stepIndex ? 'is-done' : '' }}"></span>
        @endif
        @endforeach
    </nav>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">

        @if($step === 'review')
            @include('admin.leagues.championships._review')
        @elseif($step === 'rounds')
            @include('admin.leagues.championships._rounds')
        @else
        <form action="{{ route('admin.leagues.championships.wizard.update', [$league, $championship, $step]) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="cw-card">
                <div class="cw-card__head">
                    <h2 class="cw-card__title">{{ $steps[$step] }}</h2>
                </div>

                @if($step === 'basics')
                    @include('admin.leagues.championships._basics')
                @else
                    @if($step === 'scoring')
                    <div class="cw-section">
                        @include('admin.leagues.championships._points-scheme-picker')
                    </div>
                    @endif

                    @include('admin.leagues.championships._step-sections', ['groups' => \App\Settings\ChampionshipSettingsSchema::sectionsForStep($step)])

                    @if($step === 'sessions')
                    <div class="cw-section">
                        <p class="cw-section__note mb-0">
                            Rounds with other lengths or more than one race?
                            <a href="{{ route('admin.leagues.session-formats.index', $league) }}">Set up race formats</a> and pick one per round.
                        </p>
                    </div>
                    @endif

                    @if($step === 'penalties')
                    <div class="cw-section">
                        <p class="cw-section__title">Balance &amp; Adjustments</p>
                        @include('admin.leagues.championships._adjustments-builder')
                    </div>
                    @endif
                @endif
            </div>

            <div class="cw-actions">
                @if($prevStepKey)
                <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, $prevStepKey]) }}" class="cw-btn-secondary text-decoration-none">← Back</a>
                @endif
                <button type="submit" class="cw-btn-primary">Save &amp; Continue →</button>
                <span class="cw-actions__hint">Step {{ $stepIndex + 1 }} of {{ count($steps) }}</span>
            </div>
        </form>
        @endif

        {{-- 24h practice server status: outside the step form (its own POST). --}}
        @if($step === 'sessions' && ($championship->settings->sessions->practice_server_enabled ?? false))
        @php $practiceRound = $championship->practiceRace; @endphp
        <div class="cw-card">
            <div class="cw-card__body d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div style="font-size:.82rem">
                    <p class="cw-section__title">24h Practice Server</p>
                    @if(! $championship->practice_pushed_at)
                    <span class="text-secondary">Not pushed yet. The next push is tonight at midnight (UK).</span>
                    @elseif($championship->practice_push_error)
                    <span class="fw-bold" style="color:#dc2626">Last push failed {{ $championship->practice_pushed_at->timezone('Europe/London')->format('d M H:i') }}: {{ $championship->practice_push_error }}</span>
                    @else
                    <span class="text-secondary">Last pushed {{ $championship->practice_pushed_at->timezone('Europe/London')->format('d M H:i') }}{{ $practiceRound ? ': '.$practiceRound->track.' (Round '.$practiceRound->round_number.')' : '' }}</span>
                    @endif
                </div>
                <form action="{{ route('admin.leagues.championships.practice.push', [$league, $championship]) }}" method="POST">
                    @csrf
                    <button class="cw-btn-secondary" style="font-size:.72rem">Push practice now</button>
                </form>
            </div>
        </div>
        @endif

    </div>

    <div class="col-12 col-xl-4">
        <aside class="cw-aside">
            @include('admin.leagues.championships._summary')
        </aside>
    </div>
</div>

<script>
(function () {
    // Show/hide a field (or a block of them) live with the toggle it depends on.
    document.querySelectorAll('[data-shown-if]').forEach(function (field) {
        var toggle = document.getElementById(field.dataset.shownIf);
        if (!toggle) return;
        var invert = field.hasAttribute('data-invert');
        var value = field.dataset.shownValue;

        function apply() {
            var on = value !== undefined ? toggle.value === value : toggle.checked;
            field.hidden = invert ? on : !on;
        }

        toggle.addEventListener('change', apply);
        apply();
    });
})();
</script>

@endsection
