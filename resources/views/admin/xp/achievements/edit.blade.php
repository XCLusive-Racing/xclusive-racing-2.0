@extends('layouts.admin')

@section('title', 'Edit achievement')
@section('page-title', 'Edit achievement')

@section('page-actions')
    <a href="{{ route('admin.xp.achievements.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">Back</a>
@endsection

@section('content')
<div class="admin-form-card mb-4" style="max-width:860px">
    <p class="text-secondary" style="font-size:.82rem">
        Held by {{ number_format($holders) }} {{ $holders === 1 ? 'driver' : 'drivers' }}.
        @if($achievement->is_archived) This achievement is archived: nobody can earn it any more. @endif
    </p>
    <form method="POST" action="{{ route('admin.xp.achievements.update', $achievement) }}">
        @csrf
        @method('PUT')
        @include('admin.xp.achievements._form')
        <button type="submit" class="btn fw-bold text-white mt-4" style="background:#7c3aed;font-size:.85rem">Save achievement</button>
    </form>
</div>

<div class="admin-form-card" style="max-width:860px">
    <div class="fw-black text-uppercase mb-2" style="font-size:.8rem;letter-spacing:.05em">{{ $achievement->is_archived ? 'Restore' : 'Archive' }}</div>
    <p class="text-secondary" style="font-size:.82rem">
        There is no delete. Archiving removes this achievement from the list of things still to earn.
        Drivers who already hold it keep it and keep the XP.
    </p>
    <form method="POST" action="{{ route('admin.xp.achievements.archive', $achievement) }}">
        @csrf
        <button type="submit" class="btn btn-sm fw-bold text-uppercase" style="font-size:.72rem;background:#f9fafb;color:#374151;border:1px solid #e5e7eb">
            {{ $achievement->is_archived ? 'Restore achievement' : 'Archive achievement' }}
        </button>
    </form>
</div>
@endsection
