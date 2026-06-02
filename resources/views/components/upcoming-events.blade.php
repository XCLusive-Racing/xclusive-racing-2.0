@php
$events = [
    [
        'id'         => 1,
        'game'       => 'acc',
        'gameLabel'  => 'ACC',
        'gameColor'  => '#7c3aed',
        'eventColor' => '#dc2626',
        'title'      => 'GT3 Sprint Cup',
        'carClasses' => ['GT3'],
        'track'      => 'Kyalami',
        'duration'   => '20min Race',
        'next'       => 'Tonight 20:00',
        'logo'       => '/images/events/GT3 Sprint Cup v3.png',
        'trackImage' => '/images/events/kyalami.png',
        'minEntry'   => '4.0 SR',
    ],
    [
        'id'         => 2,
        'game'       => 'iracing',
        'gameLabel'  => 'iRacing',
        'gameColor'  => '#2563eb',
        'title'      => 'The Ring',
        'carClasses' => ['GT3'],
        'track'      => 'Nürburgring',
        'duration'   => '3 Laps',
        'next'       => 'Tonight 21:00',
        'image'      => null,
    ],
    [
        'id'         => 3,
        'game'       => 'lmu',
        'gameLabel'  => 'Le Mans Ultimate',
        'gameColor'  => '#ea580c',
        'title'      => 'Endurance Series',
        'carClasses' => ['HY', 'LMP2'],
        'track'      => 'Le Mans',
        'duration'   => '60min Race',
        'next'       => 'Tomorrow 18:00',
        'image'      => null,
    ],
    [
        'id'         => 4,
        'game'       => 'acc',
        'gameLabel'  => 'ACC',
        'gameColor'  => '#7c3aed',
        'title'      => 'BMW M2 Cup',
        'carClasses' => ['M2 CUP'],
        'track'      => 'Brands Hatch',
        'duration'   => '10min Race',
        'next'       => 'Tomorrow 20:00',
        'image'      => null,
    ],
];
@endphp

<script>window.__xclEvents = @json($events);</script>

