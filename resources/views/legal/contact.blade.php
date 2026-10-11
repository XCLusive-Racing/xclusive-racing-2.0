@extends('layouts.legal')

@section('title', 'Contact - ' . config('xcl.name'))
@section('heading', 'Contact')
@section('no-updated', true)

@section('sections')

<x-legal-section title="Get in touch">
    <p>Questions about events, your account, a payment or your membership? E-mail us and we reply within 2 working days
    ({{ config('memberships.name') }}s are picked up first).</p>
    <p class="mb-0">
        <a href="mailto:{{ config('xcl.legal.email') }}" class="xcl-plan__btn d-inline-block w-auto px-4">
            <i class="fa-solid fa-envelope me-1"></i> {{ config('xcl.legal.email') }}
        </a>
    </p>
</x-legal-section>

<x-legal-section title="Discord">
    <p class="mb-0">For quick questions about races, servers and results, our community and staff are on
    <a href="{{ config('xcl.discord_url') }}" target="_blank" rel="noopener">Discord</a>.
    Please don't share payment details there; use e-mail for anything about payments.</p>
</x-legal-section>

<x-legal-section title="Company details">
    @include('legal._company')
</x-legal-section>

@endsection
