function esc(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function initFolderCreate(wrap) {
    const trigger   = wrap.querySelector('[data-folder-new-trigger]');
    const form      = wrap.querySelector('[data-folder-create-form]');
    const nameInput = wrap.querySelector('[data-folder-name-input]');
    const saveBtn   = wrap.querySelector('[data-folder-save]');
    const cancelBtn = wrap.querySelector('[data-folder-cancel]');
    const errorEl   = wrap.querySelector('[data-folder-error]');
    const storeUrl  = wrap.dataset.storeUrl;
    const baseUrl   = wrap.dataset.baseUrl;

    function showCreate() {
        if (trigger) trigger.style.display = 'none';
        if (form)    form.style.display    = '';
        nameInput?.focus();
    }

    function hideCreate() {
        if (trigger) trigger.style.display = '';
        if (form)    form.style.display    = 'none';
        if (nameInput) nameInput.value     = '';
        if (errorEl)   errorEl.textContent = '';
    }

    trigger?.addEventListener('click', showCreate);
    cancelBtn?.addEventListener('click', hideCreate);

    nameInput?.addEventListener('keydown', e => {
        if (e.key === 'Enter')  { e.preventDefault(); save(); }
        if (e.key === 'Escape') hideCreate();
    });

    saveBtn?.addEventListener('click', save);

    async function save() {
        const name = nameInput?.value.trim();
        if (!name) return;
        if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Creating…'; }
        if (errorEl) errorEl.textContent = '';

        try {
            const res  = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify({ name }),
            });
            const data = await res.json();
            if (data.slug) {
                const card = buildFolderCard(data, baseUrl);
                wrap.parentElement.insertBefore(card, wrap);
                hideCreate();
            } else {
                if (errorEl) errorEl.textContent = data.message ?? 'Something went wrong.';
            }
        } catch {
            if (errorEl) errorEl.textContent = 'Network error.';
        }

        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Create'; }
    }

    function buildFolderCard({ slug, name }, base) {
        const a = document.createElement('a');
        a.href = base + '?folder=' + slug;
        a.className = 'text-decoration-none';
        a.innerHTML = `
            <div class="admin-form-card p-0 overflow-hidden"
                 style="cursor:pointer;transition:transform .15s ease,box-shadow .15s ease"
                 onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)'"
                 onmouseleave="this.style.transform='';this.style.boxShadow=''">
                <div style="aspect-ratio:4/3;background:#f9fafb;display:flex;align-items:center;justify-content:center;font-size:2.5rem">📁</div>
                <div class="p-3">
                    <div class="fw-black text-dark" style="font-size:.9rem">${esc(name)}</div>
                    <div class="text-secondary" style="font-size:.75rem">0 items</div>
                </div>
            </div>
        `;
        return a;
    }
}

