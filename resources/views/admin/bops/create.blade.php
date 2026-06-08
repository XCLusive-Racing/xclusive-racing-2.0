@extends('layouts.admin')

@section('title', 'Add BOP Entry')
@section('page-title', 'Add BOP Entry')

@section('page-actions')
    <a href="{{ route('admin.bops.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">← Back</a>
@endsection

@section('content')
<div class="admin-card" style="max-width:560px">
    <form method="POST" action="{{ route('admin.bops.store') }}">
        @csrf
        @include('admin.bops._form')
        <button type="submit" class="btn fw-bold text-white mt-2" style="background:#7c3aed;font-size:.85rem">Save BOP Entry</button>
    </form>
</div>
@endsection