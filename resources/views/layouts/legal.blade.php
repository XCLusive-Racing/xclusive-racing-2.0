{{-- Shared frame of the legal pages (Terms, Refund Policy, Privacy, Contact): heading,
     "last updated" date and a column of white section cards (<x-legal-section>). --}}
@extends('layouts.app')

@section('content')
<main class="xcl-page xcl-legal pb-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container" style="max-width:800px;position:relative;z-index:1">

        <div class="pt-4 mb-5">
            <h1 class="display-5 fw-black text-uppercase fst-italic about-section__heading mb-2">@yield('heading')</h1>
            <div class="section-divider" style="margin-left:0"></div>
            @hasSection('no-updated')
            @else
            <p class="text-secondary mt-3 mb-0" style="font-size:.9rem">Last updated: {{ \Illuminate\Support\Carbon::parse(config('xcl.legal.updated'))->format('F j, Y') }}</p>
            @endif
        </div>

        <div class="d-flex flex-column gap-3">
            @yield('sections')
        </div>

        <p class="text-secondary text-center mt-4 mb-0" style="font-size:.8rem">
            <a href="{{ route('terms') }}" class="text-decoration-none">Terms of Service</a>
            &nbsp;&middot;&nbsp;
            <a href="{{ route('refund-policy') }}" class="text-decoration-none">Cancellation &amp; Refund Policy</a>
            &nbsp;&middot;&nbsp;
            <a href="{{ route('privacy') }}" class="text-decoration-none">Privacy Policy</a>
            &nbsp;&middot;&nbsp;
            <a href="{{ route('contact') }}" class="text-decoration-none">Contact</a>
        </p>
    </div>
</main>
@endsection
