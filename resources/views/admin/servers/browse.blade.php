@extends('layouts.admin')

@section('title', 'Browse — ' . $server->name)
@section('page-title', 'FTP Browser')

@section('page-actions')
    <a href="{{ route('admin.servers.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Servers
    </a>
@endsection

@section('content')

@php
function ftpFileSize(?int $bytes): string {
    if ($bytes === null) return '—';
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}
@endphp

<div data-file-browser
     data-fb-view-url="{{ route('admin.servers.browse.view', $server) }}"
     data-fb-save-url="{{ route('admin.servers.browse.save', $server) }}">

{{-- Server info + breadcrumb bar --}}
<div class="admin-card mb-3">
    <div class="px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-3" style="border-bottom:1px solid #f3f4f6">
        <div>
            <div class="fw-black text-dark" style="font-size:.9rem">{{ $server->name }}</div>
            <div class="text-secondary" style="font-size:.72rem;font-family:monospace">{{ $server->host }}:{{ $server->port }}</div>
        </div>
        @if(!$error)
        <div class="d-flex gap-2">
            <button data-fb-toggle-mkdir type="button"
                    class="btn btn-sm fw-bold text-uppercase"
                    style="font-size:.72rem;padding:5px 14px;background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff">
                + New Folder
            </button>
            <button data-fb-toggle-upload type="button"
                    class="btn btn-sm fw-black text-uppercase text-white"
                    style="font-size:.72rem;padding:5px 14px;background:#7c3aed">
                ↑ Upload File
            </button>
        </div>
        @endif
    </div>

    {{-- Breadcrumbs --}}
    <div class="px-4 py-2 d-flex align-items-center gap-1 flex-wrap" style="background:#f9fafb;border-bottom:1px solid #f3f4f6;font-size:.8rem">
        @foreach($crumbs as $i => $crumb)
            @if($i < count($crumbs) - 1)
                <a href="{{ route('admin.servers.browse', ['ftpServer' => $server->id, 'path' => $crumb['path']]) }}"
                   class="fw-bold text-decoration-none" style="color:#7c3aed">{{ $crumb['name'] }}</a>
                <span class="text-secondary">/</span>
            @else
                <span class="fw-black text-dark">{{ $crumb['name'] }}</span>
            @endif
        @endforeach
        <span class="text-secondary ms-1" style="font-family:monospace;font-size:.7rem">{{ $path }}</span>
    </div>

    {{-- New Folder form --}}
    <div data-fb-mkdir-form class="px-4 py-3" style="{{ $errors->has('name') ? '' : 'display:none' }};border-bottom:1px solid #f3f4f6;background:#fafafa">
        <form action="{{ route('admin.servers.browse.mkdir', $server) }}" method="POST" class="d-flex gap-2 align-items-end flex-wrap">
            @csrf
            <input type="hidden" name="path" value="{{ $path }}">
            <div>
                <label class="form-label fw-bold text-dark mb-1" style="font-size:.75rem">Folder Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="form-control form-control-sm @error('name') is-invalid @enderror"
                       placeholder="new-folder" style="font-family:monospace;width:220px">
                @error('name') <div class="invalid-feedback" style="font-size:.72rem">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-sm fw-black text-uppercase text-white"
                    style="background:#7c3aed;font-size:.72rem;padding:6px 16px">Create</button>
            <button type="button" data-fb-mkdir-cancel
                    class="btn btn-sm fw-bold text-uppercase btn-outline-secondary"
                    style="font-size:.72rem;padding:6px 14px">Cancel</button>
        </form>
    </div>

    {{-- Upload form --}}
    <div data-fb-upload-form class="px-4 py-3" style="{{ $errors->has('file') ? '' : 'display:none' }};border-bottom:1px solid #f3f4f6;background:#fafafa">
        <form action="{{ route('admin.servers.browse.upload', $server) }}" method="POST" enctype="multipart/form-data"
              class="d-flex gap-2 align-items-end flex-wrap">
            @csrf
            <input type="hidden" name="path" value="{{ $path }}">
            <div class="flex-grow-1">
                <label class="form-label fw-bold text-dark mb-1" style="font-size:.75rem">File to upload</label>
                <input type="file" name="file"
                       class="form-control form-control-sm @error('file') is-invalid @enderror"
                       style="font-size:.8rem">
                @error('file') <div class="invalid-feedback" style="font-size:.72rem">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-sm fw-black text-uppercase text-white"
                    style="background:#7c3aed;font-size:.72rem;padding:6px 16px">Upload</button>
            <button type="button" data-fb-upload-cancel
                    class="btn btn-sm fw-bold text-uppercase btn-outline-secondary"
                    style="font-size:.72rem;padding:6px 14px">Cancel</button>
        </form>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
