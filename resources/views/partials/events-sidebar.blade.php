@php
use App\Models\Race;
use App\Models\User;

$now = now();

$sbNextEvent = Race::where('scheduled_at', '>', $now)
    ->orderBy('scheduled_at')
    ->first();

$sbUpcoming = Race::where('scheduled_at', '>', $now)
    ->when($sbNextEvent, fn($q) => $q->where('id', '!=', $sbNextEvent->id))
    ->orderBy('scheduled_at')
    ->limit(3)
    ->get();

$sbEventInfo = [
    'CAR CLASS' => $sbNextEvent?->title ?? '—',
    'TEMP'      => '—',
    'CLOUDS'    => '—',
    'RAIN'      => '0%',
];

$sbLeaderboard = User::where('elo_acc', '>', 0)
    ->orderByDesc('elo_acc')
    ->limit(40)
    ->get()
    ->values()
    ->map(fn($u, $i) => [
        'pos'     => $i + 1,
        'name'    => $u->name,
        'country' => strtoupper($u->country ?? 'XX'),
        'gain'    => (int)($u->elo_acc ?? 0),
    ]);

$sbPlatforms = [
    'acc'     => ['PS', 'XB'],
    'lmu'     => ['PC'],
    'iracing' => ['PC'],
];

$sbBadges = [
    ['label' => 'DAILY SPRINT', 'bg' => '#7B2FBE', 'color' => '#fff'],
    ['label' => 'DYS GRD',      'bg' => '#16a34a', 'color' => '#fff'],
    ['label' => 'ROOKIES ONLY', 'bg' => '#ea580c', 'color' => '#fff'],
];
@endphp

<script>window.__xclLeaderboard = @json($sbLeaderboard);</script>

