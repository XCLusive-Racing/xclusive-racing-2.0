// Events page featured row below desktop width: a carousel (the CSS turns the row into a
// horizontal scroll-snap track below 992px — one card per view on phones, two on tablets).
// The title buttons and side arrows scroll to a card; swiping updates which buttons are
// active (the ones whose card is in view). On desktop the buttons and arrows are hidden
// and the row is a normal grid, so none of this has any effect there.
export function initFeaturedCarousel() {
    document.querySelectorAll('[data-featured-carousel]').forEach(carousel => {
        const track = carousel.querySelector('[data-featured-track]');
        const tabs  = [...carousel.querySelectorAll('[data-featured-tab]')];
        const prev  = carousel.querySelector('[data-featured-prev]');
        const next  = carousel.querySelector('[data-featured-next]');
        if (!track) return;

        const cards = [...track.children];

        // Distance between two cards' starts (card width + gap), and how many fit in view.
        const step    = () => (cards[1] ? cards[1].offsetLeft - cards[0].offsetLeft : track.clientWidth) || 1;
        const perView = () => Math.max(1, Math.round((track.clientWidth + step() - cards[0].offsetWidth) / step()));
        const maxIndex = () => Math.max(0, cards.length - perView());
        const current = () => Math.min(maxIndex(), Math.round(track.scrollLeft / step()));
        const goTo = index => track.scrollTo({ left: Math.max(0, Math.min(maxIndex(), index)) * step() });

        function update() {
            const index = current();
            const shown = perView();
            tabs.forEach((tab, i) => tab.classList.toggle('xcl-featured__tab--active', i >= index && i < index + shown));
            if (prev) prev.disabled = index <= 0;
            if (next) next.disabled = index >= maxIndex();
        }

        tabs.forEach((tab, i) => tab.addEventListener('click', () => goTo(i)));
        prev?.addEventListener('click', () => goTo(current() - 1));
        next?.addEventListener('click', () => goTo(current() + 1));
        track.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
        // The row is hidden until its game is picked (and reset to the first card when
        // hidden again), and the cards per view change with the screen width — re-sync
        // the buttons whenever its size changes.
        new ResizeObserver(update).observe(track);
        update();
    });
}
