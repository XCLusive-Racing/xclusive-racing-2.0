export function initNavbar() {
    const navbar = document.querySelector('.navbar-xcl');
    if (!navbar) return;

    const toggler  = navbar.querySelector('.navbar-toggler');
    const collapse = navbar.querySelector('.navbar-collapse');

    // Mobile menu toggle
    toggler?.addEventListener('click', () => {
        collapse?.classList.toggle('show');
        window.dispatchEvent(new CustomEvent('navbar-toggled', { detail: { open: collapse?.classList.contains('show') } }));
    });

    // Close mobile menu on outside click
    document.addEventListener('click', e => {
        if (collapse?.classList.contains('show') && !navbar.contains(e.target)) {
            collapse.classList.remove('show');
        }
    });

    // Dropdown menus — hover on desktop, click on mobile
    navbar.querySelectorAll('[data-dropdown]').forEach(item => {
        const menu = item.querySelector('[data-dropdown-menu]');
        if (!menu) return;

        let hoverTimer;

        item.addEventListener('mouseenter', () => {
            clearTimeout(hoverTimer);
            showDropdown(menu);
        });

        item.addEventListener('mouseleave', () => {
            hoverTimer = setTimeout(() => hideDropdown(menu), 80);
        });

        item.querySelector('[data-dropdown-toggle]')?.addEventListener('click', e => {
            e.stopPropagation();
            const open = menu.style.opacity !== '0' && menu.style.display !== 'none';
            closeAllDropdowns();
            if (!open) showDropdown(menu);
        });
    });

    function showDropdown(menu) {
        menu.style.display = 'block';
        requestAnimationFrame(() => { menu.style.opacity = '1'; menu.style.transform = 'translateX(-50%) translateY(0)'; });
    }

    function hideDropdown(menu) {
        menu.style.opacity = '0';
        menu.style.transform = 'translateX(-50%) translateY(4px)';
        setTimeout(() => { if (menu.style.opacity === '0') menu.style.display = 'none'; }, 100);
    }

    function closeAllDropdowns() {
        navbar.querySelectorAll('[data-dropdown-menu]').forEach(hideDropdown);
    }

    document.addEventListener('click', closeAllDropdowns);
}
