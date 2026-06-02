@props(['game'])

@php
    $colors = ['acc' => '#7c3aed', 'lmu' => '#db2777', 'iracing' => '#2563eb'];
    $color  = $colors[$game] ?? '#6b7280';
    $labels = ['acc' => 'ACC', 'lmu' => 'LMU', 'iracing' => 'iRACING'];
    $label  = $labels[$game] ?? strtoupper($game);
@endphp

<span class="badge text-white fw-bold"
      style="background:{{ $color }};font-size:.6rem;padding:2px 6px;border-radius:4px">
    {{ $label }}
</span>