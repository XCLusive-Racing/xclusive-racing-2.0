@extends('layouts.app')

@section('title', 'Staff - ' . config('xcl.name'))

@php
    // config/staff.php: everyone once, with all their functions; a tab per function that has people.
    $roles = config('staff.roles');
    $members = collect(config('staff.members'));
    $roleTabs = collect($roles)
        ->map(fn ($label, $key) => ['label' => $label, 'members' => $members->filter(fn ($m) => in_array($key, $m['roles'], true))])
        ->filter(fn ($tab) => $tab['members']->isNotEmpty());
@endphp

@section('content')
<main class="pro-listing-page">

    <div class="pro-topo" style="background-image:url('/topo.png')"></div>

    {{-- Header --}}
    <section class="pro-listing-header position-relative" style="z-index:1">
        <div class="container-xl px-4">
            <a href="{{ route('team') }}" class="pro-back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Team
            </a>
            <p class="pro-eyebrow">THE PEOPLE BEHIND XCL</p>
            <h1 class="pro-listing-title">XCLUSIVE<br><span class="pro-listing-title--lime">STAFF</span></h1>
            <div class="section-divider mb-4" style="margin-left:0"></div>
            <p class="pro-listing-sub">
                The admins, stewards, event managers and developers who keep XCLusive Racing running.
            </p>
        </div>
    </section>

    {{-- Function tabs + staff --}}
    <section class="position-relative" style="z-index:1;padding-bottom:4rem">
        <div class="container-xl px-4" data-tabs data-default-tab="all">

            <div class="esports-tabs">
                <button class="esports-tab" data-tab-btn="all" data-tab-active-class="esports-tab--active">All Staff</button>
                @foreach($roleTabs as $key => $tab)
                <button class="esports-tab" data-tab-btn="{{ $key }}" data-tab-active-class="esports-tab--active">{{ $tab['label'] }}</button>
                @endforeach
            </div>

            <div data-tab-panel="all" style="display:none">
                <div class="esports-grid">
                    @foreach($members as $member)
                    @include('team._staff-card', ['member' => $member, 'roles' => $roles])
                    @endforeach
                </div>
            </div>

            @foreach($roleTabs as $key => $tab)
            <div data-tab-panel="{{ $key }}" style="display:none">
                <div class="esports-grid">
                    @foreach($tab['members'] as $member)
                    @include('team._staff-card', ['member' => $member, 'roles' => $roles])
                    @endforeach
                </div>
            </div>
            @endforeach

        </div>
    </section>

</main>
@endsection
