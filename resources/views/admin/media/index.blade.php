@extends('layouts.admin')

@section('title', 'Media Library')
@section('page-title', 'Media Library')

@section('page-actions')
    <button type="button"
            class="btn btn-sm fw-black text-uppercase text-white"
            style="background:#7c3aed;font-size:.78rem"
            @click="$dispatch('open-upload-modal')">
        + Upload
    </button>
@endsection

@section('content')

<div x-data="{
    uploadOpen: false,
    confirmOpen: false,
    deleteUrl: null,
    typeFilter: 'all'
}"
@open-upload-modal.window="uploadOpen = true">

    {{-- Stats + filter bar --}}
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <div class="metric-card" style="flex:0 0 auto">
            <div class="metric-icon" style="background:#f3e8ff">
                <svg width="24" height="24" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
            </div>
            <div>
                <div class="metric-value">{{ $media->where('type','image')->count() }}</div>
                <div class="metric-label">Images</div>
            </div>
        </div>
        <div class="metric-card" style="flex:0 0 auto">
            <div class="metric-icon" style="background:#dbeafe">
                <svg width="24" height="24" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                </svg>
            </div>
            <div>
                <div class="metric-value">{{ $media->where('type','video')->count() }}</div>
                <div class="metric-label">Videos</div>
            </div>
        </div>

        <div class="d-flex gap-2 ms-auto flex-wrap">
            <button @click="typeFilter='all'"
                    class="btn btn-sm fw-bold text-uppercase"
                    :style="typeFilter==='all' ? 'background:#111827;color:white;border:1px solid #111827' : 'background:#f3f4f6;color:#374151;border:1px solid #e5e7eb'"
                    style="font-size:.72rem;border-radius:6px">All</button>
            <button @click="typeFilter='image'"
                    class="btn btn-sm fw-bold text-uppercase"
                    :style="typeFilter==='image' ? 'background:#7c3aed;color:white;border:1px solid #7c3aed' : 'background:rgba(124,58,237,.08);color:#7c3aed;border:1px solid rgba(124,58,237,.2)'"
                    style="font-size:.72rem;border-radius:6px">Images</button>
            <button @click="typeFilter='video'"
                    class="btn btn-sm fw-bold text-uppercase"
                    :style="typeFilter==='video' ? 'background:#2563eb;color:white;border:1px solid #2563eb' : 'background:rgba(37,99,235,.08);color:#2563eb;border:1px solid rgba(37,99,235,.2)'"
                    style="font-size:.72rem;border-radius:6px">Videos</button>
        </div>
    </div>

    {{-- Grid --}}
    @if($media->isEmpty())
        <div class="admin-form-card text-center py-5">
            <svg width="48" height="48" fill="none" stroke="#d1d5db" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
            <p class="text-secondary mb-0" style="font-size:.9rem">No files yet. Click <strong>+ Upload</strong> to get started.</p>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:1rem">
            @foreach($media as $item)
            <div class="admin-form-card p-0 overflow-hidden"
                 x-show="typeFilter === 'all' || typeFilter === '{{ $item->type }}'">

                {{-- Thumbnail --}}
                <div style="aspect-ratio:16/9;overflow:hidden;background:#111827;position:relative">
                    @if($item->isVideo())
                        <video src="{{ $item->url }}" muted loop preload="metadata"
                               style="width:100%;height:100%;object-fit:cover;display:block"
                               onmouseenter="this.play()" onmouseleave="this.pause();this.currentTime=0">
                        </video>
                        <div style="position:absolute;top:.5rem;left:.5rem;background:rgba(37,99,235,.85);color:white;font-size:.62rem;font-weight:700;padding:.15rem .45rem;border-radius:4px;text-transform:uppercase;letter-spacing:.04em">
                            Video
                        </div>
                    @else
                        <img src="{{ $item->url }}" alt="{{ $item->original_name }}"
                             style="width:100%;height:100%;object-fit:cover;display:block">
                    @endif
                </div>

                <div class="p-3">
                    <div class="fw-bold text-dark text-truncate mb-1" style="font-size:.82rem" title="{{ $item->original_name }}">
                        {{ $item->title ?: $item->original_name }}
                    </div>
                    <div class="text-secondary text-truncate mb-2" style="font-size:.72rem">
                        {{ $item->original_name }}
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-secondary" style="font-size:.70rem">
                            {{ $item->formatted_size }}&nbsp;&middot;&nbsp;{{ $item->created_at->format('d M Y') }}
                        </span>
                        <button type="button"
                                class="btn btn-sm fw-bold text-uppercase"
                                style="font-size:.68rem;padding:3px 8px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca"
                                @click="deleteUrl='{{ route('admin.media.destroy', $item) }}'; confirmOpen=true">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

    {{-- Upload Modal --}}
    <div x-show="uploadOpen"
         style="position:fixed;inset:0;z-index:9500;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;padding:1rem"
         @click.self="uploadOpen = false">
        <div style="background:#fff;border-radius:12px;width:100%;max-width:480px;overflow:hidden">
            <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
                <h3 class="fw-black text-uppercase fst-italic text-dark mb-0" style="font-size:1rem">Upload File</h3>
                <button type="button" class="btn-close" @click="uploadOpen = false"></button>
            </div>
            <div class="p-4">
                <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime"
                               class="form-control @error('file') is-invalid @enderror" required>
                        <div class="form-text">Images (JPG, PNG, GIF, WebP) or video (MP4, WebM, MOV). Max 200 MB.</div>
                        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Title <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span></label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="e.g. Monza race banner">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                            Upload
                        </button>
                        <button type="button" class="btn btn-outline-secondary fw-bold text-uppercase px-4" @click="uploadOpen = false">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Confirm Modal --}}
    <div x-show="confirmOpen"
         style="position:fixed;inset:0;z-index:9500;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;padding:1rem"
         @click.self="confirmOpen = false">
        <div style="background:#fff;border-radius:12px;width:100%;max-width:380px;overflow:hidden">
            <div class="p-4">
                <h3 class="fw-black text-uppercase fst-italic text-dark mb-2" style="font-size:1rem">Delete file?</h3>
                <p class="text-secondary mb-4" style="font-size:.88rem">
                    This permanently removes the file from storage. Any races or pages referencing it will lose the media.
                </p>
                <div class="d-flex gap-2">
                    <form :action="deleteUrl" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#dc2626">
                            Delete
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-secondary fw-bold text-uppercase px-4" @click="confirmOpen = false">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@if($errors->any())
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.dispatchEvent(new CustomEvent('open-upload-modal'));
    });
</script>
@endpush
@endif