function initMediaGrid(wrap) {
    const filterBtns = wrap.querySelectorAll('[data-media-type-btn]');
    const items      = wrap.querySelectorAll('[data-media-item]');
    const deleteForm = wrap.querySelector('[data-delete-form]');
    let current = 'all';

    function applyFilter() {
        items.forEach(item => {
            item.style.display = (current === 'all' || item.dataset.mediaType === current) ? '' : 'none';
        });
        filterBtns.forEach(btn => {
            const active = btn.dataset.mediaType === current;
            btn.setAttribute('style', 'font-size:.72rem;border-radius:6px;' + (active ? btn.dataset.activeStyle : btn.dataset.idleStyle));
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            current = btn.dataset.mediaType;
            applyFilter();
        });
    });

    wrap.querySelectorAll('[data-media-delete]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm('Delete this file?')) return;
            if (!deleteForm) return;
            deleteForm.action = btn.dataset.url;
            deleteForm.submit();
        });
    });

    wrap.querySelectorAll('[data-media-edit]').forEach(btn => {
        const card       = btn.closest('[data-media-item]');
        const titleEl    = card?.querySelector('[data-media-title]');
        const renameEl   = card?.querySelector('[data-media-rename]');
        const input      = card?.querySelector('[data-media-rename-input]');
        const saveBtn    = card?.querySelector('[data-media-rename-save]');
        const cancelBtn  = card?.querySelector('[data-media-rename-cancel]');

        function showRename() {
            if (input) input.value = titleEl?.textContent.trim() ?? '';
            renameEl && (renameEl.style.display = '');
            titleEl  && (titleEl.style.display  = 'none');
            btn.style.display = 'none';
            input?.focus();
        }

        function hideRename() {
            renameEl && (renameEl.style.display = 'none');
            titleEl  && (titleEl.style.display  = '');
            btn.style.display = '';
        }

        btn.addEventListener('click', showRename);
        cancelBtn?.addEventListener('click', hideRename);
        input?.addEventListener('keydown', e => {
            if (e.key === 'Enter')  { e.preventDefault(); doSave(); }
            if (e.key === 'Escape') hideRename();
        });

        async function doSave() {
            const title = input?.value.trim() ?? '';
            if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }
            try {
                const r = await fetch(btn.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': btn.dataset.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ title }),
                });
                const data = await r.json();
                if (r.ok && data.ok) {
                    if (titleEl) titleEl.textContent = data.title;
                    if (input)   input.value         = data.title;
                    hideRename();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Name updated.' } }));
                } else {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Failed to save.' } }));
                }
            } catch {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Network error.' } }));
            }
            if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save'; }
        }

        saveBtn?.addEventListener('click', doSave);
    });

    wrap.querySelectorAll('[data-media-move]').forEach(select => {
        select.addEventListener('change', async () => {
            const folder = select.value === '__uncat__' ? '' : select.value;
            const url    = select.dataset.url;
            const csrf   = select.dataset.csrf;
            const card   = select.closest('[data-media-item]');
            try {
                const r = await fetch(url, {
                    method:  'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body:    JSON.stringify({ folder }),
                });
                if (r.ok) {
                    card?.remove();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Media moved.' } }));
                } else {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Failed to move media.' } }));
                    select.value = '';
                }
            } catch {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Network error.' } }));
                select.value = '';
            }
        });
    });

    applyFilter();
}

