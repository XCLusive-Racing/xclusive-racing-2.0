export function initCountdownTimers() {
    document.querySelectorAll('[data-countdown]').forEach(el => {
        const target = new Date(el.dataset.countdown);
        const dEl = el.querySelector('[data-cd-d]');
        const hEl = el.querySelector('[data-cd-h]');
        const mEl = el.querySelector('[data-cd-m]');
        const sEl = el.querySelector('[data-cd-s]');

        const pad = n => String(Math.max(0, n)).padStart(2, '0');
        const tick = () => {
            const diff = target - new Date();
            if (diff <= 0) {
                [dEl, hEl, mEl, sEl].forEach(e => { if (e) e.textContent = '00'; });
                return;
            }
            if (dEl) dEl.textContent = pad(Math.floor(diff / 86400000));
            if (hEl) hEl.textContent = pad(Math.floor((diff % 86400000) / 3600000));
            if (mEl) mEl.textContent = pad(Math.floor((diff % 3600000) / 60000));
            if (sEl) sEl.textContent = pad(Math.floor((diff % 60000) / 1000));
        };

        tick();
        setInterval(tick, 1000);
    });
}
