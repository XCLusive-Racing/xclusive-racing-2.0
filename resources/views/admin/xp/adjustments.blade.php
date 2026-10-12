@extends('layouts.admin')

@section('title', 'XP adjustments')
@section('page-title', 'XP adjustments')

@section('content')

@include('admin.xp._nav')

<div class="admin-form-card mb-4" style="max-width:640px">
    <div class="fw-black text-uppercase mb-2" style="font-size:.8rem;letter-spacing:.05em">Grant XP</div>
    <p class="text-secondary" style="font-size:.82rem">
        Adds XP to a driver's total, with the reason kept in their XP history.
        XP can only be added. There is no way to take XP away, by design: a driver's total never goes down.
    </p>
    <form method="POST" action="{{ route('admin.xp.adjustments.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Driver</label>
                <input type="text" name="driver" value="{{ old('driver') }}" required placeholder="Account ID, email address or exact account name"
                       class="form-control @error('driver') is-invalid @enderror">
                @error('driver')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">XP</label>
                <input type="number" name="xp" value="{{ old('xp') }}" required min="1" max="1000000" step="1"
                       class="form-control @error('xp') is-invalid @enderror">
                @error('xp')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" value="{{ old('reason') }}" required minlength="5" maxlength="255"
                       class="form-control @error('reason') is-invalid @enderror">
                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <button type="submit" class="btn fw-bold text-white mt-3" style="background:#7c3aed;font-size:.85rem">Grant XP</button>
    </form>
</div>

<div class="admin-card p-0 overflow-hidden">
    <div class="admin-card-header">
        <span class="fw-black text-uppercase" style="font-size:.8rem;letter-spacing:.05em">Latest adjustments</span>
    </div>
    @if($adjustments->isEmpty())
    <p class="text-secondary text-center py-4 mb-0" style="font-size:.85rem">No adjustments yet.</p>
    @else
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.85rem">
            <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                <tr>
                    <th class="fw-bold text-uppercase ps-4 py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">When</th>
                    <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                    <th class="fw-bold text-uppercase py-3 text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">XP</th>
                    <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Reason</th>
                    <th class="fw-bold text-uppercase pe-4 py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustments as $entry)
                <tr style="border-bottom:1px solid #f9fafb">
                    <td class="ps-4 text-secondary" style="white-space:nowrap">{{ $entry->created_at?->timezone('Europe/London')->format('d M Y H:i') }}</td>
                    <td class="fw-bold text-dark">{{ $entry->user?->displayName() ?? 'Deleted account' }}</td>
                    <td class="text-end fw-black" style="color:#16a34a">+{{ number_format($entry->xp) }}</td>
                    <td class="text-secondary">{{ $entry->reason }}</td>
                    <td class="pe-4 text-secondary">{{ $entry->reference_type === 'user' ? ($admins->get($entry->reference_id)?->displayName() ?? 'Unknown') : 'System' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
