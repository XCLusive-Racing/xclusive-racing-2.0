export function initPointsSystem(wrap) {
    if (!wrap) return;
    const chipsEl = wrap.querySelector('[data-points-chips]');
    const jsonEl  = wrap.querySelector('[data-points-json]');
    const addBtn  = wrap.querySelector('[data-points-add]');
    const removeBtn = wrap.querySelector('[data-points-remove]');
    if (!chipsEl || !jsonEl) return;

    const medalColors = ['#f59e0b', '#9ca3af', '#b45309'];

    function values() {
        return (jsonEl.value || '')
            .split(',')
            .map(v => v.trim())
            .filter(v => v !== '');
    }

    function render(points) {
        chipsEl.innerHTML = '';
        points.forEach((pts, i) => {
            const chip = document.createElement('div');
            chip.className = 'd-flex flex-column align-items-center';
            chip.style.cssText = 'width:52px';

            const label = document.createElement('span');
            label.className = 'fw-black text-uppercase';
            label.style.cssText = `font-size:.62rem;letter-spacing:.04em;color:${medalColors[i] || '#9ca3af'}`;
            label.textContent = 'P' + (i + 1);

            const input = document.createElement('input');
            input.type = 'number';
            input.min = '0';
            input.className = 'form-control form-control-sm text-center fw-bold';
            input.style.cssText = 'padding:4px 2px';
            input.value = pts;
            input.addEventListener('input', sync);

            chip.appendChild(label);
            chip.appendChild(input);
            chipsEl.appendChild(chip);
        });
    }

    function sync() {
        const points = Array.from(chipsEl.querySelectorAll('input')).map(i => i.value || '0');
        jsonEl.value = points.join(',');
    }

    addBtn?.addEventListener('click', () => {
        const points = values();
        points.push('0');
        render(points);
        sync();
    });

    removeBtn?.addEventListener('click', () => {
        const points = values();
        if (points.length <= 1) return;
        points.pop();
        render(points);
        sync();
    });

    render(values());
}