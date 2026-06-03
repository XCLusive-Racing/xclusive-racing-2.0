{{-- ─────────────────────────────────────────────────────────────────────────── --}}
{{-- Meet Our Team sectie: SVG hexagon achtergrond, diagonale top, Alpine carousel --}}
{{-- ─────────────────────────────────────────────────────────────────────────── --}}
<script>
window.__xclTeams = [
    { id: 'iracing', image: '/images/home/teams/XCLusive_Placeholder_iRacing.png', logo: '/images/home/logos/iracing-logo-white.png', alt: 'iRacing' },
    { id: 'lmu',     image: '/images/home/teams/XCLusive_Placeholder_lmu.png',     logo: '/images/home/logos/LeMans-Logo.png',         alt: 'Le Mans Ultimate' },
    { id: 'acc',     image: '/images/home/teams/XCLusive_Placeholder_ACC.png',     logo: '/images/home/logos/ACC-logo.png',             alt: 'ACC Console' }
];
</script>

<section class="meet-team-section py-5"
         x-data="{
             active: 0,
             slideClass: '',
             teams: window.__xclTeams,
             get leftIndex()  { return (this.active - 1 + this.teams.length) % this.teams.length; },
             get rightIndex() { return (this.active + 1) % this.teams.length; },
             prev() {
                 this.slideClass = '';
                 this.$nextTick(() => { this.active = (this.active - 1 + this.teams.length) % this.teams.length; this.slideClass = 'swipe-in-right'; });
             },
             next() {
                 this.slideClass = '';
                 this.$nextTick(() => { this.active = (this.active + 1) % this.teams.length; this.slideClass = 'swipe-in-left'; });
             }
         }">

    {{-- ── Hexagon achtergrond — absoluut gepositioneerd buiten animated row --}}
    <div aria-hidden="true" class="meet-team-pattern"></div>

    <div class="container-xl position-relative" style="z-index:1;">

        {{-- ── Logo en sectiekop ────────────────────────────────────────────────── --}}
        <div class="text-center mb-4">
            <h2 class="fw-black fst-italic text-uppercase mb-2"
                style="font-size:clamp(1.2rem, 2.5vw, 1.8rem);color:white;padding-right:0.15em">
                MEET OUR TEAM
            </h2>

            {{-- Decoratieve lijn --}}
            <hr style="width:80px;border-color:white;border-width:2px;opacity:0.6;margin:0 auto 2.5rem;">
        </div>

        {{-- ── Carousel: drie kaarten naast elkaar, midden actief ──────────────── --}}
        <div class="row g-0 align-items-center mb-4" :class="slideClass">

            {{-- Linker kaart: verborgen op mobiel --}}
            <div class="col-4 d-none d-md-block">
                <div class="car-clip-left team-card-side position-relative">
                    <img :src="teams[leftIndex].image"
                         :alt="teams[leftIndex].alt"
                         class="car-image">
                    {{-- Platform logo badge linksonder --}}
                    <div class="logo-badge">
                        <img :src="teams[leftIndex].logo"
                             :alt="teams[leftIndex].alt"
                             style="height:28px;width:auto;object-fit:contain;">
                    </div>
                </div>
            </div>

            {{-- Midden kaart: volledig breed op mobiel --}}
            <div class="col-12 col-md-4">
                <div class="car-clip-center team-card-center position-relative">
                    <img :src="teams[active].image"
                         :alt="teams[active].alt"
                         class="car-image car-image--center">
                    <div class="logo-badge">
                        <img :src="teams[active].logo"
                             :alt="teams[active].alt"
                             style="height:28px;width:auto;object-fit:contain;">
                    </div>
                </div>
            </div>

            {{-- Rechter kaart: verborgen op mobiel --}}
            <div class="col-4 d-none d-md-block">
                <div class="car-clip-right team-card-side position-relative">
                    <img :src="teams[rightIndex].image"
                         :alt="teams[rightIndex].alt"
                         class="car-image">
                    <div class="logo-badge">
                        <img :src="teams[rightIndex].logo"
                             :alt="teams[rightIndex].alt"
                             style="height:28px;width:auto;object-fit:contain;">
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Swipe navigatie: « SWIPE » in geel ──────────────────────────────── --}}
        <div class="d-flex justify-content-center align-items-center gap-4 pt-2">
            <div class="swipe-nav" @click="prev()" style="color:#d4ee6a">
                <span>«</span>
            </div>
            <div class="swipe-nav fw-black text-uppercase" style="font-size:1.1rem;cursor:default;color:white;font-style:normal;font-family:'Poppins',sans-serif;">
                SWIPE
            </div>
            <div class="swipe-nav" @click="next()" style="color:#d4ee6a">
                <span>»</span>
            </div>
        </div>

    </div>
</section>