@php
    use App\Settings\ChampionshipSettingsSchema;
    $settings = $championship->settings;
    // Shared with every other step's footer (wizard.blade.php computes it once)
    // so Review's own Back button lands on the same "previous step" as everywhere
    // else in the wizard.
    // Every card below defaults open (data-accordion="open"), so the arrow's
    // pre-JS inline rotation matches that from the very first paint — same
    // "no flash of the wrong state" reasoning _field.blade.php uses for its
    // depends_on fields.
    $arrow = '<svg data-accordion-arrow style="transition:transform .15s;flex-shrink:0;transform:rotate(90deg)" width="12" height="12" viewBox="0 0 20 20" fill="currentColor" class="text-secondary"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>';
@endphp

{{-- Every card below can be collapsed independently (initAccordions,
     resources/js/components/tabs.js — the same component the race-results
     class groups and config-file editors already use) instead of always
     showing every field's value at once. Starts open so nothing's hidden by
     default; collapsing is just for cutting clutter on a long review. --}}
<div data-accordions>

<div class="admin-card mb-4" data-accordion="open">
    <div class="admin-card-header" data-accordion-header style="cursor:pointer">
        <div class="d-flex align-items-center gap-2">
            {!! $arrow !!}
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Basics</div>
        </div>
    </div>
    <div class="px-4 py-3" data-accordion-body>
        <div class="row g-2" style="font-size:.85rem">
            <div class="col-sm-6"><span class="text-secondary">Name:</span> {{ $championship->name }}</div>
            <div class="col-sm-6"><span class="text-secondary">Slug:</span> {{ $championship->slug ?: 'Not set' }}</div>
            <div class="col-sm-6"><span class="text-secondary">Game:</span> {{ strtoupper($championship->game) }}</div>
            <div class="col-sm-6"><span class="text-secondary">Platform:</span> {{ $championship->platform ? ucfirst($championship->platform) : 'Not set' }}</div>
            <div class="col-sm-6"><span class="text-secondary">Visibility:</span> {{ ucfirst($championship->visibility) }}</div>
        </div>
    </div>
</div>

@foreach(['schedule' => 'Schedule', 'format' => 'Format', 'sessions' => 'Sessions', 'scoring' => 'Scoring', 'requirements' => 'Requirements', 'penalties' => 'Penalties', 'balance' => 'Balance'] as $group => $label)
<div class="admin-card mb-4" data-accordion="open">
    <div class="admin-card-header" data-accordion-header style="cursor:pointer">
        <div class="d-flex align-items-center gap-2">
            {!! $arrow !!}
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">{{ $label }}</div>
        </div>
    </div>
    <div class="px-4 py-3" data-accordion-body>
        <div class="row g-2" style="font-size:.85rem">
            @foreach(ChampionshipSettingsSchema::fieldsForGroup($group) as $field)
            <div class="col-sm-6">
                <span class="text-secondary">{{ $field['label'] }}:</span>
                {{ ChampionshipSettingsSchema::humanValue($field, $settings->{$group}->{$field['key']} ?? $field['default']) }}
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

<div class="admin-card mb-4" data-accordion="open">
    <div class="admin-card-header" data-accordion-header style="cursor:pointer">
        <div class="d-flex align-items-center gap-2">
            {!! $arrow !!}
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Rounds</div>
        </div>
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}"
           class="fw-bold" style="color:#7c3aed;font-size:.78rem" onclick="event.stopPropagation()">
            Manage rounds →
        </a>
    </div>
    <div class="px-4 py-3" data-accordion-body>
        @forelse($championship->rounds as $round)
        <div class="py-2" style="border-bottom:1px solid #f3f4f6;font-size:.85rem">
            <span class="fw-bold text-dark">R{{ $round->round_number }} — {{ $round->title }}</span>
            <span class="text-secondary"> · {{ $round->track }} · {{ $round->scheduledAtUk()->format('d M Y, H:i T') }}</span>
        </div>
        @empty
        <p class="text-secondary mb-0" style="font-size:.82rem">No rounds scheduled yet.</p>
        @endforelse
    </div>
</div>

<div class="admin-card mb-4" data-accordion="open">
    <div class="admin-card-header" data-accordion-header style="cursor:pointer">
        <div class="d-flex align-items-center gap-2">
            {!! $arrow !!}
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">XCL Rating</div>
        </div>
    </div>
    <div class="px-4 py-3" data-accordion-body>
        @if($championship->xcl_rating_enabled)
        <p class="mb-2" style="font-size:.85rem">
            <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.7rem;padding:4px 8px;border-radius:6px;font-weight:700">Enabled</span>
        </p>
        @if($canApproveRating)
        <form action="{{ route('admin.leagues.championships.revoke-rating', [$league, $championship]) }}" method="POST" onsubmit="return false">
            @csrf
            <button type="button" class="btn btn-sm btn-outline-danger fw-bold" onclick="xcDeleteSubmit(this.closest('form'), 'Revoke XCL Rating for {{ addslashes($championship->name) }}?')">
                Revoke
            </button>
        </form>
        @endif
        @elseif($canApproveRating)
        <p class="text-secondary mb-0" style="font-size:.85rem">Not enabled. Turn it on from the XCL Rating option on Basics.</p>
        @else
        <p class="text-secondary mb-0" style="font-size:.85rem">Not enabled.</p>
        @endif
    </div>
</div>

</div>{{-- /data-accordions --}}

<div class="d-flex align-items-center gap-2">
    @if($prevStepKey)
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, $prevStepKey]) }}"
       class="btn btn-outline-secondary fw-black text-uppercase px-4">
        ← Back
    </a>
    @endif
    @if($championship->status === 'draft')
    <form action="{{ route('admin.leagues.championships.publish', [$league, $championship]) }}" method="POST">
        @csrf
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#16a34a">
            Publish
        </button>
    </form>
    @elseif(in_array($championship->status, ['published', 'registration_closed']))
    <form action="{{ route('admin.leagues.championships.open-registration', [$league, $championship]) }}" method="POST">
        @csrf
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#16a34a">
            Open Registration
        </button>
    </form>
    @elseif($championship->status === 'registration_open')
    <form action="{{ route('admin.leagues.championships.close-registration', [$league, $championship]) }}" method="POST">
        @csrf
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#dc2626">
            Close Registration
        </button>
    </form>
    @endif
    {{-- Independent of status -- pulling a championship out of public listings
         (visibility = unlisted) doesn't revert it to draft or touch
         registrations/rounds/standings, unlike the status actions above. Only
         makes sense once published: draft is already excluded from public
         listings by status alone (Championship::scopePubliclyVisible()). --}}
    @if($championship->status !== 'draft')
    <form action="{{ route('admin.leagues.championships.' . ($championship->visibility === 'public' ? 'hide' : 'unhide'), [$league, $championship]) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-secondary fw-black text-uppercase px-4">
            {{ $championship->visibility === 'public' ? 'Hide from Public' : 'Make Public Again' }}
        </button>
    </form>
    @endif
</div>
