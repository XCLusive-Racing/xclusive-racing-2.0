@extends('layouts.app')

@section('content')

{{-- ─── Hero ──────────────────────────────────────────────────────────────────── --}}
<section class="hero-home" style="background-image:url('/images/home/hero/XCLusive P499 Header v4.png')">
    <div class="container-xl px-4 position-relative h-100" style="z-index:1;">
        <div class="row align-items-center g-3 g-lg-5 h-100 py-5">
            {{-- Left: copy --}}
            <div class="col-lg-6 animate-fade-in text-center text-lg-start">
                <h1 class="hero-home__heading fw-black text-uppercase fst-italic lh-1 mb-4">
                    THE LION IS<br>BORN TO<br>
                    <span class="hero-home__heading--accent">DOMINATE</span>
                </h1>
                <p class="fs-5 mb-5" style="color:rgba(255,255,255,.75);max-width:460px">
                    Born on console. Built for global competition.<br><span style="display:block;margin-top:0.5em"></span><span class="fw-black xcl-gradient-text"><span style="font-size:1.2em">XCL</span>USIVE</span> is the home of premier sim racing events, a trusted community, and the <span class="fw-black xcl-gradient-text"><span style="font-size:1.2em">XCL</span>USIVE <span style="font-size:1.2em">R</span>ACING</span> team.<br><span style="display:block;margin-top:0.5em"></span>This is where champions are made.
                </p>
                <div class="d-flex gap-3 flex-wrap justify-content-center justify-content-lg-start">
                    <a href="{{ route('register') }}"
                       class="btn fw-black text-uppercase text-white px-5 py-3 fs-5"
                       style="background:#7c3aed;">SIGN UP</a>
                    <a href="#teams"
                       class="btn fw-black text-uppercase px-5 py-3 fs-5"
                       style="border:2px solid rgba(255,255,255,.3);color:white;">TEAMS</a>
                </div>
            </div>
        </div>
    </div>
    <a href="#about" aria-label="Scroll down" class="hero-scroll-indicator">
        <svg width="44" height="44" viewBox="0 0 32 32" fill="none"
             stroke="#d4ee6a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6,10 16,22 26,10"/>
        </svg>
    </a>
</section>

{{-- ─── Ramp: driehoekige overgang met topo-textuur ─────────────────────────── --}}
<div class="hero-ramp">
    <div class="hero-ramp__topo" style="background-image:url('/topo.png');"></div>
</div>

{{-- ─── Legacy / About ──────────────────────────────────────────────────────── --}}
<section id="about" class="about-section pb-5 px-3" style="padding-top:0.5rem">
    <div class="about-section__topo" style="background-image: url('/topo.png');"></div>

    <div class="container position-relative" style="max-width:960px;z-index:1;">

        {{-- Centered heading --}}
        <div class="text-center mb-5">
            <!-- IMAGE PLACEHOLDER: Place XCLusive logo here -->
            <img src="/images/home/brand/xclusive_racing_logo_lion.png"
                 alt="XCLusive" height="220" class="mb-5 d-block mx-auto" style="margin-top:10px" loading="lazy">
            <h2 class="display-4 fw-black text-uppercase fst-italic mb-3 about-section__heading">OUR LEGACY</h2>
            <div class="section-divider"></div>
        </div>

        <div class="row g-4 g-md-5 align-items-center">

            {{-- Left: text --}}
            <div class="col-md-6">
                <p class="fs-5 text-dark mb-4">
                    It started with a community. Xbox Community League, <span class="fw-black xcl-gradient-text"><span style="font-size:1.2em">XCL</span></span>, was built as a home for console sim racers who wanted more than casual lobbies. At the heart of it was our own <span class="fw-black xcl-gradient-text"><span style="font-size:1.2em">XCL</span></span> Rating system, built to identify the truly fastest and most consistent drivers. It was a standard. And from that standard, <span class="fw-black xcl-gradient-text"><span style="font-size:1.2em">XCL</span>USIVE <span style="font-size:1.2em">E</span>SPORTS</span> was born.
                </p>
                <p class="fs-5 text-dark">
                    <span class="fw-black xcl-gradient-text"><span style="font-size:1.2em">XCL</span>USIVE <span style="font-size:1.2em">R</span>ACING</span> is your new home for competitive virtual motorsport. Join the race.
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
{{-- ─── Meet Our Team carousel ─────────────────────────────────────────────── --}}
<x-meet-team />
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
        <div class="rounded-3 p-4 p-md-5 text-white text-center bg-gradient-xcl">
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