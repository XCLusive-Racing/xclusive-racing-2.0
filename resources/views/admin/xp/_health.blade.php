{{-- Curve health: does the title agnostic catalogue still land on the anchor rank?
     Needs $health (RankCurve::health()) and $anchorRank. --}}
@php
    $difference = $health['difference'];
    $ok = $difference === 0;
@endphp
<div class="admin-card mb-4">
    <div class="admin-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-black text-uppercase" style="font-size:.8rem;letter-spacing:.05em">Curve health</span>
        @if(! request()->routeIs('admin.xp.curve'))
        <a href="{{ route('admin.xp.curve') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.68rem;padding:4px 10px">Rank curve</a>
        @endif
    </div>
    <div class="px-4 py-3">
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="stat-box">
                    <div class="stat-num">{{ number_format($health['catalogue']) }}</div>
                    <div class="stat-label">Title agnostic catalogue XP</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-box">
                    <div class="stat-num">{{ $health['required'] !== null ? number_format($health['required']) : 'n/a' }}</div>
                    <div class="stat-label">XP the curve needs for rank {{ $anchorRank }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-box" style="background:{{ $ok ? '#f0fdf4' : '#fef2f2' }}">
                    <div class="stat-num" style="color:{{ $ok ? '#16a34a' : '#dc2626' }}">
                        {{ $difference === null ? 'n/a' : ($difference > 0 ? '+' : '').number_format($difference) }}
                    </div>
                    <div class="stat-label">Difference</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-box">
                    <div class="stat-num">{{ number_format($health['title_specific']) }}</div>
                    <div class="stat-label">Title specific XP</div>
                </div>
            </div>
        </div>
        <p class="text-secondary mt-3 mb-0" style="font-size:.8rem">
            @if($ok)
                Unlocking every title agnostic achievement lands exactly on rank {{ $anchorRank }}.
            @elseif($difference !== null)
                The title agnostic catalogue is {{ number_format(abs($difference)) }} XP {{ $difference > 0 ? 'above' : 'below' }} rank {{ $anchorRank }}.
                Every catalogue edit moves this. Refit the curve on the Rank curve screen to line them up again.
            @endif
            Title specific XP does not affect rank {{ $anchorRank }}, by design: it is a head start on the ranks above it.
            Archived achievements are not counted.
        </p>
    </div>
</div>
