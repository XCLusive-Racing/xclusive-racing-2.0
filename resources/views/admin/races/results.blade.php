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
    <div class="d-flex align-items-start flex-wrap">
        <div class="p-3 p-md-4" style="border-right:1px solid #f3f4f6;min-width:140px;flex:1 1 140px">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Race</div>
            <div class="fw-black text-dark mt-1" style="font-size:.9rem">{{ $race->title }}</div>
        </div>
        <div class="p-3 p-md-4 d-none d-sm-block" style="border-right:1px solid #f3f4f6;min-width:120px;flex:1 1 120px">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Track</div>
            <div class="fw-bold text-dark mt-1" style="font-size:.85rem">{{ $race->track }}</div>
        </div>
        <div class="p-3 p-md-4" style="border-right:1px solid #f3f4f6;min-width:140px;flex:1 1 140px">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Date</div>
            <div class="fw-bold text-dark mt-1" style="font-size:.82rem">{{ $race->scheduledAtUk()->format('d M Y · H:i') }}</div>
        </div>
        <div class="p-3 p-md-4" style="flex:0 0 auto">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Game</div>
            <span class="badge text-white fw-bold mt-1 d-inline-block"
                  style="background:{{ $race->gameColor() }};font-size:.72rem;padding:4px 10px;border-radius:6px">
                {{ $race->gameLabel() }}
            </span>
        </div>
    </div>
</div>

