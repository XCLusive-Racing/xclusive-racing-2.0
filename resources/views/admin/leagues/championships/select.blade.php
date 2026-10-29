@extends('layouts.admin')

{{-- Shared league picker: Championships (default) and Race Formats (SessionFormatController::selectLeague()). --}}
@php
    $title ??= 'Championships';
    $intro ??= "Pick which league you're creating or managing championships for.";
    $targetRoute ??= 'admin.leagues.championships.index';
    $linkLabel ??= 'Championships →';
@endphp

@section('title', $title)
@section('page-title', $title)

@section('content')

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Select a league</div>
            <div class="text-secondary mt-1" style="font-size:.8rem">{{ $intro }}</div>
        </div>
        <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
            {{ $leagues->count() }} {{ Str::plural('league', $leagues->count()) }}
        </span>
    </div>

    @if($leagues->isEmpty())
    <div class="p-5 text-center">
        <div style="font-size:2.5rem;margin-bottom:.75rem">🏁</div>
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">No leagues yet</div>
        <div class="text-secondary mt-2 mb-4" style="font-size:.82rem">You have not been added to a league yet.</div>
    </div>
    @else
    <div class="cw-pick-grid">
        @foreach($leagues as $league)
        <a href="{{ route($targetRoute, $league) }}" class="cw-pick" @if($league->primary_color) style="--pick-color:{{ $league->primary_color }}" @endif>
            <span class="cw-pick__logo">
                @if($league->logo_url)
                <img src="{{ $league->logo_url }}" alt="">
                @else
                {{ Str::upper(Str::substr($league->name, 0, 1)) }}
                @endif
            </span>
            <span class="cw-pick__name text-truncate">{{ $league->name }}</span>
            <span class="cw-pick__go">{{ $linkLabel }}</span>
        </a>
        @endforeach
    </div>
    @endif
</div>

@endsection
