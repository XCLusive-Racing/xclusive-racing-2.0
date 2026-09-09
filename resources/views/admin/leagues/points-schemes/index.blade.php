@extends('layouts.admin')

@section('title', $league->name . ' — Points Schemes')
@section('page-title', $league->name . ' — Points Schemes')

@section('page-actions')
    <a href="{{ route('admin.leagues.points-schemes.create', $league) }}" class="btn btn-sm fw-black text-uppercase text-white px-3" style="background:#7c3aed;font-size:.78rem">
        + New Scheme
    </a>
@endsection

@section('content')

@php
    $psPreview = function ($scheme, $count = 6) {
        $table = collect($scheme->points_table ?? [])->sortKeys();
        return $table->take($count)->map(fn ($pts, $pos) => $pos . ':' . rtrim(rtrim((string) $pts, '0'), '.'))->implode(', ');
    };
@endphp

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Your Schemes</div>
            <div class="text-secondary mt-1" style="font-size:.8rem">Created from a generator or built manually, owned by {{ $league->name }}.</div>
        </div>
    </div>

    @if($owned->isEmpty())
    <div class="p-5 text-center">
        <p class="text-secondary mb-0" style="font-size:.85rem">No schemes yet — copy a template below or create one from scratch.</p>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Name</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Type</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">First Positions</th>
                    <th class="fw-bold text-uppercase text-center d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">FL / Pole / Lead</th>
                    <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($owned as $scheme)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $scheme->name }}</div>
                        @if($scheme->isLockedByCompletedRounds())
                        <span class="badge" style="background:#fef2f2;color:#b91c1c;font-size:.62rem;padding:2px 6px;border-radius:5px;font-weight:700">Locked</span>
                        @endif
                    </td>
                    <td class="text-secondary text-capitalize">{{ $scheme->type }}</td>
                    <td><span class="text-secondary" style="font-size:.78rem">{{ $psPreview($scheme) }}{{ count($scheme->points_table ?? []) > 6 ? '…' : '' }}</span></td>
                    <td class="text-center d-none d-md-table-cell text-secondary">{{ $scheme->fastest_lap_points }} / {{ $scheme->pole_points }} / {{ $scheme->leading_lap_points }}</td>
                    <td class="text-end pe-4">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.leagues.points-schemes.edit', [$league, $scheme]) }}" class="fw-bold" style="color:#7c3aed;font-size:.78rem">Edit</a>
                            <form action="{{ route('admin.leagues.points-schemes.destroy', [$league, $scheme]) }}" method="POST" onsubmit="return false">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm fw-bold" style="background:transparent;color:#dc2626;font-size:.78rem;padding:0"
                                        onclick="xcDeleteSubmit(this.closest('form'), 'Delete {{ addslashes($scheme->name) }}?')">
                                    Delete
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

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">XCL Templates</div>
            <div class="text-secondary mt-1" style="font-size:.8rem">Read-only — copy one to create your own editable scheme. Values are modelled on real series but may not match current official regulations.</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Name</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">First Positions</th>
                    <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:140px">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $scheme)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $scheme->name }}</div>
                        @if($scheme->description)
                        <div class="text-secondary" style="font-size:.75rem">{{ $scheme->description }}</div>
                        @endif
                    </td>
                    <td><span class="text-secondary" style="font-size:.78rem">{{ $psPreview($scheme) }}{{ count($scheme->points_table ?? []) > 6 ? '…' : '' }}</span></td>
                    <td class="text-end pe-4">
                        <form action="{{ route('admin.leagues.points-schemes.copy', [$league, $scheme]) }}" method="POST" class="d-flex gap-1 justify-content-end">
                            @csrf
                            <input type="hidden" name="name" data-copy-name value="">
                            <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" style="font-size:.74rem" onclick="copyScheme(this, '{{ addslashes($scheme->name) }}')">
                                Copy
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
function copyScheme(button, templateName) {
    var name = prompt('Name for your copy of "' + templateName + '":', templateName + ' Copy');
    if (!name) return;
    var form = button.closest('form');
    form.querySelector('[data-copy-name]').value = name;
    form.submit();
}
</script>
@endpush

@endsection
