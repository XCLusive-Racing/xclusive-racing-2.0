export function initPasswordToggles() {
    document.querySelectorAll('.xcl-pw-wrap').forEach(wrap => {
        const input = wrap.querySelector('input[type="password"], input[type="text"]');
        const btn   = wrap.querySelector('.xcl-pw-toggle');
        const icon  = wrap.querySelector('.xcl-pw-icon');
        if (!input || !btn) return;

        btn.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            if (icon) {
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            }
        });
    });
}
