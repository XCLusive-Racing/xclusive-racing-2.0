@extends('layouts.admin')

@section('title', 'Results — ' . $race->title)
@section('page-title', 'Race Results')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

{{-- Race info strip --}}
<div class="admin-card mb-4 p-0 overflow-hidden">
    <div class="d-flex align-items-center gap-0 flex-wrap">
        <div class="p-4" style="border-right:1px solid #f3f4f6;min-width:160px">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Race</div>
            <div class="fw-black text-dark mt-1" style="font-size:.95rem">{{ $race->title }}</div>
        </div>
        <div class="p-4" style="border-right:1px solid #f3f4f6;min-width:140px">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Track</div>
            <div class="fw-bold text-dark mt-1" style="font-size:.9rem">{{ $race->track }}</div>
        </div>
        <div class="p-4" style="border-right:1px solid #f3f4f6;min-width:180px">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Date</div>
            <div class="fw-bold text-dark mt-1" style="font-size:.9rem">{{ $race->scheduledAtUk()->format('d M Y · H:i T') }}</div>
        </div>
        <div class="p-4">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Game</div>
            <span class="badge text-white fw-bold mt-1 d-inline-block"
                  style="background:{{ $race->gameColor() }};font-size:.72rem;padding:4px 10px;border-radius:6px">
                {{ $race->gameLabel() }}
            </span>
        </div>
    </div>
</div>

