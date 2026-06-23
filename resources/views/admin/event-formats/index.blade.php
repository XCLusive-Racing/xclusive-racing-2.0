@extends('layouts.admin')

@section('title', 'Event Formats')
@section('page-title', 'Event Formats')

@section('page-actions')
    <a href="{{ route('admin.event-formats.create') }}"
       class="btn btn-sm fw-black text-uppercase text-white" style="background:#7c3aed;font-size:.78rem">
        + Add Format
    </a>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

@php
    $games = ['acc' => 'ACC Console', 'lmu' => 'Le Mans Ultimate', 'iracing' => 'iRacing', 'ac' => 'AC Rally'];
@endphp

@foreach($games as $gameKey => $gameLabel)
    @php $gameFormats = $formats->where('game', $gameKey); @endphp
    @if($gameFormats->isNotEmpty())
    <div class="admin-form-card mb-4">
        <div class="px-4 pt-4 pb-3">
            <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">{{ $gameLabel }}</p>

            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.82rem">
                    <thead>
                        <tr style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af">
                            <th>Name</th>
                            <th class="text-center">P</th>
                            <th class="text-center">Q</th>
                            <th class="text-center">R1</th>
                            <th class="text-center">Q2</th>
                            <th class="text-center">R2</th>
                            <th>Pitstop</th>
                            <th class="text-center">XCL-R</th>
                            <th class="text-center">Server</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gameFormats->sortBy('sort_order') as $fmt)
                        <tr>
                            <td class="fw-bold">{{ $fmt->name }}</td>
                            <td class="text-center text-secondary">{{ $fmt->practice_mins }}'</td>
                            <td class="text-center text-secondary">{{ $fmt->quali_mins }}'</td>
                            <td class="text-center text-secondary">{{ $fmt->race1_mins }}'</td>
                            <td class="text-center text-secondary">{{ $fmt->quali2_mins ? $fmt->quali2_mins."'" : '—' }}</td>
                            <td class="text-center text-secondary">{{ $fmt->race2_mins ? $fmt->race2_mins."'" : '—' }}</td>
                            <td class="text-secondary">{{ $fmt->pitstopLabel() }}</td>
                            <td class="text-center">
                                <span class="fw-bold" style="color:#7c3aed">{{ $fmt->xclRLabel() }}</span>
                            </td>
                            <td class="text-center text-secondary">{{ $fmt->server_preference ?? '—' }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.event-formats.edit', $fmt) }}"
                                       class="btn btn-sm fw-bold text-uppercase"
                                       style="font-size:.68rem;padding:2px 8px;background:rgba(124,58,237,.08);color:#7c3aed;border:1px solid rgba(124,58,237,.2)">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.event-formats.destroy', $fmt) }}" method="POST"
                                          onsubmit="return confirm('Delete «{{ $fmt->name }}»?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                                                style="font-size:.68rem;padding:2px 8px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca">
                                            Del
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endforeach

@if($formats->isEmpty())
    <div class="admin-form-card text-center py-5">
        <p class="text-secondary mb-3">No formats yet.</p>
        <a href="{{ route('admin.event-formats.create') }}" class="btn fw-black text-uppercase text-white" style="background:#7c3aed">
            + Add First Format
        </a>
    </div>
@endif
@endsection
