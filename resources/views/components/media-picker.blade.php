@props(['name' => 'image', 'label' => 'Image', 'current' => null, 'currentType' => 'image', 'optional' => true, 'filterDefault' => 'all', 'folder' => null])

@php
    $currentUrl  = $current ? \Illuminate\Support\Facades\Storage::disk('media')->url($current) : '';
    $currentType = $currentType ?? 'image';
    $idleStyle   = 'background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb';
@endphp

<div class="mb-4"
     data-media-picker
     data-name="{{ $name }}"
     data-preview="{{ $currentUrl }}"
     data-preview-type="{{ $currentType }}"
     data-gallery-url="{{ route('admin.media.list') }}"
     data-folders-url="{{ route('admin.media.folders.list') }}"
     data-upload-url="{{ route('admin.media.store') }}"
     data-delete-base-url="{{ url('admin/media') }}/"
     data-csrf-token="{{ csrf_token() }}"
     data-filter-default="{{ $filterDefault }}"
     data-upload-folder="{{ $folder ?? '' }}">

    <label class="form-label">
        {{ $label }}
        @if($optional)
            <span class="text-secondary fw-normal" style="text-transform:none;font-size:.85em">(optional)</span>
        @endif
    </label>

    {{-- Preview / pick area --}}
    <div data-mp-border style="border:2px dashed #e5e7eb;border-radius:10px;overflow:hidden;min-height:120px;position:relative;transition:border-color .15s{{ $currentUrl ? ';border-color:#7c3aed' : '' }}">

        {{-- Media selected: show preview --}}
        <div data-mp-preview-wrap style="{{ $currentUrl ? '' : 'display:none' }}">

            {{-- Image / icon preview --}}
            <div data-mp-img-wrap class="position-relative" style="{{ $currentType === 'video' ? 'display:none' : '' }}">
                <img data-mp-preview-img src="{{ $currentUrl }}" style="width:100%;max-height:220px;object-fit:cover;display:block" alt="">
                <div class="position-absolute top-0 end-0 m-2 d-flex gap-1">
                    <button type="button" data-mp-replace
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(0,0,0,.55);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        Replace
                    </button>
                    <button type="button" data-mp-gallery
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(124,58,237,.85);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        Gallery
                    </button>
                    <button type="button" data-mp-clear
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(239,68,68,.75);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        ✕
                    </button>
                </div>
            </div>

            {{-- Video preview --}}
            <div data-mp-video-wrap class="position-relative" style="{{ $currentType !== 'video' ? 'display:none' : '' }}">
                <video data-mp-preview-video src="{{ $currentType === 'video' ? $currentUrl : '' }}"
                       controls muted style="width:100%;max-height:220px;display:block;background:#000"></video>
                <div class="position-absolute top-0 start-0 m-2">
                    <span style="background:rgba(37,99,235,.85);color:white;font-size:.62rem;font-weight:700;padding:.15rem .45rem;border-radius:4px;text-transform:uppercase;letter-spacing:.04em">Video</span>
                </div>
                <div class="position-absolute top-0 end-0 m-2 d-flex gap-1">
                    <button type="button" data-mp-replace
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(0,0,0,.55);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        Replace
                    </button>
                    <button type="button" data-mp-gallery
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(124,58,237,.85);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        Gallery
                    </button>
                    <button type="button" data-mp-clear
                            class="btn btn-sm text-white fw-bold"
                            style="background:rgba(239,68,68,.75);font-size:.7rem;padding:.25rem .55rem;border:0;border-radius:6px">
                        ✕
                    </button>
                </div>
            </div>
        </div>

        {{-- No media selected --}}
        <div data-mp-empty class="d-flex" style="{{ $currentUrl ? 'display:none' : '' }};min-height:118px">
            <button type="button" data-mp-upload-btn
                    class="flex-fill d-flex flex-column align-items-center justify-content-center py-4 text-secondary border-0 bg-transparent gap-2"
                    style="font-size:.82rem;cursor:pointer">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
                Upload new
            </button>
            <div style="width:1px;background:#e5e7eb;margin:.75rem 0"></div>
            <button type="button" data-mp-gallery-btn
                    class="flex-fill d-flex flex-column align-items-center justify-content-center py-4 text-secondary border-0 bg-transparent gap-2"
                    style="font-size:.82rem;cursor:pointer">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
                Browse gallery
            </button>
        </div>
    </div>

    {{-- File input (direct upload) --}}
    <input type="file" name="{{ $name }}" accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime"
           data-mp-file-input class="d-none">

    {{-- Hidden: stores media path (gallery pick or empty if direct upload) --}}
    <input type="hidden" name="{{ $name }}_path" data-mp-path-input value="{{ $current ?? '' }}">
    {{-- Hidden: 1 = media selected/unchanged, 0 = explicitly cleared --}}
    <input type="hidden" name="{{ $name }}_keep" data-mp-keep-input value="{{ $currentUrl ? '1' : '0' }}">

    @error($name)
        <div class="text-danger mt-1" style="font-size:.85rem">{{ $message }}</div>
    @enderror

    {{-- Gallery Modal --}}
    <div data-mp-modal
         style="display:none;position:fixed;inset:0;z-index:9500;background:rgba(0,0,0,.65);align-items:center;justify-content:center;padding:1rem">

        <div style="background:#fff;border-radius:12px;width:100%;max-width:900px;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">

            {{-- Header --}}
            <div class="d-flex align-items-center gap-2 px-4 py-3 border-bottom flex-shrink-0 flex-wrap">
                <h3 class="fw-black text-uppercase fst-italic text-dark mb-0 me-auto" style="font-size:1rem">Media Gallery</h3>

                {{-- Type filter --}}
                <div class="d-flex gap-1">
                    @php
                        $filters = [
                            'all'   => ['label' => 'All',    'active' => 'background:#111827;color:white;border:1px solid #111827'],
                            'image' => ['label' => 'Images', 'active' => 'background:#7c3aed;color:white;border:1px solid #7c3aed'],
                            'icon'  => ['label' => 'Icons',  'active' => 'background:#d97706;color:white;border:1px solid #d97706'],
                            'video' => ['label' => 'Videos', 'active' => 'background:#2563eb;color:white;border:1px solid #2563eb'],
                        ];
                    @endphp
                    @foreach($filters as $fKey => $fMeta)
                    <button type="button"
                            data-mp-filter="{{ $fKey }}"
                            data-mp-active-style="{{ $fMeta['active'] }}"
                            data-mp-idle-style="{{ $idleStyle }}"
                            class="btn btn-sm fw-bold text-uppercase"
                            style="font-size:.68rem;padding:.2rem .55rem;{{ ($filterDefault === $fKey) ? $fMeta['active'] : $idleStyle }}">
                        {{ $fMeta['label'] }}
                    </button>
                    @endforeach
                </div>

                {{-- Folder filter (populated by JS) --}}
                <div data-mp-folder-filter class="d-flex gap-1 flex-wrap" style="display:none"></div>

                <input type="text" data-mp-search placeholder="Search..." class="form-control form-control-sm" style="width:160px">

                {{-- Upload from modal --}}
                <button type="button" data-mp-modal-upload-btn
                        class="btn btn-sm fw-black text-uppercase text-white"
                        style="background:#7c3aed;font-size:.72rem">
                    <span data-mp-upload-text>+ Upload</span>
                    <span data-mp-uploading style="display:none">Uploading...</span>
                </button>
                <input type="file" data-mp-modal-upload accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime" class="d-none">

                <button type="button" data-mp-modal-close class="btn-close"></button>
            </div>

            {{-- Body --}}
            <div class="p-3 overflow-auto flex-grow-1">

                <div data-mp-loading class="d-flex align-items-center justify-content-center py-5 text-secondary gap-2" style="font-size:.9rem;display:none!important">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:xcl-spin 1s linear infinite">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/>
                    </svg>
                    Loading...
                </div>

                <div data-mp-empty-gallery class="text-center py-5" style="display:none;font-size:.9rem;color:#9ca3af">
                    <svg width="40" height="40" fill="none" stroke="#d1d5db" stroke-width="1.5" viewBox="0 0 24 24" class="mb-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                    </svg>
                    <br>No files found. Click <strong>+ Upload</strong> to add one.
                </div>

                <div data-mp-grid style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.6rem"></div>

            </div>
        </div>
    </div>
</div>
