import { syncDisabled } from './result-form-sync.js';

const PRO_SUBJECTS = ['dirk-schouten', 'mats-van-rooijen'];

export function initResultSubjectToggle() {
    document.querySelectorAll('[data-result-form]').forEach(form => {
        const select        = form.querySelector('[data-driver-picker-select]');
        const typeSelect    = form.querySelector('[data-result-type]');
        const proFields     = form.querySelector('[data-result-fields="pro"]');
        const esportsFields = form.querySelector('[data-result-fields="esports"]');
        if (!select) return;

        function update() {
            const isPro = PRO_SUBJECTS.includes(select.value);
            if (proFields) proFields.style.display = isPro ? '' : 'none';
            if (esportsFields) esportsFields.style.display = (!isPro && select.value) ? '' : 'none';

            const type = typeSelect?.value || 'race';
            form.querySelectorAll('[data-type-fields]').forEach(el => {
                el.style.display = el.dataset.typeFields.split(' ').includes(type) ? '' : 'none';
            });

            syncDisabled(form);
        }

        select.addEventListener('change', update);
        typeSelect?.addEventListener('change', update);
        form.addEventListener('result-sync', () => syncDisabled(form));
        update();
    });
}
