@extends('layouts.app')

@section('title', 'Memberships - ' . config('xcl.name'))

@php
    $user = auth()->user();
    // The plan this user is paid up on (MembershipService), if any.
    $currentPlan = $membership?->isPaidUp() ? $membership->plan : null;
    $renewing = $currentPlan && $membership->isRenewing();
    $intervalLabel = config('memberships.interval') === '1 month' ? 'month' : config('memberships.interval');
    $planKeys = array_keys($plans);
    $euro = fn ($price) => '€' . str_replace('.', ',', $price);
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
                Support XCLusive Racing and get more out of every race. Every plan includes everything of the plans before it.
            </p>
        </div>
    </section>

    {{-- Plans (config/memberships.php, MembershipController) --}}
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

            <div class="xcl-plans">
                @foreach($plans as $key => $plan)
                @php
                    $index = array_search($key, $planKeys, true);
                    $previous = $index > 0 ? $plans[$planKeys[$index - 1]] : null;
                    $purchasable = \App\Models\Membership::isPurchasable($key);
                    $isCurrent = $currentPlan === $key;
                @endphp
                <div class="xcl-plan xcl-plan--{{ $key }} {{ $isCurrent ? 'xcl-plan--current' : '' }}">
                    <div class="xcl-plan__head">
                        <span class="xcl-plan__badge"><i class="fa-solid fa-star"></i> {{ $plan['name'] }}</span>
                        <div class="xcl-plan__price">
                            @if($purchasable)
                            {{ $euro($plan['price']) }}<span>/ {{ $intervalLabel }}</span>
                            @else
                            <span class="ms-0" style="font-size:1.1rem">Price coming soon</span>
                            @endif
                        </div>
                    </div>

                    <ul class="xcl-plan__perks">
                        @if($previous)
                        <li class="xcl-plan__includes"><i class="fa-solid fa-plus"></i><div><strong>Everything in {{ $previous['name'] }}</strong></div></li>
                        @endif
                        @foreach($plan['perks'] as $perk)
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
                        @if($isCurrent && $renewing)
                            <p class="xcl-plan__status xcl-plan__status--active">
                                <i class="fa-solid fa-circle-check"></i> Your plan — renews on {{ $membership->paid_until->format('j M Y') }}.
                            </p>
                            <form method="POST" action="{{ route('memberships.cancel') }}"
                                  onsubmit="return confirm('Cancel your membership? Your perks stay until {{ $membership->paid_until->format('j M Y') }}.')">
                                @csrf
                                <button type="submit" class="xcl-plan__btn xcl-plan__btn--ghost">Cancel membership</button>
                            </form>
                        @elseif(! $purchasable)
                            <span class="xcl-plan__btn xcl-plan__btn--soon">Coming soon</span>
                        @elseif(! $checkoutOpen)
                            <span class="xcl-plan__btn xcl-plan__btn--soon">Launching soon</span>
                        @elseif(! $user)
                            <a href="{{ route('login') }}" class="xcl-plan__btn">Log in to choose {{ $plan['name'] }}</a>
                        @else
                            @if($isCurrent)
                            <p class="xcl-plan__status">
                                <i class="fa-solid fa-circle-info"></i> Canceled — your perks stay until {{ $membership->paid_until->format('j M Y') }}.
                            </p>
                            @endif
                            <form method="POST" action="{{ route('memberships.checkout') }}">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $key }}">
                                <button type="submit" class="xcl-plan__btn">
                                    {{ $isCurrent ? 'Restart' : ($currentPlan ? 'Switch to' : 'Choose') }} {{ $plan['name'] }} — {{ $euro($plan['price']) }} / {{ $intervalLabel }}
                                </button>
                            </form>
                            @if($currentPlan && ! $isCurrent)
                            <p class="xcl-plan__fine mb-0">Your current plan stops once this one is paid; the new plan starts today.</p>
                            @endif
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <p class="xcl-plan__fine mt-3 mb-0" style="text-align:left">
                Secure payment through Mollie (iDEAL, card, Bancontact and more). Renews automatically every {{ $intervalLabel }};
                cancel any time and your perks stay until the end of the period you paid for.
                @if($user && ! $currentPlan && $user->isSupporter())
                <br>You already have the {{ $plans['supporter']['name'] }} perks{{ $user->isStaffSupporter() ? ' through your XCL staff role' : '' }}.
                @endif
            </p>
        </div>
    </section>

</main>
@endsection
