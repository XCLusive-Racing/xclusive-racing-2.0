export function initFileBrowser() {
    const wrap = document.querySelector('[data-file-browser]');
    if (!wrap) return;

    const viewUrl = wrap.dataset.fbViewUrl;
    const saveUrl = wrap.dataset.fbSaveUrl;

    let activeKebab = null;

    function closeAllKebabs() {
        if (!activeKebab) return;
        activeKebab.querySelector('[data-fb-kebab-panel]')?.style.setProperty('display', 'none');
        const btn = activeKebab.querySelector('[data-fb-kebab-btn]');
        if (btn) btn.style.cssText = '';
        activeKebab = null;
    }

    // ── Toggle forms ──────────────────────────────────────────────────────────
    const mkdirForm  = wrap.querySelector('[data-fb-mkdir-form]');
    const uploadForm = wrap.querySelector('[data-fb-upload-form]');
    const mkdirBtn   = wrap.querySelector('[data-fb-toggle-mkdir]');

    const IDLE_STYLE   = 'font-size:.72rem;padding:5px 14px;background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff';
    const ACTIVE_STYLE = 'font-size:.72rem;padding:5px 14px;background:#7c3aed;color:white;border:1px solid #7c3aed';

    function setMkdir(on) {
        if (mkdirForm) mkdirForm.style.display = on ? '' : 'none';
        if (mkdirBtn)  mkdirBtn.setAttribute('style', on ? ACTIVE_STYLE : IDLE_STYLE);
        if (on && uploadForm) uploadForm.style.display = 'none';
    }

    function setUpload(on) {
        if (uploadForm) uploadForm.style.display = on ? '' : 'none';
        if (on) setMkdir(false);
    }

    // Init button style to match initial form visibility
    if (mkdirBtn) {
        mkdirBtn.setAttribute('style', mkdirForm?.style.display !== 'none' ? ACTIVE_STYLE : IDLE_STYLE);
    }

    wrap.querySelector('[data-fb-toggle-mkdir]')?.addEventListener('click', () => setMkdir(mkdirForm?.style.display === 'none'));
    wrap.querySelector('[data-fb-toggle-upload]')?.addEventListener('click', () => setUpload(uploadForm?.style.display === 'none'));
    wrap.querySelector('[data-fb-mkdir-cancel]')?.addEventListener('click', () => setMkdir(false));
    wrap.querySelector('[data-fb-upload-cancel]')?.addEventListener('click', () => setUpload(false));

    // ── View (JSON editor) modal ───────────────────────────────────────────────
    const viewModal    = wrap.querySelector('[data-fb-view-modal]');
    const viewNameEl   = viewModal?.querySelector('[data-fb-view-name]');
    const viewSaveBtn  = viewModal?.querySelector('[data-fb-view-save]');
    const viewLoading  = viewModal?.querySelector('[data-fb-view-loading]');
    const viewErrorEl  = viewModal?.querySelector('[data-fb-view-error]');
    const viewTextarea = viewModal?.querySelector('[data-fb-view-content]');
    const viewSavedEl  = viewModal?.querySelector('[data-fb-view-saved]');
    const viewSaveErr  = viewModal?.querySelector('[data-fb-view-save-error]');
    let   viewPath     = '';

    function openViewModal(path, name) {
        viewPath = path;
        if (viewNameEl)   viewNameEl.textContent = name;
        if (viewSavedEl)  viewSavedEl.style.display = 'none';
        if (viewSaveErr)  { viewSaveErr.style.display = 'none'; viewSaveErr.textContent = ''; }
        if (viewTextarea) { viewTextarea.value = ''; viewTextarea.style.display = 'none'; }
        if (viewErrorEl)  { viewErrorEl.style.display = 'none'; viewErrorEl.textContent = ''; }
        if (viewLoading)  viewLoading.style.display = '';
        if (viewModal)    viewModal.style.display = 'flex';

        fetch(viewUrl + '?path=' + encodeURIComponent(path))
            .then(async res => {
                const text = await res.text();
                if (!res.ok) {
                    const err = JSON.parse(text);
                    if (viewErrorEl) { viewErrorEl.textContent = err.error ?? 'Could not load file.'; viewErrorEl.style.display = ''; }
                } else {
                    if (viewTextarea) { viewTextarea.value = text; viewTextarea.style.display = ''; }
                }
            })
            .catch(() => {
                if (viewErrorEl) { viewErrorEl.textContent = 'Network error.'; viewErrorEl.style.display = ''; }
            })
            .finally(() => {
                if (viewLoading) viewLoading.style.display = 'none';
            });
    }

    function closeViewModal() {
        if (viewModal) viewModal.style.display = 'none';
    }

    async function saveViewFile() {
        const notSavingEl = viewSaveBtn?.querySelector('[data-fb-not-saving]');
        const savingEl    = viewSaveBtn?.querySelector('[data-fb-saving]');
        if (viewSaveBtn)  viewSaveBtn.disabled = true;
        if (notSavingEl)  notSavingEl.style.display = 'none';
        if (savingEl)     savingEl.style.display = '';
        if (viewSavedEl)  viewSavedEl.style.display = 'none';
        if (viewSaveErr)  { viewSaveErr.style.display = 'none'; viewSaveErr.textContent = ''; }

        try {
            const res = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ path: viewPath, content: viewTextarea?.value ?? '' }),
            });
            const data = await res.json();
            if (!res.ok) {
                if (viewSaveErr) { viewSaveErr.textContent = data.error ?? 'Save failed.'; viewSaveErr.style.display = ''; }
            } else {
                if (viewSavedEl) viewSavedEl.style.display = '';
            }
        } catch {
            if (viewSaveErr) { viewSaveErr.textContent = 'Network error.'; viewSaveErr.style.display = ''; }
        }

        if (viewSaveBtn)  viewSaveBtn.disabled = false;
        if (notSavingEl)  notSavingEl.style.display = '';
        if (savingEl)     savingEl.style.display = 'none';
    }

    viewSaveBtn?.addEventListener('click', saveViewFile);
    viewModal?.querySelector('[data-fb-view-close]')?.addEventListener('click', closeViewModal);
    viewModal?.addEventListener('click', e => { if (e.target === viewModal) closeViewModal(); });

    // ── Rename modal ──────────────────────────────────────────────────────────
    const renameModal     = wrap.querySelector('[data-fb-rename-modal]');
    const renamePathDisp  = renameModal?.querySelector('[data-fb-rename-path-display]');
    const renamePathInput = renameModal?.querySelector('[data-fb-rename-path-input]');
    const renameNameInput = renameModal?.querySelector('[data-fb-rename-name-input]');

    function openRenameModal(path, name) {
        if (renamePathDisp)  renamePathDisp.textContent = path;
        if (renamePathInput) renamePathInput.value = path;
        if (renameNameInput) renameNameInput.value = name;
        if (renameModal)     renameModal.style.display = 'flex';
        renameNameInput?.focus();
    }

    function closeRenameModal() {
        if (renameModal) renameModal.style.display = 'none';
    }

    renameModal?.querySelector('[data-fb-rename-close]')?.addEventListener('click', closeRenameModal);
    renameModal?.addEventListener('click', e => { if (e.target === renameModal) closeRenameModal(); });

    // ── Kebab menus + delete confirms ─────────────────────────────────────────
    wrap.querySelectorAll('[data-fb-kebab-wrap]').forEach(kw => {
        const kebabBtn   = kw.querySelector('[data-fb-kebab-btn]');
        const kebabPanel = kw.querySelector('[data-fb-kebab-panel]');
        const delConfirm = kw.querySelector('[data-fb-delete-confirm]');

        kebabBtn?.addEventListener('click', e => {
            e.stopPropagation();
            if (activeKebab === kw) { closeAllKebabs(); return; }
            closeAllKebabs();
            activeKebab = kw;
            if (kebabPanel) kebabPanel.style.display = '';
            if (kebabBtn)   kebabBtn.style.cssText = 'background:#f3f4f6;color:#374151';
        });

        kw.querySelector('[data-fb-delete-btn]')?.addEventListener('click', e => {
            e.stopPropagation();
            closeAllKebabs();
            if (delConfirm) delConfirm.style.display = '';
        });

        kw.querySelector('[data-fb-delete-cancel]')?.addEventListener('click', e => {
            e.stopPropagation();
            if (delConfirm) delConfirm.style.display = 'none';
        });
    });

    // View buttons (inline in name cell + inside kebab menu — both use same attribute)
    wrap.querySelectorAll('[data-fb-view-btn]').forEach(btn => {
        btn.addEventListener('click', () => {
            closeAllKebabs();
            openViewModal(btn.dataset.fbPath, btn.dataset.fbName);
        });
    });

    // Rename buttons (inside kebab menu)
    wrap.querySelectorAll('[data-fb-rename-btn]').forEach(btn => {
        btn.addEventListener('click', () => {
            closeAllKebabs();
            openRenameModal(btn.dataset.fbPath, btn.dataset.fbName);
        });
    });

    // Close kebabs on outside click
    document.addEventListener('click', e => {
        if (!e.target.closest('[data-fb-kebab-wrap]')) closeAllKebabs();
    });

    // Escape closes modals
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (viewModal?.style.display === 'flex')   closeViewModal();
        if (renameModal?.style.display === 'flex') closeRenameModal();
        closeAllKebabs();
    });
}
