@extends('layouts.admin')

@section('title', 'Add achievement')
@section('page-title', 'Add achievement')

@section('page-actions')
    <a href="{{ route('admin.xp.achievements.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">Back</a>
@endsection

@section('content')
<div class="admin-form-card" style="max-width:860px">
    <form method="POST" action="{{ route('admin.xp.achievements.store') }}">
        @csrf
        @include('admin.xp.achievements._form')
        <button type="submit" class="btn fw-bold text-white mt-4" style="background:#7c3aed;font-size:.85rem">Add achievement</button>
    </form>
</div>
@endsection
