// The XP reveal on the profile page, shown when there is XP the driver has not seen yet:
// the XP lines tick in one by one while the bar fills and the level steps up, then the
// unlocked achievements, then the total. Once it has opened, the server is told it was seen.
// "Skip" (or Esc, or the backdrop) jumps to the end; a second press closes it.
// Everything it shows comes from the element's data-xp-reveal JSON.
export function initXpReveal() {
    const root = document.querySelector('[data-xp-reveal]');

    // The Driver Level bar on the profile runs up to its value: straight away, or once the
    // reveal has been closed.
    const cardFill = document.querySelector('[data-xp-card-fill]');
    const fillCard = () => {
        if (cardFill) requestAnimationFrame(() => requestAnimationFrame(() => { cardFill.style.width = `${cardFill.dataset.xpCardFill}%`; }));
    };

    // "View achievements" on the card opens that tab further down and scrolls to it.
    document.querySelector('[data-xp-show-achievements]')?.addEventListener('click', () => {
        const tab = document.querySelector('[data-tab-btn="achievements"]');
        tab?.click();
        tab?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    if (!root) return fillCard();

    const data   = JSON.parse(root.dataset.xpReveal);
    const ranks  = Object.keys(data.curve).map(Number).sort((a, b) => a - b);
    const q      = sel => root.querySelector(sel);
    const lines  = [...root.querySelectorAll('.xp-reveal__line')];
    const cards  = [...root.querySelectorAll('.xp-ach')];
    const button = q('[data-xp-continue]');
    const fmt    = n => Math.round(n).toLocaleString('en-GB');
    const calm   = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // A long list (a whole history unlocked at once) runs through quickly.
    const quick = cards.length > 10;

    let run = 0;        // which playthrough is current (a replay cancels the previous one)
    let skipped = false;
    let shownRank = null;

    // Level, XP into it and XP it takes (null at the maximum), for a total on the curve.
    function position(xp) {
        let rank = ranks[0];
        ranks.forEach(r => { if (xp >= data.curve[r]) rank = r; });
        const next = data.curve[rank + 1];
        return [rank, xp - data.curve[rank], next === undefined ? null : next - data.curve[rank]];
    }

    function show(xp, gained) {
        const [rank, into, needed] = position(xp);
        q('[data-xp-fill]').style.width = needed ? `${Math.min(100, (into / needed) * 100)}%` : '100%';
        q('[data-xp-into]').textContent = fmt(into);
        q('[data-xp-needed]').textContent = needed ? fmt(needed) : 'max';
        q('[data-xp-total]').textContent = fmt(gained);
        q('[data-xp-rank]').textContent = rank;

        if (shownRank !== null && rank > shownRank && !skipped) rankUp();
        shownRank = rank;
    }

    function rankUp() {
        [q('[data-xp-badge]'), q('[data-xp-rankup]')].forEach(el => {
            el.classList.remove('is-up');
            void el.offsetWidth; // restart the animation
            el.classList.add('is-up');
        });
    }

    const wait = (ms, id) => new Promise(resolve => {
        if (skipped || id !== run) return resolve();
        const timer = setTimeout(resolve, ms);
        const check = setInterval(() => {
            if (skipped || id !== run) { clearTimeout(timer); clearInterval(check); resolve(); }
        }, 40);
        setTimeout(() => clearInterval(check), ms + 50);
    });

    // Counts the total from one value to the next, bar and rank following along.
    async function count(from, to, id) {
        const steps = quick ? 6 : 24;
        for (let i = 1; i <= steps; i++) {
            if (skipped || id !== run) break;
            show(from + ((to - from) * i) / steps, from + ((to - from) * i) / steps - data.before);
            await wait(22, id);
        }
        show(to, to - data.before);
    }

    function finish() {
        lines.concat(cards).forEach(el => el.classList.add('is-in'));
        q('[data-xp-ach-title]').classList.add('is-in');
        shownRank = null;
        show(data.after, data.gained);
        button.textContent = 'Continue';
    }

    async function play() {
        const id = ++run;
        skipped = calm;
        shownRank = null;
        lines.concat(cards).forEach(el => el.classList.remove('is-in'));
        q('[data-xp-ach-title]').classList.remove('is-in');
        button.textContent = 'Skip';
        root.hidden = false;
        document.body.style.overflow = 'hidden';
        show(data.before, 0);

        let xp = data.before;
        await wait(700, id);

        for (const [i, line] of lines.entries()) {
            if (id !== run) return;
            line.classList.add('is-in');
            await count(xp, xp + data.lines[i].xp, id);
            xp += data.lines[i].xp;
            await wait(260, id);
        }

        await wait(350, id);
        q('[data-xp-ach-title]').classList.add('is-in');

        for (const [i, card] of cards.entries()) {
            if (id !== run) return;
            card.classList.add('is-in');
            await count(xp, xp + data.achievements[i].xp, id);
            xp += data.achievements[i].xp;
            await wait(quick ? 60 : 420, id);
        }

        if (id === run) finish();
    }

    function close() {
        run++;
        root.hidden = true;
        document.body.style.overflow = '';
        fillCard();
    }

    // First press ends the animation, the next one closes.
    function advance() {
        if (button.textContent === 'Continue') return close();
        skipped = true;
        finish();
    }

    button.addEventListener('click', advance);
    q('[data-xp-close]').addEventListener('click', close);
    root.addEventListener('click', e => { if (e.target === root) advance(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !root.hidden) close(); });
    play();

    // Shown once: tell the server, so the next visit opens without it.
    if (root.dataset.xpSeenUrl) {
        fetch(root.dataset.xpSeenUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }).catch(() => {});
    }
}
