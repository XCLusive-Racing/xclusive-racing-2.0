@extends('layouts.admin')

@section('title', 'BOP Management')
@section('page-title', 'Balance of Performance')

@section('page-actions')
    <a href="{{ route('admin.bops.create') }}" class="btn btn-sm fw-bold text-uppercase text-white" style="background:#7c3aed;font-size:.78rem">
        + Add BOP
    </a>
@endsection

@section('content')

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif
@if(session('import_error'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#ef4444">{{ session('import_error') }}</div>
@endif

{{-- JSON Import --}}
<div class="admin-card mb-4" x-data="{ open: {{ session('import_error') ? 'true' : 'false' }} }">
    <div class="d-flex align-items-center justify-content-between px-4 py-3" style="cursor:pointer" @click="open = !open">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.82rem">Import via JSON</div>
            <div class="text-secondary mt-1" style="font-size:.72rem">Upload a JSON file to bulk-create or update BOP entries.</div>
        </div>
        <svg width="16" height="16" fill="none" stroke="#9ca3af" stroke-width="2.5" viewBox="0 0 24 24"
             :style="open ? 'transform:rotate(180deg);transition:.2s' : 'transition:.2s'">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>

    <div x-show="open" x-cloak style="border-top:1px solid #f3f4f6">
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

@foreach($games as $gameKey => $gameLabel)
@php $gameBops = $bops->get($gameKey, collect()); @endphp
<div class="admin-card mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-black text-uppercase mb-0" style="font-size:.8rem;letter-spacing:.06em">{{ $gameLabel }}</h6>
        <span class="text-secondary" style="font-size:.75rem">{{ $gameBops->count() }} entries</span>
    </div>

    @if($gameBops->isEmpty())
    <p class="text-secondary mb-0" style="font-size:.82rem">No BOP entries for {{ $gameLabel }} yet.</p>
    @else
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.83rem">
            <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                <tr>
                    <th class="fw-bold text-uppercase text-secondary py-2" style="font-size:.68rem;letter-spacing:.06em">Car Model</th>
                    <th class="fw-bold text-uppercase text-secondary py-2" style="font-size:.68rem;letter-spacing:.06em">Track</th>
                    <th class="fw-bold text-uppercase text-secondary py-2 text-end" style="font-size:.68rem;letter-spacing:.06em">Ballast</th>
                    <th class="fw-bold text-uppercase text-secondary py-2 text-end" style="font-size:.68rem;letter-spacing:.06em">Restrictor</th>
                    <th class="fw-bold text-uppercase text-secondary py-2 d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em">Notes</th>
                    <th style="width:90px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($gameBops as $bop)
                <tr style="border-bottom:1px solid #f9fafb">
                    <td class="fw-bold text-dark">{{ $bop->car_model }}</td>
                    <td class="text-secondary">{{ $bop->track ?? 'All tracks' }}</td>
                    <td class="text-end fw-bold" style="color:{{ $bop->ballast_kg > 0 ? '#ef4444' : ($bop->ballast_kg < 0 ? '#10b981' : '#374151') }}">
                        {{ $bop->ballast_kg > 0 ? '+' : '' }}{{ $bop->ballast_kg }} kg
                    </td>
                    <td class="text-end text-secondary">{{ $bop->restrictor > 0 ? $bop->restrictor . '%' : '—' }}</td>
                    <td class="text-secondary d-none d-md-table-cell" style="max-width:200px">
                        <span class="text-truncate d-block">{{ $bop->notes ?? '—' }}</span>
                    </td>
                    <td class="text-end pe-2">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('admin.bops.edit', $bop) }}"
                               class="btn btn-xs btn-outline-secondary fw-bold" style="font-size:.7rem;padding:2px 8px">Edit</a>
                            <form method="POST" action="{{ route('admin.bops.destroy', $bop) }}"
                                  onsubmit="return confirm('Delete this BOP entry?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger fw-bold" style="font-size:.7rem;padding:2px 8px">Del</button>
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
@endforeach

@endsection