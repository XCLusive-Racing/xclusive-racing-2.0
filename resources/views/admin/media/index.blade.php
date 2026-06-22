@extends('layouts.admin')

@section('title', 'Media Library')
@section('page-title', 'Media Library')

@section('page-actions')
    @if($activeFolder)
        <a href="{{ route('admin.media.index') }}"
           class="btn btn-sm btn-outline-secondary fw-bold text-uppercase me-2"
           style="font-size:.78rem">
            ← Folders
        </a>
        <button type="button"
                class="btn btn-sm fw-black text-uppercase text-white"
                style="background:#7c3aed;font-size:.78rem"
                onclick="document.getElementById('upload-modal').style.display='block'">
            + Upload
        </button>
    @endif
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

{{-- ===== FOLDER INDEX ===== --}}
@if(!$activeFolder)

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.25rem;align-items:start">

    {{-- Existing folder cards --}}
    @foreach($folders as $folder)
    @php $cover = $folder->cover(); @endphp
    <a href="{{ route('admin.media.index') }}?folder={{ $folder->slug }}" class="text-decoration-none">
        <div class="admin-form-card p-0 overflow-hidden"
             style="cursor:pointer;transition:transform .15s ease,box-shadow .15s ease"
             onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)'"
             onmouseleave="this.style.transform='';this.style.boxShadow=''">
            <div style="aspect-ratio:4/3;background:{{ $cover ? '#111827' : '#f9fafb' }};overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:2.5rem">
                @if($cover)
                    <img src="{{ $cover->url }}" alt="{{ $folder->name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                @else
                    📁
                @endif
            </div>
            <div class="p-3">
                <div class="fw-black text-dark" style="font-size:.9rem">{{ $folder->name }}</div>
                <div class="text-secondary" style="font-size:.75rem">{{ $folder->media_count }} {{ Str::plural('item', $folder->media_count) }}</div>
            </div>
        </div>
    </a>
    @endforeach

    {{-- Uncategorised card --}}
    @php $uncatCount = \App\Models\Media::where(fn($q) => $q->whereNull('category')->orWhereNotIn('category', $folders->pluck('slug')))->count(); @endphp
    @if($uncatCount > 0)
    <a href="{{ route('admin.media.index') }}?folder=__uncategorised__" class="text-decoration-none">
        <div class="admin-form-card p-0 overflow-hidden"
             style="cursor:pointer;transition:transform .15s ease,box-shadow .15s ease"
             onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)'"
             onmouseleave="this.style.transform='';this.style.boxShadow=''">
            <div style="aspect-ratio:4/3;background:#f9fafb;display:flex;align-items:center;justify-content:center;font-size:2.5rem">
                📂
            </div>
            <div class="p-3">
                <div class="fw-black text-secondary" style="font-size:.9rem">Uncategorised</div>
                <div class="text-secondary" style="font-size:.75rem">{{ $uncatCount }} {{ Str::plural('item', $uncatCount) }}</div>
            </div>
        </div>
    </a>
    @endif

    {{-- New folder card --}}
    <div data-folder-new-wrap
         data-store-url="{{ route('admin.media.folders.store') }}"
         data-base-url="{{ route('admin.media.index') }}">

        {{-- Default state: dashed card --}}
        <div data-folder-new-trigger
             class="admin-form-card p-0 overflow-hidden xcl-new-folder-card"
             style="cursor:pointer;border:2px dashed #d1d5db;background:transparent;min-height:168px;transition:border-color .15s ease"
             onmouseenter="this.style.borderColor='#7c3aed'"
             onmouseleave="this.style.borderColor='#d1d5db'">
            <div style="height:100%;min-height:168px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.25rem">
                <div style="font-size:2rem;color:#d1d5db">+</div>
                <div class="fw-black text-secondary text-uppercase" style="font-size:.72rem;letter-spacing:.06em">New Folder</div>
            </div>
        </div>

        {{-- Create state: input form --}}
        <div data-folder-create-form class="admin-form-card p-3" style="display:none">
            <div class="fw-black text-uppercase fst-italic text-dark mb-2" style="font-size:.78rem">New Folder</div>
            <div data-folder-error class="text-danger mb-1" style="font-size:.78rem;display:none"></div>
            <input type="text" data-folder-name-input
                   class="form-control form-control-sm mb-2"
                   placeholder="Folder name...">
            <div class="d-flex gap-2">
                <button data-folder-save
                        class="btn btn-sm fw-black text-uppercase text-white flex-grow-1"
                        style="background:#7c3aed;font-size:.72rem">Create</button>
                <button data-folder-cancel
                        class="btn btn-sm btn-outline-secondary fw-bold text-uppercase"
                        style="font-size:.72rem">Cancel</button>
            </div>
        </div>
    </div>

