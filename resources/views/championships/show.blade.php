@extends('layouts.app')

@section('title', $championship->name . ' — ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    {{-- Hero --}}
    <div style="position:relative;overflow:hidden;min-height:260px;background:linear-gradient(135deg,{{ $championship->gameColor() }}22,#0a0a0f)">
        @if($championship->image_url)
        <img src="{{ $championship->image_url }}" alt="{{ $championship->name }}"
             style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.25">
        @endif
        <div style="position:absolute;inset:0;background:linear-gradient(to right,#0a0a0f 30%,transparent 100%)"></div>

        <div class="container-xl px-3" style="position:relative;z-index:1;padding-top:3.5rem;padding-bottom:2.5rem">
            <div class="d-flex align-items-center gap-3 mb-3">
                @if($championship->icon_url)
                <img src="{{ $championship->icon_url }}" alt="" style="width:56px;height:56px;object-fit:contain">
                @endif
                <div>
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <span class="badge text-white fw-bold" style="background:{{ $championship->gameColor() }};font-size:.7rem;padding:4px 10px;border-radius:6px">
                            {{ $championship->gameLabel() }}
                        </span>
                        <span class="badge fw-bold" style="background:#ffffff18;color:#e5e7eb;font-size:.7rem;padding:4px 10px;border-radius:6px">
                            Season {{ $championship->season }}
                        </span>
                        @php $sc = ['active'=>'#16a34a','finished'=>'#9ca3af','draft'=>'#f59e0b'][$championship->status] ?? '#9ca3af'; @endphp
                        <span class="badge fw-bold" style="background:{{ $sc }}33;color:{{ $sc }};font-size:.7rem;padding:4px 10px;border-radius:6px">
                            {{ ucfirst($championship->status) }}
                        </span>
                        @if($championship->is_multiclass)
                        <span class="badge fw-bold" style="background:#db277733;color:#db2777;font-size:.7rem;padding:4px 10px;border-radius:6px">
                            Multiclass
                        </span>
                        @endif
                    </div>
                    <h1 class="fw-black text-white mb-0" style="font-size:clamp(1.5rem,4vw,2.5rem);line-height:1.1">{{ $championship->name }}</h1>
                </div>
            </div>

            @if($championship->description)
            <p style="color:#9ca3af;max-width:600px;font-size:.9rem">{{ $championship->description }}</p>
            @endif
        </div>
    </div>

    <div class="container-xl px-3 mt-4">
        <div class="row g-4">

            {{-- Left: standings + rounds --}}
            <div class="col-12 col-lg-8">

                {{-- Standings --}}
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Championship Standings</h2>
                    </div>

                    @if(empty($standings))
                    <div class="px-4 py-4 text-center" style="color:#6b7280;font-size:.875rem">
                        No results yet — standings will appear once rounds are completed.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:.875rem">
                            <thead style="background:#0f172a">
                                <tr>
                                    <th class="fw-bold text-uppercase ps-4" style="font-size:.68rem;color:#6b7280;letter-spacing:.06em">Pos</th>
                                    <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#6b7280;letter-spacing:.06em">Driver</th>
                                    @foreach($rounds->where('status','finished') as $r)
                                    <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;color:#6b7280;letter-spacing:.06em" title="{{ $r->title }}">R{{ $r->round_number }}</th>
                                    @endforeach
                                    <th class="fw-bold text-uppercase text-center pe-4" style="font-size:.68rem;color:#6b7280;letter-spacing:.06em">PTS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($standings as $i => $entry)
                                @php
                                    $medalColors = ['#f59e0b','#9ca3af','#b45309'];
                                    $posColor = $medalColors[$i] ?? '#6b7280';
                                @endphp
                                <tr style="border-bottom:1px solid #1f2937">
                                    <td class="ps-4 fw-black" style="color:{{ $posColor }};font-size:.95rem">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-black text-white flex-shrink-0"
                                                 style="width:30px;height:30px;font-size:.7rem;background:linear-gradient(135deg,{{ $championship->gameColor() }},#db2777)">
                                                {{ strtoupper(substr($entry['user']?->name ?? '?', 0, 1)) }}
                                            </div>
                                            <span class="fw-bold text-white">{{ $entry['user']?->name ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    @foreach($rounds->where('status','finished') as $r)
                                    @php
                                        $rd = collect($entry['rounds'])->firstWhere('race_id', $r->id);
                                        $dropped = in_array($r->id, $entry['dropped']);
                                    @endphp
                                    <td class="text-center" style="color:{{ $dropped ? '#4b5563' : '#9ca3af' }};{{ $dropped ? 'text-decoration:line-through' : '' }}">
                                        {{ $rd ? $rd['points'] : '—' }}
                                    </td>
                                    @endforeach
                                    <td class="text-center pe-4 fw-black" style="color:#db2777;font-size:1rem">{{ $entry['total_points'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                {{-- Rounds --}}
                <div style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Rounds</h2>
                    </div>

                    @if($rounds->isEmpty())
                    <div class="px-4 py-4 text-center" style="color:#6b7280;font-size:.875rem">No rounds scheduled yet.</div>
                    @else
                    @foreach($rounds as $round)
                    <div class="px-4 py-3 d-flex align-items-center gap-3" style="border-bottom:1px solid #1f2937">
                        <div class="fw-black flex-shrink-0" style="width:2rem;text-align:center;font-size:.85rem;color:#6b7280">
                            R{{ $round->round_number }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-white" style="font-size:.9rem">{{ $round->title }}</div>
                            <div style="font-size:.75rem;color:#6b7280">
                                {{ $round->track }} · {{ $round->scheduledAtUk()->format('d M Y, H:i T') }}
                            </div>
                        </div>
                        <div>
                            <span class="status-badge status-{{ $round->status }}">{{ ucfirst($round->status) }}</span>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>

            </div>

            {{-- Right: registration + drivers --}}
            <div class="col-12 col-lg-4">

                {{-- Registration card --}}
                @auth
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Registration</h2>
                    </div>
                    <div class="px-4 py-4">
                        @if($championship->status !== 'active')
                        <p style="color:#6b7280;font-size:.875rem">Registration is only available for active championships.</p>

                        @elseif($championship->isRegistered(auth()->user()))
                        <p class="text-white fw-bold mb-3" style="font-size:.875rem">You are registered for this championship.</p>
                        <form method="POST" action="{{ route('championships.unregister', $championship) }}" onsubmit="return confirm('Unregister from this championship?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm fw-bold text-uppercase text-danger w-100" style="background:#fee2e2;border:1px solid #fca5a5;font-size:.75rem">
                                Unregister
                            </button>
                        </form>

                        @elseif(!$championship->registration_open)
                        <p style="color:#6b7280;font-size:.875rem">Registration is currently closed.</p>

                        @elseif($championship->isFull())
                        <p style="color:#f59e0b;font-size:.875rem;font-weight:700">This championship is full.</p>

                        @else
                        <form method="POST" action="{{ route('championships.register', $championship) }}">
                            @csrf
                            @if($championship->is_multiclass && $championship->classes->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label text-white" style="font-size:.82rem">Select Class</label>
                                <select name="championship_class_id" class="form-select form-select-sm" required
                                        style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                                    <option value="">Choose your class...</option>
                                    @foreach($championship->classes as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}{{ $cls->car_class ? ' (' . $cls->car_class . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            @if($championship->registration_deadline)
                            <p style="color:#9ca3af;font-size:.75rem" class="mb-3">
                                Deadline: {{ $championship->registration_deadline->timezone('Europe/London')->format('d M Y, H:i T') }}
                            </p>
                            @endif

                            <button type="submit" class="btn fw-black text-uppercase text-white w-100"
                                    style="background:#db2777;font-size:.82rem">
                                Register Now
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @else
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-4 text-center">
                        <p style="color:#9ca3af;font-size:.875rem">
                            <a href="{{ route('login') }}" class="fw-bold" style="color:#db2777">Log in</a> to register for this championship.
                        </p>
                    </div>
                </div>
                @endauth

                {{-- Drivers card --}}
                <div style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">
                            Drivers
                            <span style="color:#6b7280;font-weight:400">({{ $championship->registrations->count() }}{{ $championship->max_drivers ? '/' . $championship->max_drivers : '' }})</span>
                        </h2>
                    </div>

                    @if($championship->registrations->isEmpty())
                    <div class="px-4 py-4 text-center" style="color:#6b7280;font-size:.875rem">No drivers registered yet.</div>
                    @else
                    <div class="px-4 py-2">
                        @foreach($championship->registrations->take(20) as $reg)
                        <div class="d-flex align-items-center gap-2 py-2" style="border-bottom:1px solid #1f2937">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-black text-white flex-shrink-0"
                                 style="width:28px;height:28px;font-size:.65rem;background:linear-gradient(135deg,#374151,#6b7280)">
                                {{ strtoupper(substr($reg->user?->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-white fw-bold" style="font-size:.82rem">{{ $reg->user?->name }}</span>
                                @if($championship->is_multiclass && $reg->championshipClass)
                                <span class="badge ms-1 fw-bold" style="font-size:.6rem;background:{{ $reg->championshipClass->color }}22;color:{{ $reg->championshipClass->color }}">
                                    {{ $reg->championshipClass->name }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        @if($championship->registrations->count() > 20)
                        <div class="py-2 text-center" style="color:#6b7280;font-size:.75rem">
                            + {{ $championship->registrations->count() - 20 }} more
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</main>
@endsection
