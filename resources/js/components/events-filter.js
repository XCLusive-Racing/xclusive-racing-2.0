export function initEventsFilter() {
    const wrap = document.querySelector('[data-events-filter]');
    if (!wrap) return;

    let platform          = null;
    let eventFilter       = 'all';
    let regionFilter      = 'all';
    let requirementFilter = 'all';

    const platformSelector = wrap.querySelector('[data-platform-selector]');
    const eventsList       = wrap.querySelector('[data-events-list]');
    const backBtn          = wrap.querySelector('[data-back-btn]');
    const filterBtns       = wrap.querySelectorAll('[data-event-filter]');
    const regionBtns       = wrap.querySelectorAll('[data-region-filter]');
    const requirementBtns  = wrap.querySelectorAll('[data-requirement-filter]');

    function matchesEventFilter(tag, dateStr) {
        const now = new Date();
        const d   = new Date(dateStr);
        if (d < now) return false;
        return eventFilter === 'all' || tag === eventFilter;
    }

    function matchesRegion(regionsAttr) {
        if (regionFilter === 'all') return true;
        const regions = (regionsAttr || '').split(',').filter(Boolean);
        return regions.includes(regionFilter);
    }

    function matchesRequirement(card) {
        switch (requirementFilter) {
            case 'sr':          return card.dataset.sr === '1';
            case 'rookie-only': return card.dataset.maxRating === 'rookie';
            case 'bronze-only': return card.dataset.minRating === 'bronze' && card.dataset.maxRating === 'bronze';
            case 'bronze-plus': return card.dataset.minRating === 'bronze' && !card.dataset.maxRating;
            default:            return true;
        }
    }

    function resetFilters() {
        eventFilter       = 'all';
        regionFilter      = 'all';
        requirementFilter = 'all';
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
        regionBtns.forEach(btn => {
            btn.classList.toggle('xcl-filter-btn--active', btn.dataset.regionFilter === regionFilter);
        });
        requirementBtns.forEach(btn => {
            btn.classList.toggle('xcl-filter-btn--active', btn.dataset.requirementFilter === requirementFilter);
        });

        wrap.querySelectorAll('[data-event-card]').forEach(card => {
            const visible = matchesEventFilter(card.dataset.tag, card.dataset.date)
                && matchesRegion(card.dataset.regions)
                && matchesRequirement(card);
            card.style.display = visible ? '' : 'none';
        });
    }

    // Platform cards — click to select, hover for video + active class
    wrap.querySelectorAll('[data-platform-card]').forEach(card => {
        const game = card.dataset.platformCard;
        const vid  = card.querySelector('video');

        if (game) {
            card.addEventListener('click', () => {
                platform = game;
                resetFilters();
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
        platform = null;
        resetFilters();
        apply();
    });

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            eventFilter = btn.dataset.eventFilter;
            apply();
        });
    });

    regionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            regionFilter = btn.dataset.regionFilter;
            apply();
        });
    });

    requirementBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            requirementFilter = btn.dataset.requirementFilter;
            apply();
        });
    });

    apply();
}
