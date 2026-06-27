import { toast } from '../../lib/swal.js';

export function init() {
    const body     = document.querySelector('.admin-body');
    if (!body) return;

    const overlay  = document.querySelector('.admin-sidebar-overlay');
    const sidebar  = document.querySelector('.admin-sidebar');
    const main     = document.querySelector('.admin-main');
    const logo     = document.querySelector('.admin-sidebar-logo');
    const hamburger = document.querySelector('[data-sidebar-toggle]');
    const collapseBtn = document.querySelector('[data-sidebar-collapse]');

    let sidebarOpen      = false;
    let sidebarCollapsed = localStorage.getItem('adminSidebarCollapsed') === 'true';

    function applyCollapsed() {
        sidebar?.classList.toggle('is-collapsed', sidebarCollapsed);
        main?.classList.toggle('is-collapsed', sidebarCollapsed);
        document.querySelectorAll('.admin-nav-link span, .admin-sidebar-footer .collapse-hide')
            .forEach(el => { el.style.display = sidebarCollapsed ? 'none' : ''; });
        document.querySelectorAll('.admin-nav-section-header')
            .forEach(el => { el.style.display = sidebarCollapsed ? 'none' : ''; });
        document.querySelectorAll('.admin-nav-section-divider')
            .forEach(el => { el.style.display = sidebarCollapsed ? '' : 'none'; });
        document.querySelectorAll('[data-sidebar-label]')
            .forEach(el => { el.style.display = sidebarCollapsed ? 'none' : ''; });
        if (logo) logo.style.justifyContent = sidebarCollapsed ? 'center' : 'space-between';
        const collapseIcon = document.querySelector('[data-collapse-icon]');
        if (collapseIcon) {
            collapseIcon.setAttribute('d', sidebarCollapsed
                ? 'M9 18l6-6-6-6'
                : 'M15 18l-6-6 6-6');
        }
    }

    function setSidebarOpen(open) {
        sidebarOpen = open;
        sidebar?.classList.toggle('open', open);
        if (overlay) overlay.classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    }

    hamburger?.addEventListener('click', () => setSidebarOpen(!sidebarOpen));
    overlay?.addEventListener('click', () => setSidebarOpen(false));

    collapseBtn?.addEventListener('click', () => {
        sidebarCollapsed = !sidebarCollapsed;
        localStorage.setItem('adminSidebarCollapsed', sidebarCollapsed);
        applyCollapsed();
    });

    // Section collapse
    document.querySelectorAll('.admin-nav-section-header').forEach(header => {
        const key     = header.dataset.section;
        const content = document.querySelector(`[data-section-content="${key}"]`);
        const arrow   = header.querySelector('[data-section-arrow]');
        let open      = localStorage.getItem(`adminSection_${key}`) !== 'false';

        function applySection() {
            if (content) content.style.display = open ? '' : 'none';
            if (arrow) arrow.style.transform = open ? '' : 'rotate(-90deg)';
        }

        applySection();

        header.addEventListener('click', () => {
            open = !open;
            localStorage.setItem(`adminSection_${key}`, open);
            applySection();
        });
    });

    applyCollapsed();

    // Toast system
    window.addEventListener('toast', e => {
        toast(e.detail.message, e.detail.type === 'success' ? 'success' : 'error');
    });
}
