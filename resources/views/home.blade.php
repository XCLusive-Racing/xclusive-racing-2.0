@extends('layouts.app')

@section('content')

{{-- ─── Hero ──────────────────────────────────────────────────────────────────── --}}
<section class="hero-home" style="background-image:url('/images/home/XCLusive_499P_Header_v3.png')">

    <div class="container-xl px-4 position-relative h-100" style="z-index:1;">
        <div class="row align-items-center g-5 h-100 py-5">

            {{-- Left: copy --}}
            <div class="col-lg-6 animate-fade-in">
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


        </div>
    </div>


</section>

{{-- ─── Ramp: driehoekige overgang met topo-textuur ─────────────────────────── --}}
<div class="hero-ramp">
    <div class="hero-ramp__topo" style="background-image:url('/topo.png');"></div>
</div>

{{-- ─── Legacy / About ──────────────────────────────────────────────────────── --}}
<section id="about" class="about-section py-5 px-3">
    <div class="about-section__topo" style="background-image: url('/topo.png');"></div>

    <div class="container position-relative" style="max-width:960px;z-index:1;">

        {{-- Centered heading --}}
        <div class="text-center mb-5">
            <!-- IMAGE PLACEHOLDER: Place XCLusive logo here -->
            <img src="/images/home/xclusive_racing_logo_lion.png"
                 alt="XCLusive" height="80" class="mb-4 d-block mx-auto" loading="lazy">
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
<x-meet-teams />

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