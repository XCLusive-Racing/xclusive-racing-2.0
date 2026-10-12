{{-- DESIGN PREVIEW with example data (App\Services\XpPreview, /profile?xp_preview=1, staff
     only): how the XP rank on the profile and the post-race reveal could look. Not the
     real feature — see docs/xp-achievements/PLAN.md, Phase 2. --}}
@php
    [$xpRank, $xpInto, $xpNeeded] = \App\Services\XpPreview::position($xpPreview['curve'], $xpPreview['after']);
@endphp

{{-- XP rank on the profile: its own card and colour, apart from XCL Rating and SR below. --}}
<div class="xp-card bg-white rounded-3 shadow-sm p-4 mb-4">
    <div class="xp-card__preview">Preview · example data · nothing is saved</div>
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="xp-badge">
            <span class="xp-badge__label">Rank</span>
            <span class="xp-badge__num">{{ $xpRank }}</span>
        </div>
        <div class="flex-grow-1" style="min-width:220px">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div class="fw-black text-uppercase text-dark" style="letter-spacing:.04em">Driver XP</div>
                <div class="text-secondary" style="font-size:.8rem">
                    <span class="fw-bold text-dark">{{ number_format($xpInto) }}</span> / {{ number_format($xpNeeded) }} XP to rank {{ $xpRank + 1 }}
                </div>
            </div>
            <div class="xp-bar mt-2"><div class="xp-bar__fill" style="width:{{ round($xpInto / $xpNeeded * 100, 1) }}%"></div></div>
            <div class="d-flex justify-content-between flex-wrap gap-2 mt-2" style="font-size:.78rem">
                <span class="text-secondary">{{ number_format($xpPreview['after']) }} XP in total · shows how much you've raced, not how fast you are</span>
                <span class="fw-bold" style="color:#0891b2">+{{ number_format($xpPreview['gained']) }} XP last race</span>
            </div>
        </div>
        <button type="button" class="btn btn-sm fw-bold text-uppercase text-white flex-shrink-0" data-xp-reveal-replay
                style="background:#0891b2;font-size:.75rem">Replay reveal</button>
    </div>
</div>

{{-- The reveal: plays when the profile opens after a race (xp-reveal.js). --}}
<div class="xp-reveal" data-xp-reveal='@json($xpPreview)' role="dialog" aria-modal="true" aria-label="XP earned" hidden>
    <div class="xp-reveal__panel">
        <button type="button" class="xp-reveal__close" data-xp-close aria-label="Close">&times;</button>

        <div class="xp-reveal__eyebrow">Race complete</div>
        <h2 class="xp-reveal__title">{{ $xpPreview['race']['title'] }} — {{ $xpPreview['race']['track'] }}</h2>
        <div class="xp-reveal__sub">Finished <strong>{{ $xpPreview['race']['result'] }}</strong> from {{ $xpPreview['race']['grid'] }} on the grid</div>

        <div class="xp-reveal__rank">
            <div class="xp-badge xp-badge--lg" data-xp-badge>
                <span class="xp-badge__label">Rank</span>
                <span class="xp-badge__num" data-xp-rank>0</span>
            </div>
            <div class="flex-grow-1">
                <div class="xp-reveal__rankup" data-xp-rankup>Rank up!</div>
                <div class="xp-bar xp-bar--dark"><div class="xp-bar__fill" data-xp-fill></div></div>
                <div class="xp-reveal__progress"><span data-xp-into>0</span> / <span data-xp-needed>0</span> XP</div>
            </div>
        </div>

        <ul class="xp-reveal__lines" data-xp-lines>
            @foreach($xpPreview['lines'] as $line)
            <li class="xp-reveal__line">
                <span>{{ $line['label'] }}@if($line['note']) <small>{{ $line['note'] }}</small>@endif</span>
                <span class="xp-reveal__xp">+{{ number_format($line['xp']) }}</span>
            </li>
            @endforeach
        </ul>

        <div class="xp-reveal__section" data-xp-ach-title>Achievements unlocked</div>
        <div class="xp-reveal__achievements" data-xp-achievements>
            @foreach($xpPreview['achievements'] as $achievement)
            <div class="xp-ach">
                <div class="xp-ach__icon"><i class="{{ $achievement['icon'] }}"></i></div>
                <div class="xp-ach__body">
                    <div class="xp-ach__name">{{ $achievement['name'] }}</div>
                    <div class="xp-ach__desc">{{ $achievement['description'] }}</div>
                </div>
                <div class="xp-reveal__xp">+{{ number_format($achievement['xp']) }}</div>
            </div>
            @endforeach
        </div>

        <div class="xp-reveal__footer">
            <div>
                <div class="xp-reveal__total">+<span data-xp-total>0</span> XP</div>
                <div class="xp-reveal__note">XP is time served — it only ever goes up.</div>
            </div>
            <button type="button" class="xp-reveal__btn" data-xp-continue>Skip</button>
        </div>
    </div>
</div>
