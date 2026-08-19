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
                        <div class="dropdown">
                            <button class="btn btn-sm fw-bold" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false"
                                    style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-size:.78rem;padding:4px 10px;line-height:1.2">
                                ···
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:140px;border-color:#e5e7eb">
                                <li>
                                    <button type="button" class="dropdown-item fw-bold" style="color:#16a34a"
                                            onclick="testConnection({{ $server->id }}, this)">
                                        Test Connection
                                    </button>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-bold" href="{{ route('admin.servers.edit', $server) }}" style="color:#7c3aed">
                                        Edit
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider" style="border-color:#f3f4f6"></li>
                                <li>
                                    <form action="{{ route('admin.servers.destroy', $server) }}" method="POST" onsubmit="return false">
                                        @csrf @method('DELETE')
                                        <button type="button" class="dropdown-item fw-bold" style="color:#dc2626"
                                                onclick="xcDeleteSubmit(this.closest('form'), 'Delete {{ addslashes($server->name) }}?')">
                                            Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
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

