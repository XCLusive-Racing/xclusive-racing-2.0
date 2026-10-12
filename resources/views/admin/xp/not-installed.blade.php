@extends('layouts.admin')

@section('title', 'XP & Achievements')
@section('page-title', 'XP & Achievements')

@section('content')

<div class="admin-card">
    <div class="admin-card-header">
        <span class="fw-black text-uppercase" style="font-size:.8rem;letter-spacing:.05em">Not installed yet</span>
    </div>
    <div class="px-4 py-3 text-secondary" style="font-size:.85rem">
        <p>
            The XP tables do not exist in this database yet, so there is nothing to show.
            They are created, and the achievements and the rank curve are filled in, by the database migrations.
        </p>
        <p class="mb-0">
            Run <code>php artisan migrate</code> on the server once. Nothing is awarded by doing that:
            XP stays switched off until <code>XP_ENABLED</code> and <code>XP_LAUNCH_AT</code> are set.
        </p>
    </div>
</div>

@endsection
