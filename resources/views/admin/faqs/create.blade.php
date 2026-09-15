@extends('layouts.admin')

@section('title', 'Add FAQ')
@section('page-title', 'Add FAQ')

@section('page-actions')
    <a href="{{ route('admin.faqs.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">← Back</a>
@endsection

@section('content')
<div class="admin-card" style="max-width:640px">
    <div class="px-4 py-4">
        <form method="POST" action="{{ route('admin.faqs.store') }}">
            @csrf
            @include('admin.faqs._form')
            <button type="submit" class="btn fw-bold text-white mt-2" style="background:#7c3aed;font-size:.85rem">Save FAQ</button>
        </form>
    </div>
</div>
@endsection
