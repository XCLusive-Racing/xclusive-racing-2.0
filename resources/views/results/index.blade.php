@extends('layouts.app')

@section('title', 'Results - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:1100px;position:relative;z-index:1">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">Results</h1>
                <p class="text-secondary mb-0">Race &amp; qualifying results per event</p>
            </div>
        </div>

        @if($races->isEmpty())
        <div class="bg-white rounded-3 shadow-sm p-5 text-center text-secondary">
            No finished races yet.
        </div>
        @else

        <div class="row g-4">

            {{-- Race selector --}}
            <div class="col-lg-3">
                <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                    <div class="px-3 py-2 border-bottom" style="background:#fafafa">
                        <span style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Events</span>
                    </div>
                    <div style="max-height:480px;overflow-y:auto">
                        @foreach($races as $race)
                        <a href="{{ route('results.index', ['race' => $race->id]) }}"
                           class="d-flex align-items-start gap-2 px-3 py-2 text-decoration-none border-bottom"
                           style="transition:background .1s;{{ $selected?->id === $race->id ? 'background:#7c3aed14;' : '' }}"
                           onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='{{ $selected?->id === $race->id ? '#7c3aed14' : '' }}'">
                            <span class="badge mt-1 flex-shrink-0" style="background:{{ $race->gameColor() }};font-size:.6rem">{{ $race->gameLabel() }}</span>
                            <div>
                                <div class="fw-bold text-dark" style="font-size:.82rem;line-height:1.3;{{ $selected?->id === $race->id ? 'color:#7c3aed!important' : '' }}">
                                    {{ $race->title }}
                                </div>
                                <div class="text-secondary" style="font-size:.72rem">{{ $race->scheduledAtUk()->format('d M Y') }}</div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Results panel --}}
            <div class="col-lg-9">
                @if($selected)
                <div x-data="{ tab: 'race' }">

                    {{-- Event header --}}
                    <div class="bg-white rounded-3 shadow-sm mb-3 p-3 d-flex align-items-center gap-3">
                        @if($selected->icon)
                        <img src="{{ $selected->icon_url }}" alt="" width="40" height="40" style="object-fit:contain;border-radius:6px">
                        @endif
                        <div>
                            <div class="fw-black text-dark" style="font-size:1rem">{{ $selected->title }}</div>
                            <div class="text-secondary" style="font-size:.78rem">
                                {{ $selected->track }} &middot; {{ $selected->scheduledAtUk()->format('d M Y') }}
                            </div>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                        <div class="d-flex border-bottom" style="background:#fafafa">
                            <button @click="tab = 'race'"
                                    :class="tab === 'race' ? 'text-dark border-bottom border-2 border-purple fw-bold' : 'text-secondary'"
                                    class="px-4 py-3 border-0 bg-transparent fw-bold"
                                    style="font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;cursor:pointer">
                                Race
                            </button>
                            <button @click="tab = 'quali'"
                                    :class="tab === 'quali' ? 'text-dark border-bottom border-2 border-purple fw-bold' : 'text-secondary'"
                                    class="px-4 py-3 border-0 bg-transparent fw-bold"
                                    style="font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;cursor:pointer">
                                Qualifying
                            </button>
                            <button @click="tab = 'rating'"
                                    :class="tab === 'rating' ? 'text-dark border-bottom border-2 border-purple fw-bold' : 'text-secondary'"
                                    class="px-4 py-3 border-0 bg-transparent fw-bold"
                                    style="font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;cursor:pointer">
                                Rating
                            </button>
                        </div>

                        {{-- Race results --}}
                        <div x-show="tab === 'race'">
                            @if($raceResults->isEmpty())
                            <p class="text-secondary text-center py-5" style="font-size:.85rem">No race results available.</p>
                            @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" style="font-size:.875rem">
                                    <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                                        <tr>
                                            <th class="fw-bold text-uppercase text-secondary ps-4 py-3" style="font-size:.7rem;letter-spacing:.06em;width:50px">Pos</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3" style="font-size:.7rem;letter-spacing:.06em">Driver</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3 d-none d-md-table-cell" style="font-size:.7rem;letter-spacing:.06em">Car</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3 text-center" style="font-size:.7rem;letter-spacing:.06em">Laps</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3 text-end pe-4" style="font-size:.7rem;letter-spacing:.06em">Best Lap</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($raceResults as $result)
                                        <tr style="border-bottom:1px solid #f9fafb">
                                            <td class="ps-4 fw-bold" style="font-size:.9rem;{{ $result->position === 1 ? 'color:#f59e0b' : ($result->position === 2 ? 'color:#9ca3af' : ($result->position === 3 ? 'color:#cd7f32' : 'color:#374151')) }}">
                                                {{ $result->dnf ? 'DNF' : $result->position }}
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold text-dark" style="font-size:.88rem">{{ $result->displayName() }}</span>
                                                    @if($result->fastest_lap)
                                                    <span class="badge" style="background:#7c3aed20;color:#7c3aed;font-size:.65rem;border:1px solid #7c3aed40">FL</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-secondary d-none d-md-table-cell" style="font-size:.8rem">{{ $result->vehicle ?? '—' }}</td>
                                            <td class="text-center text-secondary" style="font-size:.85rem">{{ $result->lap_count ?? '—' }}</td>
                                            <td class="text-end pe-4 fw-bold" style="font-size:.85rem;font-variant-numeric:tabular-nums">
                                                {{ \App\Models\RaceResult::formatMs($result->best_lap) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>

                        {{-- Quali results --}}
                        <div x-show="tab === 'quali'">
                            @if($qualiResults->isEmpty())
                            <p class="text-secondary text-center py-5" style="font-size:.85rem">No qualifying results available.</p>
                            @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" style="font-size:.875rem">
                                    <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                                        <tr>
                                            <th class="fw-bold text-uppercase text-secondary ps-4 py-3" style="font-size:.7rem;letter-spacing:.06em;width:50px">Pos</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3" style="font-size:.7rem;letter-spacing:.06em">Driver</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3 d-none d-md-table-cell" style="font-size:.7rem;letter-spacing:.06em">Car</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3 text-end pe-4" style="font-size:.7rem;letter-spacing:.06em">Best Lap</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($qualiResults as $result)
                                        <tr style="border-bottom:1px solid #f9fafb">
                                            <td class="ps-4 fw-bold" style="font-size:.9rem;{{ $result->position === 1 ? 'color:#f59e0b' : 'color:#374151' }}">
                                                {{ $result->position }}
                                            </td>
                                            <td class="fw-bold text-dark" style="font-size:.88rem">{{ $result->displayName() }}</td>
                                            <td class="text-secondary d-none d-md-table-cell" style="font-size:.8rem">{{ $result->vehicle ?? '—' }}</td>
                                            <td class="text-end pe-4 fw-bold" style="font-size:.85rem;font-variant-numeric:tabular-nums">
                                                {{ \App\Models\RaceResult::formatMs($result->best_lap) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>

                        {{-- Rating changes --}}
                        <div x-show="tab === 'rating'">
                            @php $ratingResults = $raceResults->filter(fn($r) => $r->elo_change !== null); @endphp
                            @if($ratingResults->isEmpty())
                            <p class="text-secondary text-center py-5" style="font-size:.85rem">No rating data available for this race.</p>
                            @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" style="font-size:.875rem">
                                    <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                                        <tr>
                                            <th class="fw-bold text-uppercase text-secondary ps-4 py-3" style="font-size:.7rem;letter-spacing:.06em;width:50px">Pos</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3" style="font-size:.7rem;letter-spacing:.06em">Driver</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3 text-end" style="font-size:.7rem;letter-spacing:.06em">Before</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3 text-end" style="font-size:.7rem;letter-spacing:.06em">Change</th>
                                            <th class="fw-bold text-uppercase text-secondary py-3 text-end pe-4" style="font-size:.7rem;letter-spacing:.06em">After</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ratingResults as $result)
                                        @php $change = round((float)$result->elo_change); @endphp
                                        <tr style="border-bottom:1px solid #f9fafb">
                                            <td class="ps-4 fw-bold text-secondary" style="font-size:.85rem">{{ $result->position }}</td>
                                            <td class="fw-bold text-dark" style="font-size:.88rem">{{ $result->displayName() }}</td>
                                            <td class="text-end text-secondary" style="font-size:.85rem;font-variant-numeric:tabular-nums">{{ number_format((float)$result->rating_before) }}</td>
                                            <td class="text-end fw-black" style="font-size:.9rem;font-variant-numeric:tabular-nums;color:{{ $change >= 0 ? '#10b981' : '#ef4444' }}">
                                                {{ $change >= 0 ? '+' : '' }}{{ $change }}
                                            </td>
                                            <td class="text-end pe-4 fw-black" style="font-size:.9rem;font-variant-numeric:tabular-nums;color:#111827">
                                                {{ number_format((float)$result->rating_after) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>

                    </div>
                </div>
                @endif
            </div>

        </div>
        @endif

    </div>
</main>
@endsection