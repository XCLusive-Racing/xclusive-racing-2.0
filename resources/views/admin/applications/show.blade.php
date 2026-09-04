@extends('layouts.admin')

@section('title', 'Application - ' . $application->name)
@section('page-title', 'Application - ' . $application->name)

@section('page-actions')
    <a href="{{ route('admin.applications.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">← Back</a>
    <form action="{{ route('admin.applications.send-to-inbox', $application) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm fw-bold text-uppercase text-white" style="background:#7c3aed;font-size:.78rem"
                title="Send to info@xclusiveracing.com">Send to Inbox</button>
    </form>
@endsection

@section('content')

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#dc2626">{{ session('error') }}</div>
@endif

<div class="row g-4">

    <div class="col-12 col-lg-7">
        <div class="admin-card">
            <h6 class="fw-black text-uppercase mb-4" style="font-size:.78rem;letter-spacing:.06em">Application Details</h6>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Name</div>
                    <div class="fw-bold mt-1">{{ $application->name }}</div>
                </div>
                <div class="col-sm-6">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Role</div>
                    <div class="fw-bold mt-1">{{ $application->role_label }}</div>
                </div>
                <div class="col-sm-6">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Email</div>
                    <div class="mt-1"><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></div>
                </div>
                <div class="col-sm-6">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Discord</div>
                    <div class="mt-1">{{ $application->discord ?: '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Platform(s)</div>
                    <div class="mt-1">{{ $application->platforms ? implode(', ', $application->platforms) : '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Submitted</div>
                    <div class="mt-1">{{ $application->created_at->format('d M Y, H:i') }}</div>
                </div>
            </div>

            <div>
                <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af" class="mb-2">Motivation</div>
                <div class="p-3 rounded-2" style="background:#f9fafb;font-size:.85rem;line-height:1.6;border:1px solid #f3f4f6;white-space:pre-line">{{ $application->motivation }}</div>
            </div>
        </div>
    </div>

</div>

@endsection
