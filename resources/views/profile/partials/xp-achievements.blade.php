{{-- The achievement collection (App\Xp\DriverXp::collection): by category, each tiered
     family as one item showing the tier the driver is working on. --}}
@php $xpNumber = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.'); @endphp

<div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2 mb-4">
    <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-0">ACHIEVEMENTS</h2>
    <div class="text-secondary" style="font-size:.85rem">
        <span class="fw-black text-dark">{{ $xpCollection['earned'] }}</span> of {{ $xpCollection['total'] }} unlocked
    </div>
</div>

@if($xpCollection['total'] === 0)
<p class="text-secondary mb-0">There are no achievements to earn yet.</p>
@endif

@foreach($xpCollection['categories'] as $category)
<div class="xp-cat">
    <div class="xp-cat__head">
        <i class="{{ $category['icon'] }}"></i>
        <span>{{ $category['name'] }}</span>
        <span class="xp-cat__count">{{ $category['families']->sum('earned') }} / {{ $category['families']->sum(fn ($f) => count($f['tiers'])) }}</span>
    </div>
    <div class="xp-fams">
        @foreach($category['families'] as $family)
        <div class="xp-fam {{ $family['complete'] ? 'xp-fam--complete' : ($family['earned'] > 0 ? 'xp-fam--started' : '') }}">
            <div class="xp-fam__icon"><i class="{{ $family['complete'] ? 'fa-solid fa-check' : $category['icon'] }}"></i></div>
            <div class="xp-fam__body">
                <div class="xp-fam__top">
                    <span class="xp-fam__name">{{ $family['name'] }}</span>
                    @if($family['scope'] !== 'all')
                    <x-game-badge :game="$family['scope']" />
                    @endif
                    <span class="xp-fam__xp">{{ $family['complete'] ? number_format($family['earned_xp']).' XP earned' : '+'.number_format($family['xp']).' XP' }}</span>
                </div>
                <div class="xp-fam__desc">{{ $family['description'] }}</div>
                <div class="xp-bar xp-bar--thin"><div class="xp-bar__fill" style="width:{{ $family['percent'] }}%"></div></div>
                <div class="xp-fam__foot">
                    <span>{{ $family['complete'] ? 'Complete' : $xpNumber($family['value']).' / '.$xpNumber($family['threshold']) }}</span>
                    @if(count($family['tiers']) > 1)
                    <span class="xp-fam__tiers" aria-label="{{ $family['earned'] }} of {{ count($family['tiers']) }} tiers unlocked">
                        @foreach($family['tiers'] as $tierHeld)
                        <i class="{{ $tierHeld ? 'is-held' : '' }}"></i>
                        @endforeach
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach
