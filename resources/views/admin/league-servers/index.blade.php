@extends('layouts.admin')

@section('title', 'League FTP Servers')
@section('page-title', 'League FTP Servers')

@section('content')

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">All League Servers</div>
            <div class="text-secondary mt-1" style="font-size:.8rem">Every server a league brings or is assigned, across every league — Owner/Admin only.</div>
        </div>
    </div>

    @if($servers->isEmpty())
    <div class="p-5 text-center">
        <div style="font-size:2.5rem;margin-bottom:.75rem">🖥️</div>
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">No league servers yet</div>
        <div class="text-secondary mt-2" style="font-size:.82rem">Add one below, or let a league manager add their own from their League page.</div>
    </div>
    @else
    @foreach($servers as $leagueName => $group)
    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
        <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.75rem;letter-spacing:.06em;color:#7c3aed">{{ $leagueName }}</p>
        <table class="table table-hover align-middle mb-0" style="font-size:.85rem">
            <tbody>
                @foreach($group as $server)
                <tr>
                    <td class="ps-0">
                        <div class="fw-bold text-dark">{{ $server->name }}</div>
                        <div class="text-secondary" style="font-size:.72rem;font-family:monospace">{{ $server->host }}:{{ $server->port }}</div>
                    </td>
                    <td class="text-center" style="width:100px">
                        <span class="badge" style="background:{{ $server->active ? '#d1fae5' : '#f3f4f6' }};color:{{ $server->active ? '#065f46' : '#6b7280' }};font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">
                            {{ $server->active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end pe-0" style="width:100px">
                        <a href="{{ route('admin.servers.edit', $server) }}" class="fw-bold" style="color:#7c3aed;font-size:.8rem">Edit →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @endif
</div>

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Add Server</div>
    </div>
    <div class="px-4 py-4">
        <form action="{{ route('admin.league-servers.store') }}" method="POST">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-sm-5">
                    <label class="form-label">League</label>
                    <select name="league_id" class="form-select @error('league_id') is-invalid @enderror" required>
                        <option value="">Select a league…</option>
                        @foreach($leagues as $l)
                        <option value="{{ $l->id }}">{{ $l->name }}</option>
                        @endforeach
                    </select>
                    @error('league_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-3">
                    <label class="form-label">Type</label>
                    <select name="server_type" class="form-select" required>
                        <option value="rolling">Rolling restart</option>
                        <option value="scheduled">Manual restart</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-8">
                    <label class="form-label">Host</label>
                    <input type="text" name="host" class="form-control @error('host') is-invalid @enderror" required>
                    @error('host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Port</label>
                    <input type="number" name="port" class="form-control @error('port') is-invalid @enderror" required>
                    @error('port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">FTP Username</label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" required>
                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label">FTP Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Config Path</label>
                    <input type="text" name="path" class="form-control @error('path') is-invalid @enderror" required>
                    @error('path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label">cfg_path <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                    <input type="text" name="cfg_path" class="form-control @error('cfg_path') is-invalid @enderror">
                    @error('cfg_path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Reset Start Hour <span class="fw-normal text-secondary" style="text-transform:none">(rolling only)</span></label>
                    <input type="number" name="reset_start_hour" min="0" max="23" class="form-control @error('reset_start_hour') is-invalid @enderror">
                    @error('reset_start_hour') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Reset Interval (min) <span class="fw-normal text-secondary" style="text-transform:none">(rolling only)</span></label>
                    <input type="number" name="reset_interval_minutes" min="30" max="1440" class="form-control @error('reset_interval_minutes') is-invalid @enderror">
                    @error('reset_interval_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                Add Server
            </button>
        </form>
    </div>
</div>

@endsection
