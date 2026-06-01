{{-- ─────────────────────────────────────────────────────────────────────────── --}}
{{-- Meet Our Team sectie: SVG hexagon achtergrond, diagonale top, Alpine carousel --}}
{{-- ─────────────────────────────────────────────────────────────────────────── --}}
<section class="meet-team-section py-5"
         x-data="{
             active: 0,
             slideClass: '',

             {{-- Teamdata: vervang placeholder URLs door echte autoafbeeldingen (1200×600px, 2:1 ratio) --}}
             teams: [
                 {
                     id: 'iracing',
                     image: '/images/home/teams/XCLusive_Placeholder_iRacing.png',
                     logo: '/images/home/logos/iracing-logo-white.png',
                     alt: 'iRacing'
                 },
                 {
                     id: 'lmu',
                     image: '/images/home/teams/XCLusive_Placeholder_lmu.png',
                     logo: '/images/home/logos/LeMans-Logo.png',
                     alt: 'Le Mans Ultimate'
                 },
                 {
                     id: 'acc',
                     image: '/images/home/teams/XCLusive_Placeholder_ACC.png',
                     logo: '/images/home/logos/ACC-logo.png',
                     alt: 'ACC Console'
                 }
             ],

             {{-- Berekende indices voor linker en rechter kaart --}}
             get leftIndex()  { return (this.active - 1 + this.teams.length) % this.teams.length; },
             get rightIndex() { return (this.active + 1) % this.teams.length; },

             {{-- Navigatie: klasse verwijderen zodat animatie opnieuw kan triggeren --}}
             prev() {
                 this.slideClass = '';
                 this.$nextTick(() => {
                     this.active = (this.active - 1 + this.teams.length) % this.teams.length;
                     this.slideClass = 'swipe-in-right';
                 });
             },
             next() {
                 this.slideClass = '';
                 this.$nextTick(() => {
                     this.active = (this.active + 1) % this.teams.length;
                     this.slideClass = 'swipe-in-left';
                 });
             }
         }">

    {{-- ── SVG hexagon patroon als halfransparante achtergrondoverlay ─────────── --}}
    <svg class="meet-team-pattern" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <defs>
            <pattern id="meet-hex-pattern" x="0" y="0" width="60" height="52"
                     patternUnits="userSpaceOnUse">
                <polygon points="30,2 56,16 56,44 30,58 4,44 4,16"
                         stroke="#9333ea" stroke-width="1" fill="none"/>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#meet-hex-pattern)"/>
    </svg>

    <div class="container-xl position-relative" style="z-index:1;">

        {{-- ── Logo en sectiekop ────────────────────────────────────────────────── --}}
        <div class="text-center mb-4">
            <h2 class="text-white fw-bold fst-italic text-uppercase mb-2"
                style="font-size:2.8rem;font-family:'Barlow Condensed',sans-serif;letter-spacing:.04em;">
                MEET OUR TEAM
            </h2>

            {{-- Paarse decoratieve lijn --}}
            <hr style="width:80px;border-color:#7c3aed;border-width:2px;opacity:1;margin:0 auto 2.5rem;">
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
            <div class="swipe-nav" @click="prev()">
                <span>«</span>
            </div>
            <div class="swipe-nav" style="font-size:1.1rem;cursor:default;">
                SWIPE
            </div>
            <div class="swipe-nav" @click="next()">
                <span>»</span>
            </div>
        </div>

    </div>
</section>