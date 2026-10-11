@extends('layouts.legal')

@section('title', 'Cancellation & Refund Policy - ' . config('xcl.name'))
@section('heading', 'Cancellation & Refunds')

@section('sections')

<x-legal-section title="1. Cancelling your membership">
    <p>You can cancel your {{ config('memberships.name') }} membership at any time, without a reason and without a notice period:</p>
    <ul>
        <li>Log in, go to the <a href="{{ route('memberships') }}">Memberships page</a> (or <em>Manage membership</em> in your profile) and click <strong class="text-dark">Cancel membership</strong>.</li>
        <li>Or e-mail <a href="mailto:{{ config('xcl.legal.email') }}">{{ config('xcl.legal.email') }}</a> and we cancel it for you.</li>
    </ul>
    <p class="mb-0">After cancelling, no further payments are taken. Your perks stay active until the end of the month or year you already paid for.</p>
</x-legal-section>

<x-legal-section title="2. 14-day right of withdrawal">
    <p>As a consumer in the EU you have the right to withdraw from your membership within <strong class="text-dark">14 days</strong> after your first payment, without giving a reason.
    Send us a clear message (for example an e-mail to <a href="mailto:{{ config('xcl.legal.email') }}">{{ config('xcl.legal.email') }}</a>) that you want to withdraw. You may use the model text below, but you don't have to.</p>
    <p>We refund the first payment <strong class="text-dark">in full</strong> within 14 days after receiving your message, using the same payment method you paid with, and end your membership and its perks.</p>
    <p class="mb-1">Model withdrawal form:</p>
    <blockquote class="border-start border-3 ps-3 mb-0" style="font-size:.9rem">
        To {{ config('xcl.legal.company') }}, {{ config('xcl.legal.email') }}:<br>
        I hereby withdraw from my {{ config('memberships.name') }} membership.<br>
        Started on: [date] &middot; Name: [name] &middot; E-mail of my XCL account: [e-mail]<br>
        Date: [date]
    </blockquote>
</x-legal-section>

<x-legal-section title="3. Refunds after the first 14 days">
    <ul>
        <li><strong class="text-dark">Monthly:</strong> a month that has already been charged is not refunded. Cancel before your renewal date to avoid the next charge.</li>
        <li><strong class="text-dark">Yearly, first year:</strong> after the 14-day withdrawal period the first year is not refunded. You can still cancel, so it doesn't renew.</li>
        <li><strong class="text-dark">Yearly, after an automatic renewal:</strong> you can cancel at any time with a notice period of at most one month. On request we refund the full months of the renewed year that remain after that month.</li>
        <li><strong class="text-dark">Something went wrong:</strong> if you were charged twice, charged after cancelling, or couldn't use the perks you paid for because of a problem on our side, contact us and we will refund the amount involved.</li>
    </ul>
    <p class="mb-0">Refunds are paid back through Mollie to the payment method you used, normally within 14 days.</p>
</x-legal-section>

<x-legal-section title="4. Chargebacks">
    <p class="mb-0">Please contact us first if something is wrong with a payment, we will sort it out. A reversed payment (chargeback or reversed direct debit) ends the membership's perks at the end of the last fully paid period.</p>
</x-legal-section>

<x-legal-section title="5. Questions">
    <p class="mb-0">See our <a href="{{ route('terms') }}">Terms of Service</a>, or <a href="{{ route('contact') }}">contact us</a>.</p>
</x-legal-section>

@endsection
