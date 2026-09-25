@extends('layouts.app')

@section('title', $championship->name . ' — ' . config('xcl.name'))

@php
    $accent = $championship->league?->primary_color ?? $championship->gameColor();
    $req    = $championship->settings->requirements;
    $pen    = $championship->settings->penalties;
    $discordRequiredHere = ($championship->league?->requires_discord_membership) || ($req->discord_membership_required ?? false);
@endphp

@section('content')
<main class="xcl-page pb-5">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    {{-- Hero --}}
    <div style="position:relative;overflow:hidden;min-height:260px;background:linear-gradient(135deg,{{ $accent }}22,#0a0a0f)">
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
                    @if($championship->league)
                    <a href="{{ route('championships.index', ['league' => $championship->league->slug]) }}"
                       class="d-inline-flex align-items-center gap-1 mb-2 text-decoration-none fw-bold text-uppercase"
                       style="color:{{ $accent }};font-size:.75rem;letter-spacing:.04em">
                        {{ $championship->league->name }}
                    </a>
                    @endif
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <span class="badge text-white fw-bold" style="background:{{ $championship->gameColor() }};font-size:.7rem;padding:4px 10px;border-radius:6px">
                            {{ $championship->gameLabel() }}
                        </span>
                        <span class="badge fw-bold" style="background:#ffffff18;color:#e5e7eb;font-size:.7rem;padding:4px 10px;border-radius:6px">
                            Season {{ $championship->season }}
                        </span>
                        @php
                            $sc = ['active'=>'#16a34a','finished'=>'#9ca3af','draft'=>'#f59e0b','published'=>'#2563eb','registration_open'=>'#16a34a','registration_closed'=>'#6b7280','running'=>'#7c3aed','completed'=>'#9ca3af','cancelled'=>'#dc2626'][$championship->status] ?? '#9ca3af';
                        @endphp
                        <span class="badge fw-bold" style="background:{{ $sc }}33;color:{{ $sc }};font-size:.7rem;padding:4px 10px;border-radius:6px">
                            {{ ucfirst(str_replace('_', ' ', $championship->status)) }}
                        </span>
                        @if($championship->is_multiclass)
                        <span class="badge fw-bold" style="background:#db277733;color:#db2777;font-size:.7rem;padding:4px 10px;border-radius:6px">
                            Multiclass
                        </span>
                        @endif
                    </div>
                    <h1 class="fw-black text-uppercase text-white mb-0" style="font-size:clamp(1.5rem,4vw,2.5rem);line-height:1.1">{{ $championship->name }}</h1>
                    @if($championship->tagline)
                    <p class="mb-0 mt-1" style="color:#9ca3af;font-size:1rem">{{ $championship->tagline }}</p>
                    @endif
                    @if($championship->slogan)
                    <p class="mb-0 mt-1" style="color:#6b7280;font-size:.8rem;font-style:italic">{{ $championship->slogan }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container-xl px-3 mt-4" data-tabs data-default-tab="about">

        {{-- Section nav — jumps between the tab panels below, same tabs.js component
             the pro driver profile's year-switcher already uses. --}}
        <div class="mb-4 d-flex gap-4" style="border-bottom:1px solid #1f2937;overflow-x:auto">
            @foreach([
                'about'     => 'About',
                'standings' => 'Standings',
                'rounds'    => 'Rounds',
                'rules'     => 'Rules',
                'drivers'   => 'Drivers',
            ] as $tabKey => $tabLabel)
            <button data-tab-btn="{{ $tabKey }}" data-tab-color="{{ $accent }}"
                    class="fw-black text-uppercase bg-transparent pb-2 flex-shrink-0"
                    style="font-size:.8rem;letter-spacing:.05em;white-space:nowrap;border:none;border-bottom:2px solid transparent;cursor:pointer">
                {{ $tabLabel }}
            </button>
            @endforeach
        </div>

        <div class="row g-4">

            {{-- Left: tab panels --}}
            <div class="col-12 col-lg-8">

                {{-- About --}}
                <div data-tab-panel="about" style="display:none">
                    @if($championship->description)
                    <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                        <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                            <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">About This Championship</h2>
                        </div>
                        <div class="px-4 py-3">
                            <div style="color:#9ca3af;font-size:.9rem;line-height:1.7">
                                {!! Illuminate\Support\Str::markdown($championship->description, ['renderer' => ['soft_break' => "<br />\n"]]) !!}
                            </div>
                        </div>
                    </div>
                    @else
                    <div style="background:#111827;border-radius:12px;overflow:hidden">
                        <div class="px-4 py-4 text-center" style="color:#6b7280;font-size:.875rem">Nothing added here yet.</div>
                    </div>
                    @endif
                </div>

                {{-- Standings --}}
                <div data-tab-panel="standings" style="display:none">
                @php
                    $standingsGroups = $championship->is_multiclass && collect($classStandings)->isNotEmpty()
                        ? collect($classStandings)->values()
                        : collect([['class' => null, 'standings' => $standings]]);
                @endphp

                @foreach($standingsGroups as $group)
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3 d-flex align-items-center gap-2" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">
                            {{ $group['class'] ? $group['class']->name : 'Overall' }} Standings
                        </h2>
                        @if($group['class'])
                        <span class="badge fw-bold" style="background:{{ $group['class']->color }}22;color:{{ $group['class']->color }};font-size:.65rem;padding:3px 8px;border-radius:5px">
                            {{ $group['class']->car_class ?? $group['class']->name }}
                        </span>
                        @endif
                    </div>

                    @if(empty($group['standings']))
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
                                @foreach($group['standings'] as $i => $entry)
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
                @endforeach

                {{-- Team Standings --}}
                @if(!empty($teamStandings))
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Team Standings</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:.875rem">
                            <thead style="background:#0f172a">
                                <tr>
                                    <th class="fw-bold text-uppercase ps-4" style="font-size:.68rem;color:#6b7280;letter-spacing:.06em">Pos</th>
                                    <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#6b7280;letter-spacing:.06em">Team</th>
                                    <th class="fw-bold text-uppercase text-center pe-4" style="font-size:.68rem;color:#6b7280;letter-spacing:.06em">PTS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teamStandings as $i => $entry)
                                @php $medalColors = ['#f59e0b','#9ca3af','#b45309']; @endphp
                                <tr style="border-bottom:1px solid #1f2937">
                                    <td class="ps-4 fw-black" style="color:{{ $medalColors[$i] ?? '#6b7280' }};font-size:.95rem">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($entry['team']->logoUrl())
                                            <img src="{{ $entry['team']->logoUrl() }}" alt="" style="width:26px;height:26px;object-fit:contain;border-radius:6px">
                                            @endif
                                            <span class="fw-bold text-white">{{ $entry['team']->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4 fw-black" style="color:#db2777;font-size:1rem">{{ $entry['total_points'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                </div>{{-- /standings panel --}}

                {{-- Rounds --}}
                <div data-tab-panel="rounds" style="display:none">
                <div style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Rounds</h2>
                    </div>

                    @if($rounds->isEmpty())
                    <div class="px-4 py-4 text-center" style="color:#6b7280;font-size:.875rem">No rounds scheduled yet.</div>
                    @else
                    @foreach($rounds as $round)
                    <a href="{{ route('events.show', $round) }}"
                       class="px-4 py-3 d-flex align-items-center gap-3 text-decoration-none"
                       style="border-bottom:1px solid #1f2937">
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
                    </a>
                    @endforeach
                    @endif
                </div>
                </div>{{-- /rounds panel --}}

                {{-- Rules — the league's own free-text Rules/Prizes notes. Entry Requirements
                     lives in the sidebar next to Registration instead (not duplicated here). --}}
                <div data-tab-panel="rules" style="display:none">
                @if($isLeagueOwned && ($req->notes || $req->prizes_text))
                <div class="row g-4">
                    @if($req->notes)
                    <div class="col-12 col-md-{{ $req->prizes_text ? '6' : '12' }}">
                        <div style="background:#111827;border-radius:12px;overflow:hidden;height:100%">
                            <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                                <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Rules</h2>
                            </div>
                            <div class="px-4 py-3" style="color:#c7ccd6;font-size:.85rem;white-space:pre-wrap">{{ $req->notes }}</div>
                        </div>
                    </div>
                    @endif
                    @if($req->prizes_text)
                    <div class="col-12 col-md-{{ $req->notes ? '6' : '12' }}">
                        <div style="background:#111827;border-radius:12px;overflow:hidden;height:100%">
                            <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                                <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Prizes</h2>
                            </div>
                            <div class="px-4 py-3" style="color:#c7ccd6;font-size:.85rem;white-space:pre-wrap">{{ $req->prizes_text }}</div>
                        </div>
                    </div>
                    @endif
                </div>
                @elseif(!$isLeagueOwned)
                <div style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-4 text-center" style="color:#6b7280;font-size:.875rem">No rules published for this championship.</div>
                </div>
                @endif
                </div>{{-- /rules panel --}}

                {{-- Drivers — full list, not capped, now that it has a full-width tab of its own. --}}
                <div data-tab-panel="drivers" style="display:none">
                <div style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">
                            Drivers
                            <span style="color:#6b7280;font-weight:400">({{ $championship->registrations->count() }}{{ $championship->max_drivers ? '/' . $championship->max_drivers : '' }})</span>
                            @if($championship->waitlistCount() > 0)
                            <span style="color:#f59e0b;font-weight:400">· {{ $championship->waitlistCount() }} waiting</span>
                            @endif
                        </h2>
                    </div>

                    @if($championship->registrations->isEmpty())
                    <div class="px-4 py-4 text-center" style="color:#6b7280;font-size:.875rem">No drivers registered yet.</div>
                    @else
                    <div class="px-4 py-2">
                        @foreach($championship->registrations as $reg)
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
                    </div>
                    @endif
                </div>
                </div>{{-- /drivers panel --}}

            </div>{{-- /col-lg-8 --}}

            {{-- Right: registration --}}
            <div class="col-12 col-lg-4">

                {{-- Registration card --}}
                @auth
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Registration</h2>
                    </div>
                    <div class="px-4 py-4">
                        @if(!in_array($championship->status, ['active', 'registration_open']))
                        <p style="color:#6b7280;font-size:.875rem">Registration is not open yet.</p>

                        @elseif($championship->isRegistered(auth()->user()))
                        @php
                            $myManageableTeam = auth()->user()->manageableRacingTeam();
                            $ownRegistration = $championship->registrations()
                                ->where(function ($q) use ($myManageableTeam) {
                                    $q->where('user_id', auth()->id());
                                    if ($myManageableTeam) {
                                        $q->orWhere('racing_team_id', $myManageableTeam->id);
                                    }
                                })
                                ->first();
                        @endphp
                        @if($championship->isRegistrationWaitlisted(auth()->user()))
                        <p class="fw-bold mb-3" style="color:#f59e0b;font-size:.875rem">You are on the waiting list for this championship.</p>
                        @else
                        <p class="text-white fw-bold mb-3" style="font-size:.875rem">
                            @if($ownRegistration?->racing_team_id)
                                Your team is registered for this championship.
                            @else
                                You are registered for this championship.
                            @endif
                        </p>
                        @endif
                        @if($ownRegistration)
                        <form method="POST" action="{{ route('championships.unregister', $championship) }}" onsubmit="return confirm('Unregister from this championship?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm fw-bold text-uppercase text-danger w-100" style="background:#fee2e2;border:1px solid #fca5a5;font-size:.75rem">
                                Unregister
                            </button>
                        </form>
                        @else
                        <p style="color:#6b7280;font-size:.78rem" class="mb-0">Your team's owner or a manager registered you — only they can unregister the team.</p>
                        @endif

                        @elseif(!$championship->registration_open || !$championship->registrationIsOpen())
                        <p style="color:#6b7280;font-size:.875rem">Registration is currently closed.</p>

                        @else
                        @php
                            $driverSwaps    = $championship->settings->format->driver_swaps_enabled ?? false;
                            $driverFull     = $championship->isFull() && !$championship->waitlistEnabled();
                            $spectatorOpen  = $championship->spectatorSlots() > 0 && !$championship->isSpectatorFull();
                            $ownedTeam      = $driverSwaps ? auth()->user()->manageableRacingTeam() : null;
                            $teamScope      = $championship->settings->format->team_registration_scope ?? 'per_round';
                        @endphp

                        @if($discordRequiredHere)
                        <div class="mb-3 p-2" style="background:#5865F21a;border:1px solid #5865F244;border-radius:8px">
                            <p class="mb-0" style="color:#c7d2fe;font-size:.78rem">
                                <strong>Discord membership required.</strong> You must be a member of
                                {{ $championship->league->name }}'s Discord server to register
                                @if($championship->league->discord_invite_url)
                                — <a href="{{ $championship->league->discord_invite_url }}" target="_blank" rel="noopener" style="color:#a5b4fc">join here</a>
                                @endif.
                            </p>
                        </div>
                        @endif

                        @if($driverFull && !$spectatorOpen)
                        <p style="color:#f59e0b;font-size:.875rem;font-weight:700">This championship is full.</p>
                        @endif

                        @if(!$driverFull)
                        <form method="POST" action="{{ route('championships.register', $championship) }}">
                            @csrf
                            @if($driverSwaps)
                            <div class="mb-3">
                                <label class="form-label text-white" style="font-size:.82rem">Register as</label>
                                @if($ownedTeam)
                                <select name="racing_team_id" id="racingTeamSelect" class="form-select form-select-sm"
                                        style="background:#1f2937;border-color:#374151;color:#e5e7eb"
                                        onchange="document.getElementById('teamEntryFields')?.classList.toggle('d-none', !this.value)">
                                    <option value="">Just me (no team)</option>
                                    <option value="{{ $ownedTeam->id }}" {{ old('racing_team_id') ? 'selected' : '' }}>My team — {{ $ownedTeam->name }}</option>
                                </select>
                                @else
                                <p style="color:#6b7280;font-size:.78rem" class="mb-0">This championship allows driver swaps, but you don't own a racing team — registering as an individual.</p>
                                @endif
                            </div>

                            @if($ownedTeam)
                            @php
                                $teamDrivers = collect([$ownedTeam->owner])->concat($ownedTeam->members)->filter()->unique('id');
                                $minDrivers  = (int) ($championship->settings->format->min_drivers_per_car ?? 0);
                                $maxDrivers  = (int) ($championship->settings->format->max_drivers_per_car ?? 0);
                                $selectedDriverIds = collect(old('driver_ids', [auth()->id()]))->map(fn ($id) => (int) $id);
                            @endphp
                            <div id="teamEntryFields" class="{{ old('racing_team_id') ? '' : 'd-none' }} mb-3 p-2" style="background:#1f293766;border:1px solid #374151;border-radius:8px">
                                <p style="color:#9ca3af;font-size:.72rem" class="mb-2">
                                    @if($teamScope === 'championship')
                                    This championship registers your team once — the drivers, car number, model and starting driver below carry over to every round automatically.
                                    @else
                                    Pick the drivers for your car. They're pre-selected when you sign your team up for each round, and you can still change them per round.
                                    @endif
                                </p>
                                <div class="mb-2">
                                    <label class="form-label text-white mb-1" style="font-size:.78rem">
                                        Drivers
                                        @if($minDrivers && $minDrivers === $maxDrivers)
                                        <span style="color:#9ca3af;font-weight:400">(pick {{ $minDrivers }})</span>
                                        @elseif($minDrivers || $maxDrivers)
                                        <span style="color:#9ca3af;font-weight:400">({{ $minDrivers ?: 1 }}–{{ $maxDrivers ?: $teamDrivers->count() }})</span>
                                        @endif
                                    </label>
                                    @foreach($teamDrivers as $driver)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="driver_ids[]" value="{{ $driver->id }}"
                                               id="champDriver{{ $driver->id }}" data-team-driver
                                               {{ $selectedDriverIds->contains($driver->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="champDriver{{ $driver->id }}" style="color:#e5e7eb;font-size:.82rem">
                                            {{ $driver->displayName() }}@if($driver->id === $ownedTeam->owner_id) <span style="color:#6b7280">(owner)</span>@endif
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                @if($teamScope === 'championship')
                                <div class="mb-2">
                                    <label class="form-label text-white" style="font-size:.78rem">Car Number</label>
                                    <input type="number" name="car_number" min="0" max="999" class="form-control form-control-sm"
                                           style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-white" style="font-size:.78rem">Car Model</label>
                                    <input type="text" name="car_model" class="form-control form-control-sm"
                                           style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label text-white" style="font-size:.78rem">Starting Driver</label>
                                    <select name="starting_driver_id" data-starting-driver class="form-select form-select-sm"
                                            style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                                        @foreach($teamDrivers as $driver)
                                        <option value="{{ $driver->id }}" {{ (int) old('starting_driver_id') === $driver->id ? 'selected' : '' }}>{{ $driver->displayName() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                            </div>
                            <script>
                            // Only a picked driver can start the race.
                            (function () {
                                var boxes   = document.querySelectorAll('[data-team-driver]');
                                var starter = document.querySelector('[data-starting-driver]');
                                if (!starter) return;
                                function sync() {
                                    var picked = Array.from(boxes).filter(function (b) { return b.checked; }).map(function (b) { return b.value; });
                                    Array.from(starter.options).forEach(function (o) { o.hidden = o.disabled = picked.indexOf(o.value) === -1; });
                                    if (starter.selectedOptions[0]?.disabled) {
                                        var first = Array.from(starter.options).find(function (o) { return !o.disabled; });
                                        starter.value = first ? first.value : '';
                                    }
                                }
                                boxes.forEach(function (b) { b.addEventListener('change', sync); });
                                sync();
                            })();
                            </script>
                            @endif
                            @endif

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

                            @if($championship->isFull())
                            <p class="fw-bold mb-3" style="color:#f59e0b;font-size:.8rem">Full — you'll join the waiting list.</p>
                            @endif

                            <button type="submit" class="btn fw-black text-uppercase text-white w-100"
                                    style="background:{{ $accent }};font-size:.82rem">
                                {{ $championship->isFull() ? 'Join Waiting List' : 'Register Now' }}
                            </button>
                        </form>
                        @endif

                        @if($spectatorOpen)
                        <form method="POST" action="{{ route('championships.register', $championship) }}" class="{{ $driverFull ? '' : 'mt-2' }}">
                            @csrf
                            <input type="hidden" name="is_spectator" value="1">
                            <button type="submit" class="btn btn-sm fw-bold text-uppercase w-100"
                                    style="background:transparent;border:1px solid #374151;color:#9ca3af;font-size:.75rem">
                                Register as Spectator
                            </button>
                        </form>
                        @endif
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

                {{-- Entry Requirements — back as its own box under Registration (its original
                     spot), not tucked away inside the Rules tab. --}}
                @if($isLeagueOwned)
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Entry Requirements</h2>
                    </div>
                    <div class="px-4 py-3" style="font-size:.82rem">
                        <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid #1f2937">
                            <span style="color:#6b7280">Minimum XCL Rating</span>
                            <span class="fw-bold text-white">{{ $req->min_xcl_rating_tier ? ucfirst($req->min_xcl_rating_tier) : 'None' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid #1f2937">
                            <span style="color:#6b7280">Minimum Safety Rating</span>
                            <span class="fw-bold text-white">{{ $req->min_safety_rating ?? 'None' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid #1f2937">
                            <span style="color:#6b7280">Discord Membership</span>
                            <span class="fw-bold" style="color:{{ $discordRequiredHere ? '#818cf8' : '#fff' }}">{{ $discordRequiredHere ? 'Required' : 'Not required' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span style="color:#6b7280">Entry Approval</span>
                            <span class="fw-bold text-white">{{ ($req->manual_approval_required ?? false) ? 'Manually reviewed' : 'Automatic' }}</span>
                        </div>
                    </div>
                    @if($pen->stewarding_enabled ?? false)
                    <div class="px-4 py-3" style="border-top:1px solid #1f2937;font-size:.82rem">
                        <div class="fw-bold text-uppercase mb-2" style="color:#6b7280;font-size:.68rem;letter-spacing:.06em">Stewarding &amp; Penalties</div>
                        <div class="d-flex justify-content-between py-1">
                            <span style="color:#6b7280">Penalties affect</span>
                            <span class="fw-bold text-white">{{ ucfirst($pen->affects ?? 'none') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span style="color:#6b7280">Post-race time penalties</span>
                            <span class="fw-bold text-white">{{ ($pen->post_race_time_penalties_enabled ?? false) ? 'Allowed' : 'Not used' }}</span>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Drivers summary — count + strength of field at a glance (same SoF
                     calculation as an event page's Drivers card), the full entry list stays
                     under the Drivers tab so this stays a small block, not a repeat of it. --}}
                @php
                    $eloCol = \App\Models\User::eloColumn($championship->game);
                    $sofRatings = $eloCol
                        ? $championship->registrations->pluck('user')->filter()
                            ->map(fn($u) => (int) ($u->{$eloCol} ?? 0))
                            ->filter(fn($r) => $r > 0)
                        : collect();
                    $sof = $sofRatings->isNotEmpty() ? $sofRatings->avg() : null;
                @endphp
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:.85rem;letter-spacing:.08em">Drivers</h2>
                    </div>
                    <div class="px-4 py-3" style="font-size:.82rem">
                        <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid #1f2937">
                            <span style="color:#6b7280">Registered</span>
                            <span class="fw-bold text-white">{{ $championship->registrations->count() }}{{ $championship->max_drivers ? '/' . $championship->max_drivers : '' }}</span>
                        </div>
                        @if($championship->waitlistCount() > 0)
                        <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid #1f2937">
                            <span style="color:#6b7280">Waiting list</span>
                            <span class="fw-bold" style="color:#f59e0b">{{ $championship->waitlistCount() }}</span>
                        </div>
                        @endif
                        @if($sof !== null)
                        <div class="d-flex justify-content-between py-1">
                            <span style="color:#6b7280">Strength of Field</span>
                            <span class="fw-bold" style="color:#c084fc">{{ number_format($sof, 0) }}</span>
                        </div>
                        @endif
                    </div>
                    <button type="button" data-activate-tab="drivers"
                            class="w-100 text-center fw-bold text-uppercase bg-transparent"
                            style="padding:.6rem 1rem;color:{{ $accent }};font-size:.72rem;letter-spacing:.05em;border:none;border-top:1px solid #1f2937;cursor:pointer">
                        View Full Entry List →
                    </button>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection
