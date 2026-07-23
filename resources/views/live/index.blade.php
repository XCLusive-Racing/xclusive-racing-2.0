@extends('layouts.app')

@section('title', 'Live Broadcast — ' . config('xcl.name'))
@section('no-sidebar')

@push('head')
<style>
/* ── TRTN Live page ────────────────────────────────────────────────── */

/* Topo background handled by .xcl-page + .about-section__topo */

/* ── Hero bar ──────────────────────────────────────────────────────── */
.live-hero {
    border-left: 4px solid #cc0000;
    padding: 1.25rem 0 1.25rem 1.25rem;
}
.live-hero__heading {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 900;
    text-transform: uppercase;
    font-style: italic;
    color: #1a1a2e;
    letter-spacing: -.02em;
    line-height: 1;
    margin-bottom: .25rem;
}
.live-hero__sub {
    color: #555;
    font-size: .9rem;
    margin: 0;
}

/* ── Live indicator ────────────────────────────────────────────────── */
.live-badge {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    background: #cc0000;
    color: #fff;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
    padding: .35rem .9rem;
    border-radius: 6px;
}
.live-badge__dot {
    width: 8px;
    height: 8px;
    background: #fff;
    border-radius: 50%;
    animation: live-pulse 1.2s ease-in-out infinite;
    flex-shrink: 0;
}
@keyframes live-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: .35; transform: scale(.7); }
}

/* ── Twitch embed ──────────────────────────────────────────────────── */
.live-embed-wrap {
    position: relative;
    padding-top: 56.25%;
    background: #0e0e10;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,.18);
    border: 2px solid rgba(204,0,0,.25);
}
.live-embed-wrap iframe {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    border: 0;
}

/* ── Twitch CTA strip ──────────────────────────────────────────────── */
.live-twitch-bar {
    background: #1a1a2e;
    border-radius: 10px;
    padding: .85rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .75rem;
}
.live-twitch-bar__info {
    display: flex;
    align-items: center;
    gap: .75rem;
    color: #fff;
    font-size: .82rem;
}
.live-twitch-bar__handle {
    font-weight: 800;
    font-size: .95rem;
    color: #a78bfa;
}
.live-twitch-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: #9147ff;
    color: #fff;
    font-size: .75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .5rem 1.1rem;
    border-radius: 6px;
    text-decoration: none;
    transition: background .15s;
}
.live-twitch-btn:hover {
    background: #7c3aed;
    color: #fff;
}

/* ── Section divider ───────────────────────────────────────────────── */
.live-section-label {
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: #cc0000;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: .75rem;
}
.live-section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

/* ── Schedule cards ────────────────────────────────────────────────── */
.live-schedule-card {
    background: #1a1a2e;
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    position: relative;
    overflow: hidden;
    border-left: 4px solid transparent;
    transition: border-color .2s, transform .2s, box-shadow .2s;
}
.live-schedule-card:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 24px rgba(0,0,0,.15);
}

/* Date block */
.live-card-date {
    flex-shrink: 0;
    text-align: center;
    width: 52px;
}
.live-card-date__day {
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    line-height: 1;
}
.live-card-date__month {
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.45);
    margin-top: .1rem;
}

/* Divider */
.live-card-divider {
    width: 1px;
    background: rgba(255,255,255,.12);
    align-self: stretch;
    flex-shrink: 0;
}

