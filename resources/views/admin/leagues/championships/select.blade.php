@extends('layouts.admin')

@section('title', 'Championships')
@section('page-title', 'Championships')

@section('content')

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">Select a league</div>
            <div class="text-secondary mt-1" style="font-size:.8rem">Pick which league you're creating or managing championships for.</div>
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
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <tbody>
                @foreach($leagues as $league)
                <tr>
                    <td class="ps-4">
                        <a href="{{ route('admin.leagues.championships.index', $league) }}" class="d-flex align-items-center gap-2 text-decoration-none py-2">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $league->primary_color }};flex-shrink:0"></span>
                            <span class="fw-bold text-dark">{{ $league->name }}</span>
                        </a>
                    </td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.leagues.championships.index', $league) }}" class="fw-bold" style="color:#7c3aed;font-size:.8rem">
                            Championships →
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
