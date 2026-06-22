@extends('layouts.admin')

@section('title', 'BOP Management')
@section('page-title', 'Balance of Performance')

@section('page-actions')
@endsection

@section('content')

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif
@if(session('import_error'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#ef4444">{{ session('import_error') }}</div>
@endif
@if(session('push_success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('push_success') }}</div>
@endif
@if(session('push_error'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#ef4444">{{ session('push_error') }}</div>
@endif

{{-- Push BOP to Server --}}
<div class="admin-card mb-4">
    <div class="px-4 py-3" style="border-bottom:1px solid #f3f4f6">
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.82rem">Push BOP to Server</div>
        <div class="text-secondary mt-1" style="font-size:.72rem">Generates <code>bop.json</code> from the entries below and uploads it to the selected server's cfg path.</div>
    </div>
    <div class="px-4 py-4">
        @if($ftpServers->isEmpty())
            <div class="text-secondary" style="font-size:.82rem">No active FTP servers configured.
                <a href="{{ route('admin.servers.create') }}" class="fw-bold" style="color:#7c3aed">Add one →</a>
            </div>
        @else
        <form action="{{ route('admin.bops.push') }}" method="POST">
            @csrf
            <div class="d-flex gap-3 align-items-end flex-wrap">
                <div>
                    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Game</label>
                    <select name="game" class="form-select form-select-sm" style="min-width:140px">
                        @foreach($games as $key => $label)
                        <option value="{{ $key }}" {{ $key === 'acc' ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-grow-1" style="min-width:220px">
                    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Server</label>
                    <select name="server_id" class="form-select form-select-sm">
                        <option value="">Select server…</option>
                        @foreach($ftpServers as $ftpServer)
                        <option value="{{ $ftpServer->id }}">{{ $ftpServer->name }} — {{ $ftpServer->cfg_path ?: $ftpServer->path }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-sm fw-black text-uppercase text-white flex-shrink-0"
                        style="background:#7c3aed;font-size:.78rem;padding:7px 18px">
                    Push BOP →
                </button>
            </div>
        </form>
        @endif
    </div>
</div>

{{-- JSON Import (accordion, opens when there's an import error) --}}
@php $importOpen = (bool) session('import_error'); @endphp
<div data-accordions>
    <div class="admin-card mb-4" data-accordion="{{ $importOpen ? 'open' : 'closed' }}">
        <div data-accordion-header
             class="d-flex align-items-center justify-content-between px-4 py-3"
             style="cursor:pointer">
            <div>
                <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.82rem">Import via JSON</div>
                <div class="text-secondary mt-1" style="font-size:.72rem">Upload a JSON file to bulk-create or update BOP entries.</div>
            </div>
            <svg data-accordion-arrow
                 width="16" height="16" fill="none" stroke="#9ca3af" stroke-width="2.5" viewBox="0 0 24 24"
                 style="transition:.2s;{{ $importOpen ? 'transform:rotate(90deg)' : '' }}">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </div>

        <div data-accordion-body style="border-top:1px solid #f3f4f6;{{ $importOpen ? '' : 'display:none' }}">
            <div class="px-4 py-4">
                <form action="{{ route('admin.bops.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-4">
                            <label class="form-label fw-bold text-dark" style="font-size:.78rem">Game</label>
                            <select name="game" class="form-select form-select-sm @error('game') is-invalid @enderror">
                                <option value="">Select game…</option>
                                @foreach($games as $key => $label)
                                <option value="{{ $key }}" {{ old('game') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-bold text-dark" style="font-size:.78rem">JSON File</label>
                            <input type="file" name="json_file" accept=".json,.txt"
                                   class="form-control form-control-sm @error('json_file') is-invalid @enderror">
                            @error('json_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label fw-bold text-dark" style="font-size:.78rem">Mode</label>
                            <select name="mode" class="form-select form-select-sm">
                                <option value="merge">Merge</option>
                                <option value="replace">Replace all</option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-sm fw-black text-uppercase text-white w-100"
                                    style="background:#7c3aed;font-size:.78rem;padding:7px">
                                Import
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-3 p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
                    <div class="fw-bold text-dark mb-1" style="font-size:.72rem">Expected JSON format</div>
                    <pre style="font-size:.72rem;color:#6b7280;margin:0;line-height:1.6">[
  { "car_model": "Ferrari 488 GT3 Evo", "track": null, "ballast_kg": 10, "restrictor": 0, "notes": "" },
  { "car_model": "Porsche 992 GT3 R",   "track": "monza", "ballast_kg": -5, "restrictor": 2 }
]</pre>
                    <div class="text-secondary mt-2" style="font-size:.71rem">
                        <strong>Merge</strong> — updates existing entries (matched on car_model + track), adds new ones.<br>
                        <strong>Replace all</strong> — deletes all existing BOP entries for the selected game first.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Global toggle bar --}}
@php $totalBops = $bops->flatten()->count(); $totalActive = $bops->flatten()->where('active', true)->count(); @endphp
@if($totalBops > 0)
<div class="admin-card mb-4">
    <div class="px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-black text-uppercase fst-italic text-dark" style="font-size:.78rem">All Games</span>
            <span class="badge fw-bold" style="background:#f3e8ff;color:#7c3aed;font-size:.7rem;padding:3px 8px;border-radius:6px">
                {{ $totalActive }} / {{ $totalBops }} active
            </span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('admin.bops.toggle-all') }}">
                @csrf <input type="hidden" name="active" value="1">
                <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                        style="font-size:.72rem;padding:4px 12px;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:6px">
                    ✓ Activate All
                </button>
            </form>
            <form method="POST" action="{{ route('admin.bops.toggle-all') }}">
                @csrf <input type="hidden" name="active" value="0">
                <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                        style="font-size:.72rem;padding:4px 12px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:6px">
                    ✕ Deactivate All
                </button>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Per-game BOP sections --}}
<div data-accordions>
@foreach($games as $gameKey => $gameLabel)
@php
    $gameBops   = $bops->get($gameKey, collect());
    $gameActive = $gameBops->where('active', true)->count();
@endphp
<div class="admin-card mb-4" data-accordion="open">

    {{-- Section header --}}
    <div class="d-flex align-items-center justify-content-between px-4 py-3 flex-wrap gap-2"
         style="border-bottom:1px solid #f3f4f6">
        <div data-accordion-header class="d-flex align-items-center gap-2" style="cursor:pointer">
            <svg data-accordion-arrow
                 width="14" height="14" fill="none" stroke="#9ca3af" stroke-width="2.5" viewBox="0 0 24 24"
                 style="transform:rotate(90deg);transition:.2s">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <h6 class="fw-black text-uppercase mb-0" style="font-size:.8rem;letter-spacing:.06em">{{ $gameLabel }}</h6>
            @if($gameBops->isNotEmpty())
            <span class="badge fw-bold" style="background:#f3f4f6;color:#6b7280;font-size:.68rem;padding:2px 8px;border-radius:5px">
                {{ $gameActive }} / {{ $gameBops->count() }} active
            </span>
            @endif
        </div>
        @if($gameBops->isNotEmpty())
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('admin.bops.toggle-game') }}">
                @csrf <input type="hidden" name="game" value="{{ $gameKey }}">
                <input type="hidden" name="active" value="1">
                <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                        style="font-size:.68rem;padding:3px 10px;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:5px">
                    ✓ All On
                </button>
            </form>
            <form method="POST" action="{{ route('admin.bops.toggle-game') }}">
                @csrf <input type="hidden" name="game" value="{{ $gameKey }}">
                <input type="hidden" name="active" value="0">
                <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                        style="font-size:.68rem;padding:3px 10px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:5px">
                    ✕ All Off
                </button>
            </form>
        </div>
        @endif
    </div>

    <div data-accordion-body>
        @if($gameBops->isEmpty())
        <p class="text-secondary mb-0 px-4 py-3" style="font-size:.82rem">No BOP entries for {{ $gameLabel }} yet.</p>
        @else
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.83rem">
                <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                    <tr>
                        <th class="fw-bold text-uppercase text-secondary py-2" style="font-size:.68rem;letter-spacing:.06em">Car Model</th>
                        <th class="fw-bold text-uppercase text-secondary py-2 d-none d-sm-table-cell" style="font-size:.68rem;letter-spacing:.06em">Track</th>
                        <th class="fw-bold text-uppercase text-secondary py-2 text-end" style="font-size:.68rem;letter-spacing:.06em">Ballast</th>
                        <th class="fw-bold text-uppercase text-secondary py-2 text-end d-none d-sm-table-cell" style="font-size:.68rem;letter-spacing:.06em">Restr.</th>
                        <th class="fw-bold text-uppercase text-secondary py-2 d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em">Notes</th>
                        <th style="width:100px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gameBops as $bop)
                    <tr style="border-bottom:1px solid #f9fafb;opacity:{{ $bop->active ? '1' : '.45' }}">
                        <td class="fw-bold" style="color:{{ $bop->active ? '#111827' : '#9ca3af' }}">
                            {{ $bop->car_model }}
                            @if(!$bop->active)
                            <span class="badge ms-1 fw-bold" style="background:#f3f4f6;color:#9ca3af;font-size:.6rem;padding:2px 6px;border-radius:4px">off</span>
                            @endif
                        </td>
                        <td class="text-secondary d-none d-sm-table-cell">{{ $bop->track ?? 'All tracks' }}</td>
                        <td class="text-end fw-bold" style="color:{{ $bop->ballast_kg > 0 ? '#ef4444' : ($bop->ballast_kg < 0 ? '#10b981' : '#374151') }}">
                            {{ $bop->ballast_kg > 0 ? '+' : '' }}{{ $bop->ballast_kg }} kg
                        </td>
                        <td class="text-end text-secondary d-none d-sm-table-cell">{{ $bop->restrictor > 0 ? $bop->restrictor . '%' : '—' }}</td>
                        <td class="text-secondary d-none d-md-table-cell" style="max-width:200px">
                            <span class="text-truncate d-block">{{ $bop->notes ?? '—' }}</span>
                        </td>
                        <td class="text-end pe-2">
                            <div class="d-flex gap-1 justify-content-end flex-wrap align-items-center">
                                <a href="{{ route('admin.bops.edit', $bop) }}"
                                   class="btn btn-xs btn-outline-secondary fw-bold" style="font-size:.7rem;padding:2px 8px">Edit</a>
                                <form method="POST" action="{{ route('admin.bops.toggle', $bop) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-xs fw-bold" style="font-size:.7rem;padding:2px 8px;{{ $bop->active ? 'background:#fef3c7;color:#92400e;border:1px solid #fde68a' : 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7' }}">
                                        {{ $bop->active ? 'Off' : 'On' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
@endforeach
</div>

@endsection
