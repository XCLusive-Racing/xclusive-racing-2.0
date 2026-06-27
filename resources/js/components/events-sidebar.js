export function initEventsSidebar() {
    const wrap = document.querySelector('[data-events-sidebar]');
    if (!wrap) return;

    const trigger     = wrap.querySelector('[data-sb-trigger]');
    const backdrop    = wrap.querySelector('[data-sb-backdrop]');
    const closeTab    = wrap.querySelector('[data-sb-close-tab]');
    const sidebar     = wrap.querySelector('[data-sb-panel]');
    const gameBtns    = wrap.querySelectorAll('[data-sb-game]');
    const tabBtns     = wrap.querySelectorAll('[data-sb-tab]');
    const tabPanels   = wrap.querySelectorAll('[data-sb-tab-panel]');
    const searchInput = wrap.querySelector('[data-sb-search]');
    const lbBody      = wrap.querySelector('[data-sb-lb-body]');
    const pagination  = wrap.querySelector('[data-sb-pagination]');

    const leaderboards = window.__xclLeaderboards || {};

    let open        = false;
    let navbarOpen  = false;
    let gameFilter  = 'all';
    let activeTab   = 'daily';
    let searchQuery = '';
    let currentPage = 1;

    // ── Open / close ──────────────────────────────────────────────────────────
    function setOpen(val) {
        open = val;
        sidebar?.classList.toggle('xcl-sidebar--open', open);
        trigger?.classList.toggle('xcl-sidebar-trigger--open', open);
        trigger?.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (backdrop) backdrop.style.display = open ? '' : 'none';
        document.body.style.overflow = open ? 'hidden' : '';
    }

    trigger?.addEventListener('click', () => setOpen(!open));
    backdrop?.addEventListener('click', () => setOpen(false));
    closeTab?.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && open) setOpen(false); });

    // Open via custom event (e.g. navbar "Events" link)
    window.addEventListener('open-events-sidebar', () => setOpen(true));

    // Hide trigger when navbar mobile drawer is open
    window.addEventListener('navbar-toggled', e => {
        navbarOpen = e.detail?.open ?? false;
        if (trigger) trigger.style.display = navbarOpen ? 'none' : '';
        if (navbarOpen) setOpen(false);
    });

    // ── Game filter ───────────────────────────────────────────────────────────
    function setGameFilter(game) {
        gameFilter = game;
        gameBtns.forEach(btn => btn.classList.toggle('active', btn.dataset.sbGame === game));

        // Show/hide game-specific event cards
        wrap.querySelectorAll('[data-sb-game-card]').forEach(card => {
            const cardGame = card.dataset.sbGameCard;
            card.style.display = (game === 'all' || game === cardGame) ? '' : 'none';
        });

        // No-results placeholder when game has no next event
        const nextNoResult = wrap.querySelector('[data-sb-no-next]');
        const nextCard     = wrap.querySelector('[data-sb-next-card]');
        if (nextNoResult && nextCard) {
            const nextGame = nextCard.dataset.sbGameCard;
            const show     = game !== 'all' && game !== nextGame;
            nextNoResult.style.display = show ? '' : 'none';
            nextCard.style.display     = show ? 'none' : '';
        }

        // Leaderboard: reset and re-render
        searchQuery = '';
        if (searchInput) searchInput.value = '';
        currentPage = 1;
        renderLeaderboard();
    }

    gameBtns.forEach(btn => btn.addEventListener('click', () => setGameFilter(btn.dataset.sbGame)));

    // ── Tabs ──────────────────────────────────────────────────────────────────
    function setTab(tab) {
        activeTab = tab;
        tabBtns.forEach(btn => btn.classList.toggle('active', btn.dataset.sbTab === tab));
        tabPanels.forEach(panel => {
            panel.style.display = panel.dataset.sbTabPanel === tab ? '' : 'none';
        });
    }

    tabBtns.forEach(btn => btn.addEventListener('click', () => setTab(btn.dataset.sbTab)));
    setTab('daily');

    // ── Leaderboard ───────────────────────────────────────────────────────────
    function activeLeaderboard() {
        const g = ['acc', 'lmu', 'iracing'].includes(gameFilter) ? gameFilter : 'acc';
        return leaderboards[g] || [];
    }

    function filteredLeaderboard() {
        const q = searchQuery.toLowerCase();
        const all = activeLeaderboard();
        return q ? all.filter(d => d.name.toLowerCase().includes(q)) : [...all];
    }

    function esc(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function renderLeaderboard() {
        if (!lbBody) return;
        const filtered   = filteredLeaderboard();
        const totalPages = Math.max(1, Math.ceil(filtered.length / 10));
        if (currentPage > totalPages) currentPage = totalPages;

        const page  = filtered.slice((currentPage - 1) * 10, currentPage * 10);

        if (page.length === 0) {
            lbBody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:#8B9BB4;padding:1.5rem .5rem;font-size:.8rem">No results found</td></tr>`;
        } else {
            lbBody.innerHTML = page.map(d => `
                <tr class="${d.pos <= 3 ? 'top-3' : ''}">
                    <td class="xcl-lb-pos">${d.pos}</td>
                    <td>
                        <div class="xcl-lb-driver">
                            <div class="xcl-lb-flag-placeholder">${esc(d.country)}</div>
                            <span class="xcl-lb-name">${esc(d.name)}</span>
                            ${d.supporter ? '<span title="Supporter" style="font-size:.6rem;color:#f59e0b;line-height:1">★</span>' : ''}
                        </div>
                    </td>
                    <td class="xcl-lb-gain" style="${d.pos <= 3 ? 'color:#C8FF00' : 'color:#8B9BB4'}">${d.gain.toLocaleString()}</td>
                </tr>
            `).join('');
        }

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        if (!pagination) return;
        pagination.style.display = totalPages > 1 ? '' : 'none';
        if (totalPages <= 1) return;

        const pages = Array.from({ length: totalPages }, (_, i) => i + 1);
        pagination.innerHTML = `
            <button data-pg="first" ${currentPage === 1 ? 'disabled' : ''}>&laquo;</button>
            <button data-pg="prev"  ${currentPage === 1 ? 'disabled' : ''}>&lsaquo;</button>
            ${pages.map(p => `<button data-pg="${p}" class="${p === currentPage ? 'active' : ''}">${p}</button>`).join('')}
            <button data-pg="next" ${currentPage === totalPages ? 'disabled' : ''}>&rsaquo;</button>
            <button data-pg="last" ${currentPage === totalPages ? 'disabled' : ''}>&raquo;</button>
        `;

        pagination.querySelectorAll('button[data-pg]').forEach(btn => {
            btn.addEventListener('click', () => {
                const pg = btn.dataset.pg;
                const total = Math.max(1, Math.ceil(filteredLeaderboard().length / 10));
                if (pg === 'first') currentPage = 1;
                else if (pg === 'prev') currentPage = Math.max(1, currentPage - 1);
                else if (pg === 'next') currentPage = Math.min(total, currentPage + 1);
                else if (pg === 'last') currentPage = total;
                else currentPage = parseInt(pg, 10);
                renderLeaderboard();
            });
        });
    }

    searchInput?.addEventListener('input', () => {
        searchQuery = searchInput.value;
        currentPage = 1;
        renderLeaderboard();
    });

    renderLeaderboard();
}

