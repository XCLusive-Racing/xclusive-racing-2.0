export function initMediaPickers() {
    document.querySelectorAll('[data-media-picker]').forEach(picker => {
        initSinglePicker(picker);
    });
}

function esc(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function initSinglePicker(picker) {
    const galleryUrl    = picker.dataset.galleryUrl;
    const foldersUrl    = picker.dataset.foldersUrl;
    const uploadUrl     = picker.dataset.uploadUrl;
    const deleteBaseUrl = picker.dataset.deleteBaseUrl;
    const csrfToken     = picker.dataset.csrfToken;
    const filterDefault = picker.dataset.filterDefault || 'all';

    // DOM refs
    const previewWrap      = picker.querySelector('[data-mp-preview-wrap]');
    const emptyWrap        = picker.querySelector('[data-mp-empty]');
    const fileInput        = picker.querySelector('[data-mp-file-input]');
    const pathInput        = picker.querySelector('[data-mp-path-input]');
    const keepInput        = picker.querySelector('[data-mp-keep-input]');
    const previewBorder    = picker.querySelector('[data-mp-border]');
    const folderFilterWrap = picker.querySelector('[data-mp-folder-filter]');

    // Gallery modal
    const modal         = picker.querySelector('[data-mp-modal]');
    const modalUpload   = picker.querySelector('[data-mp-modal-upload]');
    const searchInput   = picker.querySelector('[data-mp-search]');
    const filterBtns    = picker.querySelectorAll('[data-mp-filter]');
    const grid          = picker.querySelector('[data-mp-grid]');
    const loadingEl     = picker.querySelector('[data-mp-loading]');
    const emptyEl       = picker.querySelector('[data-mp-empty-gallery]');
    const uploadingEl   = picker.querySelector('[data-mp-uploading]');
    const uploadBtnText = picker.querySelector('[data-mp-upload-text]');

    // State
    let preview             = picker.dataset.preview || '';
    let previewType         = picker.dataset.previewType || 'image';
    let galleryItems        = [];
    let galleryFilter       = filterDefault;
    let galleryFolderFilter = 'all';
    let searchQuery         = '';
    let galleryLoaded       = false;
    let foldersLoaded       = false;
    let folderList          = [];

    // ── Preview helpers ───────────────────────────────────────────────────────
    function dispatchChange() {
        picker.dispatchEvent(new CustomEvent('mp:change', {
            bubbles: true,
            detail: { url: preview, type: previewType, name: picker.dataset.name || '' },
        }));
    }

    function renderPreview() {
        if (previewBorder) previewBorder.style.borderColor = preview ? '#7c3aed' : '';

        if (preview) {
            previewWrap.style.display = '';
            emptyWrap.style.display   = 'none';

            const imgEl   = previewWrap.querySelector('[data-mp-preview-img]');
            const videoEl = previewWrap.querySelector('[data-mp-preview-video]');
            const imgWrap = previewWrap.querySelector('[data-mp-img-wrap]');
            const vidWrap = previewWrap.querySelector('[data-mp-video-wrap]');

            if (previewType === 'video') {
                if (imgWrap)  imgWrap.style.display  = 'none';
                if (vidWrap)  vidWrap.style.display  = '';
                if (videoEl) videoEl.src = preview;
            } else {
                if (vidWrap) vidWrap.style.display  = 'none';
                if (imgWrap) imgWrap.style.display  = '';
                if (imgEl)   imgEl.src = preview;
            }
        } else {
            previewWrap.style.display = 'none';
            emptyWrap.style.display   = '';
        }

        if (keepInput) keepInput.value = preview ? '1' : '0';
    }

    function clear() {
        preview = '';
        previewType = 'image';
        if (pathInput) pathInput.value = '';
        if (fileInput) fileInput.value = '';
        renderPreview();
        dispatchChange();
    }

    // ── File input (direct upload) ────────────────────────────────────────────
    fileInput?.addEventListener('change', e => {
        const f = e.target.files[0];
        if (!f) return;
        preview     = URL.createObjectURL(f);
        previewType = f.type.startsWith('video/') ? 'video' : 'image';
        if (pathInput) pathInput.value = '';
        renderPreview();
        dispatchChange();
    });

    // Button wiring in preview wrap
    previewWrap?.querySelector('[data-mp-replace]')?.addEventListener('click', () => fileInput?.click());
    previewWrap?.querySelector('[data-mp-gallery]')?.addEventListener('click', () => openGallery());
    previewWrap?.querySelector('[data-mp-clear]')?.addEventListener('click', clear);

    // Empty wrap buttons
    emptyWrap?.querySelector('[data-mp-upload-btn]')?.addEventListener('click', () => fileInput?.click());
    emptyWrap?.querySelector('[data-mp-gallery-btn]')?.addEventListener('click', () => openGallery());

    // ── Gallery modal ─────────────────────────────────────────────────────────
    function openGallery() {
        if (modal) modal.style.display = 'flex';
        if (!galleryLoaded) loadGallery();
    }

    function closeGallery() {
        if (modal) modal.style.display = 'none';
    }

    modal?.addEventListener('click', e => { if (e.target === modal) closeGallery(); });
    modal?.querySelector('[data-mp-modal-close]')?.addEventListener('click', closeGallery);

    async function loadGallery() {
        if (loadingEl) loadingEl.style.display = '';
        if (grid)      grid.style.display      = 'none';
        if (emptyEl)   emptyEl.style.display   = 'none';
        try {
            const fetches = [fetch(galleryUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })];
            if (!foldersLoaded && foldersUrl) {
                fetches.push(fetch(foldersUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }));
            }
            const [galleryResp, foldersResp] = await Promise.all(fetches);
            galleryItems  = await galleryResp.json();
            galleryLoaded = true;
            if (foldersResp) {
                folderList    = await foldersResp.json();
                foldersLoaded = true;
                renderFolderFilter();
            }
        } catch {
            galleryItems = [];
        }
        if (loadingEl) loadingEl.style.display = 'none';
        if (grid)      grid.style.display      = '';
        renderGallery();
    }

    function renderFolderFilter() {
        if (!folderFilterWrap || !folderList.length) return;

        const idleStyle   = 'font-size:.62rem;padding:.15rem .45rem;background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb';
        const activeStyle = 'font-size:.62rem;padding:.15rem .45rem;background:#111827;color:white;border:1px solid #111827';

        const allBtn = `<button type="button" data-mp-folder="all" class="btn btn-sm fw-bold text-uppercase"
            style="${activeStyle}">All</button>`;

        const folderBtns = folderList.map(f => `
            <button type="button" data-mp-folder="${esc(f.slug)}" class="btn btn-sm fw-bold text-uppercase"
                style="${idleStyle}">📁 ${esc(f.name)}</button>
        `).join('');

        const uncatBtn = `<button type="button" data-mp-folder="__uncat__" class="btn btn-sm fw-bold text-uppercase"
            style="${idleStyle}">📂 Uncat</button>`;

        folderFilterWrap.innerHTML = allBtn + folderBtns + uncatBtn;
        folderFilterWrap.style.display = '';

        folderFilterWrap.querySelectorAll('[data-mp-folder]').forEach(btn => {
            btn.addEventListener('click', () => {
                galleryFolderFilter = btn.dataset.mpFolder;
                applyFolderFilterStyles();
                renderGallery();
            });
        });
    }

    function applyFolderFilterStyles() {
        if (!folderFilterWrap) return;
        const idleStyle   = 'font-size:.62rem;padding:.15rem .45rem;background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb';
        const activeStyle = 'font-size:.62rem;padding:.15rem .45rem;background:#111827;color:white;border:1px solid #111827';
        folderFilterWrap.querySelectorAll('[data-mp-folder]').forEach(btn => {
            btn.setAttribute('style', btn.dataset.mpFolder === galleryFolderFilter ? activeStyle : idleStyle);
        });
    }

    function getFiltered() {
        return galleryItems.filter(i => {
            const matchType   = galleryFilter === 'all' || i.type === galleryFilter;
            const matchFolder = galleryFolderFilter === 'all' ||
                (galleryFolderFilter === '__uncat__' ? !i.folder : i.folder === galleryFolderFilter);
            if (!matchType || !matchFolder) return false;
            if (!searchQuery) return true;
            const q = searchQuery.toLowerCase();
            return (i.original_name || '').toLowerCase().includes(q) ||
                   (i.title || '').toLowerCase().includes(q);
        });
    }

    function buildItemHTML(item) {
        const isVideo = item.type === 'video';
        const isIcon  = item.type === 'icon';
        let inner = '';
        if (isVideo) {
            inner = `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#1e293b">
                <svg width="28" height="28" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                </svg>
            </div>
            <div style="position:absolute;top:.3rem;left:.3rem;background:rgba(37,99,235,.9);color:white;font-size:.55rem;font-weight:700;padding:.1rem .35rem;border-radius:3px;text-transform:uppercase">Video</div>`;
        } else {
            const fit = isIcon ? 'contain' : 'cover';
            const sz  = isIcon ? '70%' : '100%';
            inner = `<img src="${esc(item.url)}" alt="${esc(item.original_name)}" style="width:${sz};height:${sz};object-fit:${fit};display:block${isIcon ? ';margin:auto' : ''}">`;
        }
        return `
            <div data-mp-item data-mp-item-id="${item.id}"
                 style="border:2px solid transparent;border-radius:8px;overflow:hidden;cursor:pointer;position:relative;aspect-ratio:1;background:#111827;transition:border-color .12s,transform .12s"
                 onmouseenter="this.style.borderColor='#7c3aed';this.style.transform='scale(1.03)';this.querySelector('[data-mp-del]').style.opacity='1'"
                 onmouseleave="this.style.borderColor='transparent';this.style.transform='scale(1)';this.querySelector('[data-mp-del]').style.opacity='0'">
                ${inner}
                <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.7));padding:.3rem .4rem">
                    <div class="text-white text-truncate" style="font-size:.62rem">${esc(item.title || item.original_name)}</div>
                    <div style="font-size:.58rem;color:rgba(255,255,255,.55)">${esc(item.size || '')}</div>
                </div>
                <button type="button" data-mp-del
                        style="position:absolute;top:.3rem;right:.3rem;background:rgba(220,38,38,.85);border:none;color:white;width:20px;height:20px;border-radius:4px;font-size:.65rem;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s"
                        title="Delete">✕</button>
            </div>
        `;
    }

    function renderGallery() {
        if (!grid) return;
        const filtered = getFiltered();
        if (emptyEl) emptyEl.style.display = filtered.length === 0 ? '' : 'none';
        if (filtered.length === 0) { grid.innerHTML = ''; return; }

        grid.innerHTML = filtered.map(buildItemHTML).join('');

        // Select
        grid.querySelectorAll('[data-mp-item]').forEach(el => {
            const id   = parseInt(el.dataset.mpItemId, 10);
            const item = galleryItems.find(i => i.id === id);
            if (!item) return;
            el.addEventListener('click', e => {
                if (e.target.closest('[data-mp-del]')) return;
                selectItem(item);
            });
            el.querySelector('[data-mp-del]')?.addEventListener('click', e => {
                e.stopPropagation();
                deleteItem(item);
            });
        });
    }

    function selectItem(item) {
        preview     = item.url;
        previewType = item.type || 'image';
        if (pathInput) pathInput.value = item.path;
        if (fileInput) fileInput.value = '';
        closeGallery();
        renderPreview();
        dispatchChange();
    }

    async function deleteItem(item) {
        if (!confirm('Delete "' + (item.title || item.original_name) + '" from the library? This cannot be undone.')) return;
        const r = await fetch(deleteBaseUrl + item.id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (r.ok) {
            galleryItems = galleryItems.filter(i => i.id !== item.id);
            if (pathInput?.value && preview === item.url) clear();
            renderGallery();
        }
    }

    // Filter buttons
    function applyFilterStyles() {
        filterBtns.forEach(b => {
            const isActive = b.dataset.mpFilter === galleryFilter;
            b.setAttribute('style', 'font-size:.68rem;padding:.2rem .55rem;' + (isActive ? b.dataset.mpActiveStyle : b.dataset.mpIdleStyle));
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            galleryFilter = btn.dataset.mpFilter;
            applyFilterStyles();
            renderGallery();
        });
    });

    applyFilterStyles();

    searchInput?.addEventListener('input', () => {
        searchQuery = searchInput.value;
        renderGallery();
    });

    // Upload from modal
    modalUpload?.addEventListener('change', async e => {
        const f = e.target.files[0];
        if (!f) return;
        preview     = URL.createObjectURL(f);
        previewType = f.type.startsWith('video/') ? 'video' : 'image';
        if (uploadingEl)   uploadingEl.style.display   = '';
        if (uploadBtnText) uploadBtnText.style.display = 'none';
        try {
            const fd = new FormData();
            fd.append('file', f);
            fd.append('_token', csrfToken);
            const r = await fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (r.ok) {
                const data = await r.json();
                if (pathInput) pathInput.value = data.path;
                preview     = data.url;
                previewType = data.type || 'image';
                galleryItems  = [];
                galleryLoaded = false;
            }
        } finally {
            if (uploadingEl)   uploadingEl.style.display   = 'none';
            if (uploadBtnText) uploadBtnText.style.display = '';
            closeGallery();
            renderPreview();
            dispatchChange();
        }
    });

    // Upload button in modal header triggers hidden input
    picker.querySelector('[data-mp-modal-upload-btn]')?.addEventListener('click', () => modalUpload?.click());

    // Init
    renderPreview();
}

// Legacy Alpine export — still registered in app.js until all usages are converted
export default function xcMediaPicker(config) {
    return {
        preview: config.preview || '',
        previewType: config.previewType || 'image',
        mediaPath: config.mediaPath || '',
        galleryUrl: config.galleryUrl,
        uploadUrl: config.uploadUrl,
        deleteBaseUrl: config.deleteBaseUrl,
        csrfToken: config.csrfToken,
        galleryOpen: false,
        galleryItems: [],
        gallerySearch: '',
        galleryFilter: config.filterDefault || 'all',
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

        async deleteMedia(item) {
            if (!confirm('Delete "' + (item.title || item.original_name) + '" from the library? This cannot be undone.')) return;
            const r = await fetch(this.deleteBaseUrl + item.id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            if (r.ok) {
                this.galleryItems = this.galleryItems.filter(i => i.id !== item.id);
                if (this.mediaPath === item.path) this.clear();
            }
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
        },
    };
}
