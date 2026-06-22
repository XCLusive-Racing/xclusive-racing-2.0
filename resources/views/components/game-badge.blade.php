@props(['game'])

@php
    $colors = ['acc' => '#7c3aed', 'lmu' => '#db2777', 'iracing' => '#2563eb'];
    $color  = $colors[$game] ?? '#6b7280';
    $labels = ['acc' => 'ACC', 'lmu' => 'LMU', 'iracing' => 'iRACING'];
    $label  = $labels[$game] ?? strtoupper($game);
    $icons  = ['acc' => '/images/home/icons/ACC Logo.png', 'lmu' => '/images/home/icons/LM Logo.png', 'iracing' => '/images/home/icons/iR Logo.png'];
    $icon   = $icons[$game] ?? null;
@endphp

<span class="badge text-white fw-bold d-inline-flex align-items-center gap-1"
      style="background:{{ $color }};font-size:.6rem;padding:2px 6px;border-radius:4px">
    @if($icon)
    <img src="{{ $icon }}" alt="{{ $label }}" style="height:10px;width:auto;object-fit:contain">
    @endif
    {{ $label }}
</span>