@extends('layouts.admin')
@section('title', 'Add Event Format')
@section('page-title', 'Add Event Format')
@section('page-actions')
    <a href="{{ route('admin.event-formats.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">← Back</a>
@endsection
@section('content')
<form action="{{ route('admin.event-formats.store') }}" method="POST">
    @csrf
    @include('admin.event-formats._form')
    <div class="d-flex gap-2">
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Create Format</button>
        <a href="{{ route('admin.event-formats.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">Cancel</a>
    </div>
</form>
@endsection
