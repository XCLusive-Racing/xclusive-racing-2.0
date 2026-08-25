@php
use App\Models\NewsArticle;
use App\Models\Race;
use App\Models\TeamEvent;
use App\Models\User;

$now = now();

$tickerArticles = NewsArticle::published()
    ->orderBy('published_at', 'desc')
    ->limit(5)
    ->get();

$sbNextEvent = Race::where('scheduled_at', '>', $now)
    ->select(['id','title','game','track','scheduled_at','status','max_drivers','image','icon','car_class','event_format_id'])
    ->with('eventFormat:id,race1_mins,race2_mins')
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

$sbTeamEvents = TeamEvent::upcoming()->with('participatingDrivers')->limit(2)->get();

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
            <div class="xcl-sidebar-trigger__divider"></div>
            <a href="{{ route('news.index') }}" class="xcl-trigger-pill xcl-trigger-pill--trtn">
                <span class="xcl-trigger-pill__icon"><img src="/images/trtn/TRTN Logo 1.png" alt="" class="xcl-trigger-pill__img"></span>
                <span class="xcl-trigger-pill__label">TRTN</span>
            </a>
        </div>
    </button>

    {{-- ── Backdrop ─────────────────────────────────────────────────────────── --}}
    <div data-sb-backdrop
         class="xcl-sidebar-overlay"
         style="display:none"
         aria-hidden="true">
    </div>

    {{-- ── Sidebar panel ───────────────────────────────────────────────────── --}}
    <aside data-sb-panel class="xcl-sidebar" aria-label="Events dashboard">

        {{-- Mobile-only close button (bottom-sheet layout has no edge close-tab) --}}
        <button data-sb-close-tab class="xcl-sidebar__mobile-close" aria-label="Close dashboard">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

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
                        <button class="xcl-sb-game-btn xcl-sb-game-btn--ac" data-sb-game="ac" title="ACC PC">
                            <img src="/images/home/icons/ACC Logo.png" alt="ACC PC">
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

        {{-- News ticker --}}
        @if($tickerArticles->count() > 0)
        <div class="xcl-news-ticker">
            <div class="xcl-news-ticker__brand">
                <span class="xcl-news-ticker__brand-badge">
                    <img src="/images/trtn/TRTN Logo 1.png" alt="TRTN" class="xcl-news-ticker__brand-logo">
                </span>
                <span class="xcl-news-ticker__divider"></span>
            </div>
            <div class="xcl-news-ticker__track">
                {{-- Two identical copies back to back, animated 0% -> -50%,
                     so the loop is seamless — there's never a moment with
                     nothing on screen, regardless of article count. --}}
                <div class="xcl-news-ticker__scroll">
                    <div class="xcl-news-ticker__content">
                        @foreach($tickerArticles as $article)
                            <a href="{{ route('news.show', $article->slug) }}" class="xcl-news-ticker__item">{{ $article->title }}</a>
                            <span class="xcl-news-ticker__dot">&#9679;</span>
                        @endforeach
                    </div>
                    <div class="xcl-news-ticker__content" aria-hidden="true">
                        @foreach($tickerArticles as $article)
                            <a href="{{ route('news.show', $article->slug) }}" class="xcl-news-ticker__item" tabindex="-1">{{ $article->title }}</a>
                            <span class="xcl-news-ticker__dot">&#9679;</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

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
                                'iracing' => 'iRACING', 'ac' => 'ACC PC',
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
                                    <span>{{ $sbNextEvent->is_endurance ? $sbNextEvent->team_entries_count : $sbNextEvent->registrations_count }} / {{ $sbNextEvent->max_drivers ?? '∞' }}</span>
                                </div>
                                {{-- Platform icons bottom-left --}}
                                <div class="xcl-sb-next__hero-platforms">
                                    @foreach($nextPlatIcons as [$icon, $label])
                                    <span class="xcl-sb-next__hero-platform-icon">
                                        <i class="{{ $icon }}"></i> {{ $label }}
                                    </span>
                                    @endforeach
                                </div>

                                {{-- Race length bottom-right --}}
                                @if($sbNextEvent->raceDurationMinutes())
                                <div class="xcl-sb-next__hero-duration">
                                    <span class="xcl-sb-next__duration-badge">
                                        <i class="fa-solid fa-clock"></i> {{ $sbNextEvent->durationLabel() }}
                                    </span>
                                </div>
                                @endif
                            </div>

                            {{-- Info below image --}}
                            <div class="xcl-sb-next__info">

                                {{-- Badges row + scheduled day/date/time, aligned on one line --}}
                                <div class="xcl-sb-next__badges-row">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="xcl-sb-badge xcl-sb-badge--game">{{ $nextGameLabel }}</span>
                                        <span class="xcl-sb-badge xcl-sb-badge--sr">4.0 SR</span>
                                        @if($sbNextEvent->status === 'open')
                                            <span class="xcl-sb-badge xcl-sb-badge--open">OPEN</span>
                                        @else
                                            <span class="xcl-sb-badge xcl-sb-badge--closed">CLOSED</span>
                                        @endif
                                    </div>
                                    <span class="xcl-sb-next__next-time">
                                        {{ strtoupper($sbNextEvent->scheduledAtUk()->format('D, M d, g:iA T')) }}
                                    </span>
                                </div>

                                {{-- Race details --}}
                                <div class="xcl-sb-next__details">
                                    <div class="xcl-sb-next__detail-item">
                                        <span class="xcl-sb-next__detail-label">CAR CLASS</span>
                                        <span class="xcl-sb-next__detail-value">{{ $sbNextEvent->car_class ?? '—' }}</span>
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

                        <div class="xcl-sb-up-list">
                        @forelse($sbUpcoming as $event)
                        @php
                            $upPlatLabel = match($event->game) {
                                'acc'             => 'PS5 / XBOX',
                                'lmu','iracing','ac' => 'PC / STEAM',
                                default           => 'PC',
                            };
                            $upGameLabel = match($event->game) {
                                'acc' => 'ACC', 'lmu' => 'LMU',
                                'iracing' => 'iRACING', 'ac' => 'ACC PC',
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

                                <div class="xcl-sb-up-card__meta-row">
                                    <div class="xcl-sb-countdown xcl-sb-countdown--small">
                                        <span data-cd-d>00</span>D&nbsp;<span data-cd-h>00</span>H&nbsp;<span data-cd-m>00</span>M
                                    </div>
                                    <div class="xcl-sb-lobby xcl-sb-lobby--small">
                                        <i class="fa-solid fa-comments"></i>
                                        <span>{{ $event->is_endurance ? $event->team_entries_count : $event->registrations_count }} / {{ $event->max_drivers ?? '∞' }}</span>
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

                        <div class="xcl-sb-lb-scroll">
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
                        </div>

                        {{-- Pagination sits below the cropped table so it's never clipped --}}
                        <div data-sb-pagination class="xcl-sb-pagination" style="display:none"></div>
                    </div>
                    {{-- end col 3 --}}

                </div>

                {{-- ── Separator + Full-width Real-World Racing ────────────── --}}
                <div style="border-top:1px solid rgba(255,255,255,0.08);margin-top:1.5rem;margin-left:1.5rem;margin-right:1.5rem;padding:0 .25rem">
                    <div style="display:flex;align-items:center;gap:.75rem;padding:.9rem 0 .75rem">
                        <div class="xcl-sb-title" style="margin:0;white-space:nowrap">
                            <span>XCLUSIVE TEAM </span><span>EVENTS</span>
                        </div>
                        <div style="flex:1;height:1px;background:rgba(255,255,255,0.07)"></div>
                    </div>

                    <div class="xcl-sb-exclusive-events-row">
                        @foreach([0, 1] as $slot)
                        @php $te = $sbTeamEvents->get($slot); @endphp

                        @if($te)
                        <div class="xcl-sb-up-card"
                             data-countdown="{{ $te->starts_at->toIso8601String() }}">

                            <div class="xcl-sb-up-card__img-wrap" style="height:300px">
                                <img src="{{ $te->image_url ?? '/images/home/teams/XCLusive_Placeholder_ACC.png' }}"
                                     alt="{{ $te->title }}" loading="lazy"
                                     class="xcl-sb-up-card__img"
                                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                                <div class="xcl-sb-up-card__img-gradient"></div>

                                @if($te->participatingDrivers->isNotEmpty())
                                <div class="xcl-sb-drivers-row xcl-sb-drivers-row--overlay" title="{{ $te->participatingDrivers->pluck('name')->join(', ') }}">
                                    @foreach($te->participatingDrivers->take(4) as $pd)
                                    <span class="xcl-sb-drivers-row__avatar">
                                        @if($pd->photo_url)
                                        <img src="{{ $pd->photo_url }}" alt="{{ $pd->name }}">
                                        @else
                                        {{ $pd->initials() }}
                                        @endif
                                    </span>
                                    @endforeach
                                    @if($te->participatingDrivers->count() > 4)
                                    <span class="xcl-sb-drivers-row__more">+{{ $te->participatingDrivers->count() - 4 }} more</span>
                                    @endif
                                </div>
                                @endif

                                <div class="xcl-sb-up-card__title">
                                    {{ strtoupper($te->title) }}
                                    @if($te->watch_url)
                                    <span class="xcl-sb-live-badge">LIVE</span>
                                    @endif
                                </div>

                                <div class="xcl-sb-up-card__meta-row" style="justify-content:flex-start;gap:.5rem">
                                    @if($te->isLive())
                                        <span class="xcl-sb-live-badge" style="margin-left:0">LIVE</span>
                                    @elseif($te->starts_at->isFuture())
                                        <div class="xcl-sb-countdown xcl-sb-countdown--small">
                                            <span data-cd-d>00</span>D&nbsp;<span data-cd-h>00</span>H&nbsp;<span data-cd-m>00</span>M
                                        </div>
                                    @endif
                                    <div style="font-size:.65rem;color:#9ca3af;font-weight:600;white-space:nowrap">
                                        {{ $te->starts_at->timezone('Europe/London')->format('d M · H:i T') }}
                                    </div>
                                    @if($te->duration_minutes)
                                    <span class="xcl-sb-next__duration-badge" style="font-size:.6rem;padding:.1rem .4rem">
                                        <i class="fa-solid fa-clock"></i> {{ $te->duration_badge }}
                                    </span>
                                    @endif
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
                        <div class="xcl-sb-exclusive-events-row__empty" style="height:300px;border-radius:8px;border:1px dashed rgba(255,255,255,0.1);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.4rem;background:rgba(255,255,255,0.02)">
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
