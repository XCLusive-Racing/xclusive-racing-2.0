{{-- A date/time shown in the viewer's own timezone and clock (resources/js/components/
     local-time.js rewrites it on load). Rendered here in UK time as the no-JS fallback,
     in the same shape as the JS format of the same name — keep the two lists in sync. --}}
@props(['at', 'format' => 'date-time', 'upper' => false])
@php
    $uk = \Carbon\Carbon::parse($at)->timezone('Europe/London');
    $time = auth()->user()?->uses_12_hour_clock ? 'g:i A' : 'H:i';
    $fallback = $uk->format(match ($format) {
        'weekday'         => 'l',
        'date-short'      => 'D, M d',
        'full'            => "D d M Y · {$time} T",
        'sidebar'         => "D, M d, {$time} T",
        'date-time-comma' => "d M Y, {$time} T",
        'dm-time'         => "d M · {$time} T",
        'time'            => $time,
        'time-tz'         => "{$time} T",
        'day'             => 'd',
        'month-year'      => 'M Y',
        default           => "d M Y · {$time} T",
    });
@endphp
<time datetime="{{ $uk->toIso8601String() }}" data-local-time="{{ $format }}" @if($upper) data-local-upper @endif {{ $attributes }}>{{ $upper ? strtoupper($fallback) : $fallback }}</time>
