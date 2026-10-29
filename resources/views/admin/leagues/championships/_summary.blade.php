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
    $onOff = fn ($value) => $value ? 'On' : 'Off';
    $mins = fn ($enabled, $length) => $enabled ? ($length ? $length.' min' : 'On') : 'Off';

    $raceMins = $s->sessions->race_length_minutes ?? null;
    $registration = match ($s->requirements->registration_mode ?? 'always_open') {
        'closes_at_first_round' => 'Until round 1',
        'specific_period' => ($closesAt = $s->requirements->registration_closes_at ?? null)
            ? 'Until '.\Illuminate\Support\Carbon::parse($closesAt)->format('d M H:i')
            : 'Set period',
        default => 'Always open',
    };
    $rounds = $championship->rounds->sortBy('scheduled_at');
    $nextRound = $rounds->first(fn ($round) => $round->scheduled_at?->isFuture());
    $roundsWithoutServer = $rounds->whereNull('ftp_server_id')->count();

    // Grouped by the step that sets them; each row links there.
    $summary = [
        'Basics' => [
            ['Game', $championship->gameLabel().($championship->platform ? ' · '.strtoupper($championship->platform) : ''), 'basics'],
            ['XCL Rating', $onOff($championship->xcl_rating_enabled), 'basics'],
        ],
        'Format' => [
            ['Class', $classLabel, 'format'],
            ['Entries', $entryCount.($maxEntries ? ' / '.$maxEntries : ''), 'format'],
            ['Driver Swaps', $onOff($s->format->driver_swaps_enabled ?? false), 'format'],
        ],
        'Sessions' => [
            ['Practice', $mins($s->sessions->practice_enabled ?? false, $s->sessions->practice_length_minutes ?? null), 'sessions'],
            ['Qualifying', $mins($s->sessions->qualifying_enabled ?? false, $s->sessions->qualifying_length_minutes ?? null), 'sessions'],
            ['Race', $raceMins ? $raceMins.' min' : 'Not set', 'sessions'],
        ],
        'Rules' => [
            ['Points', $scheme?->name ?? 'Not set', 'scoring'],
            ['Registration', $registration, 'requirements'],
            ['Manual Approval', $onOff($s->requirements->manual_approval_required ?? false), 'requirements'],
            ['Stewarding', $onOff($s->penalties->stewarding_enabled ?? false), 'penalties'],
            ['Success Ballast', $onOff($s->balance->success_ballast_enabled ?? false), 'penalties'],
        ],
        'Rounds' => [
            ['Rounds', $rounds->count(), 'rounds'],
            ['Next', $nextRound ? 'R'.$nextRound->round_number.' · '.$nextRound->scheduledAtUk()->format('d M') : 'None', 'rounds'],
            ['Server', $championship->ftpServer?->name ?? 'None', 'rounds'],
        ],
    ];
    if ($roundsWithoutServer) {
        $summary['Rounds'][] = ['Without server', $roundsWithoutServer, 'rounds'];
    }
    $blockers = $championship->status === 'draft' ? $championship->publishBlockers() : [];
    $warnings = $championship->status === 'draft' ? $championship->publishWarnings() : [];
@endphp

<div class="cw-card">
    <div class="cw-card__head">
        <h2 class="cw-card__title">Summary</h2>
        <span class="cw-tag cw-tag--muted">{{ $championship->statusLabel() }}</span>
    </div>
    <div class="cw-card__body">
        @foreach($summary as $group => $rows)
        <p class="cw-summary__group">{{ $group }}</p>
        <dl class="cw-summary">
            @foreach($rows as [$label, $value, $stepKey])
            <div class="cw-summary__row">
                <dt>{{ $label }}</dt>
                <dd><a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, $stepKey]) }}" class="text-reset text-decoration-none">{{ $value }}</a></dd>
            </div>
            @endforeach
        </dl>
        @endforeach

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
