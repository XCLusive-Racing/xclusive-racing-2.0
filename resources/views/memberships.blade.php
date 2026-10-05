@extends('layouts.app')

@section('title', 'Memberships - ' . config('xcl.name'))

@php
    $user = auth()->user();
    // How this user is paying now (MembershipService), if they're paid up.
    $current = $membership?->isPaidUp() ? $membership->billing() : null;
    $renewing = $current && $membership->isRenewing();
    $euro = fn ($price) => '€' . str_replace('.', ',', $price);
    // Yearly vs. twelve months of monthly, rounded down: "save 16%".
    $yearlySaving = (int) floor((1 - $billing['yearly']['price'] / ($billing['monthly']['price'] * 12)) * 100);
@endphp

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

    {{-- The XCL Supporter plan (config/memberships.php, MembershipController) --}}
    <section class="position-relative" style="z-index:1;padding-bottom:4rem">
        <div class="container-xl px-4">

            {{-- Before launch (memberships.checkout_enabled off) only staff can check out, to
                 test it; with the test key no real money is charged. --}}
            @if($checkoutOpen && $testMode)
            <p class="xcl-plan__test"><i class="fa-solid fa-flask"></i> Test mode — no real money is charged.</p>
            @endif
            @if($checkoutOpen && ! config('memberships.checkout_enabled'))
            <p class="xcl-plan__test"><i class="fa-solid fa-user-shield"></i> Staff preview — visitors see "Launching soon" until memberships go live.</p>
            @endif

            <div class="xcl-plan xcl-plan--single {{ $current ? 'xcl-plan--current' : '' }}">
                <div class="xcl-plan__head">
                    <span class="xcl-plan__badge"><i class="fa-solid fa-star"></i> {{ config('memberships.name') }}</span>
                    <div class="xcl-plan__price">
                        {{ $euro($billing['monthly']['price']) }}<span>/ month</span>
                    </div>
                    <p class="xcl-plan__sub mb-0">
                        or {{ $euro($billing['yearly']['price']) }} for a full year — save {{ $yearlySaving }}%.
                        Every perk, either way.
                    </p>
                </div>

                <ul class="xcl-plan__perks">
                    @foreach($perks as $perk)
                    <li>
                        <i class="{{ $perk[0] }}"></i>
                        <div>
                            <strong>{{ $perk[1] }} @if(! empty($perk['soon']))<em class="xcl-plan__soon">Coming soon</em>@endif</strong>
                            <span>{{ $perk[2] }}</span>
                        </div>
                    </li>
                    @endforeach
                </ul>

                <div class="xcl-plan__action">
                    @if($renewing)
                        <p class="xcl-plan__status xcl-plan__status--active">
                            <i class="fa-solid fa-circle-check"></i> You're a supporter ({{ strtolower($billing[$current]['label']) }}) — renews on {{ $membership->paid_until->format('j M Y') }}.
                        </p>
                    @elseif($current)
                        <p class="xcl-plan__status">
                            <i class="fa-solid fa-circle-info"></i> Canceled — your perks stay until {{ $membership->paid_until->format('j M Y') }}.
                        </p>
                    @elseif($user && $user->isSupporter())
                        <p class="xcl-plan__status xcl-plan__status--active">
                            <i class="fa-solid fa-circle-check"></i> You already have every supporter perk{{ $user->isStaffSupporter() ? ' through your XCL staff role' : '' }}.
                        </p>
                    @endif

                    @if(! $checkoutOpen)
                        <span class="xcl-plan__btn xcl-plan__btn--soon">Launching soon</span>
                    @elseif(! $user)
                        <a href="{{ route('login') }}" class="xcl-plan__btn">Log in to become a supporter</a>
                    @else
                        {{-- Both ways to pay; the one already renewing is left out. Switching
                             follows on from the period that's already paid. --}}
                        <div class="xcl-plan__options">
                            @foreach($billing as $key => $option)
                            @continue($renewing && $current === $key)
                            <form method="POST" action="{{ route('memberships.checkout') }}">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $key }}">
                                <button type="submit" class="xcl-plan__btn {{ $key === 'yearly' ? 'xcl-plan__btn--yearly' : '' }}">
                                    @if($renewing)
                                    Switch to {{ strtolower($option['label']) }} — {{ $euro($option['price']) }} / {{ $option['per'] }}
                                    @elseif($current)
                                    Restart {{ strtolower($option['label']) }} — {{ $euro($option['price']) }} / {{ $option['per'] }}
                                    @else
                                    {{ $option['label'] }} — {{ $euro($option['price']) }} / {{ $option['per'] }}
                                    @endif
                                </button>
                                @if($key === 'yearly' && ! $current)
                                <span class="xcl-plan__save">Save {{ $yearlySaving }}%</span>
                                @endif
                            </form>
                            @endforeach
                        </div>
                        @if($current)
                        <p class="xcl-plan__fine mb-0">You pay now, and the new period is added after your current one ends on {{ $membership->paid_until->format('j M Y') }}, so no days are lost.</p>
                        @endif
                    @endif

                    @if($renewing)
                    <form method="POST" action="{{ route('memberships.cancel') }}" class="mt-2"
                          onsubmit="return confirm('Cancel your membership? Your perks stay until {{ $membership->paid_until->format('j M Y') }}.')">
                        @csrf
                        <button type="submit" class="xcl-plan__btn xcl-plan__btn--ghost">Cancel membership</button>
                    </form>
                    @endif
                </div>
            </div>

            <p class="xcl-plan__fine mt-3 mb-0" style="text-align:left">
                Secure payment through Mollie (iDEAL, card, Bancontact and more). Renews automatically;
                cancel any time and your perks stay until the end of the period you paid for.
            </p>
        </div>
    </section>

</main>
@endsection
