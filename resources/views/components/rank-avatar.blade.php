@props(['user', 'size' => 88, 'badge' => true])

@php
    $rank   = $user->rank('acc');
    $slug   = $rank['slug'];
    $ringId = 'rank-' . $slug . '-' . $size;
@endphp

<div class="text-center d-inline-block">
    {{-- Spinner wrapper needed for alien conic rotation --}}
    @if($slug === 'alien')
    <div style="position:relative;width:{{ $size + 10 }}px;height:{{ $size + 10 }}px;display:inline-block">
        <div class="rank-ring-{{ $slug }}" style="position:absolute;inset:0;border-radius:50%"></div>
        <div style="position:absolute;inset:3px;background:white;border-radius:50%;padding:2px">
            <div class="rank-shine-wrap" style="width:100%;height:100%">
                @if($user->banner)
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}"
                         class="rounded-circle" style="width:100%;height:100%;object-fit:cover;display:block">
                @else
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-gradient-xcl"
                         style="width:100%;height:100%">
                        <span class="fw-black text-white" style="font-size:{{ round($size * .35) }}px">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="rank-ring-{{ $slug }}" style="padding:3px;border-radius:50%;display:inline-block">
        <div style="background:white;padding:2px;border-radius:50%">
            <div class="rank-shine-wrap" style="width:{{ $size }}px;height:{{ $size }}px">
                @if($user->banner)
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}"
                         class="rounded-circle" style="width:{{ $size }}px;height:{{ $size }}px;object-fit:cover;display:block">
                @else
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-gradient-xcl"
                         style="width:{{ $size }}px;height:{{ $size }}px">
                        <span class="fw-black text-white" style="font-size:{{ round($size * .35) }}px">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($badge)
    <div class="mt-2">
        <span class="badge fw-black text-uppercase text-white"
              style="background:{{ $rank['color'] }};font-size:.62rem;padding:3px 10px;border-radius:20px;letter-spacing:.06em">
            {{ $rank['name'] }}
        </span>
    </div>
    @endif
</div>