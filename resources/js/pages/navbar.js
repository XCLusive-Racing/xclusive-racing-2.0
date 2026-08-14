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

        // Mobile: both the chevron and the nav-link text itself expand/collapse
        // the sub-items inline — tapping the item never navigates on mobile,
        // since hover doesn't exist on touch and the item's own destination
        // usually isn't repeated as a sub-item.
        const chevron = item.querySelector('[data-mobile-dropdown-toggle]');
        const navLink = item.querySelector('.nav-link');

        function toggleMobileDropdown(e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = chevron?.classList.contains('is-open');
            closeAllDropdowns();
            if (!isOpen) {
                showDropdown(menu);
                chevron?.classList.add('is-open');
                chevron?.setAttribute('aria-expanded', 'true');
            }
        }

        chevron?.addEventListener('click', toggleMobileDropdown);
        navLink?.addEventListener('click', e => {
            if (isMobileNav()) toggleMobileDropdown(e);
        });
    });

    // Desktop dropdowns are absolutely positioned and centred under the nav
    // item via translateX(-50%). Mobile drops that (position: static, inline
    // flow via CSS) — keeping the -50% there shifts the whole box out of view.
    // Threshold matches the $xcl breakpoint (1104px) the navbar collapses at
    // (.navbar-expand-xcl in app.scss / navbar.blade.php), not Bootstrap's
    // stock md (768px).
    const isMobileNav = () => window.matchMedia('(max-width: 1103.98px)').matches;

    function showDropdown(menu) {
        menu.style.display = 'block';
        requestAnimationFrame(() => {
            menu.style.opacity   = '1';
            menu.style.transform = isMobileNav() ? 'translateY(0)' : 'translateX(-50%) translateY(0)';
        });
    }

    function hideDropdown(menu) {
        menu.style.opacity   = '0';
        menu.style.transform = isMobileNav() ? 'translateY(4px)' : 'translateX(-50%) translateY(4px)';
        setTimeout(() => { if (menu.style.opacity === '0') menu.style.display = 'none'; }, 100);
    }

    function closeAllDropdowns() {
        navbar.querySelectorAll('[data-dropdown-menu]').forEach(hideDropdown);
        navbar.querySelectorAll('[data-mobile-dropdown-toggle]').forEach(btn => {
            btn.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    // Don't fight an in-flight tap on an actual menu link — closing/hiding
    // the dropdown synchronously here can race with (and on iOS Safari,
    // silently cancel) the link's own navigation. Let it navigate; the
    // dropdown gets torn down naturally by the page unload/scroll anyway.
    document.addEventListener('click', e => {
        if (e.target.closest('[data-dropdown-menu]')) return;
        closeAllDropdowns();
    });
}
