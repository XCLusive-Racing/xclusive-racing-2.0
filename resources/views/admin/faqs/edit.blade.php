@extends('layouts.admin')

@section('title', 'Edit FAQ')
@section('page-title', 'Edit FAQ')

@section('page-actions')
    <a href="{{ route('admin.faqs.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">← Back</a>
@endsection

@section('content')
<div class="admin-card" style="max-width:640px">
    <div class="px-4 py-4">
        <form method="POST" action="{{ route('admin.faqs.update', $faq) }}">
            @csrf @method('PUT')
            @include('admin.faqs._form')
            <button type="submit" class="btn fw-bold text-white mt-2" style="background:#7c3aed;font-size:.85rem">Update FAQ</button>
        </form>
    </div>
</div>
@endsection