{{-- JSON Import card --}}
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Import from gPortal</div>
        </div>
        @if($raceResults->isNotEmpty() || $qualiResults->isNotEmpty())
        <div class="d-flex gap-2">
            @if($raceResults->isNotEmpty())
            <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
                Race: {{ $raceResults->count() }} drivers
            </span>
            @endif
            @if($qualiResults->isNotEmpty())
            <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
                Quali: {{ $qualiResults->count() }} drivers
            </span>
            @endif
        </div>
        @endif
    </div>

    <div class="p-4">
        <form action="{{ route('admin.races.results.store', $race) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="d-flex gap-3 align-items-end flex-wrap">
                <div class="flex-grow-1">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">JSON Results Files</label>
                    <input type="file"
                           name="result_json[]"
                           accept=".json,application/json"
                           class="form-control"
                           style="border-color:#e5e7eb;font-size:.875rem"
                           multiple
                           required>
                </div>
                <button type="submit"
                        class="btn fw-black text-uppercase text-white px-4 flex-shrink-0"
                        style="background:#7c3aed;height:42px">
                    Import Results
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Tabs --}}
<div class="admin-card" x-data="{ tab: '{{ $raceResults->isNotEmpty() ? 'race' : ($qualiResults->isNotEmpty() ? 'quali' : 'race') }}' }">

    {{-- Tab nav --}}
    <div class="d-flex border-bottom px-2" style="background:#f9fafb">
        <button @click="tab = 'race'"
                :style="tab === 'race' ? 'color:#7c3aed;border-bottom:2px solid #7c3aed' : 'color:#9ca3af;border-bottom:2px solid transparent'"
                class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s">
            Race Results
            @if($raceResults->isNotEmpty())
            <span class="badge ms-1 text-white" style="background:#7c3aed;font-size:.65rem;padding:2px 7px;border-radius:10px">
                {{ $raceResults->count() }}
            </span>
            @endif
        </button>
        <button @click="tab = 'quali'"
                :style="tab === 'quali' ? 'color:#2563eb;border-bottom:2px solid #2563eb' : 'color:#9ca3af;border-bottom:2px solid transparent'"
                class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s">
            Qualifying
            @if($qualiResults->isNotEmpty())
            <span class="badge ms-1 text-white" style="background:#2563eb;font-size:.65rem;padding:2px 7px;border-radius:10px">
                {{ $qualiResults->count() }}
            </span>
            @endif
        </button>
        <button @click="tab = 'ratings'"
                :style="tab === 'ratings' ? 'color:#059669;border-bottom:2px solid #059669' : 'color:#9ca3af;border-bottom:2px solid transparent'"
                class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s">
            Ratings
        </button>
    </div>

    {{-- Race Results tab --}}
    <div x-show="tab === 'race'" x-cloak>
        @if($raceResults->isEmpty())
        <div class="p-5 text-center">
            <div style="font-size:2rem;margin-bottom:.5rem">🏁</div>
            <div class="fw-bold text-dark" style="font-size:.95rem">No race results yet</div>
            <div class="text-secondary mt-1" style="font-size:.82rem">Import a gPortal JSON file to populate results.</div>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
                <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <tr>
                        <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:60px">Pos</th>
                        <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:60px">Car #</th>
                        <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:80px">Laps</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:110px">Best Lap</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:120px">Total Time</th>
                        <th class="fw-bold text-uppercase text-center pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:120px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($raceResults as $result)
                    <tr>
                        <td class="ps-4">
                            @if($result->position === 1)
                                <span class="fw-black" style="color:#f59e0b;font-size:1rem">P1</span>
                            @elseif($result->position === 2)
                                <span class="fw-black text-secondary;font-size:1rem">P2</span>
                            @elseif($result->position === 3)
                                <span class="fw-black" style="color:#92400e;font-size:1rem">P3</span>
                            @else
                                <span class="fw-bold text-secondary">P{{ $result->position }}</span>
                            @endif
                        </td>
                        <td>
                            @if($result->car_number)
                            <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.72rem">#{{ $result->car_number }}</span>
                            @else
                            <span class="text-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0"
                                     style="width:30px;height:30px;font-size:.72rem;background:{{ $race->gameColor() }}">
                                    {{ strtoupper(substr($result->displayName(), 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $result->displayName() }}</div>
                                    @if(!$result->user_id && $result->player_id)
                                    <div class="text-secondary" style="font-size:.68rem">{{ $result->player_id }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center fw-bold">{{ $result->lap_count ?? '—' }}</td>
                        <td class="text-center fw-bold" style="font-family:monospace">
                            {{ \App\Models\RaceResult::formatMs($result->best_lap) }}
                            @if($result->fastest_lap)
                            <span class="badge ms-1" style="background:#7c3aed;font-size:.6rem;padding:2px 6px">FL</span>
                            @endif
                        </td>
                        <td class="text-center" style="font-family:monospace;font-size:.82rem">
                            {{ \App\Models\RaceResult::formatMs($result->total_time) }}
                        </td>
                        <td class="text-center pe-4">
                            @if($result->dnf)
                                <span class="badge" style="background:#fef2f2;color:#dc2626;font-size:.72rem;padding:4px 8px;border-radius:6px;font-weight:700">DNF</span>
                            @else
                                <span class="badge" style="background:#f0fdf4;color:#16a34a;font-size:.72rem;padding:4px 8px;border-radius:6px;font-weight:700">FIN</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Qualifying tab --}}
    <div x-show="tab === 'quali'" x-cloak>
        @if($qualiResults->isEmpty())
        <div class="p-5 text-center">
            <div style="font-size:2rem;margin-bottom:.5rem">⏱️</div>
            <div class="fw-bold text-dark" style="font-size:.95rem">No qualifying results yet</div>
            <div class="text-secondary mt-1" style="font-size:.82rem">Import a gPortal JSON file with a qualifying session (Q) to populate this tab.</div>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
                <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <tr>
                        <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:60px">Pos</th>
                        <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:60px">Car #</th>
                        <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:80px">Laps</th>
                        <th class="fw-bold text-uppercase text-center pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:130px">Best Lap</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($qualiResults as $result)
                    <tr>
                        <td class="ps-4">
                            @if($result->position === 1)
                                <span class="fw-black" style="color:#f59e0b;font-size:1rem">P1</span>
                            @elseif($result->position === 2)
                                <span class="fw-black text-secondary">P2</span>
                            @elseif($result->position === 3)
                                <span class="fw-black" style="color:#92400e">P3</span>
                            @else
                                <span class="fw-bold text-secondary">P{{ $result->position }}</span>
                            @endif
                        </td>
                        <td>
                            @if($result->car_number)
                            <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.72rem">#{{ $result->car_number }}</span>
                            @else
                            <span class="text-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0"
                                     style="width:30px;height:30px;font-size:.72rem;background:#2563eb">
                                    {{ strtoupper(substr($result->displayName(), 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $result->displayName() }}</div>
                                    @if(!$result->user_id && $result->player_id)
                                    <div class="text-secondary" style="font-size:.68rem">{{ $result->player_id }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center fw-bold">{{ $result->lap_count ?? '—' }}</td>
                        <td class="text-center pe-4">
                            <span class="fw-bold" style="font-family:monospace">
                                {{ \App\Models\RaceResult::formatMs($result->best_lap) }}
                            </span>
                            @if($result->fastest_lap)
                            <span class="badge ms-1" style="background:#7c3aed;font-size:.6rem;padding:2px 6px">FL</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Ratings tab --}}
    <div x-show="tab === 'ratings'" x-cloak>
        <div class="p-5 text-center">
            <div style="font-size:2.5rem;margin-bottom:.75rem">📊</div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.1rem">ELO Ratings</div>
            <div class="text-secondary mt-2" style="font-size:.875rem;max-width:380px;margin:0 auto">
                Rating changes will appear here after ELO calculation is implemented.
                Ratings are based on race results per platform (ACC, LMU, iRacing).
            </div>
        </div>
    </div>

</div>

@endsection