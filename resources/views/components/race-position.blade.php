@props(['position'])

@if($position === 1)
    <span class="fw-black" style="color:#f59e0b">P1</span>
@elseif($position === 2)
    <span class="fw-black" style="color:#6b7280">P2</span>
@elseif($position === 3)
    <span class="fw-black" style="color:#92400e">P3</span>
@else
    <span class="fw-bold text-secondary">P{{ $position }}</span>
@endif