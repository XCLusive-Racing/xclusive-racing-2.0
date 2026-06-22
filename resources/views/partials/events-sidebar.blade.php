@php
use App\Models\Race;
use App\Models\TeamEvent;
use App\Models\User;

$now = now();

$sbNextEvent = Race::where('scheduled_at', '>', $now)
    ->select(['id','title','game','track','scheduled_at','status','max_drivers','image','icon'])
    ->orderBy('scheduled_at')
    ->first();
if ($sbNextEvent) $sbNextEvent->loadCount('registrations');

$sbUpcoming = Race::where('scheduled_at', '>', $now)
    ->select(['id','title','game','track','scheduled_at','status','max_drivers','image','icon'])
    ->when($sbNextEvent, fn($q) => $q->where('id', '!=', $sbNextEvent->id))
    ->orderBy('scheduled_at')
    ->limit(2)
    ->get();
$sbUpcoming->loadCount('registrations');

$sbTeamEvents = TeamEvent::upcoming()->limit(2)->get();

$sbGames = ['acc' => 'elo_acc', 'lmu' => 'elo_lmu', 'iracing' => 'elo_iracing'];
$sbLeaderboards = [];
foreach ($sbGames as $game => $col) {
    $sbLeaderboards[$game] = User::where($col, '>', 0)
        ->orderByDesc($col)
        ->limit(40)
        ->get()
        ->values()
        ->map(fn($u, $i) => [
            'pos'       => $i + 1,
            'name'      => $u->displayName(),
            'country'   => strtoupper($u->country ?? 'XX'),
            'gain'      => (int)($u->$col ?? 0),
            'supporter' => (bool)$u->is_supporter,
        ]);
}
@endphp

<script>window.__xclLeaderboards = @json($sbLeaderboards);</script>