function formatFileSize(bytes) {
    if (bytes < 1024)        return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function initUploadModal(modal) {
    const tabBtns    = modal.querySelectorAll('[data-modal-tab]');
    const panels     = modal.querySelectorAll('[data-modal-panel]');
    const fileInput  = modal.querySelector('[data-media-file-input]');
    const dropzone   = modal.querySelector('[data-upload-dropzone]');
    const fileList   = modal.querySelector('[data-upload-filelist]');
    const uploadForm = modal.querySelector('[data-modal-panel="upload"]');
    const submitBtn  = uploadForm?.querySelector('[data-upload-submit]');

    function switchTab(source) {
        tabBtns.forEach(btn => {
            const on = btn.dataset.modalTab === source;
            btn.classList.toggle('text-secondary', !on);
            btn.style.borderBottom = on ? '2px solid ' + (btn.dataset.color ?? '#7c3aed') : '';
        });
        panels.forEach(p => {
            p.style.display = p.dataset.modalPanel === source ? '' : 'none';
        });
    }

    tabBtns.forEach(btn => btn.addEventListener('click', () => switchTab(btn.dataset.modalTab)));

    modal.querySelectorAll('[data-media-type-radio]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (!fileInput) return;
            fileInput.accept = radio.value === 'video'
                ? 'video/mp4,video/webm,video/quicktime,video/x-matroska'
                : 'image/*';
        });
    });

    // ── Drag-drop zone ───────────────────────────────────────────────────────
    let selectedFiles = [];

    function renderFileList() {
        if (!fileList) return;
        if (!selectedFiles.length) { fileList.innerHTML = ''; return; }
        fileList.innerHTML = selectedFiles.map((f, i) => `
            <li data-file-idx="${i}"
                style="display:flex;align-items:center;gap:.5rem;padding:.3rem .5rem;border-radius:5px;background:#f9fafb;margin-bottom:.25rem;font-size:.75rem">
                <span data-file-status style="flex-shrink:0;font-size:.85rem">&#128206;</span>
                <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(f.name)}</span>
                <span style="flex-shrink:0;color:#9ca3af;font-size:.68rem">${formatFileSize(f.size)}</span>
            </li>`).join('');
        if (submitBtn) submitBtn.textContent = selectedFiles.length > 1 ? `Upload (${selectedFiles.length} files)` : 'Upload';
    }

    function handleFiles(files) {
        selectedFiles = [...files];
        renderFileList();
    }

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());

        dropzone.addEventListener('dragover', e => {
            e.preventDefault();
            dropzone.style.borderColor = '#7c3aed';
            dropzone.style.background  = '#f5f3ff';
        });
        ['dragleave', 'dragend'].forEach(ev => dropzone.addEventListener(ev, () => {
            dropzone.style.borderColor = '#d1d5db';
            dropzone.style.background  = '';
        }));
        dropzone.addEventListener('drop', e => {
            e.preventDefault();
            dropzone.style.borderColor = '#d1d5db';
            dropzone.style.background  = '';
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', () => handleFiles(fileInput.files));
    }

    // ── Multi-file AJAX upload ───────────────────────────────────────────────
    if (uploadForm) {
        uploadForm.addEventListener('submit', async e => {
            if (!selectedFiles.length) return; // no files picked — let browser validate
            e.preventDefault();

            const action   = uploadForm.action;
            const csrf     = uploadForm.querySelector('[name="_token"]')?.value ?? csrfToken();
            const type     = uploadForm.querySelector('[name="type"]:checked')?.value ?? 'image';
            const category = uploadForm.querySelector('[name="category"]')?.value ?? '';
            const title    = uploadForm.querySelector('[name="title"]')?.value ?? '';

            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Uploading…'; }

            let errors = 0;
            for (let i = 0; i < selectedFiles.length; i++) {
                const li       = fileList?.querySelector(`[data-file-idx="${i}"]`);
                const statusEl = li?.querySelector('[data-file-status]');
                if (statusEl) statusEl.textContent = '⏳';

                const fd = new FormData();
                fd.append('_token', csrf);
                fd.append('source', 'upload');
                fd.append('file', selectedFiles[i]);
                fd.append('type', type);
                if (category) fd.append('category', category);
                if (title)    fd.append('title', title);

                try {
                    const r = await fetch(action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd,
                    });
                    if (r.ok) {
                        if (statusEl) statusEl.textContent = '✅';
                    } else {
                        const data = await r.json().catch(() => ({}));
                        if (statusEl) { statusEl.textContent = '❌'; li.title = data.message ?? 'Upload failed'; }
                        errors++;
                    }
                } catch {
                    if (statusEl) statusEl.textContent = '❌';
                    errors++;
                }
            }

            if (submitBtn) {
                submitBtn.disabled    = false;
                submitBtn.textContent = errors ? `Done (${errors} error${errors > 1 ? 's' : ''}) — reloading…` : 'Done! Reloading…';
            }
            setTimeout(() => location.reload(), 900);
        });
    }

    switchTab(modal.dataset.activeTab || 'upload');
}

export function initMediaIndex() {
    const folderNewWrap = document.querySelector('[data-folder-new-wrap]');
    if (folderNewWrap) initFolderCreate(folderNewWrap);

    const mediaGrid = document.querySelector('[data-media-grid]');
    if (mediaGrid) initMediaGrid(mediaGrid);

    const uploadModal = document.getElementById('upload-modal');
    if (uploadModal) initUploadModal(uploadModal);
}