<section id="events" class="upcoming-events-section py-5 px-3" style="position:relative"
         x-data="{
             filter: 'all',
             page: 0,
             events: window.__xclEvents,
             get filtered() {
                 return this.filter === 'all' ? this.events : this.events.filter(e => e.game === this.filter);
             },
             get visible() {
                 return this.filtered.slice(this.page * 4, this.page * 4 + 4);
             },
             get totalPages() {
                 return Math.ceil(this.filtered.length / 4);
             },
             setFilter(f) { this.filter = f; this.page = 0; },
             prev() { if (this.page > 0) this.page--; },
             next() { if (this.page < this.totalPages - 1) this.page++; }
         }">

    {{-- Topo overlay --}}
    <div class="about-section__topo" style="background-image:url('/topo.png');"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        {{-- Title row --}}
        <div class="mb-3">
            <h2 class="fw-black text-uppercase fst-italic mb-0 about-section__heading"
                style="font-size:clamp(1.2rem, 2.5vw, 1.8rem)">UPCOMING EVENTS</h2>
        </div>

        {{-- Divider --}}
        <div class="section-divider mb-4" style="margin-left:0"></div>

        {{-- Filter buttons + nav controls on same line --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div class="d-flex gap-2 flex-wrap">
                <button @click="setFilter('all')"
                        :class="filter === 'all' ? 'xcl-filter-btn--active' : ''"
                        class="xcl-filter-btn fw-bold text-uppercase">All</button>
                <button @click="setFilter('acc')"
                        :class="filter === 'acc' ? 'xcl-filter-btn--acc-active' : ''"
                        class="xcl-filter-btn">
                    <img src="/images/home/icons/ACC Logo.png" height="20" alt="ACC">
                </button>
                <button @click="setFilter('lmu')"
                        :class="filter === 'lmu' ? 'xcl-filter-btn--lmu-active' : ''"
                        class="xcl-filter-btn">
                    <img src="/images/home/icons/LM Logo.png" height="20" alt="LMU">
                </button>
                <button @click="setFilter('iracing')"
                        :class="filter === 'iracing' ? 'xcl-filter-btn--iracing-active' : ''"
                        class="xcl-filter-btn">
                    <img src="/images/home/icons/iR Logo.png" height="20" alt="iRacing">
                </button>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button @click="prev()" :disabled="page === 0" class="xcl-nav-btn" aria-label="Previous">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polyline points="15,18 9,12 15,6"/>
                    </svg>
                </button>
                <button @click="next()" :disabled="page >= totalPages - 1" class="xcl-nav-btn" aria-label="Next">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polyline points="9,18 15,12 9,6"/>
                    </svg>
                </button>
                <a href="/race" class="btn fw-black text-uppercase text-white px-4 py-2"
                   style="background:#7c3aed;font-size:.85rem">SEE ALL EVENTS</a>
            </div>
        </div>

        {{-- Cards --}}
        <div class="row g-3">
            <template x-if="visible.length === 0">
                <div class="col-12 text-center py-5" style="color:#6b7280">No upcoming events for this platform.</div>
            </template>
            <template x-for="event in visible" :key="event.id">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="xcl-event-card h-100 d-flex flex-column"
                         :style="event.eventColor ? 'border-color:' + event.eventColor + '55' : ''">

                        {{-- Image area --}}
                        <div class="xcl-event-card__img" style="position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center">
                            <template x-if="event.trackImage">
                                <img :src="event.trackImage" aria-hidden="true"
                                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:blur(5px);transform:scale(1.08);opacity:0.7">
                            </template>
                            <template x-if="!event.trackImage && !event.logo && !event.image">
                                <div class="xcl-event-card__img-placeholder" style="position:absolute;inset:0"></div>
                            </template>
                            <template x-if="event.logo">
                                <img :src="event.logo" :alt="event.title"
                                     style="position:relative;z-index:1;max-height:85%;max-width:80%;object-fit:contain;display:block">
                            </template>
                            <template x-if="event.image && !event.logo">
                                <img :src="event.image" :alt="event.title" loading="lazy"
                                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                            </template>
                        </div>

                        {{-- Platform color bar --}}
                        <div class="xcl-event-card__bar"
                             :style="'background:' + (event.eventColor || event.gameColor)"></div>

                        {{-- Body --}}
                        <div class="xcl-event-card__body d-flex flex-column flex-grow-1 p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="xcl-event-card__badge fw-bold text-uppercase text-white"
                                      :style="'background:' + (event.eventColor || event.gameColor)"
                                      x-text="event.gameLabel"></span>
                                <div class="d-flex gap-1 align-items-center">
                                    <template x-if="event.minEntry">
                                        <span class="xcl-event-card__min-entry" x-text="event.minEntry"></span>
                                    </template>
                                    <span class="xcl-event-card__status xcl-event-card__status--open">OPEN</span>
                                </div>
                            </div>

                            <h3 class="fw-black text-uppercase fst-italic text-white mb-2"
                                style="font-size:.9rem" x-text="event.title"></h3>

                            {{-- Car classes --}}
                            <p class="xcl-event-card__track mb-1">
                                Ranked Race in:
                                <span class="fw-bold text-white"
                                      x-text="event.carClasses ? event.carClasses.join(' / ') : ''"></span>
                            </p>

                            {{-- Track --}}
                            <p class="xcl-event-card__track mb-3">
                                Track: <span class="fw-bold text-white" x-text="event.track || '—'"></span>
                            </p>

                            <div class="d-flex gap-2 flex-wrap mt-auto">
                                <span class="xcl-event-pill">
                                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24" class="me-1">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/>
                                    </svg>
                                    <span x-text="event.duration"></span>
                                </span>
                                <span class="xcl-event-pill">
                                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24" class="me-1">
                                        <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>
                                    </svg>
                                    <span x-text="'Next: ' + event.next"></span>
                                </span>
                            </div>

                            <a href="/race"
                               class="btn fw-black text-uppercase text-white w-100 mt-3"
                               style="font-size:.8rem"
                               :style="'background:' + (event.eventColor || event.gameColor)">VIEW EVENT</a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    </div>
</section>
