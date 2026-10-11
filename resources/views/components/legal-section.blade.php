{{-- One numbered card on a legal page (layouts/legal.blade.php). --}}
@props(['title'])
<section {{ $attributes->merge(['class' => 'bg-white rounded-3 shadow-sm p-4']) }}>
    <h2 class="fw-black text-uppercase text-dark mb-3" style="font-size:.85rem;letter-spacing:.08em">{{ $title }}</h2>
    <div class="text-secondary xcl-legal__body">{{ $slot }}</div>
</section>
