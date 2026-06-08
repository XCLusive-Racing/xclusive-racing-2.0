@extends('layouts.admin')

@section('title', 'Edit — ' . $server->name)
@section('page-title', 'Edit FTP Server')

@section('page-actions')
    <a href="{{ route('admin.servers.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<form action="{{ route('admin.servers.update', $server) }}" method="POST">
    @csrf @method('PUT')

    <div class="row g-4 align-items-start">
        <div class="col-lg-7">

            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-2">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Server Info</p>

                    <div class="mb-3">
                        <label class="form-label">Server Name</label>
                        <input type="text" name="name" value="{{ old('name', $server->name) }}"
                               class="form-control @error('name') is-invalid @enderror">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Connection</p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-9">
                            <label class="form-label">IP Address</label>
                            <input type="text" name="host" value="{{ old('host', $server->host) }}"
                                   class="form-control @error('host') is-invalid @enderror"
                                   style="font-family:monospace">
                            @error('host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" value="{{ old('port', $server->port) }}"
                                   class="form-control @error('port') is-invalid @enderror">
                            @error('port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">FTP Username</label>
                            <input type="text" name="username" value="{{ old('username', $server->username) }}"
                                   class="form-control @error('username') is-invalid @enderror"
                                   autocomplete="off">
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">FTP Password</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   autocomplete="new-password"
                                   placeholder="Leave blank to keep current">
                            <div class="form-text" style="font-size:.72rem;color:#9ca3af">Leave blank to keep the existing password.</div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Configuration</p>

                    <div class="mb-3">
                        <label class="form-label">Results Path</label>
                        <input type="text" name="path" value="{{ old('path', $server->path) }}"
                               class="form-control @error('path') is-invalid @enderror"
                               style="font-family:monospace">
                        <div class="form-text" style="font-size:.72rem;color:#9ca3af">Directory on the FTP server where JSON result files are saved.</div>
                        @error('path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                               {{ old('active', $server->active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark" for="active" style="font-size:.82rem">
                            Server is active
                        </label>
                        <div class="form-text" style="font-size:.72rem;color:#9ca3af">Inactive servers are hidden from the import selector.</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Save Changes
                </button>
                <a href="{{ route('admin.servers.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>

        </div>
    </div>
</form>

@endsection