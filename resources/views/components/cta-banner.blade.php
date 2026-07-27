@props([
    'heading' => 'READY TO RACE?',
    'subtext' => 'Sign up now to access all events and track your ELO rating',
    'button'  => 'JOIN NOW →',
    'href'    => '/register',
    'button2' => null,
    'href2'   => null,
])

<div class="events-cta-banner rounded-3 p-4 p-md-5 text-white text-center">
    <div class="about-section__topo" style="background-image:url('/topo.png');filter:invert(1) brightness(2);opacity:.12"></div>
    <div style="position:relative;z-index:1">
        <h2 class="fs-2 fw-black text-uppercase fst-italic mb-3">{{ $heading }}</h2>
        <p class="mb-4 fs-5" style="color:rgba(255,255,255,.8)">{{ $subtext }}</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ $href }}" class="btn btn-light fw-black text-uppercase px-5 py-3 text-xcl-purple">
                {{ $button }}
            </a>
            @if($button2 && $href2)
            <a href="{{ $href2 }}" class="btn fw-black text-uppercase px-5 py-3 text-white" style="border:2px solid rgba(255,255,255,.4)">
                {{ $button2 }}
            </a>
            @endif
        </div>
    </div>
</div>
