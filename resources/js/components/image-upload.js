export function initImageUploads() {
    document.querySelectorAll('[data-image-upload]').forEach(wrap => {
        const zone        = wrap.querySelector('[data-image-upload-zone]');
        const input       = wrap.querySelector('[data-image-upload-input]');
        const preview     = wrap.querySelector('[data-image-upload-preview]');
        const placeholder = wrap.querySelector('[data-image-upload-placeholder]');

        if (!input) return;

        zone?.addEventListener('click',      () => input.click());
        zone?.addEventListener('mouseenter', () => { if (zone) zone.style.borderColor = '#7c3aed'; });
        zone?.addEventListener('mouseleave', () => { if (zone) zone.style.borderColor = '#e5e7eb'; });

        input.addEventListener('change', () => {
            if (input.files[0]) {
                if (preview)     { preview.src = URL.createObjectURL(input.files[0]); preview.style.display = 'block'; }
                if (placeholder) placeholder.style.display = 'none';
            } else {
                if (preview)     { preview.src = ''; preview.style.display = 'none'; }
                if (placeholder) placeholder.style.display = '';
            }
        });
    });
}
