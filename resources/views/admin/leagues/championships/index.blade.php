@extends('layouts.admin')

@section('title', $league->name . ' · Championships')
@section('page-title', $league->name . ' · Championships')

@section('page-actions')
    {{-- A league admin edits championships; only the manager creates or deletes them. --}}
    @can('create', [\App\Models\Championship::class, $league])
    <form action="{{ route('admin.leagues.championships.store', $league) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-sm fw-black text-uppercase text-white px-3" style="background:#7c3aed;font-size:.78rem">
            + New Championship
        </button>
    </form>
    @endcan
@endsection

@section('content')

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">{{ $league->name }} Championships</div>
        </div>
        <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
            {{ $championships->count() }} {{ Str::plural('championship', $championships->count()) }}
        </span>
    </div>

    @if($championships->isEmpty())
    <div class="p-5 text-center">
        <div style="font-size:2.5rem;margin-bottom:.75rem">🏆</div>
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">No championships yet</div>
        <div class="text-secondary mt-2 mb-4" style="font-size:.82rem">Start the setup wizard to configure your first one.</div>
    </div>
    @else
    <div class="cw-champ-grid">
        @foreach($championships as $c)
        @php $sc = $c->statusColor(); @endphp
        <div class="cw-champ" style="--champ-color:{{ $sc }}">
            <a href="{{ route('admin.leagues.championships.wizard', [$league, $c, 'basics']) }}" class="cw-champ__head">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div style="min-width:0">
                        <div class="cw-champ__name">{{ $c->name }}</div>
                        @if($c->tagline)
                        <div class="cw-champ__tagline">{{ $c->tagline }}</div>
                        @endif
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <span class="cw-champ__status">{{ $c->statusLabel() }}</span>
                        @if($c->visibility === 'unlisted')
                        <span class="cw-champ__status" style="--champ-color:#92400e">Hidden</span>
                        @endif
                    </div>
                </div>
                <div class="cw-champ__meta">
                    <span>{{ $c->gameLabel() }}</span>
                    <span>{{ $c->rounds_count }} {{ Str::plural('round', $c->rounds_count) }}</span>
                    <span>{{ $c->entries_count }} {{ Str::plural('entry', $c->entries_count) }}</span>
                    <span>XCL-R {{ $c->xcl_rating_enabled ? 'on' : 'off' }}</span>
                </div>
            </a>
            <div class="cw-champ__actions">
                <a href="{{ route('admin.leagues.championships.wizard', [$league, $c, 'basics']) }}" class="cw-btn-sm cw-btn-sm--primary">Edit</a>
                <a href="{{ route('admin.leagues.championships.entries.index', [$league, $c]) }}" class="cw-btn-sm">Entries</a>
                @can('create', [\App\Models\Championship::class, $league])
                <a href="{{ route('admin.leagues.championships.duplicate', [$league, $c]) }}" class="cw-btn-sm">Duplicate</a>
                @endcan
                @can('delete', $c)
                <form action="{{ route('admin.leagues.championships.destroy', [$league, $c]) }}" method="POST" class="m-0 ms-auto" onsubmit="return false">
                    @csrf @method('DELETE')
                    <button type="button" class="cw-btn-sm cw-btn-sm--danger"
                            onclick="xcDeleteSubmit(this.closest('form'), 'Remove {{ addslashes($c->name) }}? Its rounds, registrations and results are kept but the championship itself will no longer be listed.')">
                        Remove
                    </button>
                </form>
                @endcan
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
