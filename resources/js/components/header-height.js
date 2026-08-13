// The topbar + navbar are both `position: fixed`, stacked on top of each
// other, and content below them relies on a padding-top that matches their
// combined rendered height (see --xcl-header-h in app.scss). That height
// isn't a constant: it changes with nav wrapping, OS/browser font scaling,
// display zoom, and viewport width in ways no fixed breakpoint list can
// fully predict (e.g. Chromebooks landing in an untested width band between
// the mobile and desktop nav layouts). Measuring the real rendered height
// and pushing it into a CSS var keeps the page content clear of the header
// on any device, instead of guessing.
export function initHeaderHeight() {
    const topbar = document.querySelector('.xcl-topbar');
    const navbar = document.querySelector('.navbar-xcl');
    if (!navbar) return;

    const root = document.documentElement;

    function update() {
        const bottom = navbar.getBoundingClientRect().bottom;
        root.style.setProperty('--xcl-header-h', `${Math.ceil(bottom)}px`);
    }

    update();

    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(update);
        observer.observe(navbar);
        if (topbar) observer.observe(topbar);
    } else {
        window.addEventListener('resize', update);
    }
}
