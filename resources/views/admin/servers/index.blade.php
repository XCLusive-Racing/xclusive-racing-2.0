@extends('layouts.admin')

@section('title', 'FTP Servers')
@section('page-title', 'FTP Servers')

@section('page-actions')
    <a href="{{ route('admin.servers.create') }}" class="btn btn-sm fw-black text-uppercase text-white px-3"
       style="background:#7c3aed;font-size:.78rem">
        + Add Server
    </a>
@endsection

@section('content')

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.05rem">GPortal FTP Servers</div>
            <div class="text-secondary mt-1" style="font-size:.8rem">Manage ACC server connections for result imports.</div>
        </div>
        <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
            {{ $servers->count() }} servers
        </span>
    </div>

    @if($servers->isEmpty())
    <div class="p-5 text-center">
        <div style="font-size:2.5rem;margin-bottom:.75rem">🖥️</div>
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">No servers yet</div>
        <div class="text-secondary mt-2 mb-4" style="font-size:.82rem">Add your first GPortal FTP server to start importing results.</div>
        <a href="{{ route('admin.servers.create') }}" class="btn fw-black text-uppercase text-white px-4"
           style="background:#7c3aed;font-size:.8rem">
            + Add Server
        </a>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Name</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Host</th>
                    <th class="fw-bold text-uppercase d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Path</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:90px">Status</th>
                    <th class="fw-bold text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($servers as $server)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $server->name }}</div>
                        <div class="text-secondary" style="font-size:.7rem">Port {{ $server->port }}</div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span style="font-family:monospace;font-size:.8rem;color:#374151">{{ $server->host }}</span>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <span style="font-family:monospace;font-size:.75rem;color:#6b7280">{{ $server->path }}</span>
                    </td>
                    <td class="text-center">
                        @if($server->active)
                        <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">Active</span>
                        @else
                        <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.68rem;padding:3px 8px;border-radius:6px;font-weight:700">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex gap-1 gap-md-2 justify-content-end align-items-center flex-wrap">
                            <button type="button"
                                    class="btn btn-sm fw-bold text-uppercase"
                                    style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;font-size:.68rem;padding:3px 10px"
                                    onclick="testConnection({{ $server->id }}, this)">
                                Test
                            </button>
                            <a href="{{ route('admin.servers.browse', $server) }}"
                               class="btn btn-sm fw-bold text-uppercase"
                               style="background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;font-size:.68rem;padding:3px 10px">
                                Browse
                            </a>
                            <a href="{{ route('admin.servers.edit', $server) }}"
                               class="btn btn-sm fw-bold text-uppercase"
                               style="background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff;font-size:.68rem;padding:3px 10px">
                                Edit
                            </a>
                            <form action="{{ route('admin.servers.destroy', $server) }}" method="POST" onsubmit="return false">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm fw-bold text-uppercase"
                                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.68rem;padding:3px 10px"
                                        onclick="xcDeleteSubmit(this.closest('form'), 'Delete {{ addslashes($server->name) }}?')">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection

