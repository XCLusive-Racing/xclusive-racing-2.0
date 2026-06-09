@extends('layouts.admin')

@section('title', 'Add BOP Entry')
@section('page-title', 'Add BOP Entry')

@section('page-actions')
    <a href="{{ route('admin.bops.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">← Back</a>
@endsection

@section('content')
<div class="admin-card" style="max-width:600px">

    <div class="px-4 py-3 border-bottom d-flex align-items-center gap-3" style="background:#fafafa;border-radius:inherit">
        <div style="width:34px;height:34px;background:#7c3aed15;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="15" height="15" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
        </div>
        <div>
            <div class="fw-black text-dark" style="font-size:.88rem">New BOP Entry</div>
            <div class="text-secondary" style="font-size:.72rem">Add a single car balance adjustment</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.bops.store') }}">
        @csrf
        @include('admin.bops._form')
        <div class="px-4 pb-4 d-flex gap-2">
            <button type="submit" class="btn fw-black text-white text-uppercase" style="background:#7c3aed;font-size:.8rem;letter-spacing:.04em">
                Save BOP Entry
            </button>
            <a href="{{ route('admin.bops.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase" style="font-size:.8rem">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection