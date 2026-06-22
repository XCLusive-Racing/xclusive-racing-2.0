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

    applyFilter();
}

function initUploadModal(modal) {
    const tabBtns   = modal.querySelectorAll('[data-modal-tab]');
    const panels    = modal.querySelectorAll('[data-modal-panel]');
    const fileInput = modal.querySelector('[data-media-file-input]');

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

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.modalTab));
    });

    modal.querySelectorAll('[data-media-type-radio]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (!fileInput) return;
            fileInput.accept = radio.value === 'video'
                ? 'video/mp4,video/webm,video/quicktime,video/x-matroska'
                : 'image/*';
        });
    });

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
