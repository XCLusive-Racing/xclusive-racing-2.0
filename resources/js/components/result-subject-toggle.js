const PRO_SUBJECTS = ['dirk-schouten', 'mats-van-rooijen'];

export function initResultSubjectToggle() {
    document.querySelectorAll('[data-result-form]').forEach(form => {
        const select       = form.querySelector('[data-driver-picker-select]');
        const proFields    = form.querySelector('[data-result-fields="pro"]');
        const esportsFields = form.querySelector('[data-result-fields="esports"]');
        if (!select) return;

        function update() {
            const isPro = PRO_SUBJECTS.includes(select.value);
            if (proFields) proFields.style.display = isPro ? '' : 'none';
            if (esportsFields) esportsFields.style.display = (!isPro && select.value) ? '' : 'none';
        }

        select.addEventListener('change', update);
        update();
    });
}
