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
