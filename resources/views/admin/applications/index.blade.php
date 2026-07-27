@extends('layouts.admin')

@section('title', 'Team Applications')
@section('page-title', 'Team Applications')

@section('content')

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif

<div class="admin-card p-0 overflow-hidden">
    @if($applications->isEmpty())
    <p class="text-secondary text-center py-5 mb-0" style="font-size:.85rem">No applications submitted yet.</p>
    @else
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.83rem">
            <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                <tr>
                    <th class="fw-bold text-uppercase text-secondary ps-4 py-3" style="font-size:.68rem;letter-spacing:.06em">Name</th>
                    <th class="fw-bold text-uppercase text-secondary py-3" style="font-size:.68rem;letter-spacing:.06em">Role</th>
                    <th class="fw-bold text-uppercase text-secondary py-3 d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em">Email</th>
                    <th class="fw-bold text-uppercase text-secondary py-3 d-none d-lg-table-cell" style="font-size:.68rem;letter-spacing:.06em">Discord</th>
                    <th class="fw-bold text-uppercase text-secondary py-3 d-none d-lg-table-cell" style="font-size:.68rem;letter-spacing:.06em">Date</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $application)
                <tr style="border-bottom:1px solid #f9fafb;transition:background .12s"
                    onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td class="ps-4 fw-bold text-dark">{{ $application->name }}</td>
                    <td style="color:#374151">{{ $application->role_label }}</td>
                    <td class="text-secondary d-none d-md-table-cell" style="font-size:.78rem">{{ $application->email }}</td>
                    <td class="text-secondary d-none d-lg-table-cell" style="font-size:.78rem">{{ $application->discord ?: '—' }}</td>
                    <td class="text-secondary d-none d-lg-table-cell" style="font-size:.78rem">{{ $application->created_at->format('d M Y') }}</td>
                    <td class="text-end pe-3">
                        <a href="{{ route('admin.applications.show', $application) }}"
                           class="btn btn-xs btn-outline-secondary fw-bold" style="font-size:.7rem;padding:2px 8px">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
