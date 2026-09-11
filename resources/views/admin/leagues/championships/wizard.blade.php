@extends('layouts.admin')

@section('title', $championship->name . ' — ' . ($steps[$step] ?? ''))
@section('page-title', $league->name . ' — ' . $championship->name)

@section('page-actions')
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

            <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                Save &amp; Continue →
            </button>
        </form>
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