{{-- FTP Server Import card (starts expanded) --}}
<div data-accordions>
<div class="admin-card mb-4" data-accordion="open">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Import via FTP Server</div>
            <div class="text-secondary mt-1" style="font-size:.78rem">Select a GPortal server to browse and import result files.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.servers.index') }}"
               class="btn btn-sm fw-bold text-uppercase"
               style="background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff;font-size:.72rem;padding:4px 12px;border-radius:6px">
                Manage Servers
            </a>
            <button data-accordion-header type="button"
                    class="btn btn-sm fw-bold text-uppercase"
                    style="background:#f9fafb;color:#6b7280;border:1px solid #e5e7eb;font-size:.72rem;padding:4px 12px;border-radius:6px;min-width:85px">
                <span data-show-when-open>▲ Collapse</span>
                <span data-show-when-closed style="display:none">▼ Expand</span>
            </button>
        </div>
    </div>

    <div data-accordion-body>

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
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary" style="font-size:.72rem">Newest first</span>
                    <button type="button" id="ftp-files-toggle"
                            class="btn btn-sm fw-bold text-uppercase"
                            style="background:#f9fafb;color:#6b7280;border:1px solid #e5e7eb;font-size:.68rem;padding:3px 10px;border-radius:6px;min-width:75px">
                        ▲ Hide
                    </button>
                </div>
            </div>
            <div id="ftp-files-content">

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
                            <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:80px">Session</th>
                            <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:130px">Date</th>
                            <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:80px">Size</th>
                            <th class="fw-bold text-uppercase text-end pe-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:120px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $maxImportsReached = count($importedFiles) >= 2; @endphp
                        @foreach($ftpFiles as $file)
                        @php
                            $parsed     = \App\Services\FtpService::parseFilename($file['name']);
                            $isImported = in_array($file['name'], $importedFiles);
                            $sizeKb     = $file['size'] !== null ? round($file['size'] / 1024, 1) . ' KB' : '—';
                            $dimRow     = $isImported || (!$isImported && $maxImportsReached);
                        @endphp
                        <tr>
                            <td class="ps-3" style="{{ $dimRow && !$isImported ? 'opacity:.4' : ($isImported ? 'opacity:.45' : '') }}">
                                <div class="fw-bold text-dark" style="font-size:.78rem;font-family:monospace">{{ $file['name'] }}</div>
                            </td>
                            <td style="{{ $dimRow && !$isImported ? 'opacity:.4' : ($isImported ? 'opacity:.45' : '') }}">
                                @if($parsed['session'] === 'Race')
                                    <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">Race</span>
                                @elseif($parsed['session'] === 'Qualifying')
                                    <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">Quali</span>
                                @else
                                    <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">?</span>
                                @endif
                            </td>
                            <td class="d-none d-sm-table-cell" style="font-size:.75rem;color:#6b7280;{{ $dimRow && !$isImported ? 'opacity:.4' : ($isImported ? 'opacity:.45' : '') }}">
                                {{ $parsed['date'] !== '—' ? $parsed['date'] : ($file['modified'] ?? '—') }}
                            </td>
                            <td class="d-none d-md-table-cell" style="font-size:.75rem;color:#6b7280;font-family:monospace;{{ $dimRow && !$isImported ? 'opacity:.4' : ($isImported ? 'opacity:.45' : '') }}">{{ $sizeKb }}</td>
                            <td class="text-end pe-3">
                                @if($isImported)
                                    <div class="d-flex align-items-center gap-2 justify-content-end">
                                        <span class="badge" style="background:#f0fdf4;color:#16a34a;font-size:.68rem;padding:4px 10px;border-radius:5px;font-weight:700">
                                            ✓ Imported
                                        </span>
                                        <form action="{{ route('admin.races.results.ftp-cancel', $race) }}" method="POST"
                                              onsubmit="return confirm('Remove all results from this file?')">
                                            @csrf
                                            <input type="hidden" name="filename" value="{{ $file['name'] }}">
                                            <button type="submit"
                                                    class="btn btn-sm fw-bold text-uppercase"
                                                    style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.65rem;padding:3px 10px;border-radius:5px">
                                                Reset
                                            </button>
                                        </form>
                                    </div>
                                @elseif($maxImportsReached)
                                    <span style="font-size:.72rem;color:#9ca3af;font-weight:600">Max reached</span>
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
            </div>{{-- #ftp-files-content --}}
        </div>
        @endif

        @endif {{-- end ftpServers not empty --}}
    </div>
</div>
</div>

{{-- Manual Upload card (starts collapsed) --}}
<div data-accordions>
<div class="admin-card mb-4" data-accordion="closed">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Manual Upload</div>
            <div class="text-secondary mt-1" style="font-size:.78rem">Manually upload one or more JSON result files.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
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
            <button data-accordion-header type="button"
                    class="btn btn-sm fw-bold text-uppercase"
                    style="background:#f9fafb;color:#6b7280;border:1px solid #e5e7eb;font-size:.72rem;padding:4px 12px;border-radius:6px;min-width:85px">
                <span data-show-when-open style="display:none">▲ Collapse</span>
                <span data-show-when-closed>▼ Expand</span>
            </button>
        </div>
    </div>

    <div data-accordion-body style="display:none">
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
</div>

{{-- DNS Candidates card --}}
@if($dnsCandidates->isNotEmpty() || $entrylistDnsCandidates->isNotEmpty())
@php $totalDns = $dnsCandidates->count() + $entrylistDnsCandidates->count(); @endphp
<div class="admin-card mb-4" style="border-left:4px solid #dc2626">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">
                DNS — Drivers Not in Results
                <span class="badge ms-2 text-white fw-bold" style="background:#dc2626;font-size:.68rem;padding:3px 8px;border-radius:6px">{{ $totalDns }}</span>
            </div>
            <div class="text-secondary mt-1" style="font-size:.78rem">Select drivers to add as DNS.</div>
        </div>
    </div>
    <form action="{{ route('admin.races.results.dns', $race) }}" method="POST">
        @csrf
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.82rem">
                <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <tr>
                        <th class="ps-4" style="width:40px">
                            <input type="checkbox" onclick="document.querySelectorAll('.dns-cb').forEach(cb => cb.checked = this.checked)">
                        </th>
                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                        <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Platform ID</th>
                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Source</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dnsCandidates as $reg)
                    <tr>
                        <td class="ps-4">
                            <input type="checkbox" name="user_ids[]" value="{{ $reg->user_id }}" class="dns-cb">
                        </td>
                        <td class="fw-bold">{{ $reg->user->name ?? '—' }}</td>
                        <td class="d-none d-sm-table-cell" style="font-size:.75rem;color:#6b7280;font-family:monospace">{{ $reg->user->platform_id ?? '—' }}</td>
                        <td><span class="badge" style="background:#ede9fe;color:#6d28d9;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">Registered</span></td>
                    </tr>
                    @endforeach
                    @foreach($entrylistDnsCandidates as $entry)
                    @php $encoded = base64_encode(json_encode(['player_id' => $entry['player_id'], 'name' => $entry['name']])); @endphp
                    <tr>
                        <td class="ps-4">
                            <input type="checkbox" name="player_entries[]" value="{{ $encoded }}" class="dns-cb">
                        </td>
                        <td>
                            <div class="fw-bold">{{ $entry['name'] }}</div>
                            @if($entry['user'])
                            <div class="text-secondary" style="font-size:.68rem">linked: {{ $entry['user']->name }}</div>
                            @endif
                        </td>
                        <td class="d-none d-sm-table-cell" style="font-size:.75rem;color:#6b7280;font-family:monospace">{{ $entry['player_id'] }}</td>
                        <td>
                            <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">Entrylist</span>
                            @if($entry['car_number'] !== null)
                            <span class="badge ms-1" style="background:#f3f4f6;color:#374151;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">#{{ $entry['car_number'] }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex justify-content-end" style="border-top:1px solid #f3f4f6">
            <button type="submit"
                    class="btn btn-sm fw-black text-uppercase text-white"
                    style="background:#dc2626;font-size:.72rem;padding:5px 16px;border-radius:6px">
                Add Selected as DNS
            </button>
        </div>
    </form>
</div>
@endif

{{-- Results tabs --}}
@php $defaultResultTab = $raceResults->isNotEmpty() ? 'race' : ($qualiResults->isNotEmpty() ? 'quali' : 'race'); @endphp
<div class="admin-card" data-tabs data-default-tab="{{ $defaultResultTab }}">

    {{-- Tab nav --}}
    <div class="d-flex border-bottom px-2" style="background:#f9fafb">
        <button class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                data-tab-btn="race"
                data-tab-color="#7c3aed"
                style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent">
            Race Results
            @if($raceResults->isNotEmpty())
            <span class="badge ms-1 text-white" style="background:#7c3aed;font-size:.65rem;padding:2px 7px;border-radius:10px">
                {{ $raceResults->count() }}
            </span>
            @endif
        </button>
        <button class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                data-tab-btn="quali"
                data-tab-color="#2563eb"
                style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent">
            Qualifying
            @if($qualiResults->isNotEmpty())
            <span class="badge ms-1 text-white" style="background:#2563eb;font-size:.65rem;padding:2px 7px;border-radius:10px">
                {{ $qualiResults->count() }}
            </span>
            @endif
        </button>
        <button class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                data-tab-btn="ratings"
                data-tab-color="#059669"
                style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent">
            Ratings
        </button>
    </div>

    {{-- Race Results tab --}}
    <div data-tab-panel="race" style="display:none">
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
                        <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:55px">No</th>
                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                        <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Vehicle</th>
                        <th class="fw-bold text-uppercase text-center d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:60px">Laps</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:115px">Time</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:105px">Best Lap</th>
                        <th class="fw-bold text-uppercase text-center d-none d-lg-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:90px">Consistency</th>
                        <th class="fw-bold text-uppercase text-center pe-4 d-none d-lg-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:55px">Led</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($raceResults as $result)
                    <tr>
                        <td class="ps-4"><x-race-position :position="$result->position" /></td>
                        <td class="d-none d-sm-table-cell">
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
                        <td class="d-none d-md-table-cell text-secondary" style="font-size:.78rem">{{ $result->vehicle ?? '—' }}</td>
                        <td class="d-none d-md-table-cell text-center fw-bold">{{ $result->lap_count ?? '—' }}</td>
                        <td class="text-center" style="font-family:monospace;font-size:.8rem">
                            @if($result->dns)
                                <span class="badge" style="background:#fef2f2;color:#6b7280;font-size:.7rem;padding:3px 8px;border-radius:5px;font-weight:700">DNS</span>
                            @elseif($result->dnf)
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
                        <td class="d-none d-lg-table-cell text-center" style="font-size:.78rem">
                            {{ $result->consistency !== null ? $result->consistency . '%' : '—' }}
                        </td>
                        <td class="d-none d-lg-table-cell text-center fw-bold pe-4">{{ $result->laps_led ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Qualifying tab --}}
    <div data-tab-panel="quali" style="display:none">
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
                        <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:60px">Car #</th>
                        <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                        <th class="fw-bold text-uppercase text-center d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:80px">Laps</th>
                        <th class="fw-bold text-uppercase text-center pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:130px">Best Lap</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($qualiResults as $result)
                    <tr>
                        <td class="ps-4"><x-race-position :position="$result->position" /></td>
                        <td class="d-none d-sm-table-cell">
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
                        <td class="d-none d-sm-table-cell text-center fw-bold">{{ $result->lap_count ?? '—' }}</td>
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
    <div data-tab-panel="ratings" style="display:none">
        @php $ratedResults = $raceResults->whereNotNull('rating_after')->sortBy('position'); @endphp

        <div class="px-4 pt-3 pb-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid #f3f4f6">
            <div>
                @if($linkedFinishers < $minRatingDrivers)
                <span style="font-size:.78rem;color:#dc2626;font-weight:700">
                    ⚠ Need {{ $minRatingDrivers }} linked finishers — have {{ $linkedFinishers }}.
                    Make sure drivers have accounts matched to their platform ID.
                </span>
                @else
                <span style="font-size:.78rem;color:#6b7280">{{ $linkedFinishers }} linked finishers</span>
                @endif
            </div>
            @if($raceResults->isNotEmpty())
            <form action="{{ route('admin.races.results.recalculate', $race) }}" method="POST">
                @csrf
                <button type="submit"
                        class="btn btn-sm fw-black text-uppercase text-white"
                        style="background:#059669;font-size:.7rem;padding:4px 14px;border-radius:6px">
                    Recalculate Ratings
                </button>
            </form>
            @endif
        </div>

        @if($ratedResults->isEmpty())
        <div class="p-5 text-center">
            <div class="fw-bold text-dark" style="font-size:.95rem">No rating data yet</div>
            <div class="text-secondary mt-1" style="font-size:.82rem">
                @if($linkedFinishers < $minRatingDrivers)
                    Not enough linked finishers. Add DNS entries or link more drivers to their accounts, then click Recalculate.
                @else
                    Click "Recalculate Ratings" above to calculate.
                @endif
            </div>
        </div>
        @else
        @php $sof = $ratedResults->first()->sof; @endphp
        <div class="px-4 py-3 d-flex align-items-center gap-3" style="border-bottom:1px solid #f3f4f6;background:#f9fafb">
            <span class="fw-black text-uppercase fst-italic text-dark" style="font-size:.78rem">Strength of Field</span>
            <span class="badge fw-bold" style="background:#7c3aed;color:white;font-size:.78rem;padding:4px 10px;border-radius:6px">
                {{ number_format($sof, 0) }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.82rem">
                <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <tr>
                        <th class="fw-bold text-uppercase ps-4" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:50px">Pos</th>
                        <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                        <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:80px">Status</th>
                        <th class="fw-bold text-uppercase text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:110px">Before</th>
                        <th class="fw-bold text-uppercase text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:100px">Elo Δ</th>
                        <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:110px">After</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ratedResults as $result)
                    @php $change = (float) $result->elo_change; @endphp
                    <tr>
                        <td class="ps-4"><x-race-position :position="$result->position" /></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0"
                                     style="width:28px;height:28px;font-size:.68rem;background:{{ $race->gameColor() }}">
                                    {{ strtoupper(substr($result->displayName(), 0, 1)) }}
                                </div>
                                <span class="fw-bold">{{ $result->displayName() }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            @if($result->dnf)
                                <span class="badge" style="background:#fef2f2;color:#dc2626;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">DNF</span>
                            @else
                                <span class="badge" style="background:#f0fdf4;color:#16a34a;font-size:.68rem;padding:3px 8px;border-radius:5px;font-weight:700">FIN</span>
                            @endif
                        </td>
                        <td class="text-end text-secondary" style="font-family:monospace;font-size:.8rem">
                            {{ number_format((float) $result->rating_before, 0) }}
                        </td>
                        <td class="text-end fw-black" style="font-family:monospace;font-size:.85rem;color:{{ $change >= 0 ? '#059669' : '#dc2626' }}">
                            {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 1) }}
                        </td>
                        <td class="text-end pe-4 fw-bold" style="font-family:monospace;font-size:.8rem">
                            {{ number_format((float) $result->rating_after, 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>

<script>
(function () {
    const toggle  = document.getElementById('ftp-files-toggle');
    const content = document.getElementById('ftp-files-content');
    if (!toggle || !content) return;

    toggle.addEventListener('click', function () {
        const open = content.style.display !== 'none';
        content.style.display = open ? 'none' : '';
        toggle.textContent    = open ? '▼ Show' : '▲ Hide';
    });
})();
</script>

@endsection
