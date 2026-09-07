@extends('layouts.admin')

@section('title', 'Practice Servers')
@section('page-title', 'Practice Servers')

@section('content')

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif

<div class="admin-card p-0 overflow-hidden mb-4">
    <div class="px-4 py-3 border-bottom" style="background:#fafafa">
        <span class="fw-black text-uppercase fst-italic" style="font-size:.85rem;color:#111827">Upcoming &amp; Live</span>
    </div>
    @include('admin.practice-servers._table', ['sessions' => $upcoming, 'emptyMessage' => 'No upcoming or live practice sessions.'])
</div>

<div class="admin-card p-0 overflow-hidden">
    <div class="px-4 py-3 border-bottom" style="background:#fafafa">
        <span class="fw-black text-uppercase fst-italic" style="font-size:.85rem;color:#111827">Recent</span>
    </div>
    @include('admin.practice-servers._table', ['sessions' => $recent, 'emptyMessage' => 'No recent practice sessions.'])
</div>

@endsection
