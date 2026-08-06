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

    // Dropdown menus — hover on desktop, tap-to-expand chevron on mobile
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

        // Mobile: chevron button expands/collapses the sub-items inline.
        // Separate from the nav-link itself so tapping the item's text still
        // navigates — hover doesn't exist on touch, so without this the
        // dropdown items were unreachable on mobile.
        const chevron = item.querySelector('[data-mobile-dropdown-toggle]');
        chevron?.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = chevron.classList.contains('is-open');
            closeAllDropdowns();
            if (!isOpen) {
                showDropdown(menu);
                chevron.classList.add('is-open');
                chevron.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Desktop's .xcl-dropdown is absolutely positioned and centered via
    // translateX(-50%); mobile's media query flattens it to position:static
    // with transform:none. Inline styles always beat that stylesheet rule,
    // so the two open/close states must branch on viewport — otherwise the
    // mobile accordion inherits the desktop translateX and renders shifted
    // off to the side instead of inline below the row.
    function isMobileNav() {
        return window.matchMedia('(max-width: 767px)').matches;
    }

    function showDropdown(menu) {
        menu.style.display = 'block';
        if (isMobileNav()) {
            menu.style.transform = 'none';
            requestAnimationFrame(() => { menu.style.opacity = '1'; });
        } else {
            requestAnimationFrame(() => { menu.style.opacity = '1'; menu.style.transform = 'translateX(-50%) translateY(0)'; });
        }
    }

    function hideDropdown(menu) {
        menu.style.opacity = '0';
        menu.style.transform = isMobileNav() ? 'none' : 'translateX(-50%) translateY(4px)';
        setTimeout(() => { if (menu.style.opacity === '0') menu.style.display = 'none'; }, 100);
    }

    function closeAllDropdowns() {
        navbar.querySelectorAll('[data-dropdown-menu]').forEach(hideDropdown);
        navbar.querySelectorAll('[data-mobile-dropdown-toggle]').forEach(btn => {
            btn.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    document.addEventListener('click', closeAllDropdowns);
}
