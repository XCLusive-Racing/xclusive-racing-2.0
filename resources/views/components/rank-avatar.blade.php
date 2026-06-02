@props(['user', 'size' => 88, 'badge' => true])

@php
    $rank   = $user->rank('acc');
    $slug   = $rank['slug'];
    $ringId = 'rank-' . $slug . '-' . $size;
@endphp

<style>
    .rank-ring-rookie   { background: linear-gradient(135deg, #ef4444, #b91c1c, #ef4444); background-size: 200% 200%; }
    .rank-ring-bronze   { background: linear-gradient(135deg, #cd7f32, #f59e0b, #92400e, #cd7f32); background-size: 300% 300%; animation: xcl-shimmer 3s ease-in-out infinite; }
    .rank-ring-silver   { background: linear-gradient(135deg, #6b7280, #e5e7eb, #9ca3af, #e5e7eb, #6b7280); background-size: 300% 300%; animation: xcl-shimmer 2.5s ease-in-out infinite; }
    .rank-ring-gold     { background: linear-gradient(135deg, #92400e, #f59e0b, #fde68a, #f59e0b, #92400e); background-size: 300% 300%; animation: xcl-shimmer 2s ease-in-out infinite; box-shadow: 0 0 10px rgba(245,158,11,.5); }
    .rank-ring-platinum { background: linear-gradient(135deg, #4c1d95, #7c3aed, #a78bfa, #7c3aed, #4c1d95); background-size: 300% 300%; animation: xcl-shimmer 1.8s ease-in-out infinite; box-shadow: 0 0 14px rgba(124,58,237,.65); }
    .rank-ring-alien    { background: conic-gradient(#10b981, #34d399, #6ee7b7, #10b981, #059669, #10b981); animation: xcl-spin 2.5s linear infinite; box-shadow: 0 0 18px rgba(16,185,129,.8); }

    @keyframes xcl-shimmer {
        0%   { background-position: 200% center; }
        100% { background-position: -200% center; }
    }
    @keyframes xcl-spin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }

    .rank-shine-wrap { position: relative; overflow: hidden; border-radius: 50%; display: block; }
    .rank-shine-wrap::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,.35) 50%, transparent 60%);
        border-radius: 50%;
        animation: xcl-shine 3s ease-in-out infinite;
    }
    @keyframes xcl-shine {
        0%   { transform: translateX(-100%) rotate(30deg); }
        40%  { transform: translateX(150%) rotate(30deg); }
        100% { transform: translateX(150%) rotate(30deg); }
    }
</style>

<div class="text-center d-inline-block">
    {{-- Spinner wrapper needed for alien conic rotation --}}
    @if($slug === 'alien')
    <div style="position:relative;width:{{ $size + 10 }}px;height:{{ $size + 10 }}px;display:inline-block">
        <div class="rank-ring-{{ $slug }}" style="position:absolute;inset:0;border-radius:50%"></div>
        <div style="position:absolute;inset:3px;background:white;border-radius:50%;padding:2px">
            <div class="rank-shine-wrap" style="width:100%;height:100%">
                @if($user->banner)
                    <img src="{{ asset($user->banner) }}" alt="{{ $user->name }}"
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
                    <img src="{{ asset($user->banner) }}" alt="{{ $user->name }}"
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