// Legacy Alpine exports — still registered in app.js for unconverted blade files
export function eventsSidebar() {
    return {
        open: false,
        activeTab: 'daily',
        gameFilter: 'all',
        searchQuery: '',
        currentPage: 1,
        leaderboards: window.__xclLeaderboards || {},
        get activeLeaderboard() {
            const g = ['acc', 'lmu', 'iracing'].includes(this.gameFilter) ? this.gameFilter : 'acc';
            return this.leaderboards[g] || [];
        },
        get filteredLeaderboard() {
            const q = this.searchQuery.toLowerCase();
            return q
                ? this.activeLeaderboard.filter(d => d.name.toLowerCase().includes(q))
                : [...this.activeLeaderboard];
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredLeaderboard.length / 10));
        },
        get paginatedLeaderboard() {
            const start = (this.currentPage - 1) * 10;
            return this.filteredLeaderboard.slice(start, start + 10);
        },
        init() {
            this.$watch('open', val => {
                document.body.style.overflow = val ? 'hidden' : '';
            });
            this.$watch('searchQuery', () => { this.currentPage = 1; });
            this.$watch('gameFilter', () => { this.currentPage = 1; this.searchQuery = ''; });
        },
    };
}

export function countdownTimer(dateStr) {
    return {
        d: 0, h: 0, m: 0, s: 0,
        init() {
            const t = new Date(dateStr);
            const tick = () => {
                const diff = t - new Date();
                if (diff <= 0) { this.d = this.h = this.m = this.s = 0; return; }
                this.d = Math.floor(diff / 86400000);
                this.h = Math.floor((diff % 86400000) / 3600000);
                this.m = Math.floor((diff % 3600000) / 60000);
                this.s = Math.floor((diff % 60000) / 1000);
            };
            tick();
            setInterval(tick, 1000);
        },
    };
}
