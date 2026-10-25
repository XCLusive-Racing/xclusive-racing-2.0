@extends('layouts.app')

@section('title', $championship->name . ' — ' . config('xcl.name'))

@php
    $accent = $championship->league?->primary_color ?? $championship->gameColor();
    // The league's second colour highlights the tab nav and section titles on the dark
    // panels — but only when it's light enough to read there; else the primary colour,
    // else plain light grey.
    $readableOnDark = function (?string $hex): bool {
        if (! $hex || ! preg_match('/^#?([0-9a-f]{6})$/i', $hex, $m)) {
            return false;
        }
        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split($m[1], 2));

        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) >= 0.25;
    };
    $highlight = collect([$championship->league?->accent_color, $accent])->first($readableOnDark) ?? '#e5e7eb';
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
                            $sc = $championship->statusColor();
                        @endphp
                        <span class="badge fw-bold" style="background:{{ $sc }}33;color:{{ $sc }};font-size:.7rem;padding:4px 10px;border-radius:6px">
                            {{ $championship->statusLabel() }}
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

    {{-- position:relative: without it the page's absolutely-positioned topo
         background is painted on top of these (unpositioned) panels, making them
         look see-through. --}}
    <div class="container-xl px-3 mt-4 xcl-champ" data-tabs data-default-tab="about"
         style="position:relative;--ch-highlight:{{ $highlight }};--ch-primary:{{ $accent }}">

        {{-- Section nav — jumps between the tab panels below, same tabs.js component
             the pro driver profile's year-switcher already uses. A solid dark bar in
             the league's colours: primary edge, active tab in the highlight colour. --}}
        <div class="mb-4 d-flex gap-4 xcl-champ__nav">
            @php
                // The Teams tab lists who drives for which team (no points); a team championship's
                // standings (team cars), or a solo championship's, sit under Standings.
                $teamTables = array_filter(!empty($teamChampionship)
                    ? ['Team Championship' => $teamChampionship, 'Car Standings' => $teamStandings]
                    : ['Team Standings' => $teamStandings ?: $soloTeamStandings]);
                $hasTeamsTab = !empty($teamRoster);
            @endphp
            @foreach(array_filter([
                'about'     => 'About',
                'standings' => 'Standings',
                'teams'     => $hasTeamsTab ? 'Teams' : null,
                'rounds'    => 'Rounds',
                'rules'     => 'Rules',
                'drivers'   => 'Drivers',
            ]) as $tabKey => $tabLabel)
            <button data-tab-btn="{{ $tabKey }}" data-tab-color="{{ $highlight }}"
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
                            <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">About This Championship</h2>
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
                    // Driver classes (Pro / Am …) come after the overall table, not instead of it.
                    $standingsGroups = $standingsGroups->concat($driverClassStandings);

                    // One sub-tab per table — drivers (overall/car class/driver class), then teams —
                    // so only one table shows at a time. Same tabs.js component, nested [data-tabs].
                    $standingsTabs = $standingsGroups->map(fn ($group) => isset($group['driver_class'])
                        ? $group['driver_class']->name
                        : (($group['class'] ?? null) ? $group['class']->name : 'Overall'))
                        ->concat(array_map(fn ($title) => $title === 'Car Standings' ? 'Cars' : 'Teams', array_keys($teamTables)))
                        ->values();
                    // tabs.js swaps the whole style attribute, so both carry the full pill style.
                    $pillOn = "background:{$highlight};color:#fff;border:1px solid {$highlight};font-size:.72rem;letter-spacing:.05em";
                    $pillOff = 'background:transparent;color:#9ca3af;border:1px solid #374151;font-size:.72rem;letter-spacing:.05em';
                @endphp

                <div data-tabs data-default-tab="st-0">
                <div class="d-flex flex-wrap gap-2 mb-3" @if($standingsTabs->count() < 2) style="display:none !important" @endif>
                    @foreach($standingsTabs as $i => $tabLabel)
                    <button type="button" data-tab-btn="st-{{ $i }}"
                            data-tab-active-style="{{ $pillOn }}" data-tab-inactive-style="{{ $pillOff }}"
                            class="btn btn-sm fw-bold text-uppercase rounded-pill px-3"
                            style="{{ $pillOff }}">
                        {{ $tabLabel }}
                    </button>
                    @endforeach
                </div>

                @foreach($standingsGroups as $group)
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden" data-tab-panel="st-{{ $loop->index }}">
                    <div class="px-4 py-3 d-flex align-items-center gap-2" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">
                            @if(isset($group['driver_class']))
                            <span style="display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:4px;background:{{ $group['driver_class']->color() }}"></span>{{ $group['driver_class']->name }} Standings
                            @else
                            {{ ($group['class'] ?? null) ? $group['class']->name : 'Overall' }} Standings
                            @endif
                        </h2>
                        @if($group['class'] ?? null)
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
                                                {{ strtoupper(substr($entry['user']?->displayName() ?? '?', 0, 1)) }}
                                            </div>
                                            <span class="fw-bold text-white">{{ $entry['user']?->displayName() ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    @foreach($rounds->where('status','finished') as $r)
                                    @php
                                        $rd = collect($entry['rounds'])->firstWhere('race_id', $r->id);
                                        $dropped = in_array($r->id, $entry['dropped']);
                                    @endphp
                                    <td class="text-center" style="color:{{ $dropped ? '#4b5563' : '#9ca3af' }};{{ $dropped ? 'text-decoration:line-through' : '' }}"
                                        @if($rd && count($rd['races'] ?? []) > 1) title="{{ collect($rd['races'])->map(fn ($race, $n) => 'Race ' . $n . ': ' . $race['points'])->implode(' · ') }}" @endif>
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

                {{-- Team Standings — with a team championship (settings.scoring.team_scoring_cars)
                     the per-car table sits below it as "Car Standings"; a solo championship
                     scores each team from its drivers (computeSoloTeamStandings()). --}}
                @foreach($teamTables as $tableTitle => $tableRows)
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden" data-tab-panel="st-{{ $standingsGroups->count() + $loop->index }}">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">{{ $tableTitle }}</h2>
                        @if($tableTitle === 'Team Championship')
                        <p class="mb-0 mt-1" style="color:#6b7280;font-size:.72rem">Each round, a team's best {{ $championship->settings->scoring->team_scoring_cars }} {{ \Illuminate\Support\Str::plural('car', $championship->settings->scoring->team_scoring_cars) }} score for the team.</p>
                        @elseif(!empty($soloTeamStandings))
                        <p class="mb-0 mt-1" style="color:#6b7280;font-size:.72rem">
                            @if($championship->settings->scoring->team_scoring_cars)
                            Each round, a team's best {{ $championship->settings->scoring->team_scoring_cars }} {{ \Illuminate\Support\Str::plural('driver', $championship->settings->scoring->team_scoring_cars) }} score for the team.
                            @else
                            Every driver's points count for their team.
                            @endif
                        </p>
                        @endif
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
                                @foreach($tableRows as $i => $entry)
                                @php $medalColors = ['#f59e0b','#9ca3af','#b45309']; @endphp
                                <tr style="border-bottom:1px solid #1f2937">
                                    <td class="ps-4 fw-black" style="color:{{ $medalColors[$i] ?? '#6b7280' }};font-size:.95rem">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($entry['team']->logoUrl())
                                            <img src="{{ $entry['team']->logoUrl() }}" alt="" style="width:26px;height:26px;object-fit:contain;border-radius:6px">
                                            @endif
                                            <span class="fw-bold text-white">{{ $entry['team']->name }}</span>
                                            @if($entry['car_label'] ?? null)
                                            <span style="color:#9ca3af;font-size:.8rem">{{ $entry['car_label'] }}</span>
                                            @endif
                                        </div>
                                        @if(isset($entry['drivers']))
                                        <div style="color:#6b7280;font-size:.75rem">{{ $entry['drivers']->map->displayName()->implode(', ') }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center pe-4 fw-black" style="color:#db2777;font-size:1rem">{{ $entry['total_points'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
                </div>{{-- /standings sub-tabs --}}

                @if($ballastChart)
                @include('championships._ballast-chart')
                @endif

                </div>{{-- /standings panel --}}

                {{-- Teams — every team with an entry and who drives for it (Championship::teamRoster()). --}}
                @if($hasTeamsTab)
                <div data-tab-panel="teams" style="display:none">
                <div class="xcl-champ__teams-scroll">
                @foreach($teamRoster as $teamRow)
                @php $team = $teamRow['team']; @endphp
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden" data-team-card>
                    <div class="px-4 py-3 d-flex align-items-center gap-3" style="border-bottom:1px solid #1f2937">
                        @if($team->logoUrl())
                        <img src="{{ $team->logoUrl() }}" alt="" style="width:40px;height:40px;object-fit:contain;border-radius:8px">
                        @else
                        <div class="rounded-2 d-flex align-items-center justify-content-center fw-black text-white flex-shrink-0"
                             style="width:40px;height:40px;font-size:.75rem;background:linear-gradient(135deg,#374151,#6b7280)">
                            {{ strtoupper(mb_substr($team->tag ?: $team->name, 0, 3)) }}
                        </div>
                        @endif
                        <div class="flex-grow-1">
                            <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.9rem;letter-spacing:.06em">{{ $team->name }}</h2>
                            <div style="color:#6b7280;font-size:.75rem">
                                @if($team->tag){{ $team->tag }} · @endif{{ $teamRow['driver_count'] }} {{ \Illuminate\Support\Str::plural('driver', $teamRow['driver_count']) }}
                                @php $carCount = $teamRow['entries']->where('is_car', true)->count(); @endphp
                                @if($carCount)
                                · {{ $carCount }} {{ \Illuminate\Support\Str::plural('car', $carCount) }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-2">
                        @foreach($teamRow['entries'] as $row)
                        <div class="d-flex align-items-center gap-2 py-2" style="{{ $loop->last ? '' : 'border-bottom:1px solid #1f2937' }}">
                            @if($row['car_number'] !== null)
                            <span class="fw-black flex-shrink-0" style="width:2.6rem;color:#9ca3af;font-size:.8rem">#{{ $row['car_number'] }}</span>
                            @endif
                            <div class="flex-grow-1">
                                <span class="text-white fw-bold" style="font-size:.82rem">{{ $row['drivers']->map->displayName()->implode(', ') }}</span>
                                @if($row['reserve'])
                                <span style="color:#6b7280;font-size:.75rem"> · Reserve: {{ $row['reserve']->displayName() }}</span>
                                @endif
                            </div>
                            @if($championship->is_multiclass && $row['class'])
                            <span class="badge fw-bold" style="font-size:.6rem;background:{{ $row['class']->color }}22;color:{{ $row['class']->color }}">{{ $row['class']->name }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
                </div>
                </div>{{-- /teams panel --}}
                @endif

                {{-- Rounds --}}
                <div data-tab-panel="rounds" style="display:none">
                <div style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">Rounds</h2>
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
                            <div class="fw-bold text-white" style="font-size:.9rem">
                                {{ $round->title }}
                                @if($round->round_type)
                                <span class="badge ms-1 fw-bold text-uppercase" style="font-size:.6rem;background:#db277722;color:#f472b6;letter-spacing:.04em">{{ $round->round_type }}</span>
                                @endif
                            </div>
                            <div style="font-size:.75rem;color:#6b7280">
                                {{ $round->track }} · <x-local-time :at="$round->scheduled_at" format="date-time-comma" />
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
                @if($req->notes || $req->prizes_text)
                <div class="row g-4">
                    @if($req->notes)
                    <div class="col-12 col-md-{{ $req->prizes_text ? '6' : '12' }}">
                        <div style="background:#111827;border-radius:12px;overflow:hidden;height:100%">
                            <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                                <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">Rules</h2>
                            </div>
                            <div class="px-4 py-3" style="color:#c7ccd6;font-size:.85rem;white-space:pre-wrap">{{ $req->notes }}</div>
                        </div>
                    </div>
                    @endif
                    @if($req->prizes_text)
                    <div class="col-12 col-md-{{ $req->notes ? '6' : '12' }}">
                        <div style="background:#111827;border-radius:12px;overflow:hidden;height:100%">
                            <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                                <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">Prizes</h2>
                            </div>
                            <div class="px-4 py-3" style="color:#c7ccd6;font-size:.85rem;white-space:pre-wrap">{{ $req->prizes_text }}</div>
                        </div>
                    </div>
                    @endif
                </div>
                @else
                <div style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-4 text-center" style="color:#6b7280;font-size:.875rem">No rules published for this championship.</div>
                </div>
                @endif
                </div>{{-- /rules panel --}}

                {{-- Drivers — full list, not capped, now that it has a full-width tab of its own. --}}
                @php
                    // Spectators sit outside the entry cap (settings.format.spectator_slots), so
                    // they're counted on their own — the driver count matches every round's list.
                    $driverEntryCount = $championship->registrations->where('is_spectator', false)->count();
                    $spectatorEntryCount = $championship->registrations->where('is_spectator', true)->count();
                @endphp
                <div data-tab-panel="drivers" style="display:none">
                <div style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">
                            Drivers
                            <span style="color:#6b7280;font-weight:400">({{ $driverEntryCount }}{{ $championship->max_drivers ? '/' . $championship->max_drivers : '' }})</span>
                            @if($spectatorEntryCount > 0)
                            <span style="color:#6b7280;font-weight:400">· {{ $spectatorEntryCount }} {{ \Illuminate\Support\Str::plural('spectator', $spectatorEntryCount) }}</span>
                            @endif
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
                        @php
                            // A team car: its team and number, with the line-up that drives it
                            // (the registration's user is only the manager who entered it).
                            $entryName = $reg->racingTeam
                                ? $reg->racingTeam->name.($reg->car_number !== null ? ' #'.$reg->car_number : '')
                                : $reg->user?->displayName();
                            $lineUp = $reg->racingTeam
                                ? collect($reg->driverIds())->map(fn ($id) => $carDrivers[$id] ?? null)->filter()
                                : collect();
                            $reserve = $reg->reserve_driver_id ? ($carDrivers[$reg->reserve_driver_id] ?? null) : null;
                        @endphp
                        <div class="d-flex align-items-center gap-2 py-2" style="border-bottom:1px solid #1f2937">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-black text-white flex-shrink-0"
                                 style="width:28px;height:28px;font-size:.65rem;background:linear-gradient(135deg,#374151,#6b7280)">
                                {{ strtoupper(substr($entryName ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-white fw-bold" style="font-size:.82rem">{{ $entryName }}</span>
                                @if($reg->is_spectator)
                                <span class="badge ms-1 fw-bold" style="font-size:.6rem;background:#0891b222;color:#22d3ee">Spectator</span>
                                @endif
                                @if($championship->is_multiclass && $reg->championshipClass)
                                <span class="badge ms-1 fw-bold" style="font-size:.6rem;background:{{ $reg->championshipClass->color }}22;color:{{ $reg->championshipClass->color }}">
                                    {{ $reg->championshipClass->name }}
                                </span>
                                @endif
                                @if($championship->usesDriverClasses() && $reg->driverClass)
                                <span class="badge ms-1 fw-bold" style="font-size:.6rem;background:{{ $reg->driverClass->color() }}22;color:{{ $reg->driverClass->color() }}">
                                    {{ $reg->driverClass->name }}
                                </span>
                                @endif
                                @if(!$reg->racingTeam && !$reg->is_spectator && ($soloTeam = $reg->soloTeam()))
                                <div style="color:#9ca3af;font-size:.75rem"><i class="fa-solid fa-users me-1"></i>{{ $soloTeam->name }}</div>
                                @endif
                                @if($reg->racingTeam)
                                <div style="color:#d1d5db;font-size:.75rem">
                                    {{ $lineUp->map->displayName()->implode(', ') }}@if($reserve)<span style="color:#6b7280"> · Reserve: {{ $reserve->displayName() }}</span>@endif
                                </div>
                                @if($reg->car_model)
                                <div style="color:#6b7280;font-size:.72rem">{{ $reg->car_model }}</div>
                                @endif
                                @elseif($reg->car_model)
                                <div style="color:#6b7280;font-size:.72rem">{{ $reg->car_number !== null ? '#'.$reg->car_number.' · ' : '' }}{{ $reg->car_model }}</div>
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
                        <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">Registration</h2>
                    </div>
                    <div class="px-4 py-4">
                        @php
                            // A team manager's cars so far — while there's room for another
                            // (settings.format.max_cars_per_team) the sign-up form stays open.
                            $myOwnedTeam   = ($championship->settings->format->driver_swaps_enabled ?? false) ? auth()->user()->manageableRacingTeam() : null;
                            $myTeamCars    = $myOwnedTeam ? $championship->teamCarRegistrations($myOwnedTeam) : collect();
                            $canAddTeamCar = $myTeamCars->isNotEmpty() && $myTeamCars->count() < $championship->maxCarsPerTeam()
                                && $championship->acceptsRegistrations();
                        @endphp
                        @if($championship->status !== 'registration_open')
                        <p style="color:#6b7280;font-size:.875rem">{{ match ($championship->status) { 'completed' => 'This championship has finished.', 'registration_closed', 'running' => 'Registration is closed.', default => 'Registration is not open yet.' } }}</p>

                        @elseif($championship->isRegistered(auth()->user()) && !$canAddTeamCar)
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
                        @if($ownRegistration?->isPending())
                        <p class="fw-bold mb-3" style="color:#f59e0b;font-size:.875rem">Your entry is waiting for approval by the league.</p>
                        @elseif($championship->isRegistrationWaitlisted(auth()->user()))
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
                        {{-- A team's cars, each with Edit (reserve driver) and Withdraw. --}}
                        @if($myTeamCars->isNotEmpty())
                        @include('championships._team-cars', ['ownedTeam' => $myOwnedTeam])
                        @elseif($ownRegistration)
                        {{-- In several teams: switch the one you score for while sign-ups are open. --}}
                        @if(!$ownRegistration->racing_team_id && !$ownRegistration->is_spectator
                            && $championship->acceptsRegistrations() && $championship->offersSoloTeamPick(auth()->user()))
                        <form method="POST" action="{{ route('championships.solo-team', $championship) }}" class="mb-3">
                            @csrf @method('PUT')
                            <label class="form-label text-white" style="font-size:.82rem">Racing for</label>
                            <div class="d-flex gap-2">
                                <select name="solo_team_id" class="form-select form-select-sm" required
                                        style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                                    @foreach(auth()->user()->allRacingTeams() as $myTeam)
                                    <option value="{{ $myTeam->id }}" @selected($ownRegistration->soloTeam()?->id === $myTeam->id)>[{{ $myTeam->tag }}] {{ $myTeam->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm fw-bold text-uppercase text-white" style="background:{{ $accent }};font-size:.72rem">Save</button>
                            </div>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('championships.unregister', $championship) }}" onsubmit="return confirm('Unregister from this championship?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm fw-bold text-uppercase text-danger w-100" style="background:#fee2e2;border:1px solid #fca5a5;font-size:.75rem">
                                Unregister
                            </button>
                        </form>
                        @else
                        <p style="color:#6b7280;font-size:.78rem" class="mb-0">Your team's owner or a manager registered you — only they can unregister the team.</p>
                        @endif

                        @elseif($championship->visibility === 'unlisted')
                        <p style="color:#6b7280;font-size:.875rem">Registration opens once this championship is public.</p>

                        @elseif(! $championship->acceptsRegistrations())
                        <p style="color:#6b7280;font-size:.875rem">Registration is currently closed.</p>

                        @else
                        @include('championships._register-form')
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
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">Entry Requirements</h2>
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

                {{-- Drivers summary — count + strength of field at a glance (same SoF
                     calculation as an event page's Drivers card), the full entry list stays
                     under the Drivers tab so this stays a small block, not a repeat of it. --}}
                @php
                    $eloCol = \App\Models\User::eloColumn($championship->game);
                    $sofRatings = $eloCol
                        // Everyone who races: a team car's line-up, not the manager who entered it.
                        ? $championship->registrations
                            ->flatMap(fn ($reg) => $reg->racingTeam
                                ? collect($reg->driverIds())->map(fn ($id) => $carDrivers[$id] ?? null)
                                : [$reg->user])
                            ->filter()
                            ->map(fn($u) => (int) ($u->{$eloCol} ?? 0))
                            ->filter(fn($r) => $r > 0)
                        : collect();
                    $sof = $sofRatings->isNotEmpty() ? $sofRatings->avg() : null;
                @endphp
                <div class="mb-4" style="background:#111827;border-radius:12px;overflow:hidden">
                    <div class="px-4 py-3" style="border-bottom:1px solid #1f2937">
                        <h2 class="fw-black text-uppercase mb-0 xcl-champ__heading" style="font-size:.85rem;letter-spacing:.08em">Drivers</h2>
                    </div>
                    <div class="px-4 py-3" style="font-size:.82rem">
                        <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid #1f2937">
                            <span style="color:#6b7280">Registered</span>
                            <span class="fw-bold text-white">{{ $driverEntryCount }}{{ $championship->max_drivers ? '/' . $championship->max_drivers : '' }}{{ $spectatorEntryCount ? ' · ' . $spectatorEntryCount . ' ' . \Illuminate\Support\Str::plural('spectator', $spectatorEntryCount) : '' }}</span>
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
