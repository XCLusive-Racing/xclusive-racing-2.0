export function initEventsFilter() {
    const wrap = document.querySelector('[data-events-filter]');
    if (!wrap) return;

    let platform          = null;
    let eventFilter       = 'all';
    let regionFilter      = 'all';
    let classFilter       = 'all';
    let requirementFilter = 'all';

    const platformSelector = wrap.querySelector('[data-platform-selector]');
    const eventsList       = wrap.querySelector('[data-events-list]');
    const backBtn          = wrap.querySelector('[data-back-btn]');
    const heading          = wrap.querySelector('[data-events-heading]');
    const defaultHeading   = heading?.textContent ?? '';
    const filterBtns       = wrap.querySelectorAll('[data-event-filter]');
    const regionBtns       = wrap.querySelectorAll('[data-region-filter]');
    const classBtns        = wrap.querySelectorAll('[data-class-filter]');
    const requirementBtns  = wrap.querySelectorAll('[data-requirement-filter]');
    const categoryBtns     = wrap.querySelectorAll('[data-filter-category]');
    const filterGroups     = wrap.querySelectorAll('[data-filter-group]');

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

    function matchesClass(classAttr) {
        return classFilter === 'all' || classAttr === classFilter;
    }

    function matchesRequirement(card) {
        switch (requirementFilter) {
            case 'open':        return card.dataset.sr === '0' && !card.dataset.minRating && !card.dataset.maxRating;
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
        classFilter       = 'all';
        requirementFilter = 'all';
        closeFilterCategories();
    }

    // Phone-only: the four filter groups (event/requirements/timezone/class) are
    // collapsed behind a single row of category buttons, and only the tapped
    // group's buttons are shown, below that row. No-op on desktop, where all
    // groups are always visible via CSS regardless of this state.
    function closeFilterCategories() {
        filterGroups.forEach(g => g.classList.remove('xcl-filter-group--open'));
        categoryBtns.forEach(b => b.classList.remove('xcl-filter-btn--active'));
    }

    function apply() {
        if (platformSelector) platformSelector.style.display = platform === null ? '' : 'none';
        if (eventsList)       eventsList.style.display       = platform !== null ? '' : 'none';

        wrap.querySelectorAll('[data-game-section]').forEach(section => {
            section.style.display = section.dataset.gameSection === platform ? '' : 'none';
        });

        filterBtns.forEach(btn => {
            const active = btn.dataset.eventFilter === eventFilter;
            btn.classList.toggle('xcl-filter-btn--active', active);

            // Each event-type button carries its own color (matching its format's image) —
            // solid fill when selected, tinted outline otherwise — the plain 'All' button
            // has no data-color and keeps the shared default/active look instead.
            const color = btn.dataset.color;
            if (color) {
                btn.style.borderColor = color;
                btn.style.background  = active ? color : `${color}22`;
                btn.style.color       = active ? '#fff' : color;
            }
        });
        regionBtns.forEach(btn => {
            btn.classList.toggle('xcl-filter-btn--active', btn.dataset.regionFilter === regionFilter);
        });
        classBtns.forEach(btn => {
            const active = btn.dataset.classFilter === classFilter;
            btn.classList.toggle('xcl-filter-btn--active', active);

            // Same [bg, text] pair as the class badge on the event card itself
            // (Race::carClassStyle()), always solid — TCX's white badge has no
            // hue, so a translucent tint would read the same as this row's own
            // default white button text and effectively disappear. Selection is
            // shown via a highlighted border + full opacity instead of a fill change.
            const bg = btn.dataset.bg;
            if (bg) {
                btn.style.borderColor = active ? '#a855f7' : btn.dataset.border;
                btn.style.opacity     = active ? '1' : '.65';
            }
        });
        requirementBtns.forEach(btn => {
            const active = btn.dataset.requirementFilter === requirementFilter;
            btn.classList.toggle('xcl-filter-btn--active', active);

            // Same solid-fill-when-selected / tinted-outline-otherwise treatment as
            // the event-type row — each button carries its own tier/status color.
            const color = btn.dataset.color;
            if (color) {
                btn.style.borderColor = color;
                btn.style.background  = active ? color : `${color}22`;
                btn.style.color       = active ? '#fff' : color;
            }
        });
        categoryBtns.forEach(btn => {
            // Open/closed state lives on the button's own active class (toggled by
            // the click handler below), not a tracked filter variable — this just
            // repaints the color to match whichever state is already set.
            const active = btn.classList.contains('xcl-filter-btn--active');
            const color  = btn.dataset.color;
            if (color) {
                btn.style.borderColor = color;
                btn.style.background  = active ? color : `${color}22`;
                btn.style.color       = active ? '#fff' : color;
            }
        });

        wrap.querySelectorAll('[data-event-card]').forEach(card => {
            const visible = matchesEventFilter(card.dataset.tag, card.dataset.date)
                && matchesRegion(card.dataset.regions)
                && matchesClass(card.dataset.class)
                && matchesRequirement(card);
            card.style.display = visible ? '' : 'none';
        });
    }

    // Each game has its own URL (/events/acc-console etc., see Race::PLATFORM_SLUGS)
    // — the list is shown in place, the URL just follows it so a reload or coming
    // back from an event page lands on the same game.
    function selectPlatform(game) {
        platform = game || null;
        const card = platform && wrap.querySelector(`[data-platform-card="${platform}"]`);
        if (heading) heading.textContent = card ? `${card.dataset.platformLabel} Events` : defaultHeading;
        resetFilters();
        apply();
    }

    // Platform cards — click to select, hover for video + active class
    wrap.querySelectorAll('[data-platform-card]').forEach(card => {
        const game = card.dataset.platformCard;
        const vid  = card.querySelector('video');

        if (game) {
            card.addEventListener('click', () => {
                selectPlatform(game);
                history.pushState({ platform: game }, '', card.dataset.platformUrl);
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
        selectPlatform(null);
        history.pushState({ platform: null }, '', wrap.dataset.eventsUrl);
    });

    // Browser back/forward between /events and /events/{game}
    window.addEventListener('popstate', e => {
        selectPlatform(e.state?.platform ?? wrap.dataset.initialPlatform ?? null);
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

    classBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            classFilter = btn.dataset.classFilter;
            apply();
        });
    });

    requirementBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            requirementFilter = btn.dataset.requirementFilter;
            apply();
        });
    });

    categoryBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const name    = btn.dataset.filterCategory;
            const wasOpen = btn.classList.contains('xcl-filter-btn--active');
            closeFilterCategories();
            if (!wasOpen) {
                btn.classList.add('xcl-filter-btn--active');
                wrap.querySelector(`[data-filter-group="${name}"]`)?.classList.add('xcl-filter-group--open');
            }
            apply();
        });
    });

    history.replaceState({ platform: wrap.dataset.initialPlatform || null }, '');
    selectPlatform(wrap.dataset.initialPlatform);
}
