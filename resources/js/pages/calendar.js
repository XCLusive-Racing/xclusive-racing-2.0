export function initCalendar() {
    const main = document.querySelector('[data-cal]');
    if (!main) return;

    const myIds = JSON.parse(main.dataset.myIds || '[]');
    let filter = 'all';
    let view = 'all';

    const params = new URLSearchParams(window.location.search);
    if (params.get('view') === 'mine') view = 'mine';

    function applyFilters() {
        main.querySelectorAll('[data-cal-pill]').forEach(pill => {
            const game    = pill.dataset.calGame;
            const raceId  = parseInt(pill.dataset.calRaceId, 10);
            const matchesFilter = filter === 'all' || filter === game;
            const matchesView   = view === 'all' || myIds.includes(raceId);
            pill.style.display  = (matchesFilter && matchesView) ? '' : 'none';
        });

        main.querySelectorAll('[data-cal-view]').forEach(btn => {
            btn.classList.toggle('xcl-cal-toggle__btn--active', btn.dataset.calView === view);
        });

        main.querySelectorAll('[data-cal-filter]').forEach(btn => {
            btn.classList.toggle('xcl-filter-btn--active', btn.dataset.calFilter === filter);
        });
    }

    main.querySelectorAll('[data-cal-view]').forEach(btn => {
        btn.addEventListener('click', () => {
            view = btn.dataset.calView;
            applyFilters();
        });
    });

    main.querySelectorAll('[data-cal-filter]').forEach(btn => {
        btn.addEventListener('click', () => {
            filter = btn.dataset.calFilter;
            applyFilters();
        });
    });

    applyFilters();
}
