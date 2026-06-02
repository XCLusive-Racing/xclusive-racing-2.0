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

{{-- FTP Server Import card --}}
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Import via FTP Server</div>
            <div class="text-secondary mt-1" style="font-size:.78rem">Select a GPortal server to browse and import result files.</div>
        </div>
        <a href="{{ route('admin.servers.index') }}"
           class="btn btn-sm fw-bold text-uppercase"
           style="background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff;font-size:.72rem;padding:4px 12px;border-radius:6px">
            Manage Servers
        </a>
    </div>

    <div class="p-4">

        @if($ftpServers->isEmpty())
        <div class="text-center py-3">
            <div class="text-secondary" style="font-size:.82rem">No active FTP servers configured.</div>
            <a href="{{ route('admin.servers.create') }}" class="btn btn-sm fw-bold text-uppercase mt-2"
               style="background:#7c3aed;color:white;font-size:.72rem">+ Add Server</a>
        </div>
        @else

        {{-- Server cards grid --}}
        <div class="row g-3">
            @foreach($ftpServers as $server)
            @php $isSelected = $selectedServer?->id === $server->id; @endphp
            <div class="col-12 col-md-6 col-lg-4">
                <a href="{{ request()->fullUrlWithQuery(['server' => $server->id]) }}"
                   class="text-decoration-none d-block h-100">
                    <div style="
                        background: {{ $isSelected ? '#faf5ff' : 'white' }};
                        border: {{ $isSelected ? '2px solid #7c3aed' : '1px solid #e5e7eb' }};
                        border-radius: 12px;
                        padding: 1rem 1.25rem;
                        cursor: pointer;
                        transition: border-color .15s, background .15s;
                        height: 100%;
                        min-height: 90px;
                    ">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="fw-black text-dark" style="font-size:.9rem">{{ $server->name }}</div>
                            @if($isSelected && !$ftpError)
                                <span title="Connected" style="width:8px;height:8px;background:#22c55e;border-radius:50%;display:inline-block;margin-top:5px;flex-shrink:0"></span>
                            @elseif($isSelected && $ftpError)
                                <span title="Error" style="width:8px;height:8px;background:#ef4444;border-radius:50%;display:inline-block;margin-top:5px;flex-shrink:0"></span>
                            @else
                                <span style="width:8px;height:8px;background:#d1d5db;border-radius:50%;display:inline-block;margin-top:5px;flex-shrink:0"></span>
                            @endif
                        </div>
                        <div class="text-secondary mt-1" style="font-size:.72rem;font-family:monospace">{{ $server->host }}</div>
                        <div class="mt-2">
                            @if($isSelected && !$ftpError)
                                <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">
                                    {{ count($ftpFiles) }} {{ Str::plural('file', count($ftpFiles)) }} found
                                </span>
                            @elseif($isSelected && $ftpError)
                                <span style="font-size:.7rem;font-weight:700;color:#dc2626">Offline</span>
                            @else
                                <span style="font-size:.7rem;color:#9ca3af">Click to load files</span>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>

        {{-- Connection error --}}
        @if($selectedServer && $ftpError)
        <div class="mt-3 p-3 rounded-3" style="background:#fef2f2;border:1px solid #fecaca">
            <div class="fw-bold" style="font-size:.82rem;color:#dc2626">{{ $ftpError }}</div>
            <div class="mt-1" style="font-size:.75rem;color:#6b7280">
                Check the credentials in
                <a href="{{ route('admin.servers.edit', $selectedServer) }}" style="color:#7c3aed">server settings</a>.
            </div>
        </div>
        @endif

        {{-- File list --}}
        @if($selectedServer && !$ftpError)
        <div class="mt-4" style="border-top:1px solid #f3f4f6;padding-top:1.25rem">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <span class="fw-black text-uppercase fst-italic text-dark" style="font-size:.82rem">
                        Files on {{ $selectedServer->name }}
                    </span>
                    <span class="text-secondary ms-2" style="font-size:.72rem;font-family:monospace">{{ $selectedServer->path }}</span>
                </div>
                <span class="text-secondary" style="font-size:.72rem">Newest first</span>
            </div>

            @if(empty($ftpFiles))
            <div class="p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb">
                <div class="fw-bold text-dark" style="font-size:.82rem">No JSON files found in <code>{{ $selectedServer->path }}</code></div>
                @if(!empty($ftpAllFiles))
                    <div class="text-secondary mt-1 mb-2" style="font-size:.75rem">
                        {{ count($ftpAllFiles) }} {{ Str::plural('file', count($ftpAllFiles)) }} found in this directory (not JSON):
                    </div>
                    <div style="font-family:monospace;font-size:.72rem;color:#6b7280">
                        {{ implode(', ', array_slice($ftpAllFiles, 0, 20)) }}
                        @if(count($ftpAllFiles) > 20) <span class="text-secondary">… and {{ count($ftpAllFiles) - 20 }} more</span> @endif
                    </div>
                @else
                    <div class="text-secondary mt-1" style="font-size:.75rem">Directory appears empty. Check the path in <a href="{{ route('admin.servers.edit', $selectedServer) }}" style="color:#7c3aed">server settings</a>.</div>
                @endif
            </div>
            @else
            <div class="table-responsive" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
                <table class="table align-middle mb-0" style="font-size:.82rem">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <tr>
                            <th class="fw-bold text-uppercase ps-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">File</th>
                            <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:95px">Session</th>
                            <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:140px">Date</th>
                            <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:80px">Size</th>
                            <th class="fw-bold text-uppercase text-end pe-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:130px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ftpFiles as $file)
                        @php
                            $parsed     = \App\Services\FtpService::parseFilename($file['name']);
                            $isImported = in_array($file['name'], $importedFiles);
                            $sizeKb     = $file['size'] !== null ? round($file['size'] / 1024, 1) . ' KB' : '—';
                        @endphp
                        <tr style="{{ $isImported ? 'opacity:.5' : '' }}">
                            <td class="ps-3">
                                <div class="fw-bold text-dark" style="font-size:.78rem;font-family:monospace">{{ $file['name'] }}</div>
                            </td>
                            <td>
                                @if($parsed['session'] === 'Race')
                                    <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">Race</span>
                                @elseif($parsed['session'] === 'Qualifying')
                                    <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">Quali</span>
                                @else
                                    <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">?</span>
                                @endif
                            </td>
                            <td style="font-size:.75rem;color:#6b7280">
                                {{ $parsed['date'] !== '—' ? $parsed['date'] : ($file['modified'] ?? '—') }}
                            </td>
                            <td style="font-size:.75rem;color:#6b7280;font-family:monospace">{{ $sizeKb }}</td>
                            <td class="text-end pe-3">
                                @if($isImported)
                                    <span class="badge" style="background:#f0fdf4;color:#16a34a;font-size:.68rem;padding:4px 10px;border-radius:5px;font-weight:700">
                                        ✓ Imported
                                    </span>
                                @else
                                    <form action="{{ route('admin.races.results.ftp', $race) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                        <input type="hidden" name="filename" value="{{ $file['name'] }}">
                                        <button type="submit"
                                                class="btn btn-sm fw-black text-uppercase text-white"
                                                style="background:#7c3aed;font-size:.68rem;padding:4px 14px;border-radius:6px">
                                            Import
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

        @endif {{-- end ftpServers not empty --}}
    </div>
