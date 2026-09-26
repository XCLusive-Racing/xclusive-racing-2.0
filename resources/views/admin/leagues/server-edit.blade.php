@extends('layouts.admin')

@section('title', 'Edit Server')
@section('page-title', 'Edit Server — ' . $league->name)

@section('page-actions')
    <a href="{{ route('admin.leagues.edit', $league) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        &larr; Back
    </a>
@endsection

@section('content')

<form action="{{ route('admin.leagues.servers.update', [$league, $server]) }}" method="POST">
    @csrf @method('PUT')

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-7">

            <div class="admin-card mb-4">
                @include('admin.servers._add-server-fields', ['editServer' => $server])
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Save Server
                </button>
                <a href="{{ route('admin.leagues.edit', $league) }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>

        </div>
    </div>
</form>

@endsection
