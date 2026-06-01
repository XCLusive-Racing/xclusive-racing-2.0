@extends('layouts.app')

@section('content')

{{-- ─── Hero ──────────────────────────────────────────────────────────────────── --}}
<section class="hero-home">
    <div class="hero-home__bg" style="background-image: url('/topo.png');"></div>

    <div class="container-xl px-4 position-relative h-100" style="z-index:1;">
        <div class="row align-items-center g-5 h-100 py-5">

            {{-- Left: copy --}}
            <div class="col-lg-6 animate-fade-in">
                <div class="mb-4">
                    <img src="/images/home/xclusive_racing_logo_lion.png" alt="XCLusive Lion" height="90">
                </div>
                <h1 class="hero-home__heading fw-black text-uppercase fst-italic lh-1 mb-4">
                    THE LION IS<br>BORN TO<br>
                    <span class="hero-home__heading--accent">DOMINATE</span>
                </h1>
                <p class="fs-5 mb-5" style="color:rgba(255,255,255,.75);max-width:460px">
                    From console championships to global PC competition.<br>
                    <span class="fw-black" style="color:#a78bfa">XCLUSIVE ESPORTS</span> sets the standard in sim racing excellence.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('register') }}"
                       class="btn fw-black text-uppercase text-white px-5 py-3 fs-5"
                       style="background:#7c3aed;">SIGN UP</a>
                    <a href="#teams"
                       class="btn fw-black text-uppercase px-5 py-3 fs-5"
                       style="border:2px solid rgba(255,255,255,.3);color:white;">TEAMS</a>
                </div>
            </div>

            {{-- Right: car image --}}
            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                <!-- IMAGE PLACEHOLDER: Place race car image here -->
                <div class="hero-home__car-placeholder">
                    <svg width="72" height="72" viewBox="0 0 24 24" fill="none"
                         stroke="rgba(255,255,255,.12)" stroke-width="1.2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>
                    </svg>
                    <p class="mt-3 mb-0 fw-bold text-uppercase"
                       style="color:rgba(255,255,255,.18);letter-spacing:.1em;font-size:.7rem">
                        PLACE CAR IMAGE HERE
                    </p>
                </div>
            </div>

        </div>
    </div>

    {{-- Scroll indicator --}}
    <div class="position-absolute bottom-0 start-50 translate-middle-x pb-4 animate-bounce" style="z-index:1;">
        <svg width="24" height="24" fill="none" stroke="rgba(255,255,255,.35)"
             stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
</section>

{{-- ─── Legacy / About ──────────────────────────────────────────────────────── --}}
<section id="about" class="about-section py-5 px-3">
    <div class="about-section__topo" style="background-image: url('/topo.png');"></div>

    <div class="container position-relative" style="max-width:960px;z-index:1;">

        {{-- Centered heading --}}
        <div class="text-center mb-5">
            <!-- IMAGE PLACEHOLDER: Place XCLusive logo here -->
            <img src="/images/home/xclusive_racing_logo_lion.png"
                 alt="XCLusive" height="80" class="mb-4 d-block mx-auto">
            <h2 class="display-4 fw-black text-uppercase fst-italic text-dark mb-3">OUR LEGACY</h2>
            <div class="section-divider"></div>
        </div>

        <div class="row g-5 align-items-center">

            {{-- Left: text --}}
            <div class="col-md-6">
                <p class="fs-5 text-dark mb-4">
                    <span class="text-xcl-purple fw-black">XCLUSIVE ESPORTS</span> was born from the highly competitive
                    ACC console championships, where it quickly established itself as a dominant force in sim racing.
                </p>
                <p class="fs-5 text-dark mb-4">
                    Built on a foundation of <span class="fw-black text-dark">performance, structure, and community</span>,
                    the team has grown into one of the most recognized and competitive console-based esports organizations.
                </p>
                <p class="fs-5 text-dark">
                    Now expanding into the PC scene, XCLUSIVE ESPORTS is taking its competitive DNA to the global stage —
                    stepping into top splits and challenging established names in the industry.
                </p>
            </div>

            {{-- Right: dark purple stats card --}}
            <div class="col-md-6">
                <div class="about-stats-card rounded-3 p-4">
                    <div class="mb-4 pb-4 about-stats-card__row">
                        <div class="display-4 fw-black mb-1" style="color:#a78bfa">7000+</div>
                        <div class="fw-bold text-uppercase tracking-wide text-white">Active Members</div>
                    </div>
                    <div class="mb-4 pb-4 about-stats-card__row">
                        <div class="display-4 fw-black mb-1" style="color:#a78bfa">1000</div>
                        <div class="fw-bold text-uppercase tracking-wide text-white">Events Hosted</div>
                    </div>
                    <div>
                        <div class="display-4 fw-black mb-1" style="color:#a78bfa">33</div>
                        <div class="fw-bold text-uppercase tracking-wide text-white">Professional Drivers</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ─── Teams ──────────────────────────────────────────────────────────────── --}}
