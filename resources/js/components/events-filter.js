export default function eventsFilter() {
    return {
        platform: null,
        weeksShown: 1,
        eventFilter: 'all',
        selectPlatform(p) {
            this.platform = p;
            this.weeksShown = 1;
            this.eventFilter = 'all';
        },
        matchesEventFilter(tag, dateStr) {
            const now = new Date();
            const d   = new Date(dateStr);
            if (d < now) return false;
            if (this.eventFilter !== 'all') return tag === this.eventFilter;
            const cutoff = new Date(now);
            cutoff.setDate(cutoff.getDate() + this.weeksShown * 7);
            return d <= cutoff;
        },
    };
}
