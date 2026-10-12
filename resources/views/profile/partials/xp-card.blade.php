{{-- Driver Level: XP and the way to the next level (App\Xp\DriverXp). Its own card and
     colour, apart from XCL Rating and SR above: it shows how much a driver has raced, not
     how fast they are. --}}
<div class="xp-card bg-white rounded-3 shadow-sm p-4 mb-4">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="xp-badge">
            <span class="xp-badge__label">Level</span>
            <span class="xp-badge__num">{{ $xp['level'] }}</span>
        </div>
        <div class="flex-grow-1" style="min-width:220px">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div class="fw-black text-uppercase text-dark" style="letter-spacing:.04em">Driver Level</div>
                <div class="text-secondary" style="font-size:.8rem">
                    @if($xp['max'])
                    <span class="fw-bold text-dark">Maximum level reached</span>
                    @else
                    <span class="fw-bold text-dark">{{ number_format($xp['into']) }}</span> / {{ number_format($xp['needed']) }} XP to level {{ $xp['next'] }}
                    @endif
                </div>
            </div>
            <div class="xp-bar mt-2"><div class="xp-bar__fill" style="width:{{ $xp['percent'] }}%"></div></div>
            <div class="d-flex justify-content-between flex-wrap gap-2 mt-2" style="font-size:.78rem">
                <span class="text-secondary">{{ number_format($xp['total']) }} XP in total, earned by racing and by unlocking achievements</span>
                <span class="fw-bold" style="color:#0891b2">{{ $xpCollection['earned'] }} of {{ $xpCollection['total'] }} achievements</span>
            </div>
        </div>
    </div>
</div>
