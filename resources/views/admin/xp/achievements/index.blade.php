@extends('layouts.admin')

@section('title', 'Achievements')
@section('page-title', 'Achievements')

@section('page-actions')
    <a href="{{ route('admin.xp.achievements.create') }}" class="btn btn-sm fw-black text-uppercase text-white" style="background:#7c3aed;font-size:.78rem">
        + Add achievement
    </a>
@endsection

@section('content')

@include('admin.xp._nav')
@include('admin.xp._health')

{{-- Filters --}}
<div class="admin-card mb-4">
    <form method="GET" action="{{ route('admin.xp.achievements.index') }}" class="px-4 py-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label fw-bold text-uppercase text-secondary mb-1" style="font-size:.68rem;letter-spacing:.05em">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-bold text-uppercase text-secondary mb-1" style="font-size:.68rem;letter-spacing:.05em">Game scope</label>
                <select name="scope" class="form-select form-select-sm">
                    <option value="">Any scope</option>
                    @foreach($scopes as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['scope'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-bold text-uppercase text-secondary mb-1" style="font-size:.68rem;letter-spacing:.05em">Family</label>
                <select name="family" class="form-select form-select-sm">
                    <option value="">All families</option>
                    @foreach($families as $family)
                    <option value="{{ $family }}" @selected(($filters['family'] ?? '') === $family)>{{ $family }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-bold text-uppercase text-secondary mb-1" style="font-size:.68rem;letter-spacing:.05em">Archived</label>
                <select name="archived" class="form-select form-select-sm">
                    <option value="no" @selected($filters['archived'] === 'no')>Active only</option>
                    <option value="yes" @selected($filters['archived'] === 'yes')>Archived only</option>
                    <option value="all" @selected($filters['archived'] === 'all')>Both</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm fw-bold text-uppercase text-white flex-grow-1" style="background:#111827;font-size:.72rem">Filter</button>
                <a href="{{ route('admin.xp.achievements.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.72rem">Reset</a>
            </div>
        </div>
    </form>
</div>

<div class="admin-card p-0 overflow-hidden">
    <div class="px-4 py-3 text-secondary" style="font-size:.8rem;border-bottom:1px solid #f3f4f6">
        {{ number_format($achievements->total()) }} {{ $achievements->total() === 1 ? 'achievement' : 'achievements' }}.
        An achievement is never deleted. Archiving takes it off the list of things still to earn:
        drivers who already hold it keep it and keep the XP.
    </div>

    @if($achievements->isEmpty())
    <p class="text-secondary text-center py-4 mb-0" style="font-size:.85rem">No achievements match these filters.</p>
    @else
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.85rem">
            <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                <tr>
                    <th class="fw-bold text-uppercase ps-4 py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Achievement</th>
                    <th class="fw-bold text-uppercase py-3 d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Category</th>
                    <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Scope</th>
                    <th class="fw-bold text-uppercase py-3 d-none d-lg-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Metric</th>
                    <th class="fw-bold text-uppercase py-3 text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">XP</th>
                    <th class="fw-bold text-uppercase py-3 text-end d-none d-sm-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Held by</th>
                    <th class="pe-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($achievements as $achievement)
                @php $metric = $metrics[$achievement->metric_key] ?? null; @endphp
                <tr style="border-bottom:1px solid #f9fafb;{{ $achievement->is_archived ? 'opacity:.6' : '' }}">
                    <td class="ps-4">
                        <div class="fw-bold text-dark">
                            {{ $achievement->name }}
                            @if($achievement->is_archived)
                            <span class="status-badge status-finished ms-1">Archived</span>
                            @endif
                        </div>
                        <div class="text-secondary" style="font-size:.76rem">{{ $achievement->description }}</div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="badge fw-bold" style="background:#f3e8ff;color:#7c3aed;font-size:.68rem;padding:3px 8px;border-radius:6px">{{ $achievement->category }}</span>
                    </td>
                    <td class="text-secondary" style="font-size:.78rem;white-space:nowrap">{{ $scopes[$achievement->game_scope] ?? $achievement->game_scope }}</td>
                    <td class="d-none d-lg-table-cell" style="font-size:.76rem">
                        <code>{{ $achievement->metric_key }}</code>
                        {{ $achievement->comparison === 'lte' ? 'at most' : 'at least' }} {{ rtrim(rtrim(number_format($achievement->threshold, 2), '0'), '.') }}
                        @if(! $metric)
                        <div class="fw-bold" style="color:#dc2626">Not in the registry: never awarded</div>
                        @elseif(! $metric->countable())
                        <div class="fw-bold" style="color:#b45309" title="{{ $metric->reason() }}">Not countable yet</div>
                        @endif
                    </td>
                    <td class="text-end fw-black">{{ number_format($achievement->xp) }}</td>
                    <td class="text-end d-none d-sm-table-cell">{{ number_format($achievement->awards_count) }}</td>
                    <td class="pe-4 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.xp.achievements.edit', $achievement) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase"
                               style="font-size:.68rem;padding:4px 10px">Edit</a>
                            <form action="{{ route('admin.xp.achievements.archive', $achievement) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                                        style="font-size:.68rem;padding:4px 10px;background:#f9fafb;color:#374151;border:1px solid #e5e7eb">
                                    {{ $achievement->is_archived ? 'Restore' : 'Archive' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($achievements->hasPages())
    <div class="px-4 py-3">{{ $achievements->links() }}</div>
    @endif
    @endif
</div>
@endsection
