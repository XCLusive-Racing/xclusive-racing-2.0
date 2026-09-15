@extends('layouts.app')

@section('title', 'FAQ - XCLusive Racing')

@section('content')
<main class="xcl-page pb-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container" style="max-width:800px;position:relative;z-index:1">

        <div class="pt-4 mb-5">
            <h1 class="display-5 fw-black text-uppercase fst-italic about-section__heading mb-2">Frequently Asked Questions</h1>
            <div class="section-divider" style="margin-left:0"></div>
            <p class="text-secondary mt-3 mb-0" style="font-size:.9rem">
                Can't find what you're looking for? Reach us on our
                <a href="{{ config('xcl.discord_url') }}" target="_blank" class="text-decoration-none fw-bold" style="color:#7c3aed">Discord server</a>.
            </p>
        </div>

        @if($faqs->isEmpty())
        <div class="bg-white rounded-3 shadow-sm p-4 text-center">
            <p class="text-secondary mb-0" style="line-height:1.8">No questions have been added yet — check back soon.</p>
        </div>
        @else

        @if(count($categories) > 1)
        <div data-faq-filter class="d-flex flex-wrap gap-2 mb-4">
            <button type="button" data-faq-category-btn="" class="fw-bold text-uppercase"
                    style="font-size:.72rem;padding:6px 14px;border-radius:20px;border:1.5px solid #7c3aed;background:#7c3aed;color:#fff;cursor:pointer">
                All
            </button>
            @foreach($categories as $cat)
            <button type="button" data-faq-category-btn="{{ $cat }}" class="fw-bold text-uppercase"
                    style="font-size:.72rem;padding:6px 14px;border-radius:20px;border:1.5px solid #e5e7eb;background:#fff;color:#374151;cursor:pointer">
                {{ $cat }}
            </button>
            @endforeach
        </div>
        @endif

        <div data-accordions class="d-flex flex-column gap-3">
            @foreach($faqs as $faq)
            <div class="bg-white rounded-3 shadow-sm overflow-hidden" data-faq-item data-faq-category="{{ $faq->category }}" data-accordion="{{ $loop->first ? 'open' : 'closed' }}">
                <div class="d-flex align-items-center justify-content-between gap-3 px-4 py-3" data-accordion-header style="cursor:pointer">
                    <div>
                        <h2 class="fw-black text-uppercase text-dark mb-0" style="font-size:.85rem;letter-spacing:.05em;line-height:1.5">
                            {{ $faq->question }}
                        </h2>
                        @if(count($categories) > 1)
                        <span class="text-secondary" style="font-size:.68rem;letter-spacing:.05em;text-transform:uppercase">{{ $faq->category }}</span>
                        @endif
                    </div>
                    <svg data-accordion-arrow width="14" height="14" viewBox="0 0 20 20" fill="currentColor" class="text-secondary flex-shrink-0"
                         style="transition:transform .15s;transform:{{ $loop->first ? 'rotate(90deg)' : '' }}">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div data-accordion-body style="{{ $loop->first ? '' : 'display:none' }};border-top:1px solid #f3f4f6">
                    <p class="text-secondary px-4 py-3 mb-0" style="line-height:1.8;white-space:pre-line">{{ $faq->answer }}</p>
                </div>
            </div>
            @endforeach
        </div>

        <p data-faq-empty class="text-secondary text-center mt-4 mb-0" style="font-size:.85rem;display:none">
            No questions in this category yet.
        </p>
        @endif

    </div>
</main>

@if(count($categories) > 1)
@push('scripts')
<script>
(function () {
    const wrap = document.querySelector('[data-faq-filter]');
    if (!wrap) return;

    const buttons = wrap.querySelectorAll('[data-faq-category-btn]');
    const items    = document.querySelectorAll('[data-faq-item]');
    const empty    = document.querySelector('[data-faq-empty]');

    function apply(category) {
        let visibleCount = 0;

        buttons.forEach(btn => {
            const active = btn.dataset.faqCategoryBtn === category;
            btn.style.background   = active ? '#7c3aed' : '#fff';
            btn.style.color        = active ? '#fff' : '#374151';
            btn.style.borderColor  = active ? '#7c3aed' : '#e5e7eb';
        });

        items.forEach(item => {
            const visible = category === '' || item.dataset.faqCategory === category;
            item.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        if (empty) empty.style.display = visibleCount === 0 ? '' : 'none';
    }

    buttons.forEach(btn => {
        btn.addEventListener('click', () => apply(btn.dataset.faqCategoryBtn));
    });
})();
</script>
@endpush
@endif
@endsection
