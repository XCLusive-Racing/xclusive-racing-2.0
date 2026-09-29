@extends('layouts.admin')

@section('title', $championship->name . ' — Entries')
@section('page-title', $league->name . ' — ' . $championship->name . ' — Entries')

@section('page-actions')
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'basics']) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<div class="admin-card">
    <div class="admin-card-header d-flex align-items-center justify-content-between">
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">
            Entries <span class="text-secondary fw-bold" style="font-size:.8rem">({{ $entries->count() }})</span>
        </div>
        @if($driverClasses->isNotEmpty())
        <span class="text-secondary" style="font-size:.75rem">
            @foreach($driverClasses as $driverClass)
            <span class="me-2"><i class="fa-solid fa-square" style="color:{{ $driverClass->color() }};-webkit-text-stroke:1px #d1d5db"></i> {{ $driverClass->name }}: {{ $driverClass->registrations_count }}{{ $driverClass->max_entries ? '/'.$driverClass->max_entries : '' }}</span>
            @endforeach
        </span>
        @endif
        @if($championship->requiresManualApproval())
        <span class="text-secondary" style="font-size:.75rem">Manual approval is on — new entries wait here until you approve them.</span>
        @endif
    </div>

    @if($entries->isEmpty())
    <div class="text-center py-5 text-secondary" style="font-size:.85rem">No entries yet.</div>
    @else
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Entrant</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Class</th>
                    @if($driverClasses->isNotEmpty())
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Driver Class</th>
                    @endif
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Entered</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:110px">Status</th>
                    <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:170px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">
                            {{ $entry->racingTeam ? $entry->racingTeam->name : $entry->user?->displayName() }}
                            @if($entry->car_number !== null)<span class="text-secondary fw-normal">#{{ $entry->car_number }}</span>@endif
                        </div>
                        @if($entry->racingTeam)
                        <div class="text-secondary" style="font-size:.75rem">Entered by {{ $entry->user?->displayName() }}</div>
                        @endif
                        {{-- A solo driver can't change their own car once registered — the league can, here. --}}
                        @if(!$entry->racingTeam && !$entry->is_spectator && $carOptions->isNotEmpty())
                        <details class="mt-1" @if($errors->any() && (int) old('registration_id') === $entry->id) open @endif>
                            <summary class="text-secondary" style="font-size:.75rem;cursor:pointer">
                                {{ $entry->car_model ?? 'No car picked' }}
                            </summary>
                            <form action="{{ route('admin.leagues.championships.entries.car', [$league, $championship, $entry]) }}" method="POST" class="d-flex flex-wrap gap-2 mt-2">
                                @csrf @method('PUT')
                                <input type="hidden" name="registration_id" value="{{ $entry->id }}">
                                <select name="car_model" class="form-select form-select-sm" style="max-width:240px;font-size:.78rem" required>
                                    <option value="">Select car…</option>
                                    @foreach($carOptions as $carName)
                                    <option value="{{ $carName }}" @selected($entry->car_model === $carName)>{{ $carName }}</option>
                                    @endforeach
                                </select>
                                <input type="number" name="car_number" min="0" max="999" value="{{ $entry->car_number }}" placeholder="#"
                                       class="form-control form-control-sm" style="max-width:80px;font-size:.78rem" required>
                                <button class="btn btn-sm fw-bold text-uppercase text-white" style="background:#7c3aed;font-size:.7rem">Save</button>
                            </form>
                            @if((int) old('registration_id') === $entry->id)
                            @error('car_number')<div class="text-danger" style="font-size:.75rem">{{ $message }}</div>@enderror
                            @error('car_model')<div class="text-danger" style="font-size:.75rem">{{ $message }}</div>@enderror
                            @endif
                        </details>
                        @endif
                    </td>
                    <td class="d-none d-md-table-cell text-secondary">{{ $entry->championshipClass?->name ?? '—' }}</td>
                    @if($driverClasses->isNotEmpty())
                    <td>
                        @unless($entry->is_spectator)
                        <form action="{{ route('admin.leagues.championships.entries.driver-class', [$league, $championship, $entry]) }}" method="POST">
                            @csrf @method('PUT')
                            <select name="driver_class_id" class="form-select form-select-sm" style="min-width:130px;font-size:.8rem" onchange="this.form.submit()">
                                <option value="">— No class —</option>
                                @foreach($driverClasses as $driverClass)
                                <option value="{{ $driverClass->id }}" @selected($entry->driver_class_id === $driverClass->id)>
                                    {{ $driverClass->name }}{{ $driverClass->max_entries ? ' ('.$driverClass->registrations_count.'/'.$driverClass->max_entries.')' : '' }}
                                </option>
                                @endforeach
                            </select>
                        </form>
                        @endunless
                    </td>
                    @endif
                    <td class="d-none d-md-table-cell text-secondary">{{ $entry->created_at->format('j M Y') }}</td>
                    <td class="text-center">
                        @if($entry->is_spectator)
                        <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">Spectator</span>
                        @elseif($entry->isPending())
                        <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">Pending</span>
                        @else
                        <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">Approved</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        @if($entry->isPending())
                        <form action="{{ route('admin.leagues.championships.entries.approve', [$league, $championship, $entry]) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm fw-bold text-uppercase text-white" style="background:#16a34a;font-size:.7rem">Approve</button>
                        </form>
                        <form action="{{ route('admin.leagues.championships.entries.reject', [$league, $championship, $entry]) }}" method="POST" class="d-inline" onsubmit="return false">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-link fw-bold p-0 ms-2" style="color:#dc2626;font-size:.75rem"
                                    onclick="xcDeleteSubmit(this.closest('form'), 'Reject this entry? The driver gets a message.')">Reject</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="row g-4 mt-1">
    <div class="col-12 col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Points Penalties</div>
            </div>
            @if($penalties->isEmpty())
            <div class="px-4 py-4 text-secondary" style="font-size:.85rem">No penalties.</div>
            @else
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.85rem">
                    <tbody>
                        @foreach($penalties as $penalty)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $penalty->user?->displayName() }}</td>
                            <td class="fw-bold text-danger">−{{ $penalty->points }}</td>
                            <td class="text-secondary d-none d-md-table-cell" style="font-size:.78rem">{{ $penalty->race?->title ?? '—' }}</td>
                            <td class="text-secondary" style="font-size:.78rem">{{ $penalty->reason ?? '—' }}</td>
                            <td class="text-end pe-4">
                                <form action="{{ route('admin.leagues.championships.penalties.destroy', [$league, $championship, $penalty]) }}" method="POST" onsubmit="return false">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-link fw-bold p-0" style="color:#dc2626;font-size:.75rem"
                                            onclick="xcDeleteSubmit(this.closest('form'), 'Remove this penalty?')">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Add Penalty</div>
            </div>
            <form action="{{ route('admin.leagues.championships.penalties.store', [$league, $championship]) }}" method="POST" class="px-4 py-3">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Driver</label>
                    <select name="user_id" class="form-select form-select-sm @error('user_id') is-invalid @enderror" required>
                        <option value="">Select driver…</option>
                        @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" @selected(old('user_id') == $driver->id)>{{ $driver->displayName() }}</option>
                        @endforeach
                    </select>
                    @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-sm-5">
                        <label class="form-label">Points to deduct</label>
                        <input type="number" name="points" min="1" max="999" value="{{ old('points') }}" class="form-control form-control-sm @error('points') is-invalid @enderror" required>
                        @error('points') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-7">
                        <label class="form-label">Round <span class="text-secondary fw-normal">(optional)</span></label>
                        <select name="race_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($rounds as $round)
                            <option value="{{ $round->id }}" @selected(old('race_id') == $round->id)>Round {{ $round->round_number }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason <span class="text-secondary fw-normal">(optional)</span></label>
                    <input type="text" name="reason" maxlength="255" value="{{ old('reason') }}" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-sm fw-black text-uppercase text-white w-100" style="background:#7c3aed;font-size:.78rem">Deduct Points</button>
            </form>
        </div>
    </div>
</div>

@endsection
