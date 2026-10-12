{{-- The XP & Achievements screens, as a row of links above each of them. --}}
<div class="admin-card mb-4">
    <div class="px-4 py-3 d-flex align-items-center gap-2 flex-wrap">
        @foreach([
            ['admin.xp.achievements.index', 'admin.xp.achievements.*', 'Achievements'],
            ['admin.xp.curve', 'admin.xp.curve', 'Rank curve'],
            ['admin.xp.games.index', 'admin.xp.games.*', 'Games'],
            ['admin.xp.adjustments.index', 'admin.xp.adjustments.*', 'Adjustments'],
        ] as [$route, $pattern, $label])
        @php $active = request()->routeIs($pattern); @endphp
        <a href="{{ route($route) }}"
           class="btn btn-sm fw-bold text-uppercase {{ $active ? '' : 'btn-outline-secondary' }}"
           style="font-size:.7rem;padding:4px 12px;{{ $active ? 'background:#7c3aed;color:#fff' : '' }}">
            {{ $label }}
        </a>
        @endforeach
        @php $xpLaunch = \App\Xp\Launch::at(); @endphp
        <span class="ms-auto fw-bold" style="font-size:.72rem;color:{{ config('xp.enabled') && $xpLaunch ? '#15803d' : '#b45309' }}">
            @if(! config('xp.enabled'))
            XP is switched off (XP_ENABLED): races award nothing yet.
            @elseif(! $xpLaunch)
            No launch date is set (XP_LAUNCH_AT): races award nothing yet.
            @else
            XP is on. Everything counts from {{ $xpLaunch->timezone('Europe/London')->format('j M Y, H:i') }} UK time.
            @endif
        </span>
    </div>
</div>
