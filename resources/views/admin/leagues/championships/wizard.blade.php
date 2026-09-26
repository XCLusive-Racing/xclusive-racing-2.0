@extends('layouts.admin')

@section('title', $championship->name . ' — ' . ($steps[$step] ?? ''))
@section('page-title', $league->name . ' — ' . $championship->name)

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
        Preview Championship →
    </a>
    <a href="{{ route('admin.leagues.championships.index', $league) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

{{-- League context strip, same pattern as the league edit screen --}}
<div class="admin-card mb-4" style="overflow:hidden">
    <div class="d-flex align-items-center gap-3 px-4 py-2" style="background:linear-gradient(90deg, {{ $league->primary_color }}18, {{ $league->accent_color }}10)">
        <span style="width:10px;height:10px;border-radius:50%;background:{{ $league->primary_color }}"></span>
        <span class="fw-black text-uppercase fst-italic text-dark" style="font-size:.8rem">{{ $league->name }}</span>
        <span class="text-secondary" style="font-size:.75rem">·</span>
        <span class="text-secondary text-uppercase" style="font-size:.72rem;letter-spacing:.05em">{{ ucfirst($championship->status) }}</span>
        @if($championship->visibility === 'unlisted')
        <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">Hidden</span>
        @endif
    </div>
</div>

{{-- Stepper nav — each step is its own saved page, so this is plain links, not JS panels --}}
<div class="admin-card mb-4">
    <div class="d-flex align-items-center flex-wrap px-4 py-3" style="gap:.5rem">
        @php $stepKeys = array_keys($steps); @endphp
        @foreach($steps as $key => $label)
        @php
            $isActive = $key === $step;
            $isDone   = array_search($key, $stepKeys, true) < array_search($step, $stepKeys, true);
        @endphp
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, $key]) }}"
           class="d-flex align-items-center gap-2 text-decoration-none px-2 py-1"
           style="border-radius:6px;{{ $isActive ? 'background:#f3e8ff' : '' }}">
            <span class="fw-black d-flex align-items-center justify-content-center"
                  style="width:24px;height:24px;border-radius:50%;font-size:.7rem;
                         background:{{ $isActive || $isDone ? '#7c3aed' : '#fff' }};
                         color:{{ $isActive || $isDone ? '#fff' : '#9ca3af' }};
                         border:2px solid {{ $isActive || $isDone ? '#7c3aed' : '#e5e7eb' }}">
                {{ array_search($key, $stepKeys, true) + 1 }}
            </span>
            <span class="fw-bold" style="font-size:.78rem;color:{{ $isActive ? '#7c3aed' : '#374151' }}">{{ $label }}</span>
        </a>
        @if(!$loop->last)
        <span style="flex:0 0 16px;height:2px;background:{{ $isDone ? '#7c3aed' : '#e5e7eb' }}"></span>
        @endif
        @endforeach
    </div>
</div>

@php
    // Every step's footer gets a Back button to the previous one in STEPS
    // order (user-directed 2026-09: "bij elk ding moet een backbutton
    // staan") — null on the very first step (Basics), where there's nothing
    // to go back to inside the wizard.
    $stepIndex   = array_search($step, $stepKeys, true);
    $prevStepKey = $stepIndex > 0 ? $stepKeys[$stepIndex - 1] : null;
@endphp

