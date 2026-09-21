@props(['grade', 'size' => 'sm'])

@php
    // Accepts the enum case or its letter. The letter, colour and labels all come
    // from App\Enums\SafetyRatingGrade, never from this component.
    $gradeCase = $grade instanceof \App\Enums\SafetyRatingGrade
        ? $grade
        : \App\Enums\SafetyRatingGrade::tryFrom((string) $grade);
@endphp

@if($gradeCase)
<span {{ $attributes->class(['xcl-sr-badge', 'xcl-sr-badge--'.($size === 'lg' ? 'lg' : 'sm')]) }}
      data-grade="{{ $gradeCase->value }}"
      style="--sr-color:{{ $gradeCase->color() }}"
      role="img"
      aria-label="{{ $gradeCase->ariaLabel() }}"><span class="xcl-sr-badge__letter" aria-hidden="true">{{ $gradeCase->value }}</span></span>
@endif