<div data-events-sidebar>

    {{-- ── Trigger tab ──────────────────────────────────────────────────────── --}}
    <button
        data-sb-trigger
        class="xcl-sidebar-trigger"
        aria-label="Toggle events panel"
        aria-expanded="false">
        <div class="xcl-sidebar-trigger__chevrons">
            <span class="xcl-sidebar-trigger__chevron-1">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="#d4ee6a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"/>
                </svg>
            </span>
            <span class="xcl-sidebar-trigger__chevron-2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="#d4ee6a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"/>
                </svg>
            </span>
        </div>
        <span class="xcl-sidebar-trigger__text">DASHBOARD</span>
        <div class="xcl-sidebar-trigger__socials">
            <a href="{{ config('xcl.discord_url') }}" class="xcl-trigger-pill xcl-trigger-pill--discord" target="_blank" rel="noopener">
                <span class="xcl-trigger-pill__icon"><i class="fa-brands fa-discord"></i></span>
                <span class="xcl-trigger-pill__label">Discord</span>
            </a>
            <a href="#" class="xcl-trigger-pill xcl-trigger-pill--twitch">
                <span class="xcl-trigger-pill__icon"><i class="fa-brands fa-twitch"></i></span>
                <span class="xcl-trigger-pill__label">Twitch</span>
            </a>
            <a href="https://www.instagram.com/xclusive_esport/" class="xcl-trigger-pill xcl-trigger-pill--instagram" target="_blank" rel="noopener">
                <span class="xcl-trigger-pill__icon"><i class="fa-brands fa-instagram"></i></span>
                <span class="xcl-trigger-pill__label">Instagram</span>
            </a>
            <a href="#" class="xcl-trigger-pill xcl-trigger-pill--tiktok">
                <span class="xcl-trigger-pill__icon"><i class="fa-brands fa-tiktok"></i></span>
                <span class="xcl-trigger-pill__label">TikTok</span>
            </a>
        </div>
    </button>

    {{-- ── Backdrop ─────────────────────────────────────────────────────────── --}}
    <div data-sb-backdrop
         class="xcl-sidebar-overlay"
         style="display:none"
         aria-hidden="true">
    </div>

    {{-- ── Vertical CLOSE tab ───────────────────────────────────────────────── --}}
    <button data-sb-close-tab class="xcl-sidebar__close-tab" aria-label="Close panel" style="display:none">
        <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
        <span>CLOSE</span>
    </button>

    {{-- ── Sidebar panel ───────────────────────────────────────────────────── --}}
    <aside data-sb-panel class="xcl-sidebar" aria-label="Events dashboard">

        {{-- Header --}}
        <div class="xcl-sidebar__header xcl-sidebar__header--v2">
            <div class="xcl-sidebar__header-top">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="xcl-sidebar__logo-text">XCL EVENTS DASHBOARD</span>
                    <div class="xcl-sidebar__game-filters">
                        <button class="xcl-sb-game-btn active" data-sb-game="all" title="All games">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                        <button class="xcl-sb-game-btn xcl-sb-game-btn--acc" data-sb-game="acc" title="Assetto Corsa Competizione">
                            <img src="/images/home/icons/ACC Logo.png" alt="ACC">
                        </button>
                        <button class="xcl-sb-game-btn xcl-sb-game-btn--lmu" data-sb-game="lmu" title="Le Mans Ultimate">
                            <img src="/images/home/icons/LM Logo.png" alt="LMU">
                        </button>
                        <button class="xcl-sb-game-btn xcl-sb-game-btn--iracing" data-sb-game="iracing" title="iRacing">
                            <img src="/images/home/icons/iR Logo.png" alt="iRacing">
                        </button>
                        <button class="xcl-sb-game-btn xcl-sb-game-btn--ac" data-sb-game="ac" title="AC Rally">
                            <img src="/images/home/icons/AC R Logo.png" alt="AC Rally">
                        </button>
                    </div>
                </div>
                <div class="xcl-sb-powered-by">
                    @if(config('xcl.sponsor'))
                    POWERED BY <span class="xcl-sb-powered-by__name">{{ config('xcl.sponsor') }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="xcl-sidebar-tabs" role="tablist">
            <button class="xcl-sidebar-tab active" data-sb-tab="daily" role="tab">DAILY EVENTS</button>
            <button class="xcl-sidebar-tab" data-sb-tab="championships" role="tab">CHAMPIONSHIPS</button>
            <button class="xcl-sidebar-tab" data-sb-tab="timetrials" role="tab">TIME TRIALS</button>
        </div>

        {{-- ── Scrollable content ──────────────────────────────────────────── --}}
        <div class="xcl-sidebar__content">

            {{-- ═══ DAILY EVENTS ══════════════════════════════════════════════ --}}
            <div data-sb-tab-panel="daily">
                <div class="xcl-sidebar__grid">

                    {{-- ─ COLUMN 1: NEXT EVENT ──────────────────────────────── --}}
                    <div class="xcl-sb-col">
                        <div class="xcl-sb-title">
                            <span>NEXT </span><span>EVENT</span>
                        </div>

                        @if($sbNextEvent)
                        @php
                            $nextGameLabel = match($sbNextEvent->game) {
                                'acc' => 'ACC', 'lmu' => 'LMU',
                                'iracing' => 'iRACING', 'ac' => 'AC RALLY',
                                default => strtoupper($sbNextEvent->game)
                            };
                            $nextPlatIcons = match($sbNextEvent->game) {
                                'acc'     => [['fa-brands fa-playstation','PS5'], ['fa-brands fa-xbox','Xbox']],
                                'lmu'     => [['fa-brands fa-steam','Steam'], ['fa-solid fa-desktop','PC']],
                                'iracing' => [['fa-brands fa-steam','Steam'], ['fa-solid fa-desktop','PC']],
                                'ac'      => [['fa-brands fa-steam','Steam'], ['fa-solid fa-desktop','PC']],
                                default   => [['fa-solid fa-desktop','PC']],
                            };
                        @endphp
                        {{-- Shown when game filter doesn't match next event --}}
                        <div data-sb-no-next class="xcl-sb-empty" style="display:none">
                            <p>NO <span data-sb-no-next-game></span> EVENTS</p>
                            <p>No upcoming events for this game</p>
                        </div>
                        <div data-sb-next-card data-sb-game-card="{{ $sbNextEvent->game }}"
                             class="xcl-sb-next"
                             data-countdown="{{ $sbNextEvent->scheduled_at->toIso8601String() }}">

                            {{-- Hero image with overlays --}}
                            <div class="xcl-sb-next__hero">
                                @if($sbNextEvent->image)
                                    <img src="{{ $sbNextEvent->image_url }}"
                                         alt="{{ $sbNextEvent->title }}" loading="lazy"
                                         class="xcl-sb-next__hero-img">
                                @else
                                    <div class="xcl-sb-next__hero-placeholder"></div>
                                @endif

                                <div class="xcl-sb-next__hero-gradient"></div>

                                @if($sbNextEvent->icon)
                                <div class="xcl-sb-next__icon-overlay">
                                    <img src="{{ $sbNextEvent->icon_url }}" alt="{{ $sbNextEvent->title }}">
                                </div>
                                @endif

                                {{-- Countdown top-left --}}
                                <div class="xcl-sb-countdown xcl-sb-countdown--hero">
                                    <span class="xcl-sb-countdown__label">STARTS IN</span>
                                    <span class="xcl-sb-countdown__time">
                                        <span data-cd-d>00</span>D&nbsp;<span data-cd-h>00</span>H&nbsp;<span data-cd-m>00</span>M&nbsp;<span data-cd-s>00</span>S
                                    </span>
                                </div>

                                {{-- Lobby counter top-right --}}
                                <div class="xcl-sb-lobby">
                                    <i class="fa-solid fa-comments"></i>
                                    <span>{{ $sbNextEvent->registrations_count }} / {{ $sbNextEvent->max_drivers ?? '∞' }}</span>
                                </div>

                                {{-- Race name big overlay --}}
                                <div class="xcl-sb-next__title-overlay">
                                    {{ strtoupper($sbNextEvent->title ?? $sbNextEvent->gameLabel()) }}
                                </div>

                                {{-- Platform icons bottom --}}
                                <div class="xcl-sb-next__hero-platforms">
                                    @foreach($nextPlatIcons as [$icon, $label])
                                    <span class="xcl-sb-next__hero-platform-icon">
                                        <i class="{{ $icon }}"></i> {{ $label }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Info below image --}}
                            <div class="xcl-sb-next__info">

                                {{-- Badges row --}}
                                <div class="xcl-sb-next__badges-row">
                                    <span class="xcl-sb-badge xcl-sb-badge--game">{{ $nextGameLabel }}</span>
                                    <span class="xcl-sb-badge xcl-sb-badge--sr">4.0 SR</span>
                                    @if($sbNextEvent->status === 'open')
                                        <span class="xcl-sb-badge xcl-sb-badge--open">OPEN</span>
                                    @else
                                        <span class="xcl-sb-badge xcl-sb-badge--closed">CLOSED</span>
                                    @endif
                                </div>

                                {{-- Race details --}}
                                <div class="xcl-sb-next__details">
                                    <div class="xcl-sb-next__detail-item">
                                        <span class="xcl-sb-next__detail-label">CAR CLASS</span>
                                        <span class="xcl-sb-next__detail-value">{{ $sbNextEvent->title ?? '—' }}</span>
                                    </div>
                                    <div class="xcl-sb-next__detail-item">
                                        <span class="xcl-sb-next__detail-label">TRACK</span>
                                        <span class="xcl-sb-next__detail-value">{{ $sbNextEvent->track ?? '—' }}</span>
                                    </div>
                                    <div class="xcl-sb-next__detail-item">
                                        <span class="xcl-sb-next__detail-label">WEATHER</span>
                                        <span class="xcl-sb-next__detail-value">
                                            <i class="fa-solid fa-sun" style="color:#fbbf24;font-size:.7rem"></i> DRY
                                        </span>
                                    </div>
                                </div>

                                {{-- Duration + scheduled time --}}
                                <div class="xcl-sb-next__footer-row">
                                    <span class="xcl-sb-next__duration-badge">
                                        <i class="fa-solid fa-clock"></i> 20 MIN
                                    </span>
                                    <span class="xcl-sb-next__next-time">
                                        {{ strtoupper($sbNextEvent->scheduledAtUk()->format('D, M d')) }}<br>
                                        {{ strtoupper($sbNextEvent->scheduledAtUk()->format('g:iA T')) }}
                                    </span>
                                </div>

                                <a href="{{ route('events.show', $sbNextEvent) }}" class="xcl-sb-next__join-btn">
                                    JOIN EVENT
                                </a>
                            </div>
                        </div>
                        @else
                        <div class="xcl-sb-empty">
                            <p>NO EVENTS</p>
                            <p>Check back soon</p>
                        </div>
                        @endif
                    </div>
                    {{-- end col 1 --}}

                    {{-- ─ COLUMN 2: UPCOMING EVENTS ────────────────────────── --}}
                    <div class="xcl-sb-col">
                        <div class="xcl-sb-title">
                            <span>UPCOMING </span><span>EVENTS</span>
                        </div>

                        @forelse($sbUpcoming as $event)
                        @php
                            $upPlatLabel = match($event->game) {
                                'acc'             => 'PS5 / XBOX',
                                'lmu','iracing','ac' => 'PC / STEAM',
                                default           => 'PC',
                            };
                            $upGameLabel = match($event->game) {
                                'acc' => 'ACC', 'lmu' => 'LMU',
                                'iracing' => 'iRACING', 'ac' => 'AC RALLY',
                                default => strtoupper($event->game),
                            };
                        @endphp
                        <div class="xcl-sb-up-card"
                             data-sb-game-card="{{ $event->game }}"
                             data-countdown="{{ $event->scheduled_at->toIso8601String() }}">

                            <div class="xcl-sb-up-card__img-wrap">
                                @php
                                    $upPlaceholder = match($event->game) {
                                        'lmu'     => '/images/home/teams/XCLusive_Placeholder_lmu.png',
                                        'iracing' => '/images/home/teams/XCLusive_Placeholder_iRacing.png',
                                        default   => '/images/home/teams/XCLusive_Placeholder_ACC.png',
                                    };
                                @endphp
                                <img src="{{ $event->image ? $event->image_url : $upPlaceholder }}"
                                     alt="{{ $event->title }}" loading="lazy"
                                     class="xcl-sb-up-card__img">
                                <div class="xcl-sb-up-card__img-gradient"></div>

                                @if($event->icon)
                                <div class="xcl-sb-up-card__icon-overlay">
                                    <img src="{{ $event->icon_url }}" alt="{{ $event->title }}">
                                </div>
                                @endif

                                <div class="xcl-sb-up-card__title">
                                    {{ strtoupper($event->title ?? $event->gameLabel()) }}
                                </div>

                                <div class="xcl-sb-up-card__meta-row">
                                    <div class="xcl-sb-countdown xcl-sb-countdown--small">
                                        <span data-cd-d>00</span>D&nbsp;<span data-cd-h>00</span>H&nbsp;<span data-cd-m>00</span>M
                                    </div>
                                    <div class="xcl-sb-lobby xcl-sb-lobby--small">
                                        <i class="fa-solid fa-comments"></i>
                                        <span>{{ $event->registrations_count }} / {{ $event->max_drivers ?? '∞' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="xcl-sb-up-card__footer">
                                <div class="d-flex gap-1 flex-wrap align-items-center">
                                    <span class="xcl-sb-badge xcl-sb-badge--platform">{{ $upPlatLabel }}</span>
                                    <span class="xcl-sb-badge xcl-sb-badge--game">{{ $upGameLabel }}</span>
                                </div>
                                <a href="{{ route('events.show', $event) }}" class="xcl-sb-up-card__join">JOIN EVENT</a>
                            </div>
                        </div>
                        @empty
                        <p style="color:#8B9BB4;font-size:.8rem;padding:.5rem 0">No further events scheduled</p>
                        @endforelse

                    </div>
                    {{-- end col 2 --}}

                    {{-- ─ COLUMN 3: WEEKLY LEADERBOARD ─────────────────────── --}}
                    <div class="xcl-sb-col">
                        <div class="xcl-sb-title">
                            <span>WEEKLY </span><span>LEADERBOARD</span>
                        </div>

                        <div class="xcl-sb-search">
                            <svg class="xcl-sb-search__icon" width="14" height="14" fill="none"
                                 stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                            </svg>
                            <input class="xcl-sb-search__input"
                                   data-sb-search
                                   type="text"
                                   placeholder="Search driver…"
                                   autocomplete="off">
                        </div>

                        <table class="xcl-sb-lb-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>DRIVER</th>
                                    <th style="text-align:right">GAIN</th>
                                </tr>
                            </thead>
                            <tbody data-sb-lb-body></tbody>
                        </table>

                        <div data-sb-pagination class="xcl-sb-pagination" style="display:none"></div>
                    </div>
                    {{-- end col 3 --}}

                </div>

                {{-- ── Separator + Full-width Real-World Racing ────────────── --}}
                <div style="border-top:1px solid rgba(255,255,255,0.08);margin:.75rem 1.5rem 0;padding:0 .25rem">
                    <div style="display:flex;align-items:center;gap:.75rem;padding:.9rem 0 .75rem">
                        <div class="xcl-sb-title" style="margin:0;white-space:nowrap">
                            <span>REAL-WORLD </span><span>RACING</span>
                        </div>
                        <div style="flex:1;height:1px;background:rgba(255,255,255,0.07)"></div>
                    </div>

                    <div style="display:flex;gap:1rem;align-items:stretch;padding-bottom:.25rem">
                        @foreach([0, 1] as $slot)
                        @php $te = $sbTeamEvents->get($slot); @endphp

                        @if($te)
                        <div class="xcl-sb-up-card" style="flex:1;min-width:0"
                             data-countdown="{{ $te->starts_at->toIso8601String() }}">

                            <div class="xcl-sb-up-card__img-wrap" style="height:300px">
                                <img src="{{ $te->image_url ?? '/images/home/teams/XCLusive_Placeholder_ACC.png' }}"
                                     alt="{{ $te->title }}" loading="lazy"
                                     class="xcl-sb-up-card__img"
                                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                                <div class="xcl-sb-up-card__img-gradient"></div>

                                <div class="xcl-sb-up-card__title">
                                    {{ strtoupper($te->title) }}
                                </div>

                                <div class="xcl-sb-up-card__meta-row">
                                    <div class="xcl-sb-countdown xcl-sb-countdown--small">
                                        <span data-cd-d>00</span>D&nbsp;<span data-cd-h>00</span>H&nbsp;<span data-cd-m>00</span>M
                                    </div>
                                    <div style="font-size:.65rem;color:#9ca3af;font-weight:600">
                                        {{ $te->starts_at->format('d M · H:i') }}
                                    </div>
                                </div>
                            </div>

                            <div class="xcl-sb-up-card__footer" style="padding:.5rem .6rem">
                                <div class="d-flex gap-1 flex-wrap align-items-center">
                                    <span class="xcl-sb-badge xcl-sb-badge--game"
                                          style="background:rgba(212,238,106,.15);color:#d4ee6a;border:1px solid rgba(212,238,106,.3)">
                                        {{ TeamEvent::subjects()[$te->subject] ?? $te->subject }}
                                    </span>
                                    @if($te->subtitle)
                                    <span class="xcl-sb-badge xcl-sb-badge--platform">{{ $te->subtitle }}</span>
                                    @endif
                                </div>
                                @if($te->watch_url)
                                <a href="{{ $te->watch_url }}" target="_blank" rel="noopener"
                                   class="xcl-sb-up-card__join"
                                   style="background:#d4ee6a;color:#0d0d0d;font-weight:800;padding:4px 10px;font-size:.65rem">
                                    ▶ WATCH LIVE
                                </a>
                                @else
                                <span class="xcl-sb-up-card__join"
                                      style="background:rgba(212,238,106,.08);color:#4b5563;cursor:default;pointer-events:none;padding:4px 10px;font-size:.65rem">
                                    WATCH LIVE
                                </span>
                                @endif
                            </div>
                        </div>

                        @else
                        <div style="flex:1;min-width:0;height:300px;border-radius:8px;border:1px dashed rgba(255,255,255,0.1);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.4rem;background:rgba(255,255,255,0.02)">
                            <svg width="24" height="24" fill="none" stroke="#4b5563" stroke-width="1.5" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                            </svg>
                            <span style="font-size:.7rem;font-weight:700;color:#4b5563;letter-spacing:.06em;text-transform:uppercase">No upcoming events</span>
                        </div>
                        @endif

                        @endforeach
                    </div>
                </div>

            </div>
            {{-- end daily tab --}}

            {{-- ═══ CHAMPIONSHIPS ═════════════════════════════════════════════ --}}
            <div data-sb-tab-panel="championships" style="display:none">
                <div class="xcl-sb-empty">
                    <p>CHAMPIONSHIPS</p>
                    <p>Season standings coming soon</p>
                </div>
            </div>

            {{-- ═══ TIME TRIALS ════════════════════════════════════════════════ --}}
            <div data-sb-tab-panel="timetrials" style="display:none">
                <div class="xcl-sb-empty">
                    <p>TIME TRIALS</p>
                    <p>Hotlap records coming soon</p>
                </div>
            </div>

        </div>

    </aside>

</div>
