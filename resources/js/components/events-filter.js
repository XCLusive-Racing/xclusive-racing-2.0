export function initEventsFilter() {
    const wrap = document.querySelector('[data-events-filter]');
    if (!wrap) return;

    let platform    = null;
    let weeksShown  = 1;
    let eventFilter = 'all';

    const platformSelector = wrap.querySelector('[data-platform-selector]');
    const eventsList       = wrap.querySelector('[data-events-list]');
    const backBtn          = wrap.querySelector('[data-back-btn]');
    const filterBtns       = wrap.querySelectorAll('[data-event-filter]');

    function matchesEventFilter(tag, dateStr) {
        const now    = new Date();
        const d      = new Date(dateStr);
        if (d < now) return false;
        if (eventFilter !== 'all') return tag === eventFilter;
        const cutoff = new Date(now);
        cutoff.setDate(cutoff.getDate() + weeksShown * 7);
        return d <= cutoff;
    }

    function apply() {
        if (platformSelector) platformSelector.style.display = platform === null ? '' : 'none';
        if (eventsList)       eventsList.style.display       = platform !== null ? '' : 'none';

        wrap.querySelectorAll('[data-game-section]').forEach(section => {
            section.style.display = section.dataset.gameSection === platform ? '' : 'none';
        });

        filterBtns.forEach(btn => {
            btn.classList.toggle('xcl-filter-btn--active', btn.dataset.eventFilter === eventFilter);
        });

        wrap.querySelectorAll('[data-load-more]').forEach(btn => {
            btn.style.display = btn.dataset.loadAtWeeks === String(weeksShown) ? '' : 'none';
        });

        wrap.querySelectorAll('[data-event-card]').forEach(card => {
            card.style.display = matchesEventFilter(card.dataset.tag, card.dataset.date) ? '' : 'none';
        });
    }

    // Platform cards — click to select, hover for video + active class
    wrap.querySelectorAll('[data-platform-card]').forEach(card => {
        const game = card.dataset.platformCard;
        const vid  = card.querySelector('video');

        if (game) {
            card.addEventListener('click', () => {
                platform    = game;
                weeksShown  = 1;
                eventFilter = 'all';
                apply();
            });
        }

        card.addEventListener('mouseenter', () => {
            card.classList.add('events-platform-card--active');
            card.querySelector('.events-platform-card__desc')
                ?.classList.add('events-platform-card__desc--visible');
            vid?.play().catch(() => {});
        });
        card.addEventListener('mouseleave', () => {
            card.classList.remove('events-platform-card--active');
            card.querySelector('.events-platform-card__desc')
                ?.classList.remove('events-platform-card__desc--visible');
            if (vid) { vid.pause(); }
        });
    });

    backBtn?.addEventListener('click', () => {
        platform    = null;
        weeksShown  = 1;
        eventFilter = 'all';
        apply();
    });

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            eventFilter = btn.dataset.eventFilter;
            apply();
        });
    });

    wrap.querySelectorAll('[data-load-more]').forEach(btn => {
        btn.addEventListener('click', () => {
            weeksShown = parseInt(btn.dataset.targetWeeks, 10);
            apply();
        });
    });

    apply();
}
