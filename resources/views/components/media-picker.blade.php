@props(['name' => 'image', 'label' => 'Image', 'current' => null, 'currentType' => 'image', 'optional' => true])

@once
@push('scripts')
<script>
function xcMediaPicker(config) {
    return {
        preview: config.preview || '',
        previewType: config.previewType || 'image',
        mediaPath: config.mediaPath || '',
        galleryUrl: config.galleryUrl,
        uploadUrl: config.uploadUrl,
        csrfToken: config.csrfToken,
        galleryOpen: false,
        galleryItems: [],
        gallerySearch: '',
        galleryFilter: 'all',
        galleryLoading: false,
        modalUploading: false,

        async openGallery() {
            this.galleryOpen = true;
            if (!this.galleryItems.length) {
                this.galleryLoading = true;
                try {
                    const r = await fetch(this.galleryUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    this.galleryItems = await r.json();
                } finally {
                    this.galleryLoading = false;
                }
            }
        },

        selectGallery(item) {
            this.preview = item.url;
            this.previewType = item.type || 'image';
            this.mediaPath = item.path;
            this.$refs.fileInput.value = '';
            this.galleryOpen = false;
        },

        onFileChange(e) {
            const f = e.target.files[0];
            if (!f) return;
            this.preview = URL.createObjectURL(f);
            this.previewType = f.type.startsWith('video/') ? 'video' : 'image';
            this.mediaPath = '';
        },

        async onModalUpload(e) {
            const f = e.target.files[0];
            if (!f) return;
            this.preview = URL.createObjectURL(f);
            this.previewType = f.type.startsWith('video/') ? 'video' : 'image';
            this.modalUploading = true;
            try {
                const fd = new FormData();
                fd.append('file', f);
                fd.append('_token', this.csrfToken);
                const r = await fetch(this.uploadUrl, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (r.ok) {
                    const data = await r.json();
                    this.mediaPath = data.path;
                    this.preview = data.url;
                    this.previewType = data.type || 'image';
                    this.galleryItems = [];
                }
            } finally {
                this.modalUploading = false;
                this.galleryOpen = false;
            }
        },

        clear() {
            this.preview = '';
            this.previewType = 'image';
            this.mediaPath = '';
            this.$refs.fileInput.value = '';
        },

        get filtered() {
            return this.galleryItems.filter(i => {
                const matchType = this.galleryFilter === 'all' || i.type === this.galleryFilter;
                if (!this.gallerySearch) return matchType;
                const q = this.gallerySearch.toLowerCase();
                return matchType && (
                    (i.original_name || '').toLowerCase().includes(q) ||
                    (i.title || '').toLowerCase().includes(q)
                );
            });
        }
    };
}
</script>
@endpush
@endonce

@php
    $currentUrl  = $current ? asset('storage/'.$current) : '';
    $currentType = $currentType ?? 'image';
@endphp

<div class="mb-4" x-data="xcMediaPicker({
    preview: '{{ $currentUrl }}',
    previewType: '{{ $currentType }}',
    mediaPath: '{{ $current ?? '' }}',
    galleryUrl: '{{ route('admin.media.list') }}',
    uploadUrl: '{{ route('admin.media.store') }}',
    csrfToken: '{{ csrf_token() }}'
})">

    <label class="form-label">
        {{ $label }}
        @if($optional)
            <span class="text-secondary fw-normal" style="text-transform:none;font-size:.85em">(optional)</span>
        @endif
    </label>

    {{-- Preview / pick area --}}
    <div style="border:2px dashed #e5e7eb;border-radius:10px;overflow:hidden;min-height:120px;position:relative;transition:border-color .15s"
         :style="preview ? 'border-color:#7c3aed' : ''">

        {{-- Media selected: show preview with action buttons --}}
        <template x-if="preview && previewType === 'image'">
            <div class="position-relative">
                <img :src="preview" style="width:100%;max-height:220px;object-fit:cover;display:block">
                <div class="position-absolute top-0 end-0 m-2 d-flex gap-1">
                    <button type="button" @click="$refs.fileInput.click()"
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(0,0,0,.55);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        Replace
                    </button>
                    <button type="button" @click="openGallery()"
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(124,58,237,.85);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        Gallery
                    </button>
                    <button type="button" @click="clear()"
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(239,68,68,.75);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        ✕
                    </button>
                </div>
            </div>
        </template>

        <template x-if="preview && previewType === 'video'">
            <div class="position-relative">
                <video :src="preview" controls muted style="width:100%;max-height:220px;display:block;background:#000"></video>
                <div class="position-absolute top-0 start-0 m-2">
                    <span style="background:rgba(37,99,235,.85);color:white;font-size:.62rem;font-weight:700;padding:.15rem .45rem;border-radius:4px;text-transform:uppercase;letter-spacing:.04em">Video</span>
                </div>
                <div class="position-absolute top-0 end-0 m-2 d-flex gap-1">
                    <button type="button" @click="$refs.fileInput.click()"
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(0,0,0,.55);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        Replace
                    </button>
                    <button type="button" @click="openGallery()"
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(124,58,237,.85);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        Gallery
                    </button>
                    <button type="button" @click="clear()"
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(239,68,68,.75);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        ✕
                    </button>
                </div>
            </div>
        </template>

        {{-- No media selected: show two options --}}
        <template x-if="!preview">
            <div class="d-flex" style="min-height:118px">
                <button type="button" @click="$refs.fileInput.click()"
                        class="flex-fill d-flex flex-column align-items-center justify-content-center py-4 text-secondary border-0 bg-transparent gap-2"
                        style="font-size:.82rem;cursor:pointer">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                    Upload new
                </button>
                <div style="width:1px;background:#e5e7eb;margin:.75rem 0"></div>
                <button type="button" @click="openGallery()"
                        class="flex-fill d-flex flex-column align-items-center justify-content-center py-4 text-secondary border-0 bg-transparent gap-2"
                        style="font-size:.82rem;cursor:pointer">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                    </svg>
                    Browse gallery
                </button>
            </div>
        </template>
    </div>

    {{-- File input (images + videos) --}}
    <input type="file" name="{{ $name }}" accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime"
           x-ref="fileInput" class="d-none" @change="onFileChange">

    {{-- Hidden: stores media path (gallery pick or empty if direct upload) --}}
    <input type="hidden" name="{{ $name }}_path" x-model="mediaPath">

    @error($name)
        <div class="text-danger mt-1" style="font-size:.85rem">{{ $message }}</div>
    @enderror

    {{-- Gallery Modal --}}
    <div x-show="galleryOpen"
         style="position:fixed;inset:0;z-index:9500;background:rgba(0,0,0,.65);display:flex;align-items:center;justify-content:center;padding:1rem"
         @click.self="galleryOpen = false">

        <div style="background:#fff;border-radius:12px;width:100%;max-width:900px;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">

            {{-- Header --}}
            <div class="d-flex align-items-center gap-2 px-4 py-3 border-bottom flex-shrink-0 flex-wrap">
                <h3 class="fw-black text-uppercase fst-italic text-dark mb-0 me-auto" style="font-size:1rem">Media Gallery</h3>

                {{-- Type filter --}}
                <div class="d-flex gap-1">
                    <button type="button" @click="galleryFilter='all'"
                            class="btn btn-sm fw-bold text-uppercase"
                            :style="galleryFilter==='all' ? 'background:#111827;color:white;border:1px solid #111827' : 'background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb'"
                            style="font-size:.68rem;padding:.2rem .55rem">All</button>
                    <button type="button" @click="galleryFilter='image'"
                            class="btn btn-sm fw-bold text-uppercase"
                            :style="galleryFilter==='image' ? 'background:#7c3aed;color:white;border:1px solid #7c3aed' : 'background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb'"
                            style="font-size:.68rem;padding:.2rem .55rem">Images</button>
                    <button type="button" @click="galleryFilter='video'"
                            class="btn btn-sm fw-bold text-uppercase"
                            :style="galleryFilter==='video' ? 'background:#2563eb;color:white;border:1px solid #2563eb' : 'background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb'"
                            style="font-size:.68rem;padding:.2rem .55rem">Videos</button>
                </div>

                <input type="text" x-model="gallerySearch" placeholder="Search..." class="form-control form-control-sm" style="width:160px">

                {{-- Upload from modal --}}
                <button type="button"
                        class="btn btn-sm fw-black text-uppercase text-white"
                        style="background:#7c3aed;font-size:.72rem"
                        :disabled="modalUploading"
                        @click="$refs.modalUpload.click()">
                    <span x-show="!modalUploading">+ Upload</span>
                    <span x-show="modalUploading">Uploading...</span>
                </button>
                <input type="file" x-ref="modalUpload" accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime" class="d-none" @change="onModalUpload">

                <button type="button" class="btn-close" @click="galleryOpen = false"></button>
            </div>

            {{-- Body --}}
            <div class="p-3 overflow-auto flex-grow-1">

                <template x-if="galleryLoading">
                    <div class="d-flex align-items-center justify-content-center py-5 text-secondary gap-2" style="font-size:.9rem">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:xcl-spin 1s linear infinite">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/>
                        </svg>
                        Loading...
                    </div>
                </template>

                <template x-if="!galleryLoading && filtered.length === 0">
                    <div class="text-center py-5" style="font-size:.9rem;color:#9ca3af">
                        <svg width="40" height="40" fill="none" stroke="#d1d5db" stroke-width="1.5" viewBox="0 0 24 24" class="mb-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                        <br>No files found. Click <strong>+ Upload</strong> to add one.
                    </div>
                </template>

                <template x-if="!galleryLoading && filtered.length > 0">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.6rem">
                        <template x-for="item in filtered" :key="item.id">
                            <div @click="selectGallery(item)"
                                 style="border:2px solid transparent;border-radius:8px;overflow:hidden;cursor:pointer;position:relative;aspect-ratio:1;background:#111827;transition:border-color .12s,transform .12s"
                                 @mouseenter="$el.style.borderColor='#7c3aed';$el.style.transform='scale(1.03)'"
                                 @mouseleave="$el.style.borderColor='transparent';$el.style.transform='scale(1)'">

                                {{-- Image preview --}}
                                <template x-if="item.type === 'image'">
                                    <img :src="item.url" :alt="item.original_name"
                                         style="width:100%;height:100%;object-fit:cover;display:block">
                                </template>

                                {{-- Video preview --}}
                                <template x-if="item.type === 'video'">
                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#1e293b">
                                        <svg width="28" height="28" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.7">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                                        </svg>
                                    </div>
                                </template>

                                {{-- Type badge --}}
                                <template x-if="item.type === 'video'">
                                    <div style="position:absolute;top:.3rem;left:.3rem;background:rgba(37,99,235,.9);color:white;font-size:.55rem;font-weight:700;padding:.1rem .35rem;border-radius:3px;text-transform:uppercase">
                                        Video
                                    </div>
                                </template>

                                {{-- Name overlay --}}
                                <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.7));padding:.3rem .4rem">
                                    <div class="text-white text-truncate" style="font-size:.62rem" x-text="item.title || item.original_name"></div>
                                    <div style="font-size:.58rem;color:rgba(255,255,255,.55)" x-text="item.size"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@once
<style>
@keyframes xcl-spin { to { transform: rotate(360deg); } }
</style>
@endonce