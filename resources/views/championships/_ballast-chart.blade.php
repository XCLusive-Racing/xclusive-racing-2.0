{{-- Ballast Record — every driver's success ballast round by round
     (EntryBalanceService::chart()), drawn as a plain inline SVG line chart. --}}
@php
    $w = 720; $h = 300; $padL = 40; $padR = 16; $padT = 14; $padB = 30;
    $allKg = collect($ballastChart['series'])->flatMap(fn ($s) => collect($s['points'])->pluck(1));
    $step = max(1, (int) ceil(max(1, $allKg->max() - $allKg->min()) / 8 / 5) * 5);
    $minKg = (int) floor(min(0, $allKg->min()) / $step) * $step;
    $maxKg = (int) ceil(max($step, $allKg->max()) / $step) * $step;
    $rounds = max(1, $ballastChart['rounds']);
    $x = fn ($round) => round($padL + ($w - $padL - $padR) * $round / $rounds, 1);
    $y = fn ($kg) => round($padT + ($h - $padT - $padB) * ($maxKg - $kg) / max(1, $maxKg - $minKg), 1);
    $colour = fn ($i) => 'hsl('.(($i * 137) % 360).', 70%, 60%)';
@endphp
<div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Ballast Record</h2>
    </div>
    <div class="px-3 pt-3">
        <svg viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="Success ballast per driver per round" style="width:100%;height:auto;display:block">
            @for($kg = $minKg; $kg <= $maxKg; $kg += $step)
            <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $y($kg) }}" y2="{{ $y($kg) }}" stroke="{{ $kg === 0 ? '#4b5563' : '#1f2937' }}" stroke-width="1"/>
            <text x="{{ $padL - 6 }}" y="{{ $y($kg) + 4 }}" text-anchor="end" fill="#6b7280" font-size="11">{{ $kg }}</text>
            @endfor
            @for($round = 0; $round <= $rounds; $round++)
            <text x="{{ $x($round) }}" y="{{ $h - 10 }}" text-anchor="middle" fill="#6b7280" font-size="11">R{{ $round }}</text>
            @endfor
            @foreach($ballastChart['series'] as $i => $line)
            <g>
                <title>{{ $line['user']->displayName() }}: {{ $line['current'] }} kg</title>
                <polyline fill="none" stroke="{{ $colour($i) }}" stroke-width="2" stroke-linejoin="round"
                          points="{{ collect($line['points'])->map(fn ($p) => $x($p[0]).','.$y($p[1]))->join(' ') }}"/>
                @foreach($line['points'] as [$round, $kg])
                <circle cx="{{ $x($round) }}" cy="{{ $y($kg) }}" r="3" fill="{{ $colour($i) }}">
                    <title>{{ $line['user']->displayName() }} — R{{ $round }}: {{ $kg }} kg</title>
                </circle>
                @endforeach
            </g>
            @endforeach
        </svg>
    </div>
    <div class="px-4 pb-3 pt-2 d-flex flex-wrap gap-3" style="font-size:.75rem">
        @foreach($ballastChart['series'] as $i => $line)
        <span class="d-inline-flex align-items-center gap-1" style="color:#d1d5db">
            <span style="width:10px;height:10px;border-radius:50%;background:{{ $colour($i) }};display:inline-block"></span>
            {{ $line['user']->displayName() }}
            <span class="fw-bold" style="color:#9ca3af">{{ $line['current'] }} kg</span>
        </span>
        @endforeach
    </div>
</div>