<div class="alert alert-success py-2 px-3 mb-3" style="font-size:.82rem">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem">{{ session('error') }}</div>
@endif

{{-- Connection error --}}
@if($error)
<div class="admin-card p-4">
    <div class="fw-bold text-danger mb-2" style="font-size:.875rem">{{ $error }}</div>
    <a href="{{ route('admin.servers.index') }}"
       class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.75rem">← Back to Servers</a>
</div>

{{-- File browser --}}
@else
<div class="admin-card">

    @if(empty($entries) && $path === '/')
    <div class="p-5 text-center">
        <div class="fw-bold text-dark" style="font-size:.95rem">Empty server</div>
        <div class="text-secondary mt-1" style="font-size:.82rem">No files or folders found on this server.</div>
    </div>
    @elseif(empty($entries))
    <div class="p-5 text-center">
        <div class="fw-bold text-dark" style="font-size:.95rem">Empty directory</div>
        <div class="text-secondary mt-1" style="font-size:.82rem">No files or folders found in <code>{{ $path }}</code>.</div>
    </div>
    @else
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.82rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="ps-4" style="width:32px"></th>
                    <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Name</th>
                    <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:90px">Size</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:155px">Modified</th>
                    <th style="width:48px"></th>
                </tr>
            </thead>
            <tbody>

                {{-- Parent dir row --}}
                @if($path !== '/')
                @php $parentPath = dirname($path) === '.' ? '/' : dirname($path); @endphp
                <tr style="background:#fafafa">
                    <td class="ps-4 text-secondary fw-bold" style="font-size:.85rem">↑</td>
                    <td colspan="3">
                        <a href="{{ route('admin.servers.browse', ['ftpServer' => $server->id, 'path' => $parentPath]) }}"
                           class="text-decoration-none fw-bold text-secondary" style="font-size:.82rem">..</a>
                    </td>
                    <td></td>
                </tr>
                @endif

                @foreach($entries as $entry)
                @php
                    $entryPath = rtrim($path, '/') . '/' . $entry['name'];
                    $isDir     = $entry['type'] === 'dir';
                    $ext       = $isDir ? null : strtoupper(pathinfo($entry['name'], PATHINFO_EXTENSION) ?: 'FILE');
                    $isJson    = !$isDir && strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION)) === 'json';
                @endphp
                <tr>
                    {{-- Type icon --}}
                    <td class="ps-4">
                        @if($isDir)
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:18px;background:#fef3c7;border:1px solid #fde68a;border-radius:3px;font-size:.55rem;font-weight:900;color:#d97706;letter-spacing:.02em">DIR</span>
                        @else
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:18px;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:3px;font-size:.5rem;font-weight:900;color:#6b7280;letter-spacing:.02em;overflow:hidden">{{ $ext }}</span>
                        @endif
                    </td>

                    {{-- Name --}}
                    <td>
                        @if($isDir)
                            <a href="{{ route('admin.servers.browse', ['ftpServer' => $server->id, 'path' => $entryPath]) }}"
                               class="fw-bold text-decoration-none text-dark" style="font-size:.82rem">
                                {{ $entry['name'] }}
                            </a>
                        @elseif($isJson)
                            <button type="button"
                                    data-fb-view-btn
                                    data-fb-path="{{ $entryPath }}"
                                    data-fb-name="{{ $entry['name'] }}"
                                    class="fw-bold text-decoration-none btn btn-link p-0"
                                    style="font-family:monospace;font-size:.78rem;color:#7c3aed">
                                {{ $entry['name'] }}
                            </button>
                        @else
                            <span class="fw-bold text-dark" style="font-family:monospace;font-size:.78rem">{{ $entry['name'] }}</span>
                        @endif
                    </td>

                    {{-- Size --}}
                    <td class="d-none d-sm-table-cell" style="font-size:.75rem;color:#6b7280;font-family:monospace">
                        {{ $isDir ? '—' : ftpFileSize($entry['size']) }}
                    </td>

                    {{-- Modified --}}
                    <td class="d-none d-md-table-cell" style="font-size:.75rem;color:#6b7280">{{ $entry['modified'] ?? '—' }}</td>

                    {{-- Kebab menu --}}
                    <td class="pe-3 text-end" data-fb-kebab-wrap style="position:relative">
                        <button data-fb-kebab-btn type="button"
                                class="btn btn-sm"
                                style="background:transparent;border:none;color:#9ca3af;font-size:1.1rem;padding:2px 6px;line-height:1;border-radius:4px">
                            ···
                        </button>
                        <div data-fb-kebab-panel
                             style="display:none;position:absolute;right:12px;top:100%;z-index:200;background:white;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);min-width:140px;padding:4px 0">

                            @if(!$isDir)
                            @if($isJson)
                            <button type="button"
                                    data-fb-view-btn
                                    data-fb-path="{{ $entryPath }}"
                                    data-fb-name="{{ $entry['name'] }}"
                                    class="d-flex align-items-center gap-2 px-3 py-2 fw-bold text-uppercase w-100 text-start"
                                    style="font-size:.72rem;color:#7c3aed;background:none;border:none;white-space:nowrap">
                                <span style="width:14px;text-align:center">{ }</span> View
                            </button>
                            @endif
                            <a href="{{ route('admin.servers.browse.download', ['ftpServer' => $server->id, 'path' => $entryPath]) }}"
                               class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none fw-bold text-uppercase"
                               style="font-size:.72rem;color:#16a34a;white-space:nowrap">
                                <span style="width:14px;text-align:center">↓</span> Download
                            </a>
                            @endif

                            <button type="button"
                                    data-fb-rename-btn
                                    data-fb-path="{{ $entryPath }}"
                                    data-fb-name="{{ $entry['name'] }}"
                                    class="d-flex align-items-center gap-2 px-3 py-2 fw-bold text-uppercase w-100 text-start"
                                    style="font-size:.72rem;color:#374151;background:none;border:none;white-space:nowrap">
                                <span style="width:14px;text-align:center">✎</span> Rename
                            </button>

                            @if(!$isDir)<div style="border-top:1px solid #f3f4f6;margin:3px 0"></div>@endif

                            <button type="button"
                                    data-fb-delete-btn
                                    class="d-flex align-items-center gap-2 px-3 py-2 fw-bold text-uppercase w-100 text-start"
                                    style="font-size:.72rem;color:#dc2626;background:none;border:none;white-space:nowrap">
                                <span style="width:14px;text-align:center">✕</span> Delete
                            </button>
                        </div>

                        {{-- Delete confirmation (inline) --}}
                        <div data-fb-delete-confirm
                             style="display:none;position:absolute;right:12px;top:100%;z-index:200;background:white;border:1px solid #fecaca;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);padding:10px 14px;white-space:nowrap">
                            <div class="fw-bold text-dark mb-2" style="font-size:.75rem">Delete "{{ $entry['name'] }}"?</div>
                            <div class="d-flex gap-2">
                                <form action="{{ route('admin.servers.browse.delete', $server) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="path" value="{{ $entryPath }}">
                                    <input type="hidden" name="type" value="{{ $entry['type'] }}">
                                    <button type="submit"
                                            class="btn btn-sm fw-black text-uppercase text-white"
                                            style="background:#dc2626;font-size:.68rem;padding:4px 12px;border-radius:5px">
                                        Delete
                                    </button>
                                </form>
                                <button type="button" data-fb-delete-cancel
                                        class="btn btn-sm fw-bold text-uppercase btn-outline-secondary"
                                        style="font-size:.68rem;padding:4px 10px;border-radius:5px">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach

            </tbody>
        </table>
    </div>
    @endif

    {{-- Footer: file count --}}
    @if(!empty($entries))
    <div class="px-4 py-2" style="border-top:1px solid #f3f4f6;background:#f9fafb">
        <span class="text-secondary" style="font-size:.72rem">
            @php
                $dirCount  = collect($entries)->where('type', 'dir')->count();
                $fileCount = collect($entries)->where('type', 'file')->count();
            @endphp
            {{ $fileCount }} {{ Str::plural('file', $fileCount) }}
            @if($dirCount) · {{ $dirCount }} {{ Str::plural('folder', $dirCount) }} @endif
        </span>
    </div>
    @endif

