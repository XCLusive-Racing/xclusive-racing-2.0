@extends('layouts.app')

@section('content')

{{-- ─── Hero ──────────────────────────────────────────────────────────────────── --}}
<section class="hero-home" style="background-image:url('/images/home/hero/XCLusive P499 Header v4.png')">
    <div class="container-xl px-4 position-relative h-100" style="z-index:1;">
        <div class="row align-items-center g-3 g-lg-5 h-100 py-5">
            {{-- Left: copy --}}
            <div class="col-lg-6 animate-fade-in text-center text-lg-start">
                <h1 class="hero-home__heading fw-black text-uppercase fst-italic lh-1 mb-4">
                    WHERE<br>
                    <span class="hero-home__heading--accent">RACING LEGENDS</span><br>
                    ARE FORGED
                </h1>
                <p class="hero-home__sub fs-5 mb-5">
                    Born on console. Built for global competition.<br><span style="display:block;margin-top:0.5em"></span><span class="fw-black xcl-gradient-text"><span style="font-size:1.2em">XCL</span>USIVE</span> is the home of premier sim racing events, a trusted community, and the <span class="fw-black xcl-gradient-text"><span style="font-size:1.2em">XCL</span>USIVE <span style="font-size:1.2em">R</span>ACING</span> team.<br><span style="display:block;margin-top:0.5em"></span>This is where champions are made.
                </p>
                <div class="d-flex gap-3 flex-wrap justify-content-center justify-content-lg-start">
                    @auth
                        <a href="{{ route('profile') }}"
                           class="btn fw-black text-uppercase text-white px-5 py-3 fs-5"
                           style="background:#7c3aed;">MY PROFILE</a>
                        <a href="{{ route('events.index') }}"
                           class="btn fw-black text-uppercase px-5 py-3 fs-5"
                           style="border:2px solid rgba(255,255,255,.3);color:white;">SEE EVENTS</a>
                    @else
                        <a href="{{ route('register') }}"
                           class="btn fw-black text-uppercase text-white px-5 py-3 fs-5"
                           style="background:#7c3aed;">JOIN NOW</a>
                        <a href="#"
                           class="btn fw-black text-uppercase px-5 py-3 fs-5"
                           style="border:2px solid rgba(255,255,255,.3);color:white;"
                           onclick="event.preventDefault();window.dispatchEvent(new CustomEvent('open-events-sidebar'))">SEE EVENTS</a>
                    @endauth
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
<x-about-section />

{{-- ─── Meet Our Team ──────────────────────────────────────────────────────── --}}
<x-meet-team />
{{-- ─── Partners ───────────────────────────────────────────────────────────── --}}
<section id="partners" class="partners-section py-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png');"></div>
    <div class="container-xl text-center position-relative" style="z-index:1">
        <h2 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">PARTNERS</h2>
        <div class="section-divider mb-2"></div>
        <p class="mb-5" style="color:#6b7280;font-size:.9rem">Proud to race alongside the best in the business.</p>
        <div class="row g-4 justify-content-center">
            @foreach([
                ['boostlogo-DEF-wit-01-06.png', 'Boost'],
                ['ds-logo-white.png',             'DS'],
                ['logo-dark.png',                'Partner'],
                ['Logo-White.webp',              'Partner'],
                ['simlab-white-no-tagline-e1637234882156.png', 'SimLab'],
                ['sunvitlogo3000X981-300x98-1.png',            'Sunvit'],
            ] as [$file, $name])
            <div class="col-6 col-md-4 col-lg-2">
                <div class="partner-box">
                    <img src="/images/home/partners/{{ $file }}"
                         alt="{{ $name }}"
                         class="partner-logo">
                </div>
            </div>
            @endforeach
        </div>
        <p class="mt-5" style="font-size:.82rem;color:#9ca3af">
            Interested in partnering with XCLusive?
            <a href="mailto:info@xclusive-esports.com" style="color:#a78bfa;font-weight:700;text-decoration:none">Get in touch →</a>
        </p>
    </div>
</section>

{{-- ─── Merchandise ──────────────────────────────────────────────────────── --}}
<section class="merch-section py-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png');"></div>
    <div class="container-xl position-relative" style="z-index:1;">
        <div class="merch-cta rounded-3 p-4 p-md-5 text-white text-center">
            <div class="merch-cta__icon mb-3">
                <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.6)" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h2 class="display-5 fw-black text-uppercase fst-italic mb-2">GET YOUR XCLUSIVE GEAR</h2>
            <p class="mb-4" style="color:rgba(255,255,255,.75);font-size:1.1rem">Represent the pride. Wear the purple.</p>
            <a href="https://raven.gg/stores/xclusive-esports/" target="_blank"
               class="btn btn-light fw-black text-uppercase px-5 py-3 fs-5 text-xcl-purple">
                SHOP NOW →
            </a>
        </div>
    </div>
</section>

{{-- ─── Scroll to top ────────────────────────────────────────────────────── --}}
<button id="scroll-top" class="xcl-scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Scroll to top">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
    </svg>
</button>
<script>
    (function() {
        var btn = document.getElementById('scroll-top');
        window.addEventListener('scroll', function() {
            btn.classList.toggle('xcl-scroll-top--visible', window.scrollY > 300);
        });
    })();
</script>

@endsection