@extends('layouts.admin')

@section('title', $race->title)
@section('page-title', $race->title)

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
    @if(!$race->isPast())
    <a href="{{ route('admin.races.edit', $race) }}" class="btn btn-sm fw-bold text-uppercase text-white" style="background:#7c3aed;font-size:.78rem">
        Edit
    </a>
    @endif
@endsection

@section('content')

{{-- Event header strip --}}
<div class="admin-card mb-4 p-0 overflow-hidden">
    <div class="d-flex align-items-center flex-wrap">
        <div class="p-4" style="border-right:1px solid #f3f4f6;min-width:60px">
            <span class="badge text-white fw-bold d-inline-block"
                  style="background:{{ $race->gameColor() }};font-size:.72rem;padding:4px 10px;border-radius:6px">
                {{ $race->gameLabel() }}
            </span>
        </div>
        <div class="p-4" style="border-right:1px solid #f3f4f6;min-width:160px">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Track</div>
            <div class="fw-bold text-dark mt-1" style="font-size:.9rem">{{ $race->track }}</div>
        </div>
        <div class="p-4" style="border-right:1px solid #f3f4f6;min-width:200px">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Date</div>
            <div class="fw-bold text-dark mt-1" style="font-size:.9rem">{{ $race->scheduledAtUk()->format('d M Y · H:i T') }}</div>
        </div>
        <div class="p-4">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Status</div>
            <span class="status-badge status-{{ $race->status }} mt-1 d-inline-flex align-items-center gap-1">
                @if($race->status === 'open')
                    <svg width="7" height="7" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                @endif
                {{ ucfirst($race->status) }}
            </span>
        </div>
    </div>
</div>

