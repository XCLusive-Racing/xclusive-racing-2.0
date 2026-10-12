{{-- The reveal: plays when the profile opens with XP the driver has not seen yet
     (App\Xp\DriverXp::reveal, xp-reveal.js). Once shown it is marked as seen. --}}
<div class="xp-reveal" data-xp-reveal='@json($xpReveal)' @unless($xpReveal['replay']) data-xp-seen-url="{{ route('profile.xp.seen') }}" @endunless
     role="dialog" aria-modal="true" aria-label="XP earned" hidden>
    <div class="xp-reveal__panel">
        <button type="button" class="xp-reveal__close" data-xp-close aria-label="Close">&times;</button>

        <div class="xp-reveal__eyebrow">{{ $xpReveal['eyebrow'] }}</div>
        <h2 class="xp-reveal__title">{{ $xpReveal['title'] }}</h2>

        <div class="xp-reveal__rank">
            <div class="xp-badge xp-badge--lg" data-xp-badge>
                <span class="xp-badge__label">Level</span>
                <span class="xp-badge__num" data-xp-rank>0</span>
            </div>
            <div class="flex-grow-1">
                <div class="xp-reveal__rankup" data-xp-rankup>Level up!</div>
                <div class="xp-bar xp-bar--dark"><div class="xp-bar__fill" data-xp-fill></div></div>
                <div class="xp-reveal__progress"><span data-xp-into>0</span> / <span data-xp-needed>0</span> XP</div>
            </div>
        </div>

        <ul class="xp-reveal__lines" data-xp-lines>
            @foreach($xpReveal['lines'] as $line)
            <li class="xp-reveal__line">
                <span>{{ $line['label'] }}@if($line['note']) <small>{{ $line['note'] }}</small>@endif</span>
                <span class="xp-reveal__xp">+{{ number_format($line['xp']) }}</span>
            </li>
            @endforeach
        </ul>

        <div class="xp-reveal__section" data-xp-ach-title @if(empty($xpReveal['achievements'])) style="display:none" @endif>Achievements unlocked</div>
        <div class="xp-reveal__achievements" data-xp-achievements>
            @foreach($xpReveal['achievements'] as $achievement)
            <div class="xp-ach">
                <div class="xp-ach__icon"><i class="{{ $achievement['icon'] }}"></i></div>
                <div class="xp-ach__body">
                    <div class="xp-ach__name">{{ $achievement['name'] }}</div>
                    <div class="xp-ach__desc">{{ $achievement['description'] }}</div>
                </div>
                <div class="xp-reveal__xp">+{{ number_format($achievement['xp']) }}</div>
            </div>
            @endforeach
        </div>

        <div class="xp-reveal__footer">
            <div>
                <div class="xp-reveal__total">+<span data-xp-total>0</span> XP</div>
                <div class="xp-reveal__note">XP only ever goes up.</div>
            </div>
            <button type="button" class="xp-reveal__btn" data-xp-continue>Skip</button>
        </div>
    </div>
</div>
