export function initMulticlass(wrap) {
    if (!wrap) return;

    const checkbox   = wrap.querySelector('[data-multiclass-checkbox]');
    const section    = wrap.querySelector('[data-multiclass-section]');
    const hiddenFlag = wrap.querySelector('[data-multiclass-flag]');
    const addBtn     = wrap.querySelector('[data-multiclass-add]');
    const listEl     = wrap.querySelector('[data-multiclass-list]');
    const jsonInput  = wrap.querySelector('[data-multiclass-json]');

    const initialJson = wrap.dataset.multiclassInitialClasses;
    let classes = initialJson ? JSON.parse(initialJson) : [];

    function esc(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function syncJson() {
        if (jsonInput) jsonInput.value = JSON.stringify(classes);
    }

    function renderRow(i) {
        const cls = classes[i];
        const row = document.createElement('div');
        row.className = 'p-3 rounded-2 mb-2';
        row.style.cssText = 'background:#f9fafb;border:1px solid #e5e7eb';
        row.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold" style="font-size:.82rem">Class ${i + 1}</span>
                <button type="button" class="btn btn-sm text-danger" style="font-size:.72rem;padding:2px 8px" data-remove>Remove</button>
            </div>
            <div class="row g-2">
                <div class="col-sm-4">
                    <label class="form-label" style="font-size:.78rem">Name</label>
                    <input type="text" class="form-control form-control-sm" placeholder="e.g. GT3" value="${esc(cls.name)}" data-field="name">
                </div>
                <div class="col-sm-2">
                    <label class="form-label" style="font-size:.78rem">Color</label>
                    <input type="color" class="form-control form-control-sm form-control-color" style="width:100%;padding:2px" value="${esc(cls.color)}" data-field="color">
                </div>
                <div class="col-sm-3">
                    <label class="form-label" style="font-size:.78rem">Car Class</label>
                    <input type="text" class="form-control form-control-sm" placeholder="e.g. GT3" value="${esc(cls.car_class)}" data-field="car_class">
                </div>
                <div class="col-sm-3">
                    <label class="form-label" style="font-size:.78rem">Max Drivers</label>
                    <input type="number" class="form-control form-control-sm" placeholder="No limit" min="1" value="${esc(cls.max_drivers)}" data-field="max_drivers">
                </div>
            </div>
        `;
        row.querySelector('[data-remove]').addEventListener('click', () => {
            classes.splice(i, 1);
            render();
            syncJson();
        });
        row.querySelectorAll('[data-field]').forEach(input => {
            input.addEventListener('input', () => {
                classes[i][input.dataset.field] = input.value;
                syncJson();
            });
        });
        return row;
    }

    function render() {
        if (!listEl) return;
        listEl.innerHTML = '';
        classes.forEach((_, i) => listEl.appendChild(renderRow(i)));
    }

    function setMulticlass(enabled) {
        if (section)    section.style.display = enabled ? '' : 'none';
        if (hiddenFlag) hiddenFlag.value       = enabled ? '1' : '0';
        wrap.querySelectorAll('[data-multiclass-hide-when-active]').forEach(el => {
            el.style.display = enabled ? 'none' : '';
        });
    }

    checkbox?.addEventListener('change', () => setMulticlass(checkbox.checked));

    addBtn?.addEventListener('click', () => {
        classes.push({ name: '', color: '#db2777', car_class: '', max_drivers: '' });
        render();
        syncJson();
    });

    setMulticlass(checkbox?.checked ?? false);
    render();
    syncJson();
}
