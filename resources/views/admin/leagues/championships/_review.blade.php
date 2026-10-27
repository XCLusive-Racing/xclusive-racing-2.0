@php
    use App\Settings\ChampionshipSettingsSchema;
    $settings = $championship->settings;
    // Cards start open, so the arrow starts rotated (no flash pre-JS).
    $arrow = '<svg data-accordion-arrow style="transition:transform .15s;flex-shrink:0;transform:rotate(90deg)" width="12" height="12" viewBox="0 0 20 20" fill="currentColor" class="text-secondary"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>';
    $groupCards = [
        'format' => ['Format', 'format'],
        'sessions' => ['Sessions', 'sessions'],
        'scoring' => ['Scoring', 'scoring'],
        'requirements' => ['Requirements', 'requirements'],
        'penalties' => ['Penalties', 'penalties'],
        'balance' => ['Balance', 'penalties'],
    ];
    $editLink = fn ($stepKey) => '<a href="'.route('admin.leagues.championships.wizard', [$league, $championship, $stepKey]).'" class="cw-link-btn cw-link-btn--accent text-decoration-none" onclick="event.stopPropagation()">Edit</a>';
@endphp

{{-- Collapsible cards (initAccordions, resources/js/components/tabs.js); all open by default. --}}
<div data-accordions>

<div class="cw-card" data-accordion="open">
    <div class="cw-card__head" data-accordion-header style="cursor:pointer">
        <div class="d-flex align-items-center gap-2">{!! $arrow !!}<h2 class="cw-card__title">Basics</h2></div>
        {!! $editLink('basics') !!}
    </div>
    <div class="cw-card__body" data-accordion-body>
        <div class="cw-values">
            <div><span>Name:</span> {{ $championship->name }}</div>
            <div><span>Subtitle:</span> {{ $championship->tagline ?: 'Not set' }}</div>
            <div><span>Slogan:</span> {{ $championship->slogan ?: 'Not set' }}</div>
            <div><span>Slug:</span> {{ $championship->slug ?: 'Not set' }}</div>
            <div><span>Game:</span> {{ $championship->gameLabel() }}</div>
            <div><span>Platform:</span> {{ $championship->platform ? ucfirst($championship->platform) : 'Not set' }}</div>
            <div><span>Visibility:</span> {{ ucfirst($championship->visibility) }}</div>
            <div><span>XCL Rating:</span> {{ $championship->xcl_rating_enabled ? 'Enabled' : 'Off' }}</div>
        </div>
        @if($championship->xcl_rating_enabled && $canApproveRating)
        <form action="{{ route('admin.leagues.championships.revoke-rating', [$league, $championship]) }}" method="POST" onsubmit="return false" class="mt-3">
            @csrf
            <button type="button" class="cw-link-btn cw-link-btn--danger px-0" onclick="xcDeleteSubmit(this.closest('form'), 'Revoke XCL Rating for {{ addslashes($championship->name) }}?')">
                Revoke XCL Rating
            </button>
        </form>
        @endif
    </div>
</div>

@foreach($groupCards as $group => [$label, $stepKey])
<div class="cw-card" data-accordion="open">
    <div class="cw-card__head" data-accordion-header style="cursor:pointer">
        <div class="d-flex align-items-center gap-2">{!! $arrow !!}<h2 class="cw-card__title">{{ $label }}</h2></div>
        {!! $editLink($stepKey) !!}
    </div>
    <div class="cw-card__body" data-accordion-body>
        <div class="cw-values">
            @foreach(ChampionshipSettingsSchema::fieldsForGroup($group) as $field)
            @continue($field['hidden'] ?? false)
            <div>
                <span>{{ $field['label'] }}:</span>
                {{ ChampionshipSettingsSchema::humanValue($field, $settings->{$group}->{$field['key']} ?? $field['default']) }}
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

<div class="cw-card" data-accordion="open">
    <div class="cw-card__head" data-accordion-header style="cursor:pointer">
        <div class="d-flex align-items-center gap-2">{!! $arrow !!}<h2 class="cw-card__title">Rounds</h2></div>
        {!! $editLink('rounds') !!}
    </div>
    <div class="cw-card__body" data-accordion-body>
        <div class="cw-values mb-3">
            @foreach(ChampionshipSettingsSchema::fieldsForGroup('schedule') as $field)
            <div>
                <span>{{ $field['label'] }}:</span>
                {{ ChampionshipSettingsSchema::humanValue($field, $settings->schedule->{$field['key']} ?? $field['default']) }}
            </div>
            @endforeach
            <div><span>Default Server:</span> {{ $championship->ftpServer?->name ?? 'None' }}</div>
        </div>
        @forelse($championship->rounds as $round)
        <div class="cw-list-row">
            <div>
                <div class="cw-list-row__title">R{{ $round->round_number }} · {{ $round->track }}</div>
                <div class="cw-list-row__meta">{{ $round->scheduledAtUk()->format('D d M Y, H:i T') }}</div>
            </div>
        </div>
        @empty
        <p class="text-secondary mb-0" style="font-size:.82rem">No rounds yet.</p>
        @endforelse
    </div>
</div>

</div>{{-- /data-accordions --}}

<div class="cw-actions">
    @if($prevStepKey)
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, $prevStepKey]) }}" class="cw-btn-secondary text-decoration-none">← Back</a>
    @endif
    @if($championship->status === 'draft')
    <form action="{{ route('admin.leagues.championships.publish', [$league, $championship]) }}" method="POST" class="m-0">
        @csrf
        <button type="submit" class="cw-btn-primary" style="background:#16a34a">Publish</button>
    </form>
    @elseif(in_array($championship->status, ['published', 'registration_closed']))
    <form action="{{ route('admin.leagues.championships.open-registration', [$league, $championship]) }}" method="POST" class="m-0">
        @csrf
        <button type="submit" class="cw-btn-primary" style="background:#16a34a">Open Registration</button>
    </form>
    @elseif($championship->status === 'registration_open')
    <form action="{{ route('admin.leagues.championships.close-registration', [$league, $championship]) }}" method="POST" class="m-0">
        @csrf
        <button type="submit" class="cw-btn-primary" style="background:#dc2626">Close Registration</button>
    </form>
    @endif
    {{-- Visibility only: doesn't touch status, registrations or standings. Draft is
         already off every public list, so this only shows once published. --}}
    @if($championship->status !== 'draft')
    <form action="{{ route('admin.leagues.championships.' . ($championship->visibility === 'public' ? 'hide' : 'unhide'), [$league, $championship]) }}" method="POST" class="m-0">
        @csrf
        <button type="submit" class="cw-btn-secondary">{{ $championship->visibility === 'public' ? 'Hide from Public' : 'Make Public Again' }}</button>
    </form>
    @endif
    <span class="cw-actions__hint">
        @switch($championship->status)
            @case('draft') Publish puts it on the site. Sign-up starts with Open Registration. @break
            @case('published') Published. Drivers can sign up after Open Registration. @break
            @case('registration_open') Drivers can sign up. @break
            @case('completed') Every round has finished. Adding a round reopens it. @break
            @default Drivers can't sign up right now.
        @endswitch
    </span>
</div>
