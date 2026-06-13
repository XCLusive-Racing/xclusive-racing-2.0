@extends('layouts.admin')

@section('title', 'Report #' . $report->id)
@section('page-title', 'Report #' . $report->id)

@section('page-actions')
    <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">← Back</a>
@endsection

@section('content')

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif

@php $meta = $report->statusMeta(); @endphp

<div class="row g-4">

    {{-- Report details --}}
    <div class="col-12 col-lg-7">
        <div class="admin-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="fw-black text-uppercase mb-0" style="font-size:.78rem;letter-spacing:.06em">Incident Details</h6>
                <span class="badge fw-bold text-white" style="background:{{ $meta['color'] }};font-size:.72rem">{{ $meta['label'] }}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Reporter</div>
                    <div class="fw-bold mt-1">{{ $report->user->name ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Reported Driver</div>
                    <div class="fw-bold mt-1">{{ $report->reported_driver_name }}</div>
                </div>
                <div class="col-sm-6">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Race</div>
                    <div class="mt-1">{{ $report->race?->title ?? '—' }}</div>
                </div>
                <div class="col-sm-3">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Lap</div>
                    <div class="mt-1">{{ $report->lap_number ?? '—' }}</div>
                </div>
                <div class="col-sm-3">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Corner</div>
                    <div class="mt-1">{{ $report->incident_corner ?? '—' }}</div>
                </div>
            </div>

            <div class="mb-4">
                <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af" class="mb-2">Description</div>
                <div class="p-3 rounded-2" style="background:#f9fafb;font-size:.85rem;line-height:1.6;border:1px solid #f3f4f6">
                    {{ $report->description }}
                </div>
            </div>

            @if($report->video_url)
            <div>
                <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af" class="mb-2">Evidence</div>
                <a href="{{ $report->video_url }}" target="_blank" rel="noopener"
                   class="btn btn-sm btn-outline-secondary fw-bold" style="font-size:.78rem">
                    View Video / Clip →
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Steward action --}}
    <div class="col-12 col-lg-5">
        <div class="admin-card">
            <h6 class="fw-black text-uppercase mb-4" style="font-size:.78rem;letter-spacing:.06em">Steward Decision</h6>

            <form method="POST" action="{{ route('admin.reports.status', $report) }}">
                @csrf @method('PATCH')

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.8rem">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        @foreach(\App\Models\Report::statuses() as $key => $s)
                        <option value="{{ $key }}" {{ $report->status === $key ? 'selected' : '' }}>{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold" style="font-size:.8rem">Steward Notes</label>
                    <textarea name="admin_notes" rows="4"
                              class="form-control form-control-sm"
                              placeholder="Decision, penalty, explanation...">{{ $report->admin_notes }}</textarea>
                </div>

                <button type="submit" class="btn fw-bold text-white w-100" style="background:#7c3aed;font-size:.85rem">
                    Save Decision
                </button>
            </form>

            @if($report->reviewed_by)
            <div class="mt-3 pt-3 border-top text-secondary" style="font-size:.75rem">
                Reviewed by <span class="fw-bold">{{ $report->reviewer->name ?? '—' }}</span>
            </div>
            @endif
        </div>
    </div>

</div>

@endsection