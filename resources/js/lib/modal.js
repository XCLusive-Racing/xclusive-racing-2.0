export function openModal(el) {
    if (typeof el === 'string') el = document.getElementById(el);
    if (!el) return;
    el.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

export function closeModal(el) {
    if (typeof el === 'string') el = document.getElementById(el);
    if (!el) return;
    el.style.display = 'none';
    document.body.style.overflow = '';
}

export function initModalTriggers() {
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => openModal(btn.dataset.modalOpen));
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.closest('.xcl-modal') ?? btn.dataset.modalClose));
    });

    document.querySelectorAll('.xcl-modal').forEach(modal => {
        modal.addEventListener('click', e => {
            if (e.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.xcl-modal').forEach(m => {
                if (m.style.display !== 'none') closeModal(m);
            });
        }
    });
}
