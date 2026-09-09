@extends('layouts.admin')

@section('title', $league->name . ' — Points Schemes')
@section('page-title', $league->name . ' — Points Schemes')

@section('content')

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Points Schemes</div>
        <div class="text-secondary" style="font-size:.8rem">Copy a template, or download/upload a CSV to build your own.</div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Name</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Points</th>
                    <th class="fw-bold text-uppercase text-center d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">FL / Pole</th>
                    <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:220px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($schemes as $scheme)
                @php $drivers = $scheme->points_map['drivers'] ?? []; ksort($drivers); @endphp
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $scheme->name }}</div>
                        @if($scheme->is_template)
                        <span class="badge xcl-badge" style="background:#f3f4f6;color:#6b7280;font-size:.62rem;padding:2px 6px;border-radius:5px;font-weight:700">Template</span>
                        @endif
                        @if(!empty($scheme->points_map['teams']))
                        <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.62rem;padding:2px 6px;border-radius:5px;font-weight:700">Team points</span>
                        @endif
                    </td>
                    <td>
                        <span class="text-secondary" style="font-size:.78rem">{{ implode(', ', array_slice($drivers, 0, 8)) }}{{ count($drivers) > 8 ? '…' : '' }}</span>
                    </td>
                    <td class="text-center d-none d-md-table-cell text-secondary">{{ $scheme->fastest_lap_points }} / {{ $scheme->pole_points }}</td>
                    <td class="text-end pe-4">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.points-schemes.export', $scheme) }}" class="fw-bold" style="color:#7c3aed;font-size:.78rem">CSV</a>
                            @if($scheme->is_template)
                            <form action="{{ route('admin.leagues.points-schemes.copy', [$league, $scheme]) }}" method="POST" class="d-flex gap-1 justify-content-end">
                                @csrf
                                <input type="hidden" name="name" data-copy-name value="">
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" style="font-size:.74rem" onclick="copyScheme(this, '{{ addslashes($scheme->name) }}')">
                                    Copy
                                </button>
                            </form>
                            @else
                            <span class="text-secondary" style="font-size:.74rem">Owned</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Create From CSV</div>
    </div>
    <div class="px-4 py-4">
        <p class="text-secondary mb-3" style="font-size:.78rem">
            CSV columns: <code>position,points</code> — add a <code>team_points</code> column if you also score teams.
            Only the positions you list score points; leave out anything beyond your grid size (e.g. stop at 20 for a 20-driver grid).
        </p>
        <form action="{{ route('admin.leagues.points-schemes.store', $league) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-3">
                    <label class="form-label">Fastest Lap Points</label>
                    <input type="number" name="fastest_lap_points" value="0" min="0" class="form-control @error('fastest_lap_points') is-invalid @enderror">
                    @error('fastest_lap_points') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-3">
                    <label class="form-label">Pole Points</label>
                    <input type="number" name="pole_points" value="0" min="0" class="form-control @error('pole_points') is-invalid @enderror">
                    @error('pole_points') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">CSV File</label>
                <input type="file" name="csv" accept=".csv,text/csv" class="form-control @error('csv') is-invalid @enderror" required>
                @error('csv') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @if(auth()->user()->canManage())
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_template" id="is_template" value="1">
                <label class="form-check-label fw-bold text-dark" for="is_template" style="font-size:.82rem">
                    Save as XCL template (visible to every league, instead of just {{ $league->name }})
                </label>
            </div>
            @endif
            <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                Create Scheme
            </button>
        </form>
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
