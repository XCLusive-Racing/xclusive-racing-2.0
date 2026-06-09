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

{{-- JSON Import --}}
<div class="admin-card mb-5" x-data="{ open: {{ session('import_error') ? 'true' : 'false' }} }">
    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom"
         style="cursor:pointer;background:#fafafa;border-radius:inherit" @click="open = !open">
        <div class="d-flex align-items-center gap-3">
            <div style="width:32px;height:32px;background:#7c3aed15;border-radius:8px;display:flex;align-items:center;justify-content:center">
                <svg width="15" height="15" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </div>
            <div>
                <div class="fw-black text-dark" style="font-size:.85rem">Import via JSON</div>
                <div class="text-secondary" style="font-size:.72rem">Bulk-create or update BOP entries from a JSON file</div>
            </div>
        </div>
        <svg width="16" height="16" fill="none" stroke="#9ca3af" stroke-width="2.5" viewBox="0 0 24 24"
             :style="open ? 'transform:rotate(180deg);transition:.2s' : 'transition:.2s'">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>

    <div x-show="open" x-cloak>
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

            <div class="mt-4 p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
                <div class="fw-bold text-dark mb-2" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em">Expected format</div>
                <pre style="font-size:.72rem;color:#6b7280;margin:0;line-height:1.8;background:transparent">{ "entries": [
  { "carModel": 24, "track": "spa", "ballastKg": -5, "restrictor": 0 },
  { "car_model": "Ferrari 488 GT3 Evo", "track": null, "ballast_kg": 10, "restrictor": 0 }
]}</pre>
                <div class="d-flex gap-4 mt-2" style="font-size:.71rem;color:#6b7280">
                    <span><strong class="text-dark">Merge</strong> — updates existing, adds new</span>
                    <span><strong class="text-dark">Replace all</strong> — clears game first, then inserts</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Game sections --}}
@foreach($games as $gameKey => $gameLabel)
@php $gameBops = $bops->get($gameKey, collect()); @endphp
<div class="admin-card mb-3" x-data="{ open: false, search: '' }">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between px-4 py-3"
         style="cursor:pointer" @click="open = !open">
        <div class="d-flex align-items-center gap-3">
            <h6 class="fw-black text-uppercase mb-0" style="font-size:.82rem;letter-spacing:.06em">{{ $gameLabel }}</h6>
            <span class="badge fw-bold" style="background:#7c3aed15;color:#7c3aed;font-size:.7rem">
                {{ $gameBops->count() }} entries
            </span>
        </div>
        <svg width="14" height="14" fill="none" stroke="#9ca3af" stroke-width="2.5" viewBox="0 0 24 24"
             :style="open ? 'transform:rotate(180deg);transition:.2s' : 'transition:.2s'">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>

    {{-- Body --}}
    <div x-show="open" x-cloak style="border-top:1px solid #f3f4f6">
        @if($gameBops->isEmpty())
        <p class="text-secondary px-4 py-4 mb-0" style="font-size:.82rem">No BOP entries for {{ $gameLabel }} yet.</p>
        @else

        {{-- Search bar --}}
        <div class="px-4 py-3 border-bottom" style="background:#fafafa" @click.stop>
            <div class="position-relative" style="max-width:300px">
                <svg width="13" height="13" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24"
                     style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none">
                    <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" x-model="search" placeholder="Search car or track…"
                       class="form-control form-control-sm"
                       style="padding-left:30px;font-size:.82rem;border-color:#e5e7eb">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.83rem">
                <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                    <tr>
                        <th class="fw-bold text-uppercase text-secondary ps-4 pe-3 py-3" style="font-size:.68rem;letter-spacing:.06em">Car Model</th>
                        <th class="fw-bold text-uppercase text-secondary px-3 py-3" style="font-size:.68rem;letter-spacing:.06em">Track</th>
                        <th class="fw-bold text-uppercase text-secondary px-3 py-3 text-end" style="font-size:.68rem;letter-spacing:.06em;width:100px">Ballast</th>
                        <th class="fw-bold text-uppercase text-secondary px-3 py-3 text-end" style="font-size:.68rem;letter-spacing:.06em;width:100px">Restrictor</th>
                        <th class="fw-bold text-uppercase text-secondary px-3 py-3 d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em">Notes</th>
                        <th class="pe-4" style="width:100px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gameBops as $bop)
                    <tr style="border-bottom:1px solid #f9fafb;transition:background .1s"
                        onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''"
                        x-show="search === '' || '{{ strtolower($bop->car_model) }} {{ strtolower($bop->track ?? '') }}'.includes(search.toLowerCase())">
                        <td class="fw-bold text-dark ps-4 pe-3 py-3" style="font-size:.85rem">{{ $bop->car_model }}</td>
                        <td class="px-3 py-3" style="font-size:.82rem">
                            @if($bop->track)
                            <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.7rem">{{ $bop->track }}</span>
                            @else
                            <span class="text-secondary" style="font-size:.8rem">All tracks</span>
                            @endif
                        </td>
                        <td class="text-end fw-black px-3 py-3" style="font-size:.88rem;color:{{ $bop->ballast_kg > 0 ? '#ef4444' : ($bop->ballast_kg < 0 ? '#10b981' : '#9ca3af') }}">
                            {{ $bop->ballast_kg > 0 ? '+' : '' }}{{ $bop->ballast_kg }} kg
                        </td>
                        <td class="text-end px-3 py-3 text-secondary" style="font-size:.85rem">
                            {{ $bop->restrictor > 0 ? $bop->restrictor . '%' : '—' }}
                        </td>
                        <td class="text-secondary d-none d-md-table-cell px-3 py-3" style="font-size:.8rem;max-width:200px">
                            <span class="text-truncate d-block">{{ $bop->notes ?? '—' }}</span>
                        </td>
                        <td class="text-end pe-4 py-3">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.bops.edit', $bop) }}"
                                   class="btn btn-xs btn-outline-secondary fw-bold" style="font-size:.7rem;padding:3px 10px">Edit</a>
                                <form method="POST" action="{{ route('admin.bops.destroy', $bop) }}"
                                      onsubmit="return confirm('Delete this BOP entry?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger fw-bold" style="font-size:.7rem;padding:3px 10px">Del</button>
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

@endsection