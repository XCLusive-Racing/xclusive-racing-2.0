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