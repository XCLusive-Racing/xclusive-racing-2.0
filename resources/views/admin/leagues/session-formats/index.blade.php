@extends('layouts.admin')

@section('title', $league->name . ' — Race Formats')
@section('page-title', $league->name . ' — Race Formats')

@section('page-actions')
    <a href="{{ route('admin.leagues.session-formats.create', $league) }}" class="btn btn-sm fw-black text-uppercase text-white px-3" style="background:#7c3aed;font-size:.78rem">
        + New Format
    </a>
@endsection

@section('content')

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Your Race Formats</div>
            <div class="text-secondary mt-1" style="font-size:.8rem">
                Session lengths, number of races and pit/tyre rules — pick one per round on Add/Edit Round to fill in that round's settings.
                Changing a format later doesn't change rounds already set up with it.
            </div>
        </div>
    </div>

    @if($formats->isEmpty())
    <div class="p-5 text-center">
        <p class="text-secondary mb-0" style="font-size:.85rem">No formats yet — create one to reuse across rounds.</p>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Name</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Sessions</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Pits / Tyres</th>
                    <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($formats as $format)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $format->name }}</div>
                        @if($format->description)
                        <div class="text-secondary" style="font-size:.75rem">{{ $format->description }}</div>
                        @endif
                    </td>
                    <td class="text-secondary">{{ $format->summary() }}</td>
                    <td class="text-secondary d-none d-md-table-cell">
                        {{ $format->pitstop_count ? $format->pitstop_count . ' stop' . ($format->pitstop_count > 1 ? 's' : '') : 'No stops' }}{{ $format->fixed_stop_time ? ' (25s)' : '' }}
                        · {{ $format->tyre_set_count ? $format->tyre_set_count . ' tyre sets' : 'Unlimited tyres' }}
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.leagues.session-formats.edit', [$league, $format]) }}" class="fw-bold" style="color:#7c3aed;font-size:.78rem">Edit</a>
                            <form action="{{ route('admin.leagues.session-formats.destroy', [$league, $format]) }}" method="POST" onsubmit="return false">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm fw-bold" style="background:transparent;color:#dc2626;font-size:.78rem;padding:0"
                                        onclick="xcDeleteSubmit(this.closest('form'), 'Delete {{ addslashes($format->name) }}?')">
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

@endsection
