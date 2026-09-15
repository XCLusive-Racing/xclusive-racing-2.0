@extends('layouts.admin')

@section('title', 'Add FTP Server')
@section('page-title', 'Add FTP Server')

@section('page-actions')
    <a href="{{ route('admin.servers.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<form action="{{ route('admin.servers.store') }}" method="POST">
    @csrf

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-7">

            <div class="admin-card mb-4">
                @include('admin.servers._add-server-fields')
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Add Server
                </button>
                <a href="{{ route('admin.servers.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>

        </div>
    </div>
</form>

@endsection