{{-- Sidebar on every wizard step: the key choices at a glance, each a link to the
     step that sets it, plus what still blocks publishing. --}}
@php
    $s = $championship->settings;
    $entryCount = $championship->registrations()->where('is_spectator', false)->count();
    $maxEntries = $s->format->max_entries ?? null;
    $classLabel = ($s->format->multiclass_enabled ?? false)
        ? (collect($s->format->classes ?? [])->pluck('name')->filter()->implode(', ') ?: 'Multiclass, none picked')
        : ($s->format->car_class ?? 'Any');
    $scheme = $championship->pointsScheme();
    $raceMins = $s->sessions->race_length_minutes ?? null;
    $summary = [
        ['Game', $championship->gameLabel(), 'basics'],
        ['Class', $classLabel, 'format'],
        ['Entries', $entryCount.($maxEntries ? ' / '.$maxEntries : ''), 'format'],
        ['Race', $raceMins ? $raceMins.' min' : 'Not set', 'sessions'],
        ['Points', $scheme?->name ?? 'Not set', 'scoring'],
        ['Rounds', $championship->rounds->count(), 'rounds'],
        ['XCL Rating', $championship->xcl_rating_enabled ? 'On' : 'Off', 'basics'],
    ];
    $blockers = $championship->status === 'draft' ? $championship->publishBlockers() : [];
    $warnings = $championship->status === 'draft' ? $championship->publishWarnings() : [];
@endphp

<div class="cw-card">
    <div class="cw-card__head">
        <h2 class="cw-card__title">Summary</h2>
        <span class="cw-tag cw-tag--muted">{{ $championship->statusLabel() }}</span>
    </div>
    <div class="cw-card__body">
        <dl class="cw-summary">
            @foreach($summary as [$label, $value, $stepKey])
            <div class="cw-summary__row">
                <dt>{{ $label }}</dt>
                <dd><a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, $stepKey]) }}" class="text-reset text-decoration-none">{{ $value }}</a></dd>
            </div>
            @endforeach
        </dl>

        @if($blockers || $warnings)
        <div class="mt-3">
            <p class="cw-section__title">Before publishing</p>
            @foreach($blockers as $blocker)
            <div class="cw-tag cw-tag--error d-block ms-0 mb-1 text-wrap" style="font-size:.7rem;text-transform:none">{{ $blocker }}</div>
            @endforeach
            @foreach($warnings as $warning)
            <div class="cw-tag cw-tag--warn d-block ms-0 mb-1 text-wrap" style="font-size:.7rem;text-transform:none">{{ $warning }}</div>
            @endforeach
        </div>
        @endif
    </div>
</div>