{{-- Main tabs --}}
<div x-data="{ tab: '{{ request('server') ? 'results' : 'info' }}' }">

    <div class="admin-card">

        {{-- Tab nav --}}
        <div class="d-flex border-bottom px-2" style="background:#f9fafb">
            <button @click="tab = 'info'"
                    :style="tab === 'info' ? 'color:#7c3aed;border-bottom:2px solid #7c3aed' : 'color:#9ca3af;border-bottom:2px solid transparent'"
                    class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                    style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s">
                Info
            </button>
            <button @click="tab = 'entries'"
                    :style="tab === 'entries' ? 'color:#7c3aed;border-bottom:2px solid #7c3aed' : 'color:#9ca3af;border-bottom:2px solid transparent'"
                    class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                    style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s">
                Entry List
                @if($registrations->isNotEmpty())
                <span class="badge ms-1" style="background:#7c3aed;color:white;font-size:.65rem;padding:2px 7px;border-radius:10px">
                    {{ $registrations->count() }}
                </span>
                @endif
            </button>
            <button @click="tab = 'results'"
                    :style="tab === 'results' ? 'color:#7c3aed;border-bottom:2px solid #7c3aed' : 'color:#9ca3af;border-bottom:2px solid transparent'"
                    class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                    style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s">
                Results
                @if($raceResults->isNotEmpty())
                <span class="badge ms-1" style="background:#059669;color:white;font-size:.65rem;padding:2px 7px;border-radius:10px">
                    {{ $raceResults->count() }}
                </span>
                @endif
            </button>
        </div>

        {{-- INFO TAB --}}
        <div x-show="tab === 'info'" x-cloak>
            <div class="px-4 py-4">
                <div class="row g-4">

                    {{-- Left: details --}}
                    <div class="col-lg-8">

                        @if($race->description)
                        <div class="mb-4 p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
                            <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af">Description</p>
                            <p class="mb-0 text-secondary" style="font-size:.875rem;line-height:1.6">{{ $race->description }}</p>
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-sm-6 col-lg-4">
                                <div class="p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
                                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Registrations</div>
                                    <div class="fw-black text-dark mt-1" style="font-size:1.4rem">
                                        {{ $registrations->count() }}
                                        @if($race->max_drivers)
                                        <span class="fw-normal text-secondary" style="font-size:.9rem">/ {{ $race->max_drivers }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-4">
                                <div class="p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
                                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Multiplier</div>
                                    @php $multipliers = ['15'=>'0.6','20'=>'0.8','30'=>'1.0','30+'=>'1.2','30++'=>'1.3','45'=>'1.5','45+'=>'1.6','60'=>'2.0','60+'=>'2.1','90'=>'2.5','90+'=>'2.6']; @endphp
                                    <div class="fw-black text-dark mt-1" style="font-size:1.1rem">
                                        {{ ($multipliers[$race->duration_key] ?? '1.0') }}×
                                    </div>
                                </div>
                            </div>
                            @if($race->event_tag)
                            <div class="col-sm-6 col-lg-4">
                                <div class="p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
                                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Event Tag</div>
                                    <div class="fw-black text-dark mt-1">{{ $race->event_tag }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Push Config --}}
                    @if($ftpServers->isNotEmpty())
                    <div class="col-12">
                        <div class="p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.82rem">Push Config to Server</div>
                                    <div class="text-secondary mt-1" style="font-size:.75rem">Uploads entrylist.json, configuration.json and settings.json to the server's cfg directory.</div>
                                </div>
                            </div>
                            @if(session('success'))
                            <div class="alert alert-success py-2 px-3 mb-3" style="font-size:.8rem">{{ session('success') }}</div>
                            @endif
                            @if(session('error'))
                            <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.8rem">{{ session('error') }}</div>
                            @endif
                            <form action="{{ route('admin.races.push-config', $race) }}" method="POST" class="d-flex gap-2 align-items-end flex-wrap">
                                @csrf
                                <div class="flex-grow-1" style="min-width:200px">
                                    <label class="form-label fw-bold text-dark" style="font-size:.78rem">Server</label>
                                    <select name="server_id" class="form-select form-select-sm @error('server_id') is-invalid @enderror">
                                        <option value="">Select server…</option>
                                        @foreach($ftpServers as $server)
                                        <option value="{{ $server->id }}">{{ $server->name }} — {{ $server->cfg_path }}</option>
                                        @endforeach
                                    </select>
                                    @error('server_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <button type="submit" class="btn btn-sm fw-black text-uppercase text-white flex-shrink-0"
                                        style="background:#7c3aed;font-size:.78rem;padding:7px 16px">
                                    Push Config →
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    {{-- Right: media --}}
                    @if($race->image_url || $race->icon_url)
                    <div class="col-lg-4">
                        @if($race->image_url)
                        <div class="mb-3">
                            <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af">Background Image</p>
                            <img src="{{ $race->image_url }}" alt="Background"
                                 style="width:100%;border-radius:8px;object-fit:cover;max-height:140px">
                        </div>
                        @endif
                        @if($race->icon_url)
                        <div>
                            <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.68rem;letter-spacing:.08em;color:#9ca3af">Event Icon</p>
                            <img src="{{ $race->icon_url }}" alt="Icon"
                                 style="height:60px;object-fit:contain">
                        </div>
                        @endif
                    </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- ENTRY LIST TAB --}}
        <div x-show="tab === 'entries'" x-cloak>
            <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid #f3f4f6">
                <span class="fw-black text-uppercase fst-italic text-secondary" style="font-size:.72rem;letter-spacing:.08em">
                    {{ $registrations->count() }} {{ $registrations->count() === 1 ? 'driver' : 'drivers' }} registered
                </span>
                <a href="{{ route('admin.races.entry-list', $race) }}"
                   class="btn btn-sm fw-bold text-uppercase"
                   style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;font-size:.72rem;padding:4px 12px;border-radius:6px">
                    ↓ Download JSON
                </a>
            </div>
            @if($registrations->isEmpty())
            <div class="p-5 text-center">
                <div class="fw-bold text-dark" style="font-size:.95rem">No registrations yet</div>
                <div class="text-secondary mt-1" style="font-size:.82rem">Drivers can register via the public event page.</div>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <tr>
                            <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:50px">#</th>
                            <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                            <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Platform ID</th>
                            <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Team</th>
                            <th class="fw-bold text-uppercase pe-4 text-end" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:140px">Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registrations as $i => $reg)
                        <tr>
                            <td class="ps-4 text-secondary fw-bold" style="font-size:.8rem">{{ $i + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0"
                                         style="width:30px;height:30px;font-size:.72rem;background:linear-gradient(135deg,#7c3aed,#db2777)">
                                        {{ strtoupper(substr($reg->user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $reg->user->name }}</div>
                                        @if($reg->user->platform)
                                        <span class="badge fw-bold" style="background:#f3f4f6;color:#6b7280;font-size:.62rem;padding:1px 5px">
                                            {{ strtoupper($reg->user->platform) }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($reg->user->platform_id)
                                <code style="font-size:.78rem;color:#374151">{{ $reg->user->platform_id }}</code>
                                @else
                                <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-secondary" style="font-size:.82rem">{{ $reg->user->team ?? '—' }}</td>
                            <td class="pe-4 text-end text-secondary" style="font-size:.78rem">
                                {{ $reg->created_at->format('d M Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- RESULTS TAB --}}
        <div x-show="tab === 'results'" x-cloak>

            {{-- FTP Import --}}
            <div class="px-4 pt-4 pb-0">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Import via FTP</div>
                        <div class="text-secondary mt-1" style="font-size:.75rem">Select a GPortal server to browse and import result files.</div>
                    </div>
                    <a href="{{ route('admin.servers.index') }}"
                       class="btn btn-sm fw-bold text-uppercase"
                       style="background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff;font-size:.72rem;padding:4px 12px;border-radius:6px">
                        Manage Servers
                    </a>
                </div>

                @if($ftpServers->isEmpty())
                <div class="text-center py-3 mb-3">
                    <div class="text-secondary" style="font-size:.82rem">No active FTP servers configured.</div>
                    <a href="{{ route('admin.servers.create') }}" class="btn btn-sm fw-bold text-uppercase mt-2"
                       style="background:#7c3aed;color:white;font-size:.72rem">+ Add Server</a>
                </div>
                @else

                <div class="row g-3 mb-4">
                    @foreach($ftpServers as $server)
                    @php $isSelected = $selectedServer?->id === $server->id; @endphp
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="{{ request()->fullUrlWithQuery(['server' => $server->id]) }}"
                           class="text-decoration-none d-block h-100">
                            <div style="background:{{ $isSelected ? '#faf5ff' : 'white' }};border:{{ $isSelected ? '2px solid #7c3aed' : '1px solid #e5e7eb' }};border-radius:12px;padding:1rem 1.25rem;cursor:pointer;height:100%;min-height:80px">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="fw-black text-dark" style="font-size:.875rem">{{ $server->name }}</div>
                                    @if($isSelected && !$ftpError)
                                        <span style="width:8px;height:8px;background:#22c55e;border-radius:50%;display:inline-block;margin-top:5px;flex-shrink:0"></span>
                                    @elseif($isSelected && $ftpError)
                                        <span style="width:8px;height:8px;background:#ef4444;border-radius:50%;display:inline-block;margin-top:5px;flex-shrink:0"></span>
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

                @if($selectedServer && $ftpError)
                <div class="mb-4 p-3 rounded-3" style="background:#fef2f2;border:1px solid #fecaca">
                    <div class="fw-bold" style="font-size:.82rem;color:#dc2626">{{ $ftpError }}</div>
                </div>
                @endif

                @if($selectedServer && !$ftpError)
                <div class="mb-4" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2" style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <span class="fw-black text-uppercase fst-italic text-dark" style="font-size:.75rem">{{ $selectedServer->name }}</span>
                        <span class="text-secondary" style="font-size:.72rem;font-family:monospace">{{ $selectedServer->path }}</span>
                    </div>
                    @if(empty($ftpFiles))
                    <div class="p-3">
                        <div class="fw-bold text-dark" style="font-size:.82rem">No JSON files found in <code>{{ $selectedServer->path }}</code></div>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:.82rem">
                            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                <tr>
                                    <th class="fw-bold text-uppercase ps-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">File</th>
                                    <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:90px">Session</th>
                                    <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:140px">Date</th>
                                    <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:75px">Size</th>
                                    <th class="fw-bold text-uppercase text-end pe-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:120px">Action</th>
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
                                    <td style="font-size:.75rem;color:#6b7280">{{ $parsed['date'] !== '—' ? $parsed['date'] : ($file['modified'] ?? '—') }}</td>
                                    <td style="font-size:.75rem;color:#6b7280;font-family:monospace">{{ $sizeKb }}</td>
                                    <td class="text-end pe-3">
                                        @if($isImported)
                                            <span class="badge" style="background:#f0fdf4;color:#16a34a;font-size:.68rem;padding:4px 10px;border-radius:5px;font-weight:700">✓ Imported</span>
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

            {{-- Manual Upload --}}
            <div class="px-4 pb-4">
                <div class="p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
                    <div class="fw-black text-uppercase fst-italic text-dark mb-3" style="font-size:.82rem">Manual Upload</div>
                    <form action="{{ route('admin.races.results.store', $race) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="d-flex gap-3 align-items-end flex-wrap">
                            <div class="flex-grow-1">
                                <label class="form-label fw-bold text-dark" style="font-size:.78rem">JSON Results Files</label>
                                <input type="file" name="result_json[]" accept=".json,application/json"
                                       class="form-control" style="font-size:.875rem" multiple required>
                            </div>
                            <button type="submit"
                                    class="btn fw-black text-uppercase text-white px-4 flex-shrink-0"
                                    style="background:#7c3aed;height:42px">
                                Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Results sub-tabs --}}
            @php $defaultSubtab = $raceResults->isNotEmpty() ? 'race' : ($qualiResults->isNotEmpty() ? 'quali' : 'race'); @endphp
            <div x-data="{ subtab: '{{ $defaultSubtab }}' }" style="border-top:1px solid #f3f4f6">

                <div class="d-flex px-2" style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <button @click="subtab = 'race'"
                            :style="subtab === 'race' ? 'color:#059669;border-bottom:2px solid #059669' : 'color:#9ca3af;border-bottom:2px solid transparent'"
                            class="btn btn-link fw-black text-uppercase text-decoration-none py-2 px-3"
                            style="font-size:.75rem;border-radius:0;letter-spacing:.05em;transition:color .15s">
                        Race
                        @if($raceResults->isNotEmpty())
                        <span class="badge ms-1" style="background:#059669;color:white;font-size:.6rem;padding:2px 6px;border-radius:10px">{{ $raceResults->count() }}</span>
                        @endif
                    </button>
                    <button @click="subtab = 'quali'"
                            :style="subtab === 'quali' ? 'color:#2563eb;border-bottom:2px solid #2563eb' : 'color:#9ca3af;border-bottom:2px solid transparent'"
                            class="btn btn-link fw-black text-uppercase text-decoration-none py-2 px-3"
                            style="font-size:.75rem;border-radius:0;letter-spacing:.05em;transition:color .15s">
                        Qualifying
                        @if($qualiResults->isNotEmpty())
                        <span class="badge ms-1" style="background:#2563eb;color:white;font-size:.6rem;padding:2px 6px;border-radius:10px">{{ $qualiResults->count() }}</span>
                        @endif
                    </button>
                    <button @click="subtab = 'ratings'"
                            :style="subtab === 'ratings' ? 'color:#7c3aed;border-bottom:2px solid #7c3aed' : 'color:#9ca3af;border-bottom:2px solid transparent'"
                            class="btn btn-link fw-black text-uppercase text-decoration-none py-2 px-3"
                            style="font-size:.75rem;border-radius:0;letter-spacing:.05em;transition:color .15s">
                        Ratings
                    </button>
                </div>

                {{-- Race sub-tab --}}
                <div x-show="subtab === 'race'" x-cloak>
                    @if($raceResults->isEmpty())
                    <div class="p-5 text-center">
                        <div class="fw-bold text-dark" style="font-size:.95rem">No race results yet</div>
                        <div class="text-secondary mt-1" style="font-size:.82rem">Import a result file via FTP or manual upload above.</div>
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
                                    <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:110px">Best Lap</th>
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
                                    <td class="text-center" style="font-size:.78rem">{{ $result->consistency !== null ? $result->consistency . '%' : '—' }}</td>
                                    <td class="text-center fw-bold pe-4">{{ $result->laps_led ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                {{-- Qualifying sub-tab --}}
                <div x-show="subtab === 'quali'" x-cloak>
                    @if($qualiResults->isEmpty())
                    <div class="p-5 text-center">
                        <div class="fw-bold text-dark" style="font-size:.95rem">No qualifying results yet</div>
                        <div class="text-secondary mt-1" style="font-size:.82rem">Import a qualifying session file to populate this tab.</div>
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

                {{-- Ratings sub-tab --}}
                <div x-show="subtab === 'ratings'" x-cloak>
                    @php $ratedResults = $raceResults->whereNotNull('rating_after')->sortBy('position'); @endphp
                    @if($ratedResults->isEmpty())
                    <div class="p-5 text-center">
                        <div class="fw-bold text-dark" style="font-size:.95rem">No rating data yet</div>
                        <div class="text-secondary mt-1" style="font-size:.82rem">Import race results first — ratings are calculated automatically.</div>
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
                                        {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 0) }}
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
        </div>
        {{-- END Results tab --}}

    </div>
</div>

@endsection