/* Info */
.live-card-info {
    flex: 1;
    min-width: 0;
}
.live-card-series {
    font-size: .6rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #fff;
    padding: .15rem .5rem;
    border-radius: 4px;
    display: inline-block;
    margin-bottom: .4rem;
}
.live-card-title {
    font-size: .98rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.3;
    margin-bottom: .2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.live-card-subtitle {
    font-size: .75rem;
    color: rgba(255,255,255,.5);
}

/* Countdown */
.live-card-countdown {
    flex-shrink: 0;
    text-align: right;
}
.live-card-countdown__nums {
    display: flex;
    align-items: baseline;
    gap: .15rem;
    justify-content: flex-end;
}
.live-card-countdown__num {
    font-size: 1.1rem;
    font-weight: 900;
    color: #fff;
    font-variant-numeric: tabular-nums;
    min-width: 1.8ch;
    text-align: center;
}
.live-card-countdown__sep {
    font-size: .62rem;
    font-weight: 700;
    color: rgba(255,255,255,.35);
    text-transform: uppercase;
    margin-right: .2rem;
}
.live-card-countdown__label {
    font-size: .6rem;
    color: rgba(255,255,255,.3);
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-top: .2rem;
}

/* Time tag */
.live-card-time {
    font-size: .72rem;
    font-weight: 700;
    color: rgba(255,255,255,.5);
    margin-top: .35rem;
}

/* TRTN footer bar */
.live-trtn-bar {
    background: #cc0000;
    border-radius: 10px;
    padding: .9rem 1.4rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #fff;
}
.live-trtn-bar__text {
    font-size: .8rem;
    font-weight: 700;
    line-height: 1.5;
}
.live-trtn-bar__text strong { font-size: .9rem; font-weight: 900; }
</style>
@endpush

@section('content')
<main class="xcl-page pb-5 px-3" style="background:white">
<div class="about-section__topo" style="background-image:url('/topo.png')"></div>
<div class="container-xl px-3 px-md-4" style="padding-top:60px;padding-bottom:60px;position:relative;z-index:1">

    {{-- ── Page header ──────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-5">
        <div class="live-hero">
            <h1 class="live-hero__heading">LIVE BROADCAST</h1>
            <p class="live-hero__sub">Watch TRTN race coverage — live & upcoming</p>
        </div>
        <div class="d-flex flex-column align-items-end gap-2">
            <div style="background:#cc0000;border-radius:8px;padding:.6rem 1.4rem;display:inline-flex;align-items:center">
                <img src="/images/trtn/trtn - wide.png" alt="TRTN" height="32"
                     style="object-fit:contain;filter:brightness(0) invert(1)">
            </div>
            <span style="font-size:.7rem;font-weight:700;color:#cc0000;text-transform:uppercase;letter-spacing:.06em">
                Powered by TRTN
            </span>
        </div>
    </div>

    {{-- ── Live stream embed ────────────────────────────────────── --}}
    <div class="mb-3 d-flex align-items-center gap-3">
        <span class="live-badge"><span class="live-badge__dot"></span>LIVE ON TWITCH</span>
        <span style="font-size:.8rem;color:#6b7280">Stream may be offline between broadcasts</span>
    </div>

    <div class="live-embed-wrap mb-3">
        <iframe
            src="https://player.twitch.tv/?channel={{ $twitchChannel }}&parent={{ $twitchParent }}"
            allowfullscreen>
        </iframe>
    </div>

    {{-- Twitch action bar --}}
    <div class="live-twitch-bar mb-5">
        <div class="live-twitch-bar__info">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="#9147ff">
                <path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z"/>
            </svg>
            <div>
                <div style="color:rgba(255,255,255,.5);font-size:.7rem;text-transform:uppercase;letter-spacing:.06em">Official TRTN channel</div>
                <div class="live-twitch-bar__handle">twitch.tv/trueracingrevival</div>
            </div>
        </div>
        <a href="https://www.twitch.tv/trueracingrevival" target="_blank" rel="noopener" class="live-twitch-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                <path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z"/>
            </svg>
            Follow on Twitch
        </a>
    </div>

    {{-- ── Upcoming schedule ─────────────────────────────────────── --}}
    <div class="live-section-label">Upcoming Broadcasts</div>

    <div class="d-flex flex-column gap-3 mb-5">
        @foreach($schedule as $event)
        @php $isPast = $event['date']->isPast(); @endphp
        <div class="live-schedule-card {{ $isPast ? 'opacity-50' : '' }}"
             style="border-left-color:{{ $event['color'] }}"
             @if(!$isPast) data-live-countdown="{{ $event['date']->toIso8601String() }}" @endif>

            {{-- Date block --}}
            <div class="live-card-date">
                <div class="live-card-date__day">{{ $event['date']->format('d') }}</div>
                <div class="live-card-date__month">{{ $event['date']->format('M Y') }}</div>
            </div>

            <div class="live-card-divider"></div>

            {{-- Info --}}
            <div class="live-card-info">
                <span class="live-card-series" style="background:{{ $event['color'] }}25;color:{{ $event['color'] }};border:1px solid {{ $event['color'] }}50">
                    {{ $event['series'] }}
                </span>
                <div class="live-card-title">{{ $event['title'] }}</div>
                <div class="live-card-subtitle">{{ $event['subtitle'] }}</div>
                <div class="live-card-time">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:-.1em;opacity:.5">
                        <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                    </svg>
                    {{ $event['date']->format('H:i') }} CET
                </div>
            </div>

            {{-- Countdown --}}
            @if(!$isPast)
            <div class="live-card-countdown">
                <div class="live-card-countdown__nums">
                    <span class="live-card-countdown__num" data-cd-d>--</span><span class="live-card-countdown__sep">d</span>
                    <span class="live-card-countdown__num" data-cd-h>--</span><span class="live-card-countdown__sep">h</span>
                    <span class="live-card-countdown__num" data-cd-m>--</span><span class="live-card-countdown__sep">m</span>
                </div>
                <div class="live-card-countdown__label">until broadcast</div>
                <a href="https://www.twitch.tv/trueracingrevival" target="_blank" rel="noopener"
                   style="display:inline-block;margin-top:.5rem;background:#cc0000;color:#fff;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;padding:.3rem .75rem;border-radius:5px;text-decoration:none">
                    ▶ WATCH LIVE
                </a>
            </div>
            @else
            <div class="live-card-countdown" style="color:rgba(255,255,255,.3);font-size:.72rem;font-weight:700;text-transform:uppercase">
                Completed
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- ── TRTN footer bar ───────────────────────────────────────── --}}
    <div class="live-trtn-bar">
        <img src="/images/trtn/trtn - wide.png" alt="TRTN" height="28"
             style="object-fit:contain;filter:brightness(0) invert(1);flex-shrink:0">
        <div class="live-trtn-bar__text">
            <strong>True Racing Revival Network</strong><br>
            XCLusive Racing's official broadcast partner — delivering professional race coverage across GT3, GT4, Endurance and more.
        </div>
        <a href="https://www.twitch.tv/trueracingrevival" target="_blank" rel="noopener"
           style="margin-left:auto;background:rgba(255,255,255,.15);color:#fff;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;padding:.5rem 1.1rem;border-radius:6px;text-decoration:none;white-space:nowrap;flex-shrink:0">
            FOLLOW TRTN →
        </a>
    </div>

</div>
</div>
</main>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-live-countdown]').forEach(card => {
    const target = new Date(card.dataset.liveCountdown).getTime();
    const dEl = card.querySelector('[data-cd-d]');
    const hEl = card.querySelector('[data-cd-h]');
    const mEl = card.querySelector('[data-cd-m]');
    if (!dEl || !hEl || !mEl) return;

    function tick() {
        const diff = target - Date.now();
        if (diff <= 0) {
            dEl.textContent = '00';
            hEl.textContent = '00';
            mEl.textContent = '00';
            return;
        }
        const d = Math.floor(diff / 86400000);
        const h = Math.floor((diff % 86400000) / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        dEl.textContent = String(d).padStart(2, '0');
        hEl.textContent = String(h).padStart(2, '0');
        mEl.textContent = String(m).padStart(2, '0');
    }

    tick();
    setInterval(tick, 30000);
});
</script>
@endpush
