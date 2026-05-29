@extends('layouts.app')

@section('content')

{{-- Hero --}}
<section class="hero-section">
    <div class="hero-bg" style="background-image: url('/topo.png');"></div>

    <div class="position-relative z-1 text-center px-3">
        <div class="animate-fade-in mb-5">
            <img src="/logo.png" alt="XCLusive" height="128" class="mb-4">
        </div>
        <h1 class="display-3 fw-black text-uppercase fst-italic text-dark mb-3 lh-1">
            THE LION IS BORN<br>TO DOMINATE
        </h1>
        <p class="fs-5 text-dark mx-auto mb-5" style="max-width:700px">
            From console championships to global PC competition.<br>
            <span class="text-xcl-purple fw-black">XCLUSIVE ESPORTS</span> sets the standard in sim racing excellence.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('register') }}"
               class="btn fw-black text-uppercase text-white px-4 py-3 fs-5"
               style="background:#7c3aed;">SIGN UP NOW</a>
            <a href="#teams"
               class="btn btn-outline fw-black text-uppercase px-4 py-3 fs-5"
               style="border:2px solid #7c3aed; color:#7c3aed;">VIEW TEAMS</a>
        </div>
    </div>

    <div class="position-absolute bottom-0 start-50 translate-middle-x pb-4 animate-bounce">
        <svg width="24" height="24" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
</section>

{{-- About --}}
<section id="about" class="py-5 px-3" style="background: linear-gradient(to bottom, white, #f9fafb)">
    <div class="container" style="max-width:960px">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-black text-uppercase fst-italic text-dark mb-3">OUR LEGACY</h2>
            <div class="section-divider"></div>
        </div>

        <div class="row g-5 align-items-center">
            <div class="col-md-6">
                <p class="fs-5 text-dark mb-4">
                    <span class="text-xcl-purple fw-black">XCLUSIVE ESPORTS</span> was born from the highly competitive ACC console championships, where it quickly established itself as a dominant force in sim racing.
                </p>
                <p class="fs-5 text-dark mb-4">
                    Built on a foundation of <span class="fw-black text-dark">performance, structure, and community</span>, the team has grown into one of the most recognized and competitive console-based esports organizations.
                </p>
                <p class="fs-5 text-dark">
                    Now, the team is entering a new phase. Expanding into the PC scene, XCLUSIVE ESPORTS is taking its competitive DNA to the global stage, stepping into top splits and challenging established names in the industry.
                </p>
            </div>
            <div class="col-md-6">
                <div class="p-4 rounded-3 border border-2" style="border-color:#c4b5fd !important; background:linear-gradient(135deg,#f3e8ff,#fce7f3)">
                    <div class="mb-4">
                        <div class="display-4 fw-black text-xcl-purple mb-1">7000+</div>
                        <div class="text-dark fw-bold text-uppercase tracking-wide">Active Members</div>
                    </div>
                    <div class="mb-4">
                        <div class="display-4 fw-black text-xcl-purple mb-1">33</div>
                        <div class="text-dark fw-bold text-uppercase tracking-wide">Professional Drivers</div>
                    </div>
                    <div>
                        <div class="display-4 fw-black text-xcl-purple mb-1">3</div>
                        <div class="text-dark fw-bold text-uppercase tracking-wide">Racing Platforms</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Teams --}}
<section id="teams" class="py-5 px-3" x-data="{ active: 'acc' }">
    <div class="container-xl">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-black text-uppercase fst-italic text-dark mb-3">OUR TEAMS</h2>
            <div class="section-divider"></div>
        </div>

        {{-- Platform selector --}}
        <div class="d-flex justify-content-center gap-3 mb-5 flex-wrap">
            <button @click="active = 'acc'" :class="active === 'acc' ? 'active' : ''" class="platform-btn">ACC CONSOLE</button>
            <button @click="active = 'lmu'" :class="active === 'lmu' ? 'active' : ''" class="platform-btn">LE MANS ULTIMATE</button>
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

{{-- Partners --}}
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

{{-- Merchandise --}}
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

{{-- Scroll to top --}}
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