</div>

{{-- Manual Upload card --}}
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Manual Upload</div>
            <div class="text-secondary mt-1" style="font-size:.78rem">Manually upload one or more JSON result files.</div>
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
            <div class="text-secondary mt-1" style="font-size:.82rem">Import a result file via FTP or manual upload.</div>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.82rem">
                <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <tr>
                        <th class="fw-bold text-uppercase ps-4" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:50px">Pos</th>
                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:55px">No</th>
                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Vehicle</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:60px">Laps</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:115px">Time/Retired</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:105px">Best Lap</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:90px">Consistency</th>
                        <th class="fw-bold text-uppercase text-center pe-4" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:55px">Led</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($raceResults as $result)
                    <tr>
                        <td class="ps-4"><x-race-position :position="$result->position" /></td>
                        <td>
                            @if($result->car_number !== null)
                            <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.72rem">#{{ $result->car_number }}</span>
                            @else
                            <span class="text-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0"
                                     style="width:28px;height:28px;font-size:.68rem;background:{{ $race->gameColor() }}">
                                    {{ strtoupper(substr($result->displayName(), 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $result->displayName() }}</div>
                                    @if(!$result->user_id && $result->player_id)
                                    <div class="text-secondary" style="font-size:.65rem">{{ $result->player_id }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-secondary" style="font-size:.78rem">{{ $result->vehicle ?? '—' }}</td>
                        <td class="text-center fw-bold">{{ $result->lap_count ?? '—' }}</td>
                        <td class="text-center" style="font-family:monospace;font-size:.8rem">
                            @if($result->dnf)
                                <span class="badge" style="background:#fef2f2;color:#dc2626;font-size:.7rem;padding:3px 8px;border-radius:5px;font-weight:700">DNF</span>
                            @else
                                {{ \App\Models\RaceResult::formatMs($result->total_time) }}
                            @endif
                        </td>
                        <td class="text-center fw-bold" style="font-family:monospace;font-size:.8rem">
                            {{ \App\Models\RaceResult::formatMs($result->best_lap) }}
                            @if($result->fastest_lap)
                            <span class="badge ms-1" style="background:#7c3aed;font-size:.58rem;padding:2px 5px">FL</span>
                            @endif
                        </td>
                        <td class="text-center" style="font-size:.78rem">
                            {{ $result->consistency !== null ? $result->consistency . '%' : '—' }}
                        </td>
                        <td class="text-center fw-bold pe-4">{{ $result->laps_led ?? '—' }}</td>
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
            <div class="text-secondary mt-1" style="font-size:.82rem">Import a qualifying session file (Q) to populate this tab.</div>
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
                        <td class="ps-4"><x-race-position :position="$result->position" /></td>
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