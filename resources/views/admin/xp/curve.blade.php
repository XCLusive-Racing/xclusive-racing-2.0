@extends('layouts.admin')

@section('title', 'Rank curve')
@section('page-title', 'Rank curve')

@section('content')

@include('admin.xp._nav')
@include('admin.xp._health')

<div class="admin-card">
    <div class="admin-card-header">
        <span class="fw-black text-uppercase" style="font-size:.8rem;letter-spacing:.05em">Recalculate the curve</span>
    </div>
    <div class="px-4 py-3">
        <p class="text-secondary" style="font-size:.85rem">
            Refitting changes the XP each rank costs so that the title agnostic catalogue
            ({{ number_format($preview['target']) }} XP) lands exactly on rank {{ $anchorRank }}.
            The first four steps stay fixed at 100, 150, 300 and 500. The steps from rank 5 up to rank {{ $anchorRank }}
            are scaled together, and every step from rank {{ $anchorRank }} to rank {{ $maxRank }} keeps its cost.
        </p>

        @if(! $preview['possible'])
        <div class="alert mb-0" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:.85rem">
            The curve cannot be refitted: {{ $preview['reason'] }}
        </div>
        @elseif($preview['changed'] === 0)
        <div class="alert mb-0" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:.85rem">
            Nothing to do: the curve already fits the catalogue.
        </div>
        @else
        <div class="fw-bold text-uppercase text-secondary mb-2" style="font-size:.7rem;letter-spacing:.05em">
            Preview: {{ $preview['changed'] }} of the rank steps would change
        </div>
        <div class="table-responsive mb-3">
            <table class="table align-middle mb-0" style="font-size:.85rem;max-width:560px">
                <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                    <tr>
                        <th class="fw-bold text-uppercase py-2" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Rank</th>
                        <th class="fw-bold text-uppercase py-2 text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">XP needed now</th>
                        <th class="fw-bold text-uppercase py-2 text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">After the refit</th>
                        <th class="fw-bold text-uppercase py-2 text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['before'] as $rank => $before)
                    @php $change = $preview['after'][$rank] - $before; @endphp
                    <tr>
                        <td class="fw-bold">{{ $rank }}</td>
                        <td class="text-end">{{ number_format($before) }}</td>
                        <td class="text-end fw-bold">{{ number_format($preview['after'][$rank]) }}</td>
                        <td class="text-end" style="color:{{ $change === 0 ? '#9ca3af' : ($change > 0 ? '#dc2626' : '#16a34a') }}">
                            {{ $change > 0 ? '+' : '' }}{{ number_format($change) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="alert" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:.82rem">
            <strong>This changes every driver's rank, not their XP.</strong>
            Rank is always looked up from a driver's XP total, so a rank that becomes more expensive can show a driver on a lower rank than before,
            even though no XP was taken away. Stored ranks are updated the next time a driver earns XP.
        </div>
        <form method="POST" action="{{ route('admin.xp.curve.refit') }}"
              onsubmit="return confirm('Refit the rank curve? This changes the rank every XP total maps to.')">
            @csrf
            <button type="submit" class="btn fw-bold text-white" style="background:#7c3aed;font-size:.85rem">Apply the refit</button>
        </form>
        @endif
    </div>
</div>
@endsection