</div>
@endif

{{-- View (JSON editor) modal --}}
<div data-fb-view-modal
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;align-items:center;justify-content:center;padding:1rem">
    <div style="background:white;border-radius:12px;width:100%;max-width:860px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2)">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid #f3f4f6;flex-shrink:0">
            <div>
                <div class="fw-black text-dark" style="font-size:.9rem;font-family:monospace" data-fb-view-name></div>
                <div class="text-secondary" style="font-size:.72rem">{{ $server->name }}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span data-fb-view-saved class="fw-bold text-success" style="display:none;font-size:.75rem">✓ Saved</span>
                <span data-fb-view-save-error class="fw-bold text-danger" style="display:none;font-size:.75rem"></span>
                <button type="button" data-fb-view-save
                        class="btn btn-sm fw-black text-uppercase text-white"
                        style="background:#7c3aed;font-size:.72rem;padding:4px 14px">
                    <span data-fb-not-saving>Save</span>
                    <span data-fb-saving style="display:none">Saving…</span>
                </button>
                <button data-fb-view-close type="button"
                        style="background:none;border:none;color:#9ca3af;font-size:1.2rem;line-height:1;padding:4px 8px;cursor:pointer">✕</button>
            </div>
        </div>

        {{-- Body --}}
        <div style="flex:1;overflow:hidden;display:flex;flex-direction:column;padding:1rem">
            <div data-fb-view-loading class="text-center py-5" style="display:none">
                <div class="text-secondary fw-bold" style="font-size:.82rem">Loading…</div>
            </div>
            <div data-fb-view-error class="text-danger fw-bold py-3" style="display:none;font-size:.82rem"></div>
            <textarea data-fb-view-content
                      class="form-control font-monospace flex-grow-1"
                      style="display:none;font-size:.78rem;line-height:1.6;resize:none;height:100%;min-height:400px"
                      spellcheck="false"></textarea>
        </div>

    </div>
</div>

{{-- Rename modal --}}
<div data-fb-rename-modal
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;align-items:center;justify-content:center">
    <div style="background:white;border-radius:12px;padding:1.5rem;width:420px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <div class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:.9rem">Rename</div>
        <div class="text-secondary mb-3" style="font-size:.75rem;font-family:monospace" data-fb-rename-path-display></div>
        <form action="{{ route('admin.servers.browse.rename', $server) }}" method="POST">
            @csrf
            <input type="hidden" name="path" data-fb-rename-path-input>
            <div class="mb-3">
                <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">New Name</label>
                <input type="text" name="newname" data-fb-rename-name-input
                       class="form-control @error('newname') is-invalid @enderror"
                       style="font-family:monospace">
                @error('newname') <div class="invalid-feedback" style="font-size:.72rem">{{ $message }}</div> @enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4"
                        style="background:#7c3aed;font-size:.82rem">Rename</button>
                <button type="button" data-fb-rename-close
                        class="btn btn-outline-secondary fw-bold text-uppercase px-4"
                        style="font-size:.82rem">Cancel</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