<div class="row g-4">
    <div class="col-12 col-lg-8">

        @if($step === 'review')
            @include('admin.leagues.championships._review')
        @elseif($step === 'rounds')
            @include('admin.leagues.championships._rounds')
        @else
        <form action="{{ route('admin.leagues.championships.wizard.update', [$league, $championship, $step]) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-0" style="font-size:.85rem">{{ $steps[$step] }}</p>
                </div>

                <div class="px-4 pb-4">
                    @if($step === 'basics')
                        @include('admin.leagues.championships._basics')
                    @else
                        @php
                            $sections = \App\Settings\ChampionshipSettingsSchema::sectionsForStep($step);
                            $sectionIndex = 0;
                        @endphp
                        {{-- Grouped into subsections (race wizard's "Event" / "Track & Conditions"
                             pattern, resources/views/admin/races/form.blade.php) instead of every
                             field in one flat, undifferentiated stack. --}}
                        @foreach($sections as $sectionLabel => $sectionFields)
                            @php
                                // points_scheme_id gets its own picker below, with an inline table
                                // preview a plain integer input can't show — skip the generic one.
                                $visibleFields = collect($sectionFields)->filter(fn ($f) =>
                                    $f['type'] !== 'list' && !($f['hidden'] ?? false) && !($step === 'scoring' && $f['key'] === 'points_scheme_id')
                                )->values();
                            @endphp
                            @continue($visibleFields->isEmpty())
                            @php $sectionIndex++; @endphp
                            <div class="{{ $sectionIndex > 1 ? 'mt-4 pt-4' : '' }}" @if($sectionIndex > 1) style="border-top:1px solid #f3f4f6" @endif>
                                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">{{ $sectionLabel }}</p>
                                <div class="row g-3">
                                    {{-- A field can carry 'depends_on' => another boolean field's key
                                         (same group) — _field.blade.php wraps it so the shared script
                                         below shows/hides it live against that toggle, instead of every
                                         field appearing equally relevant regardless of the toggle's state. --}}
                                    @foreach($visibleFields as $field)
                                        @include('admin.leagues.championships._field', ['field' => $field])
                                        {{-- Right under Multiclass itself, not stranded at the bottom of
                                             the whole step — it already shows/hides on that same toggle. --}}
                                        @if($step === 'format' && $field['key'] === 'multiclass_enabled')
                                        <div class="col-12">
                                            @include('admin.leagues.championships._classes-builder')
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if($step === 'sessions')
                        <div class="mt-4 pt-4" style="border-top:1px solid #f3f4f6;font-size:.82rem">
                            These are the defaults for every new round. For rounds that differ — other session lengths, or more than one race —
                            <a href="{{ route('admin.leagues.session-formats.index', $league) }}">set up race formats →</a>
                            and pick one per round on Add/Edit Round.
                        </div>
                        @endif

                        @if($step === 'scoring')
                        <div class="mt-4 pt-4" style="border-top:1px solid #f3f4f6">
                            @include('admin.leagues.championships._points-scheme-picker')
                        </div>
                        @endif

                        @if($step === 'penalties')
                        <div class="mt-4 pt-4" style="border-top:1px solid #f3f4f6">
                            <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Balance &amp; Adjustments</p>
                            @include('admin.leagues.championships._adjustments-builder')
                        </div>
                        @endif
                    @endif
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if($prevStepKey)
                <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, $prevStepKey]) }}"
                   class="btn btn-outline-secondary fw-black text-uppercase px-4">
                    ← Back
                </a>
                @endif
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Save &amp; Continue →
                </button>
            </div>
        </form>
        @endif

        {{-- 24h practice server status — outside the step form (its own POST). --}}
        @if($step === 'sessions' && ($championship->settings->sessions->practice_server_enabled ?? false))
        @php $practiceRound = $championship->practiceRace; @endphp
        <div class="admin-card mt-4">
            <div class="px-4 py-3 d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div style="font-size:.82rem">
                    <div class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:.72rem;letter-spacing:.08em">24h Practice Server</div>
                    @if(! $championship->practice_pushed_at)
                    <span class="text-secondary">Not pushed yet — the next push is tonight at midnight (UK).</span>
                    @elseif($championship->practice_push_error)
                    <span class="fw-bold" style="color:#dc2626">Last push failed {{ $championship->practice_pushed_at->timezone('Europe/London')->format('d M H:i') }}: {{ $championship->practice_push_error }}</span>
                    @else
                    <span class="text-secondary">Last pushed {{ $championship->practice_pushed_at->timezone('Europe/London')->format('d M H:i') }}{{ $practiceRound ? ' — '.$practiceRound->track.' (Round '.$practiceRound->round_number.')' : '' }}</span>
                    @endif
                </div>
                <form action="{{ route('admin.leagues.championships.practice.push', [$league, $championship]) }}" method="POST">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.72rem">Push practice now</button>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-bool-pill]').forEach(label => {
        const cb = label.querySelector('input[type=checkbox]');
        if (!cb) return;

        function applyStyle() {
            if (cb.checked) {
                label.style.border     = '2px solid #7c3aed';
                label.style.background = '#7c3aed18';
                label.style.color      = '#7c3aed';
            } else {
                label.style.border     = '2px solid #e5e7eb';
                label.style.background = '#fff';
                label.style.color      = '#374151';
            }
        }

        cb.addEventListener('change', applyStyle);
    });

    document.querySelectorAll('[data-shown-if]').forEach(field => {
        const toggle = document.getElementById(field.dataset.shownIf);
        if (!toggle) return;
        const invert = field.hasAttribute('data-invert');

        function apply() {
            field.hidden = invert ? toggle.checked : !toggle.checked;
        }

        toggle.addEventListener('change', apply);
        apply();
    });
})();
</script>

@endsection