<section id="teams" class="py-5 px-3" x-data="{ active: 'acc' }">
    <div class="container-xl">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-black text-uppercase fst-italic text-dark mb-3">OUR TEAMS</h2>
            <div class="section-divider"></div>
        </div>

        {{-- Platform selector --}}
        <div class="d-flex justify-content-center gap-3 mb-5 flex-wrap">
            <button @click="active = 'acc'"     :class="active === 'acc'     ? 'active' : ''" class="platform-btn">ACC CONSOLE</button>
            <button @click="active = 'lmu'"     :class="active === 'lmu'     ? 'active' : ''" class="platform-btn">LE MANS ULTIMATE</button>
            <button @click="active = 'iracing'" :class="active === 'iracing' ? 'active' : ''" class="platform-btn">iRACING</button>
        </div>

        {{-- ACC Team --}}
        <div x-show="active === 'acc'" class="row g-3">
            @php
            $accTeam = [
                ['name' => 'Nat',      'lastName' => 'BENNET',       'country' => '🇬🇧'],
                ['name' => 'Sergio',   'lastName' => 'HERNÁNDEZ',    'country' => '🇪🇸'],
                ['name' => 'Phil',     'lastName' => 'SOURCY',       'country' => '🇨🇦'],
                ['name' => 'Joakim',   'lastName' => 'ERIKSSON',     'country' => '🇸🇪'],
                ['name' => 'Matteo',   'lastName' => 'MASTROMAURO',  'country' => '🇮🇹'],
                ['name' => 'Gianluca', 'lastName' => 'ZAMBIONE',     'country' => '🇮🇹'],
            ];
            @endphp
            @foreach($accTeam as $driver)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="driver-card rounded-2 p-4 bg-white">
                    <div class="driver-avatar bg-gradient-xcl">
                        <span>{{ $driver['name'][0] }}</span>
                    </div>
                    <div class="small fw-bold text-xcl-purple mb-1">{{ $driver['name'] }}</div>
                    <div class="fw-black text-dark mb-2">{{ $driver['lastName'] }}</div>
                    <div class="fs-4">{{ $driver['country'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- LMU Team --}}
        <div x-show="active === 'lmu'" class="row g-3">
            @php
            $lmuTeam = [
                ['name' => 'Giuseppe', 'lastName' => 'DINOIA',   'country' => '🇮🇹'],
                ['name' => 'Paul',     'lastName' => 'MÖLLER',   'country' => '🇩🇪'],
                ['name' => 'Jesse',    'lastName' => 'AALBREGT', 'country' => '🇳🇱'],
                ['name' => 'Denis',    'lastName' => 'EBERT',    'country' => '🇩🇪'],
            ];
            @endphp
            @foreach($lmuTeam as $driver)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="driver-card rounded-2 p-4 bg-white">
                    <div class="driver-avatar" style="background:linear-gradient(135deg,#db2777,#7c3aed)">
                        <span>{{ $driver['name'][0] }}</span>
                    </div>
                    <div class="small fw-bold text-xcl-purple mb-1">{{ $driver['name'] }}</div>
                    <div class="fw-black text-dark mb-2">{{ $driver['lastName'] }}</div>
                    <div class="fs-4">{{ $driver['country'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- iRacing Team --}}
        <div x-show="active === 'iracing'" class="row g-3">
            @php
            $iracingTeam = [
                ['name' => 'Ethan',  'lastName' => 'AMBURG',  'country' => '🇺🇸'],
                ['name' => 'Parker', 'lastName' => 'SOUKUP',  'country' => '🇺🇸'],
                ['name' => 'James',  'lastName' => 'CURTIN',  'country' => '🇺🇸'],
            ];
            @endphp
            @foreach($iracingTeam as $driver)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="driver-card rounded-2 p-4 bg-white">
                    <div class="driver-avatar" style="background:linear-gradient(135deg,#2563eb,#7c3aed)">
                        <span>{{ $driver['name'][0] }}</span>
                    </div>
                    <div class="small fw-bold text-xcl-purple mb-1">{{ $driver['name'] }}</div>
                    <div class="fw-black text-dark mb-2">{{ $driver['lastName'] }}</div>
                    <div class="fs-4">{{ $driver['country'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ─── Partners ─────────────────────────────────────────────────────────── --}}
<section id="partners" class="py-5 px-3 bg-light">
    <div class="container-xl text-center">
        <h2 class="display-4 fw-black text-uppercase fst-italic text-dark mb-3">PARTNERS</h2>
        <div class="section-divider mb-5"></div>
        <div class="row g-3">
            @for($i = 1; $i <= 6; $i++)
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-box">LOGO HERE</div>
            </div>
            @endfor
        </div>
    </div>
</section>

{{-- ─── Merchandise ──────────────────────────────────────────────────────── --}}
<section class="py-5 px-3">
    <div class="container-xl">
        <div class="rounded-3 p-5 text-white text-center bg-gradient-xcl">
            <h2 class="display-5 fw-black text-uppercase fst-italic mb-3">GET YOUR XCLUSIVE MERCHANDISE</h2>
            <p class="fs-5 mb-4">Represent the pride. Wear the purple.</p>
            <a href="https://raven.gg/stores/xclusive-esports/" target="_blank"
               class="btn btn-light fw-black text-uppercase px-4 py-3 fs-5 text-xcl-purple">
                SHOP NOW
            </a>
        </div>
    </div>
</section>

{{-- ─── Scroll to top ────────────────────────────────────────────────────── --}}
<button id="scroll-top"
        onclick="window.scrollTo({top:0,behavior:'smooth'})"
        style="position:fixed;bottom:2rem;right:2rem;width:44px;height:44px;border-radius:50%;background:#7c3aed;color:white;border:none;cursor:pointer;display:none;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(124,58,237,.4);transition:transform .2s;z-index:999"
        onmouseover="this.style.transform='translateY(-2px)'"
        onmouseout="this.style.transform='translateY(0)'">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
    </svg>
</button>
<script>
    (function() {
        var btn = document.getElementById('scroll-top');
        window.addEventListener('scroll', function() {
            btn.style.display = window.scrollY > 300 ? 'flex' : 'none';
        });
    })();
</script>

@endsection