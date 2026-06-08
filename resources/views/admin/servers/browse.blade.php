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

<div x-data="{
    showUpload: {{ $errors->has('file') ? 'true' : 'false' }},
    showMkdir: {{ $errors->has('name') ? 'true' : 'false' }},
    renameModal: false,
    renamePath: '',
    renameName: '',
    deleteConfirm: null,
    viewModal: false,
    viewName: '',
    viewContent: '',
    viewLoading: false,
    viewError: '',
    openRename(path, name) {
        this.renamePath = path;
        this.renameName = name;
        this.renameModal = true;
        this.$nextTick(() => { if (this.$refs.renameInput) this.$refs.renameInput.focus(); });
    },
    async openView(path, name) {
        this.viewName = name;
        this.viewContent = '';
        this.viewError = '';
        this.viewLoading = true;
        this.viewModal = true;
        try {
            const url = '{{ route('admin.servers.browse.view', $server) }}?path=' + encodeURIComponent(path);
            const res = await fetch(url);
            const text = await res.text();
            if (!res.ok) {
                const err = JSON.parse(text);
                this.viewError = err.error ?? 'Could not load file.';
            } else {
                this.viewContent = text;
            }
        } catch (e) {
            this.viewError = 'Network error.';
        }
        this.viewLoading = false;
    }
}">

{{-- Server info + breadcrumb bar --}}
<div class="admin-card mb-3">
    <div class="px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-3" style="border-bottom:1px solid #f3f4f6">
        <div>
            <div class="fw-black text-dark" style="font-size:.9rem">{{ $server->name }}</div>
            <div class="text-secondary" style="font-size:.72rem;font-family:monospace">{{ $server->host }}:{{ $server->port }}</div>
        </div>
        @if(!$error)
        <div class="d-flex gap-2">
            <button @click="showMkdir = !showMkdir; showUpload = false" type="button"
                    :style="showMkdir ? 'background:#7c3aed;color:white;border:1px solid #7c3aed' : 'background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff'"
                    class="btn btn-sm fw-bold text-uppercase"
                    style="font-size:.72rem;padding:5px 14px">
                + New Folder
            </button>
            <button @click="showUpload = !showUpload; showMkdir = false" type="button"
                    :style="showUpload ? 'background:#7c3aed;color:white' : 'background:#7c3aed;color:white'"
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
    <div x-show="showMkdir" x-cloak class="px-4 py-3" style="border-bottom:1px solid #f3f4f6;background:#fafafa">
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
            <button type="button" @click="showMkdir = false"
                    class="btn btn-sm fw-bold text-uppercase btn-outline-secondary"
                    style="font-size:.72rem;padding:6px 14px">Cancel</button>
        </form>
    </div>

    {{-- Upload form --}}
    <div x-show="showUpload" x-cloak class="px-4 py-3" style="border-bottom:1px solid #f3f4f6;background:#fafafa">
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
            <button type="button" @click="showUpload = false"
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
                    <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:90px">Size</th>
                    <th class="fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:155px">Modified</th>
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
                        @else
                            <span class="fw-bold text-dark" style="font-family:monospace;font-size:.78rem">{{ $entry['name'] }}</span>
                        @endif
                    </td>

                    {{-- Size --}}
                    <td style="font-size:.75rem;color:#6b7280;font-family:monospace">
                        {{ $isDir ? '—' : ftpFileSize($entry['size']) }}
                    </td>

                    {{-- Modified --}}
                    <td style="font-size:.75rem;color:#6b7280">{{ $entry['modified'] ?? '—' }}</td>

                    {{-- Kebab menu --}}
                    <td class="pe-3 text-end" x-data="{ open: false }" @click.outside="open = false" style="position:relative">
                        <button @click="open = !open" type="button"
                                class="btn btn-sm"
                                style="background:transparent;border:none;color:#9ca3af;font-size:1.1rem;padding:2px 6px;line-height:1;border-radius:4px"
                                :style="open ? 'background:#f3f4f6;color:#374151' : ''">
                            ···
                        </button>
                        <div x-show="open" x-cloak
                             style="position:absolute;right:12px;top:100%;z-index:200;background:white;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);min-width:140px;padding:4px 0">

                            @if(!$isDir)
                            @if(strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION)) === 'json')
                            <button type="button"
                                    @click="open = false; openView('{{ $entryPath }}', '{{ addslashes($entry['name']) }}')"
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
                                    @click="open = false; openRename('{{ $entryPath }}', '{{ addslashes($entry['name']) }}')"
                                    class="d-flex align-items-center gap-2 px-3 py-2 fw-bold text-uppercase w-100 text-start"
                                    style="font-size:.72rem;color:#374151;background:none;border:none;white-space:nowrap">
                                <span style="width:14px;text-align:center">✎</span> Rename
                            </button>

                            @if(!$isDir)<div style="border-top:1px solid #f3f4f6;margin:3px 0"></div>@endif

                            <button type="button"
                                    @click="open = false; deleteConfirm = '{{ $entryPath }}'"
                                    class="d-flex align-items-center gap-2 px-3 py-2 fw-bold text-uppercase w-100 text-start"
                                    style="font-size:.72rem;color:#dc2626;background:none;border:none;white-space:nowrap">
                                <span style="width:14px;text-align:center">✕</span> Delete
                            </button>
                        </div>

                        {{-- Delete confirmation (inline, replaces menu) --}}
                        <div x-show="deleteConfirm === '{{ $entryPath }}'"
                             style="position:absolute;right:12px;top:100%;z-index:200;background:white;border:1px solid #fecaca;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);padding:10px 14px;white-space:nowrap">
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
                                <button type="button" @click="deleteConfirm = null"
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

