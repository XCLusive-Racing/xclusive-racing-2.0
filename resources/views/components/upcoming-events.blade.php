@php
use App\Models\Race;

$upcomingRaces = Race::where('scheduled_at', '>', now())
    ->orderBy('scheduled_at')
    ->limit(24)
    ->get()
    ->map(function ($race) {
        $titleLower = strtolower($race->title ?? '');

        if ($race->is_championship) {
            $badge   = 'SR5 GRID';
            $overlay = 'rgba(123,47,190,0.55)';
        } elseif (str_contains($titleLower, 'multiclass') || str_contains($titleLower, 'endurance')) {
            $badge   = 'MULTICLASS';
            $overlay = 'rgba(0,210,120,0.45)';
        } else {
            $badge   = 'DAILY SPRINT';
            $overlay = 'rgba(0,180,160,0.45)';
        }

        $gameShort = match($race->game) {
            'acc'     => 'ACC',
            'lmu'     => 'LMU',
            'iracing' => 'iRACING',
            'ac'      => 'AC RALLY',
            default   => strtoupper($race->game),
        };

        $platforms = match($race->game) {
            'acc'     => ['fa-brands fa-playstation', 'fa-brands fa-xbox'],
            'lmu'     => ['fa-solid fa-desktop'],
            'iracing' => ['fa-solid fa-desktop'],
            'ac'      => ['fa-solid fa-desktop'],
            default   => [],
        };

        return [
            'id'       => $race->id,
            'game'     => $race->game,
            'image'    => $race->image_url,
            'icon'     => $race->icon_url,
            'badge'    => $badge,
            'badgeSub' => $gameShort,
            'dayTime'  => strtoupper($race->scheduledAtUk()->format('l')) . ' / ' . strtoupper($race->scheduledAtUk()->format('g:i A T')),
            'dateMeta' => $race->scheduledAtUk()->format('D, M d') . ($race->track ? ' | ' . $race->track : ''),
            'url'      => route('events.show', $race),
        ];
    });
@endphp

<script>window.__xclEvents = @json($upcomingRaces);</script>

<section id="events" class="upcoming-events-section py-5 px-3" style="position:relative"
         x-data="{
             filter: 'all',
             page: 0,
             events: window.__xclEvents,
             get filtered() {
                 return this.filter === 'all' ? this.events : this.events.filter(e => e.game === this.filter);
             },
             get visible() {
                 return this.filtered.slice(this.page * 6, this.page * 6 + 6);
             },
             get totalPages() {
                 return Math.max(1, Math.ceil(this.filtered.length / 6));
             },
             setFilter(f) { this.filter = f; this.page = 0; },
             prev() { if (this.page > 0) this.page--; },
             next() { if (this.page < this.totalPages - 1) this.page++; }
         }">

    <div class="about-section__topo" style="background-image:url('/topo.png');"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        {{-- Title --}}
        <div class="mb-3">
            <h2 class="fw-black text-uppercase fst-italic mb-0 about-section__heading"
                style="font-size:clamp(1.2rem, 2.5vw, 1.8rem)">UPCOMING EVENTS</h2>
        </div>

        <div class="section-divider mb-4" style="margin-left:0"></div>

        {{-- Filter + nav --}}
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
                <button @click="setFilter('ac')"
                        :class="filter === 'ac' ? 'xcl-filter-btn--ac-active' : ''"
                        class="xcl-filter-btn">
                    <img src="/images/home/icons/AC R Logo.png" height="20" alt="AC Rally">
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
                <a href="/events" class="btn fw-black text-uppercase text-white px-4 py-2"
                   style="background:#7c3aed;font-size:.85rem">SEE ALL EVENTS</a>
            </div>
        </div>

        {{-- Cards --}}
        <div class="row row-cols-3 g-3">
            <template x-if="visible.length === 0">
                <div class="col-12 text-center py-5" style="color:#6b7280">
                    No upcoming events for this platform.
                </div>
            </template>
            <template x-for="event in visible" :key="event.id">
                <div class="col">
                    <div class="xcl-ec2">

                        {{-- Image 16:9 --}}
                        <div class="xcl-ec2__img-wrap">
                            <template x-if="event.image">
                                <img :src="event.image" :alt="event.badge" class="xcl-ec2__img">
                            </template>
                            <template x-if="!event.image">
                                <div class="xcl-ec2__img-placeholder"></div>
                            </template>

                            {{-- Center overlay: icon or text badge --}}
                            <div class="xcl-ec2__badge-wrap">
                                <template x-if="event.icon">
                                    <div class="xcl-ec2__icon-badge">
                                        <img :src="event.icon" :alt="event.badge" class="xcl-ec2__icon-badge-img">
                                    </div>
                                </template>
                                <template x-if="!event.icon">
                                    <div class="xcl-ec2__badge">
                                        <div class="xcl-ec2__badge-main" x-text="event.badge"></div>
                                        <div class="xcl-ec2__badge-sub" x-text="event.badgeSub"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Info below image --}}
                        <div class="xcl-ec2__body">
                            <div class="xcl-ec2__time" x-text="event.dayTime"></div>
                            <div class="xcl-ec2__meta" x-text="event.dateMeta"></div>
                            <a :href="event.url" class="xcl-see-event-btn">SEE EVENT</a>
                        </div>

                    </div>
                </div>
            </template>
        </div>

    </div>
</section>