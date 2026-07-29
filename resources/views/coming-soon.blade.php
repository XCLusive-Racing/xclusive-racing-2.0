@extends('layouts.app')

@section('title', 'Coming Soon - XCLusive Racing')

@section('content')
<main class="xcl-page pb-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container" style="max-width:640px;position:relative;z-index:1">

        <div class="pt-5 pb-4 text-center">
            <div class="mb-4" style="font-size:4rem;line-height:1">🚧</div>
            <h1 class="display-5 fw-black text-uppercase fst-italic about-section__heading mb-2">Coming Soon</h1>
            <div class="section-divider mx-auto"></div>
            <p class="text-secondary mt-4" style="font-size:1rem;line-height:1.7">
                This page is currently under construction.<br>
                We're working on it — check back soon!
            </p>
        </div>

        <div class="bg-white rounded-3 shadow-sm p-4 text-center">
            <p class="text-secondary mb-3" style="font-size:.9rem">
                Stay up to date and be the first to know when this launches.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ url('/') }}" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed;font-size:.82rem">
                    ← Back to Home
                </a>
                <a href="{{ config('xcl.discord_url') }}" target="_blank" class="btn btn-outline-secondary fw-bold text-uppercase px-4" style="font-size:.82rem">
                    Join Discord
                </a>
            </div>
        </div>

    </div>
</main>
@endsection