</div>

@else

{{-- ===== FOLDER ITEM VIEW ===== --}}
@php $currentFolder = $folders->firstWhere('slug', $activeFolder); @endphp

<div data-media-grid>

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-black text-uppercase fst-italic text-dark mb-0" style="font-size:1.1rem">
                📁 {{ $currentFolder ? $currentFolder->name : 'Uncategorised' }}
            </h2>
            <p class="text-secondary mb-0" style="font-size:.8rem">{{ $media->count() }} {{ Str::plural('item', $media->count()) }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button data-media-type-btn data-media-type="all"
                    data-active-style="background:#111827;color:white;border:1px solid #111827"
                    data-idle-style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb"
                    class="btn btn-sm fw-bold text-uppercase"
                    style="font-size:.72rem;border-radius:6px">All</button>
            <button data-media-type-btn data-media-type="image"
                    data-active-style="background:#7c3aed;color:white;border:1px solid #7c3aed"
                    data-idle-style="background:rgba(124,58,237,.08);color:#7c3aed;border:1px solid rgba(124,58,237,.2)"
                    class="btn btn-sm fw-bold text-uppercase"
                    style="font-size:.72rem;border-radius:6px">Images</button>
            <button data-media-type-btn data-media-type="icon"
                    data-active-style="background:#d97706;color:white;border:1px solid #d97706"
                    data-idle-style="background:rgba(217,119,6,.08);color:#d97706;border:1px solid rgba(217,119,6,.2)"
                    class="btn btn-sm fw-bold text-uppercase"
                    style="font-size:.72rem;border-radius:6px">Icons</button>
            <button data-media-type-btn data-media-type="video"
                    data-active-style="background:#2563eb;color:white;border:1px solid #2563eb"
                    data-idle-style="background:rgba(37,99,235,.08);color:#2563eb;border:1px solid rgba(37,99,235,.2)"
                    class="btn btn-sm fw-bold text-uppercase"
                    style="font-size:.72rem;border-radius:6px">Videos</button>
            <button data-media-type-btn data-media-type="youtube"
                    data-active-style="background:#ff0000;color:white;border:1px solid #ff0000"
                    data-idle-style="background:rgba(255,0,0,.06);color:#ff0000;border:1px solid rgba(255,0,0,.2)"
                    class="btn btn-sm fw-bold text-uppercase"
                    style="font-size:.72rem;border-radius:6px">YouTube</button>

            @if($currentFolder)
            <form action="{{ route('admin.media.folders.destroy', $currentFolder) }}" method="POST"
                  onsubmit="return confirm('Delete folder «{{ $currentFolder->name }}»? Media will become uncategorised.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                        style="font-size:.72rem;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px">
                    Delete Folder
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Grid --}}
    @if($media->isEmpty())
        <div class="admin-form-card text-center py-5">
            <div style="font-size:3rem">📭</div>
            <p class="text-secondary mt-2 mb-0" style="font-size:.9rem">
                No files yet. Click <strong>+ Upload</strong> to add some.
            </p>
        </div>
    @else
    @php
        $moveTargets = $folders->filter(fn($f) => $f->slug !== $activeFolder);
        $canMoveToUncat = $activeFolder !== '__uncategorised__';
        $showMove = $moveTargets->isNotEmpty() || $canMoveToUncat;
    @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem">
        @foreach($media as $item)
        <div class="admin-form-card p-0 overflow-hidden" data-media-item data-media-type="{{ $item->type }}">
            @php $thumbBg = $item->isIcon() ? '#f8f5ff' : '#111827'; @endphp
            <div style="aspect-ratio:16/9;overflow:hidden;background:{{ $thumbBg }};position:relative;display:flex;align-items:center;justify-content:center">
                @if($item->isYoutube())
                    <img src="{{ $item->youtube_thumbnail }}" alt="{{ $item->title }}" style="width:100%;height:100%;object-fit:cover;display:block">
                    <div style="position:absolute;top:.5rem;left:.5rem;background:rgba(220,0,0,.9);color:white;font-size:.62rem;font-weight:700;padding:.15rem .45rem;border-radius:4px;text-transform:uppercase">YouTube</div>
                    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="rgba(255,255,255,.85)"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                @elseif($item->isVideo())
                    <video src="{{ $item->url }}" muted loop preload="metadata"
                           style="width:100%;height:100%;object-fit:cover;display:block"
                           onmouseenter="this.play()" onmouseleave="this.pause();this.currentTime=0"></video>
                    <div style="position:absolute;top:.5rem;left:.5rem;background:rgba(37,99,235,.85);color:white;font-size:.62rem;font-weight:700;padding:.15rem .45rem;border-radius:4px;text-transform:uppercase">Video</div>
                @elseif($item->isIcon())
                    <img src="{{ $item->url }}" alt="{{ $item->original_name }}" style="max-width:70%;max-height:70%;object-fit:contain;display:block">
                    <div style="position:absolute;top:.5rem;left:.5rem;background:rgba(217,119,6,.85);color:white;font-size:.62rem;font-weight:700;padding:.15rem .45rem;border-radius:4px;text-transform:uppercase">Icon</div>
                @else
                    <img src="{{ $item->url }}" alt="{{ $item->original_name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                @endif
            </div>
            <div class="p-3">
                <div class="fw-bold text-dark text-truncate mb-1" style="font-size:.82rem" title="{{ $item->original_name }}">
                    {{ $item->title ?: $item->original_name }}
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-secondary" style="font-size:.70rem">{{ $item->formatted_size }}&nbsp;&middot;&nbsp;{{ $item->created_at->format('d M Y') }}</span>
                    <button type="button" class="btn btn-sm fw-bold text-uppercase"
                            style="font-size:.68rem;padding:3px 8px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca"
                            data-media-delete data-url="{{ route('admin.media.destroy', $item) }}">
                        Delete
                    </button>
                </div>
                @if($showMove)
                <select data-media-move
                        data-url="{{ route('admin.media.folder', $item) }}"
                        data-csrf="{{ csrf_token() }}"
                        class="form-select form-select-sm mt-2"
                        style="font-size:.7rem">
                    <option value="" disabled selected>Move to…</option>
                    @foreach($moveTargets as $f)
                        <option value="{{ $f->slug }}">📁 {{ $f->name }}</option>
                    @endforeach
                    @if($canMoveToUncat)
                        <option value="__uncat__">📂 Uncategorised</option>
                    @endif
                </select>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <form data-delete-form method="POST" style="display:none">
        @csrf @method('DELETE')
    </form>

