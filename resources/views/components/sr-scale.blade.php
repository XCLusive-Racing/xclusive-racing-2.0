@props(['heading' => null, 'readMoreUrl' => null, 'readMoreLabel' => 'Read more'])

{{-- XCL Safety Rating scale. Letters, colours and both label forms (full range and
     short threshold) come from App\Enums\SafetyRatingGrade, the single source of
     truth. The heading, the explanation (the slot) and the Read more link are
     supplied by the caller. Both labels are rendered; CSS picks one by screen width. --}}
<section {{ $attributes->class(['xcl-sr-scale']) }}>
    @if($heading)
    <h2 class="xcl-sr-scale__heading">{{ $heading }}</h2>
    @endif

    @if(trim((string) $slot) !== '')
    <div class="xcl-sr-scale__text">{{ $slot }}</div>
    @endif

    @if($readMoreUrl)
    <a href="{{ $readMoreUrl }}" class="xcl-sr-scale__more">{{ $readMoreLabel }}</a>
    @endif

    <div class="xcl-sr-scale__row">
        @foreach(\App\Enums\SafetyRatingGrade::scale() as $grade)
        <div class="xcl-sr-scale__item">
            <x-sr-badge :grade="$grade" size="lg" />
            <span class="xcl-sr-scale__range xcl-sr-scale__range--full">{{ $grade->rangeLabel() }}</span>
            <span class="xcl-sr-scale__range xcl-sr-scale__range--short" aria-hidden="true">{{ $grade->shortLabel() }}</span>
        </div>
        @endforeach
    </div>
</section>