<div x-data="{
    open: false,
    activeTab: 'daily',
    gameFilter: 'all',
    searchQuery: '',
    currentPage: 1,
    sortCol: 'pos',
    sortDir: 'asc',
    leaderboard: window.__xclLeaderboard || [],

    get filteredLeaderboard() {
        const q = this.searchQuery.toLowerCase();
        let data = q
            ? this.leaderboard.filter(d => d.name.toLowerCase().includes(q))
            : [...this.leaderboard];

        data.sort((a, b) => {
            let av = a[this.sortCol], bv = b[this.sortCol];
            if (typeof av === 'string') { av = av.toLowerCase(); bv = bv.toLowerCase(); }
            if (av < bv) return this.sortDir === 'asc' ? -1 : 1;
            if (av > bv) return this.sortDir === 'asc' ? 1 : -1;
            return 0;
        });
        return data;
    },

    get paginatedLeaderboard() {
        const start = (this.currentPage - 1) * 10;
        return this.filteredLeaderboard.slice(start, start + 10);
    },

    get totalPages() {
        return Math.max(1, Math.ceil(this.filteredLeaderboard.length / 10));
    },

    toggleSort(col) {
        if (this.sortCol === col) {
            this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortCol = col;
            this.sortDir = 'asc';
        }
        this.currentPage = 1;
    },

    sortIcon(col) {
        if (this.sortCol !== col) return '↕';
        return this.sortDir === 'asc' ? '↑' : '↓';
    },

    init() {
        this.$watch('open', val => {
            document.body.style.overflow = val ? 'hidden' : '';
        });
        this.$watch('searchQuery', () => { this.currentPage = 1; });
    }
}" @keydown.escape.window="open = false">

    {{-- ── Trigger tab ─────────────────────────────────────────────────────── --}}
    <button
        class="xcl-sidebar-trigger"
        :class="{ 'xcl-sidebar-trigger--open': open }"
        @click="open = !open"
        aria-label="Toggle events panel"
        :aria-expanded="open.toString()">
        <i class="fa-solid fa-chevron-right xcl-sidebar-trigger__arrow"></i>
        <span class="xcl-sidebar-trigger__text">UPCOMING EVENTS</span>
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

    {{-- ── Sidebar panel ───────────────────────────────────────────────────── --}}
    <aside class="xcl-sidebar" :class="{ 'xcl-sidebar--open': open }" aria-label="Events dashboard">

        {{-- Header --}}
        <div class="xcl-sidebar__header">
            <div class="xcl-sidebar__header-top">
                <span class="xcl-sidebar__logo-text">XCL EVENTS DASHBOARD</span>
                <button class="xcl-sidebar__close" @click="open = false" aria-label="Close">&#215;</button>
            </div>
            <div class="xcl-sidebar__game-filters">
                <button class="xcl-sb-game-btn"
                        :class="{ 'active': gameFilter === 'all' }"
                        @click="gameFilter = 'all'"
                        title="All games">
                    <i class="fa-solid fa-grip"></i>
                </button>
                <button class="xcl-sb-game-btn xcl-sb-game-btn--acc"
                        :class="{ 'active': gameFilter === 'acc' }"
                        @click="gameFilter = 'acc'"
                        title="Assetto Corsa Competizione">
                    <img src="/images/home/icons/ACC Logo.png" alt="ACC">
                </button>
                <button class="xcl-sb-game-btn xcl-sb-game-btn--lmu"
                        :class="{ 'active': gameFilter === 'lmu' }"
                        @click="gameFilter = 'lmu'"
                        title="Le Mans Ultimate">
                    <img src="/images/home/icons/LM Logo.png" alt="LMU">
                </button>
                <button class="xcl-sb-game-btn xcl-sb-game-btn--iracing"
                        :class="{ 'active': gameFilter === 'iracing' }"
                        @click="gameFilter = 'iracing'"
                        title="iRacing">
                    <img src="/images/home/icons/iR Logo.png" alt="iRacing">
                </button>
                <button class="xcl-sb-game-btn xcl-sb-game-btn--ac"
                        :class="{ 'active': gameFilter === 'ac' }"
                        @click="gameFilter = 'ac'"
                        title="AC Rally">
                    <img src="/images/home/icons/AC R Logo.png" alt="AC Rally">
                </button>
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
                        <div class="xcl-sb-next">
                            <div class="xcl-sb-next__img-wrap">
                                @if($sbNextEvent->image)
                                    <img src="{{ asset('storage/'.$sbNextEvent->image) }}"
                                         alt="{{ $sbNextEvent->title }}" loading="lazy">
                                @else
                                    <div style="width:100%;aspect-ratio:16/9;background:linear-gradient(135deg,#1F1040 0%,#0B0B1A 100%)"></div>
                                @endif
                                <span class="xcl-sb-next__badge">DAILY SPRINT</span>
                                <div class="xcl-sb-next__platforms">
                                    @foreach($sbPlatforms[$sbNextEvent->game] ?? [] as $platform)
                                        <span class="xcl-sb-next__platform">{{ $platform }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <p class="xcl-sb-next__time">
                                {{ strtoupper($sbNextEvent->scheduledAtUk()->format('l')) }} /
                                {{ strtoupper($sbNextEvent->scheduledAtUk()->format('gA T')) }}
                            </p>
                            <p class="xcl-sb-next__circuit">
                                {{ $sbNextEvent->scheduledAtUk()->format('D, M d') }}
                                @if($sbNextEvent->track) | {{ $sbNextEvent->track }} @endif
                            </p>
                            <div class="xcl-sb-next__actions">
                                <a href="{{ route('race.show', $sbNextEvent) }}" class="xcl-sb-next__more-info">More Info</a>
                                <a href="{{ route('race.show', $sbNextEvent) }}" class="xcl-sb-next__enter">ENTER</a>
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

                    {{-- ─ COLUMN 2: UPCOMING + EVENT INFO ──────────────────── --}}
                    <div class="xcl-sb-col">
                        <div class="xcl-sb-title">
                            <span>UPCOMING </span><span>EVENTS</span>
                        </div>

                        @forelse($sbUpcoming as $i => $event)
                        @php $badge = $sbBadges[$i % count($sbBadges)]; @endphp
                        <div class="xcl-sb-event-row">
                            <div class="xcl-sb-event-row__thumb">
                                @if($event->image)
                                    <img src="{{ asset('storage/'.$event->image) }}"
                                         alt="{{ $event->title }}" loading="lazy">
                                @else
                                    <div class="xcl-sb-event-row__thumb-placeholder"
                                         style="color:{{ $event->gameColor() }}">
                                        {{ strtoupper(substr($event->game, 0, 3)) }}
                                    </div>
                                @endif
                                <span class="xcl-sb-event-row__thumb-badge"
                                      style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }}">
                                    {{ $badge['label'] }}
                                </span>
                            </div>
                            <div class="xcl-sb-event-row__info">
                                <p class="xcl-sb-event-row__date">
                                    {{ strtoupper($event->scheduledAtUk()->format('D d M')) }}
                                    &bull; {{ $event->scheduledAtUk()->format('g:iA') }}
                                </p>
                                <p class="xcl-sb-event-row__circuit">{{ $event->track ?? '—' }}</p>
                            </div>
                            <a href="{{ route('race.show', $event) }}" class="xcl-sb-event-row__enter">ENTER</a>
                        </div>
                        @empty
                        <p style="color:#8B9BB4;font-size:.8rem;padding:.5rem 0">No further events scheduled</p>
                        @endforelse

                        {{-- Event Info --}}
                        @if($sbNextEvent)
                        <div class="xcl-sb-info">
                            <div class="xcl-sb-title" style="margin-top:0">
                                <span>EVENT </span><span>INFO</span>
                            </div>
                            <div class="xcl-sb-info__grid">
                                @foreach($sbEventInfo as $label => $value)
                                <div class="xcl-sb-info__cell">
                                    <span class="xcl-sb-info__label">{{ $label }}</span>
                                    <span class="xcl-sb-info__value">{{ $value }}</span>
                                </div>
                                @endforeach
                            </div>
                            <a href="{{ route('race.show', $sbNextEvent) }}" class="xcl-sb-info__req-link">
                                REQUIREMENTS &rarr;
                            </a>
                        </div>
                        @endif
                    </div>
                    {{-- end col 2 --}}

                    {{-- ─ COLUMN 3: WEEKLY LEADERBOARD ─────────────────────── --}}
                    <div class="xcl-sb-col">
                        <div class="xcl-sb-title">
                            <span>WEEKLY </span><span>LEADERBOARD</span>
                        </div>

                        {{-- Search --}}
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

                        {{-- Table --}}
                        <table class="xcl-sb-lb-table">
                            <thead>
                                <tr>
                                    <th @click="toggleSort('pos')"
                                        :class="{ active: sortCol === 'pos' }">
                                        POS <span x-text="sortIcon('pos')"></span>
                                    </th>
                                    <th @click="toggleSort('name')"
                                        :class="{ active: sortCol === 'name' }">
                                        DRIVER <span x-text="sortIcon('name')"></span>
                                    </th>
                                    <th @click="toggleSort('gain')"
                                        :class="{ active: sortCol === 'gain' }"
                                        style="text-align:right">
                                        GAIN <span x-text="sortIcon('gain')"></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="paginatedLeaderboard.length === 0">
                                    <tr>
                                        <td colspan="3" style="text-align:center;color:#8B9BB4;padding:1.5rem .5rem;font-size:.8rem">
                                            No results found
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="(driver, idx) in paginatedLeaderboard" :key="driver.pos">
                                    <tr :class="{ 'top-3': driver.pos <= 3 }">
                                        <td class="xcl-lb-pos" x-text="driver.pos"></td>
                                        <td>
                                            <div class="xcl-lb-driver">
                                                <div class="xcl-lb-flag-placeholder" x-text="driver.country"></div>
                                                <span class="xcl-lb-name" x-text="driver.name"></span>
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

                        {{-- Pagination --}}
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
                {{-- end grid --}}
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
        {{-- end xcl-sidebar__content --}}

    </aside>

</div>