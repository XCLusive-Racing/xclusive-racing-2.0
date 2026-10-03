@extends('layouts.app')

@section('title', 'Memberships - ' . config('xcl.name'))

@section('content')
<main class="pro-listing-page">

    <div class="pro-topo" style="background-image:url('/topo.png')"></div>

    {{-- Header --}}
    <section class="pro-listing-header position-relative" style="z-index:1">
        <div class="container-xl px-4">
            <p class="pro-eyebrow">SHOP</p>
            <h1 class="pro-listing-title">XCLUSIVE<br><span class="pro-listing-title--lime">MEMBERSHIPS</span></h1>
            <div class="section-divider mb-4" style="margin-left:0"></div>
            <p class="pro-listing-sub">
                Support XCLusive Racing and get more out of every race.
            </p>
        </div>
    </section>

    {{-- Memberships aren't for sale yet — placeholder until the tiers and Mollie checkout exist. --}}
    <section class="position-relative" style="z-index:1;padding-bottom:4rem">
        <div class="container-xl px-4">
            <div class="events-empty">
                <h3 class="fw-black text-uppercase fst-italic mb-2">COMING SOON</h3>
                <p>Memberships are on their way. Keep an eye on our Discord for the launch.</p>
            </div>
        </div>
    </section>

</main>
@endsection
