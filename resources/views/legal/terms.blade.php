@extends('layouts.legal')

@section('title', 'Terms of Service - ' . config('xcl.name'))
@section('heading', 'Terms of Service')

@php
    $billing = config('memberships.billing');
    $euro = fn ($price) => '€' . str_replace('.', ',', $price);
@endphp

@section('sections')

<x-legal-section title="1. Who we are">
    <p>These terms apply to the use of {{ parse_url(url('/'), PHP_URL_HOST) }} (the "Platform") and to the
    {{ config('memberships.name') }} membership. The Platform is operated by:</p>
    @include('legal._company')
</x-legal-section>

<x-legal-section title="2. Your account">
    <ul>
        <li>You need an account to register for events, appear in results and buy a membership. You must be at least 16 years old, or have permission from a parent or guardian.</li>
        <li>The information you give us (name, e-mail, platform IDs) must be correct and your own. One account per person.</li>
        <li>You are responsible for keeping your login details secret and for everything done with your account.</li>
        <li>You can delete your account at any time by contacting us (see <a href="{{ route('contact') }}">Contact</a>).</li>
    </ul>
</x-legal-section>

<x-legal-section title="3. Events and fair play">
    <ul>
        <li>Taking part in XCL events is free unless stated otherwise. By registering you agree to the event's rules, the instructions of race control and the decisions of the stewards.</li>
        <li>Cheating, deliberate wrecking, abusive behaviour, impersonation or sharing accounts can lead to penalties, a suspension or removal of your account.</li>
        <li>Results, ratings (XCL-R, SR) and standings are calculated automatically from the game servers and may be corrected by us afterwards.</li>
        <li>Events depend on game servers and third-party games (Assetto Corsa Competizione, Le Mans Ultimate, iRacing, Assetto Corsa). We are not affiliated with their publishers, and an event may be moved or canceled, for example because of a server or game outage.</li>
    </ul>
</x-legal-section>

<x-legal-section title="4. Content you share">
    <p>Content you add to the Platform (team names, in-game quotes, stream links, incident reports and clips) must not be offensive, misleading or infringe anyone's rights.
    You keep the rights to it, and you allow us to show it on the Platform, our game servers and our channels for as long as it is online. We may remove content that breaks these terms.</p>
</x-legal-section>

<x-legal-section title="5. The {{ config('memberships.name') }} membership">
    <ul>
        <li><strong class="text-dark">What you get:</strong> the perks listed on the <a href="{{ route('memberships') }}">Memberships page</a> at the time you sign up. Perks marked "Coming soon" are planned and not yet part of what you pay for.</li>
        <li><strong class="text-dark">Price:</strong> {{ $euro($billing['monthly']['price']) }} per month or {{ $euro($billing['yearly']['price']) }} per year. All prices are in euros and include VAT.</li>
        <li><strong class="text-dark">Payment:</strong> payments are processed by our payment provider Mollie B.V. You pay the first period in advance at sign-up.</li>
        <li><strong class="text-dark">Automatic renewal:</strong> the membership renews automatically for the same period (a month or a year), and the amount is charged automatically with the payment method you used for the first payment, until you cancel.</li>
        <li><strong class="text-dark">Cancelling:</strong> you can cancel at any time yourself on the Memberships page or in your profile, without giving a reason. No further payments are taken, and your perks stay active until the end of the period you already paid for.</li>
        <li><strong class="text-dark">Failed payments:</strong> if a renewal payment fails or is reversed, your perks end when the paid period runs out.</li>
        <li><strong class="text-dark">Right of withdrawal and refunds:</strong> see our <a href="{{ route('refund-policy') }}">Cancellation &amp; Refund Policy</a>.</li>
        <li><strong class="text-dark">Price changes:</strong> we announce a price change at least 30 days before it applies to you. If you don't agree, you can cancel before the new price takes effect.</li>
        <li>A suspension for breaking these terms or the event rules does not cancel the membership automatically. If we remove your account because of a serious breach, the membership ends without a refund for the remaining period.</li>
    </ul>
</x-legal-section>

<x-legal-section title="6. Availability and liability">
    <p>We do our best to keep the Platform and our game servers online and accurate, but we can't guarantee they are always available or free of errors.
    To the extent the law allows, our liability is limited to the amount you paid us in the 12 months before the event that caused the damage.
    This does not limit liability for intent or gross negligence, or your rights as a consumer under mandatory law.</p>
</x-legal-section>

<x-legal-section title="7. Intellectual property">
    <p>The XCLusive name, logo, designs and the Platform itself belong to {{ config('xcl.legal.company') }}. Game names and logos belong to their respective owners.</p>
</x-legal-section>

<x-legal-section title="8. Changes to these terms">
    <p>We may change these terms. Important changes are announced on the Platform or by e-mail at least 30 days in advance. If you don't agree with a change that affects your membership, you can cancel it before the change applies.</p>
</x-legal-section>

<x-legal-section title="9. Applicable law and complaints">
    <p>These terms are governed by Dutch law. If you live in another EU country, you keep the protection of the mandatory consumer law of that country.
    Questions or complaints? E-mail <a href="mailto:{{ config('xcl.legal.email') }}">{{ config('xcl.legal.email') }}</a>: we reply within 14 days.
    If we can't work it out together, you can take the dispute to the competent court.</p>
</x-legal-section>

@endsection
