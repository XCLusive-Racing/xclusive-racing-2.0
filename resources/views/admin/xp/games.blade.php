@extends('layouts.admin')

@section('title', 'XP games')
@section('page-title', 'Games')

@section('content')

@include('admin.xp._nav')

<div class="admin-card mb-4">
    <div class="px-4 py-3 text-secondary" style="font-size:.85rem">
        The titles XP is earned in. Every title feeds the same XP total and the same rank.
        A new title needs its own ladder of achievements in the Per Title category, scoped to that title.
        It does not move the rank curve: the curve is fitted to the title agnostic catalogue only.
        A title's key cannot be changed or removed once added, because awards and XP refer to it.
    </div>
</div>

<div class="admin-card p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.85rem">
            <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                <tr>
                    <th class="fw-bold text-uppercase ps-4 py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Key</th>
                    <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Name</th>
                    <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:110px">Order</th>
                    <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Active</th>
                    <th class="fw-bold text-uppercase py-3 text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Own achievements</th>
                    <th class="pe-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                @php $own = $achievements[$game->key] ?? null; @endphp
                <tr style="border-bottom:1px solid #f9fafb">
                    <td class="ps-4"><code>{{ $game->key }}</code></td>
                    <td>
                        <input type="text" name="name" form="game-{{ $game->id }}" value="{{ $game->name }}" required maxlength="255" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="number" name="sort_order" form="game-{{ $game->id }}" value="{{ $game->sort_order }}" min="0" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="checkbox" name="is_active" value="1" form="game-{{ $game->id }}" @checked($game->is_active)
                               style="width:16px;height:16px;accent-color:#7c3aed">
                    </td>
                    <td class="text-end text-secondary" style="white-space:nowrap">
                        {{ $own ? number_format($own->n).' ('.number_format($own->xp).' XP)' : 'None yet' }}
                    </td>
                    <td class="pe-4 text-end">
                        <form id="game-{{ $game->id }}" method="POST" action="{{ route('admin.xp.games.update', $game) }}">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.68rem;padding:4px 10px">Save</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="admin-form-card" style="max-width:640px">
    <div class="fw-black text-uppercase mb-3" style="font-size:.8rem;letter-spacing:.05em">Add a title</div>
    <form method="POST" action="{{ route('admin.xp.games.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Key</label>
                <input type="text" name="key" value="{{ old('key') }}" required maxlength="20" placeholder="rf2"
                       class="form-control @error('key') is-invalid @enderror">
                @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-5">
                <label class="form-label">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                       class="form-control @error('name') is-invalid @enderror">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $games->max('sort_order') + 1) }}" min="0" class="form-control">
            </div>
        </div>
        <div class="form-text mt-2">
            The key is what a race's game is matched on. Races only earn XP in a title once the site records results for it.
        </div>
        <button type="submit" class="btn fw-bold text-white mt-3" style="background:#7c3aed;font-size:.85rem">Add title</button>
    </form>
</div>
@endsection