{{-- View JSON modal --}}
<div x-show="viewModal" x-cloak
     style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;display:flex;align-items:center;justify-content:center;padding:1rem"
     @keydown.escape.window="viewModal = false"
     @click.self="viewModal = false">
    <div style="background:white;border-radius:12px;width:100%;max-width:760px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2)">

        {{-- Modal header --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid #f3f4f6;flex-shrink:0">
            <div>
                <div class="fw-black text-dark" style="font-size:.9rem;font-family:monospace" x-text="viewName"></div>
                <div class="text-secondary" style="font-size:.72rem">{{ $server->name }}</div>
            </div>
            <button @click="viewModal = false" type="button"
                    style="background:none;border:none;color:#9ca3af;font-size:1.2rem;line-height:1;padding:4px 8px;cursor:pointer">✕</button>
        </div>

        {{-- Modal body --}}
        <div style="overflow-y:auto;flex:1;padding:1rem">
            <div x-show="viewLoading" class="text-center py-5">
                <div class="text-secondary fw-bold" style="font-size:.82rem">Loading…</div>
            </div>
            <div x-show="viewError" class="text-danger fw-bold" style="font-size:.82rem" x-text="viewError"></div>
            <pre x-show="!viewLoading && !viewError"
                 style="margin:0;font-size:.78rem;line-height:1.6;color:#1f2937;white-space:pre-wrap;word-break:break-all"
                 x-text="viewContent"></pre>
        </div>

    </div>
</div>

{{-- Rename modal --}}
<div x-show="renameModal" x-cloak
     style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;display:flex;align-items:center;justify-content:center"
     @keydown.escape.window="renameModal = false"
     @click.self="renameModal = false">
    <div style="background:white;border-radius:12px;padding:1.5rem;width:420px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <div class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:.9rem">Rename</div>
        <div class="text-secondary mb-3" style="font-size:.75rem;font-family:monospace" x-text="renamePath"></div>
        <form action="{{ route('admin.servers.browse.rename', $server) }}" method="POST">
            @csrf
            <input type="hidden" name="path" :value="renamePath">
            <div class="mb-3">
                <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">New Name</label>
                <input type="text" name="newname" x-model="renameName" x-ref="renameInput"
                       class="form-control @error('newname') is-invalid @enderror"
                       style="font-family:monospace">
                @error('newname') <div class="invalid-feedback" style="font-size:.72rem">{{ $message }}</div> @enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4"
                        style="background:#7c3aed;font-size:.82rem">Rename</button>
                <button type="button" @click="renameModal = false"
                        class="btn btn-outline-secondary fw-bold text-uppercase px-4"
                        style="font-size:.82rem">Cancel</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection