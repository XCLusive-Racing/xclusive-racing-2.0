@php
use App\Models\Race;
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
    ->limit(4)
    ->get();
$sbUpcoming->loadCount('registrations');

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

<div x-data="{ ...eventsSidebar(), navbarOpen: false }" @keydown.escape.window="open = false" @open-events-sidebar.window="open = true" @navbar-toggled.window="navbarOpen = $event.detail.open; if($event.detail.open) open = false">

    {{-- ── Trigger tab ──────────────────────────────────────────────────────── --}}
    <button
        x-show="!navbarOpen"
        class="xcl-sidebar-trigger"
        :class="{ 'xcl-sidebar-trigger--open': open }"
        @click="open = !open"
        aria-label="Toggle events panel"
        :aria-expanded="open.toString()">
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
        <div class="xcl-sidebar-trigger__socials" @click.stop>
            <a href="{{ config('xcl.discord_url') }}" class="xcl-trigger-pill xcl-trigger-pill--discord" target="_blank" rel="noopener" title="Discord">
                <span class="xcl-trigger-pill__icon"><i class="fa-brands fa-discord"></i></span>
                <span class="xcl-trigger-pill__label">Discord</span>
            </a>
            <a href="#" class="xcl-trigger-pill xcl-trigger-pill--twitch" title="Twitch">
                <span class="xcl-trigger-pill__icon"><i class="fa-brands fa-twitch"></i></span>
                <span class="xcl-trigger-pill__label">Twitch</span>
            </a>
            <a href="https://www.instagram.com/xclusive_esport/" class="xcl-trigger-pill xcl-trigger-pill--instagram" target="_blank" rel="noopener" title="Instagram">
                <span class="xcl-trigger-pill__icon"><i class="fa-brands fa-instagram"></i></span>
                <span class="xcl-trigger-pill__label">Instagram</span>
            </a>
            <a href="#" class="xcl-trigger-pill xcl-trigger-pill--tiktok" title="TikTok">
                <span class="xcl-trigger-pill__icon"><i class="fa-brands fa-tiktok"></i></span>
                <span class="xcl-trigger-pill__label">TikTok</span>
            </a>
        </div>
    </button>

    {{-- ── Backdrop ─────────────────────────────────────────────────────────── --}}
    <div x-show="open"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="xcl-sidebar-overlay"
         @click="open = false"
         style="display:none"
         aria-hidden="true">
    </div>

    {{-- ── Vertical CLOSE tab (left edge of sidebar, outside overflow:hidden) ── --}}
    <button class="xcl-sidebar__close-tab"
            x-show="open"
            @click="open = false"
            aria-label="Close panel"
            style="display:none">
        <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
        <span>CLOSE</span>
    </button>

    {{-- ── Sidebar panel ───────────────────────────────────────────────────── --}}
    <aside class="xcl-sidebar" :class="{ 'xcl-sidebar--open': open }" aria-label="Events dashboard">

        {{-- Header --}}
        <div class="xcl-sidebar__header xcl-sidebar__header--v2">
            <div class="xcl-sidebar__header-top">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="xcl-sidebar__logo-text">XCL EVENTS DASHBOARD</span>
                    <div class="xcl-sidebar__game-filters">
                        <button class="xcl-sb-game-btn"
                                :class="{ 'active': gameFilter === 'all' }"
                                @click="gameFilter = 'all'" title="All games">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                        <button class="xcl-sb-game-btn xcl-sb-game-btn--acc"
                                :class="{ 'active': gameFilter === 'acc' }"
                                @click="gameFilter = 'acc'" title="Assetto Corsa Competizione">
                            <img src="/images/home/icons/ACC Logo.png" alt="ACC">
                        </button>
                        <button class="xcl-sb-game-btn xcl-sb-game-btn--lmu"
                                :class="{ 'active': gameFilter === 'lmu' }"
                                @click="gameFilter = 'lmu'" title="Le Mans Ultimate">
                            <img src="/images/home/icons/LM Logo.png" alt="LMU">
                        </button>
                        <button class="xcl-sb-game-btn xcl-sb-game-btn--iracing"
                                :class="{ 'active': gameFilter === 'iracing' }"
                                @click="gameFilter = 'iracing'" title="iRacing">
                            <img src="/images/home/icons/iR Logo.png" alt="iRacing">
                        </button>
                        <button class="xcl-sb-game-btn xcl-sb-game-btn--ac"
                                :class="{ 'active': gameFilter === 'ac' }"
                                @click="gameFilter = 'ac'" title="AC Rally">
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
            <button class="xcl-sidebar-tab" :class="{ 'active': activeTab === 'daily' }"
                    @click="activeTab = 'daily'" role="tab">DAILY EVENTS</button>
            <button class="xcl-sidebar-tab" :class="{ 'active': activeTab === 'championships' }"
                    @click="activeTab = 'championships'" role="tab">CHAMPIONSHIPS</button>
            <button class="xcl-sidebar-tab" :class="{ 'active': activeTab === 'timetrials' }"
                    @click="activeTab = 'timetrials'" role="tab">TIME TRIALS</button>
        </div>

        {{-- ── Scrollable content ──────────────────────────────────────────── --}}
        <div class="xcl-sidebar__content">

            {{-- ═══ DAILY EVENTS ══════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'daily'" x-transition>
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
                        <div x-show="gameFilter !== 'all' && gameFilter !== '{{ $sbNextEvent->game }}'" class="xcl-sb-empty" style="display:none">
                            <p>NO <span x-text="gameFilter.toUpperCase()"></span> EVENTS</p>
                            <p>No upcoming events for this game</p>
                        </div>
                        <div class="xcl-sb-next"
                             x-show="gameFilter === 'all' || gameFilter === '{{ $sbNextEvent->game }}'"
                             x-data="countdownTimer('{{ $sbNextEvent->scheduled_at->toIso8601String() }}')">

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

                                {{-- Race icon centered on hero --}}
                                @if($sbNextEvent->icon)
                                <div class="xcl-sb-next__icon-overlay">
                                    <img src="{{ $sbNextEvent->icon_url }}" alt="{{ $sbNextEvent->title }}">
                                </div>
                                @endif

                                {{-- Countdown top-left --}}
                                <div class="xcl-sb-countdown xcl-sb-countdown--hero">
                                    <span class="xcl-sb-countdown__label">STARTS IN</span>
                                    <span class="xcl-sb-countdown__time">
                                        <span x-text="String(d).padStart(2,'0')"></span>D&nbsp;<span x-text="String(h).padStart(2,'0')"></span>H&nbsp;<span x-text="String(m).padStart(2,'0')"></span>M&nbsp;<span x-text="String(s).padStart(2,'0')"></span>S
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

                                {{-- Badges row: sim, SR, status --}}
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
                             x-show="gameFilter === 'all' || gameFilter === '{{ $event->game }}'"
                             x-data="countdownTimer('{{ $event->scheduled_at->toIso8601String() }}')">

                            <div class="xcl-sb-up-card__img-wrap">
                                @if($event->image)
                                    <img src="{{ $event->image_url }}"
                                         alt="{{ $event->title }}" loading="lazy"
                                         class="xcl-sb-up-card__img">
                                @else
                                    <div class="xcl-sb-up-card__img-placeholder"></div>
                                @endif
                                <div class="xcl-sb-up-card__img-gradient"></div>

                                {{-- Race icon centered on card --}}
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
                                        <span x-text="String(d).padStart(2,'0')"></span>D&nbsp;<span x-text="String(h).padStart(2,'0')"></span>H&nbsp;<span x-text="String(m).padStart(2,'0')"></span>M
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
                                   type="text"
                                   x-model="searchQuery"
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
                            <tbody>
                                <template x-if="filteredLeaderboard.length === 0">
                                    <tr>
                                        <td colspan="3" style="text-align:center;color:#8B9BB4;padding:1.5rem .5rem;font-size:.8rem">
                                            No results found
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="driver in paginatedLeaderboard" :key="driver.pos">
                                    <tr :class="{ 'top-3': driver.pos <= 3 }">
                                        <td class="xcl-lb-pos" x-text="driver.pos"></td>
                                        <td>
                                            <div class="xcl-lb-driver">
                                                <div class="xcl-lb-flag-placeholder" x-text="driver.country"></div>
                                                <span class="xcl-lb-name" x-text="driver.name"></span>
                                                <span x-show="driver.supporter" title="Supporter"
                                                      style="font-size:.6rem;color:#f59e0b;line-height:1">★</span>
                                            </div>
                                        </td>
                                        <td class="xcl-lb-gain"
                                            :style="driver.pos <= 3 ? 'color:#C8FF00' : 'color:#8B9BB4'"
                                            x-text="driver.gain.toLocaleString()">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <div class="xcl-sb-pagination" x-show="totalPages > 1">
                            <button @click="currentPage = 1" :disabled="currentPage === 1">&laquo;</button>
                            <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1">&lsaquo;</button>
                            <template x-for="p in totalPages" :key="p">
                                <button @click="currentPage = p"
                                        :class="{ active: currentPage === p }"
                                        x-text="p">
                                </button>
                            </template>
                            <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages">&rsaquo;</button>
                            <button @click="currentPage = totalPages" :disabled="currentPage === totalPages">&raquo;</button>
                        </div>
                    </div>
                    {{-- end col 3 --}}

                </div>
            </div>
            {{-- end daily tab --}}

            {{-- ═══ CHAMPIONSHIPS ═════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'championships'" x-transition style="display:none">
                <div class="xcl-sb-empty">
                    <p>CHAMPIONSHIPS</p>
                    <p>Season standings coming soon</p>
                </div>
            </div>

            {{-- ═══ TIME TRIALS ════════════════════════════════════════════════ --}}
            <div x-show="activeTab === 'timetrials'" x-transition style="display:none">
                <div class="xcl-sb-empty">
                    <p>TIME TRIALS</p>
                    <p>Hotlap records coming soon</p>
                </div>
            </div>

        </div>

    </aside>

</div>