</div>
@endif

{{-- ===== UPLOAD MODAL (only inside a folder) ===== --}}
@if($activeFolder)
<div id="upload-modal"
     style="display:none;position:fixed;inset:0;z-index:9500"
     data-active-tab="{{ $errors->any() && old('source') === 'youtube' ? 'youtube' : 'upload' }}">
    <div style="width:100%;height:100%;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;padding:1rem"
         onclick="if(event.target===this) document.getElementById('upload-modal').style.display='none'">
        <div style="background:#fff;border-radius:12px;width:100%;max-width:520px;overflow:hidden;max-height:90vh;overflow-y:auto">
            <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
                <h3 class="fw-black text-uppercase fst-italic text-dark mb-0" style="font-size:1rem">Add Media</h3>
                <button type="button" class="btn-close"
                        onclick="document.getElementById('upload-modal').style.display='none'"></button>
            </div>

            <div class="d-flex border-bottom">
                <button type="button"
                        data-modal-tab="upload" data-color="#7c3aed"
                        class="btn btn-link fw-black text-uppercase text-decoration-none px-4 py-3 rounded-0"
                        style="font-size:.78rem">Upload File</button>
                <button type="button"
                        data-modal-tab="youtube" data-color="#ff0000"
                        class="btn btn-link fw-black text-uppercase text-decoration-none px-4 py-3 rounded-0 text-secondary"
                        style="font-size:.78rem;color:#ff0000">YouTube Link</button>
            </div>

            <div class="p-4">

                <form data-modal-panel="upload"
                      action="{{ route('admin.media.store') }}?folder={{ $activeFolder }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="source" value="upload">
                    @if($activeFolder !== '__uncategorised__')
                        <input type="hidden" name="category" value="{{ $activeFolder }}">
                    @endif

                    @php $currentFolder = $folders->firstWhere('slug', $activeFolder); @endphp
                    <div class="mb-3 p-2 rounded-2" style="background:#f8f5ff;font-size:.82rem;color:#6b21a8">
                        📁 Uploading to: <strong>{{ $currentFolder ? $currentFolder->name : 'Uncategorised' }}</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            @foreach([['image','Image','#7c3aed'],['icon','Icon','#d97706'],['video','Video','#2563eb']] as [$val,$lbl,$clr])
                            <label class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-bold text-uppercase"
                                   style="font-size:.75rem;border:1.5px solid #e5e7eb;cursor:pointer;flex:1;justify-content:center;color:#374151">
                                <input type="radio" name="type" value="{{ $val }}" {{ old('type','image')===$val?'checked':'' }}
                                       data-media-type-radio style="accent-color:{{ $clr }}">
                                {{ $lbl }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" data-media-file-input
                               accept="{{ old('type','image') === 'video' ? 'video/mp4,video/webm,video/quicktime,video/x-matroska' : 'image/*' }}"
                               class="form-control @error('file') is-invalid @enderror" required>
                        <div class="form-text">Images & Icons: JPG, PNG, GIF, WebP, SVG · Video: MP4, WebM, MOV, MKV · Max 200 MB</div>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Title <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span></label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="e.g. Monza race banner">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">Upload</button>
                        <button type="button" class="btn btn-outline-secondary fw-bold text-uppercase px-4"
                                onclick="document.getElementById('upload-modal').style.display='none'">Cancel</button>
                    </div>
                </form>

                <form data-modal-panel="youtube" style="display:none"
                      action="{{ route('admin.media.store') }}?folder={{ $activeFolder }}"
                      method="POST">
                    @csrf
                    <input type="hidden" name="source" value="youtube">
                    @if($activeFolder !== '__uncategorised__')
                        <input type="hidden" name="category" value="{{ $activeFolder }}">
                    @endif

                    <div class="mb-3 p-2 rounded-2" style="background:#f8f5ff;font-size:.82rem;color:#6b21a8">
                        📁 Uploading to: <strong>{{ $currentFolder ? $currentFolder->name : 'Uncategorised' }}</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">YouTube URL <span class="text-danger">*</span></label>
                        <input type="url" name="youtube_url" value="{{ old('youtube_url') }}"
                               class="form-control @error('youtube_url') is-invalid @enderror"
                               placeholder="https://www.youtube.com/watch?v=...">
                        <div class="form-text">Supports youtube.com/watch, youtu.be and youtube.com/shorts.</div>
                        @error('youtube_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Title <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" class="form-control"
                               placeholder="e.g. Monza race highlights">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#ff0000">Add Video</button>
                        <button type="button" class="btn btn-outline-secondary fw-bold text-uppercase px-4"
                                onclick="document.getElementById('upload-modal').style.display='none'">Cancel</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@if($errors->any())
<script>document.addEventListener('DOMContentLoaded', () => { document.getElementById('upload-modal').style.display = 'block'; });</script>
@endif
@endif

@endsection
