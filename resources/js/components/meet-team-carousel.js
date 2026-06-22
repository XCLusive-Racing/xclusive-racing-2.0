const drivers = [
    // ── PRO ──────────────────────────────────────────────────────
    {
        name: 'Dirk Schouten',
        cat: 'pro', platform: 'hybrid', platformLabel: 'Hybrid',
        photo: '/images/drivers/D.Schouten.png',
        flag: 'netherlands', role: 'racing',
        socials: [
            { type: 'instagram', href: 'https://www.instagram.com/dirk_schouten_/' },
            { type: 'tiktok',    href: 'https://www.tiktok.com/@dirkschouten34' },
            { type: 'youtube',   href: 'https://www.youtube.com/channel/UC6PwvyoGGVmql0a2Ch5RJ9w' },
            { type: 'linkedin',  href: 'https://www.linkedin.com/in/dirk-schouten-690221167/' },
            { type: 'facebook',  href: 'https://www.facebook.com/p/Dirk-Schouten-100007931509430/' },
        ],
    },
    {
        name: 'Mats van Rooijen',
        cat: 'pro', platform: 'hybrid', platformLabel: 'Hybrid',
        photo: '/images/drivers/M.vanRooijen.png',
        flag: 'netherlands', role: 'racing',
        socials: [
            { type: 'website',   href: 'https://matsvrooijen.vercel.app/' },
            { type: 'instagram', href: 'https://www.instagram.com/matsvanrooijen_official/' },
            { type: 'linkedin',  href: 'https://www.linkedin.com/in/mats-van-rooijen-540354314/' },
        ],
    },
    {
        name: 'Jesse Aalbregt', cat: 'pro', platform: 'hybrid', platformLabel: 'Hybrid',
        photo: '/images/drivers/J.Aalbregt.png', flag: 'netherlands', role: 'racing',
        socials: [
            { type: 'instagram', href: 'https://www.instagram.com/teamjesse81/' },
            { type: 'youtube',   href: 'https://www.youtube.com/@teamjesse81' },
            { type: 'tiktok',    href: 'https://www.tiktok.com/@teamjesse81' },
            { type: 'twitch',    href: 'https://www.twitch.tv/teamjesse81' },
        ],
    },

    // ── LMU ──────────────────────────────────────────────────────
    { name: 'Giuseppe Dinoia',   cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'italy',            role: 'esports', socials: [] },
    { name: 'Denis Ebert',       cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
    { name: 'Wilson Gigé',       cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: '/images/drivers/W.Gige.png',   flag: 'france',            role: 'esports', socials: [] },
    { name: 'Luca Gönnheimer',   cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
    { name: 'Kyan Heyninck',     cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'belgium',          role: 'esports', socials: [] },
    { name: 'Alex Lucky',        cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: '/images/drivers/A.Lucky.png',  flag: 'italy',             role: 'esports', socials: [] },
    { name: 'Paul Möller',       cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
    { name: 'Thato Motubatse',   cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'south%20africa',   role: 'esports', socials: [] },
    { name: 'Lukas Oesterreich', cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
    { name: 'Gianluca Walczak',  cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
    { name: 'Kyle Williams',     cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'south%20africa',   role: 'esports', socials: [] },
    { name: 'Aidan Winchester',  cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'united%20kingdom', role: 'esports', socials: [] },

    // ── ACC ──────────────────────────────────────────────────────
    { name: 'Nat Benett',          cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'united%20kingdom', role: 'esports', socials: [] },
    { name: 'Joakim Eriksson',     cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: null,              role: 'esports', socials: [] },
    { name: 'Fabio Faar',          cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'italy',           role: 'esports', socials: [] },
    { name: 'James Farish',        cat: 'acc', platform: 'console', platformLabel: 'Console', photo: '/images/drivers/J.Farish.png', flag: 'united%20kingdom', role: 'esports', socials: [] },
    { name: 'Will Friedmann',      cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'france',          role: 'esports', socials: [] },
    { name: 'José García',         cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: null,              role: 'esports', socials: [] },
    { name: 'Sergio Hernández',    cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: null,              role: 'esports', socials: [] },
    { name: 'Matteo Mastromauro',  cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'italy',           role: 'esports', socials: [] },
    { name: 'Danny Meeldijk',      cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'netherlands',     role: 'esports', socials: [] },
    { name: 'Elmārs Miķelsons',    cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'latvia',          role: 'esports', socials: [] },
    { name: 'Florian Ochsmann',    cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'germany',         role: 'esports', socials: [] },
    { name: 'Menno Peters',        cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'netherlands',     role: 'esports', socials: [] },
    { name: 'Phil Sourcy',         cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: null,              role: 'esports', socials: [] },
    { name: 'Gianluca Zambione',   cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'italy',           role: 'esports', socials: [] },
    { name: 'Federico Zamblera',   cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'italy',           role: 'esports', socials: [] },

    // ── iRacing ──────────────────────────────────────────────────
    { name: 'Ethan Amburg',     cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'usa',     role: 'esports', socials: [] },
    { name: 'James Curtin',     cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'usa',     role: 'esports', socials: [] },
    { name: 'CJ Farish',        cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'usa',     role: 'esports', socials: [] },
    { name: 'Mario García',     cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: null,      role: 'esports', socials: [] },
    { name: 'Jake Goldman',     cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'usa',     role: 'esports', socials: [] },
    { name: 'Michael Martinz',  cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'austria', role: 'esports', socials: [] },
    { name: 'Parker Soukup',    cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: '/images/drivers/P.Soukup.png', flag: 'usa', role: 'esports', socials: [] },
    { name: 'André Damrat',     cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany', role: 'esports', socials: [] },
];

const socialIconMap = {
    twitter:   'fa-brands fa-x-twitter',
    instagram: 'fa-brands fa-instagram',
    website:   'fa-solid fa-globe',
    linkedin:  'fa-brands fa-linkedin',
    facebook:  'fa-brands fa-facebook',
    twitch:    'fa-brands fa-twitch',
    tiktok:    'fa-brands fa-tiktok',
    youtube:   'fa-brands fa-youtube',
};

const gameBadgeLabel = cat =>
    ({ lmu: 'LMU', acc: 'ACC', iracing: 'IRACING', pro: 'PRO', staff: 'STAFF' }[cat] || cat.toUpperCase());

const platformBadgeClass = platform =>
    ({ pc: 'mt-badge--pc', hybrid: 'mt-badge--hybrid', xbox: 'mt-badge--xbox', ps5: 'mt-badge--ps5', console: 'mt-badge--console' }[platform] || '');

const roleLabel = role =>
    ({ esports: 'Esports Driver', racing: 'Professional Driver', staff: 'Staff' }[role] || role);

const roleClass = role =>
    ({ esports: 'mt-driver-role--esports', racing: 'mt-driver-role--racing', staff: 'mt-driver-role--staff' }[role] || '');

function renderCard(driver) {
    const socialsHtml = driver.socials.map(s => {
        const icon = socialIconMap[s.type] || 'fa-solid fa-link';
        const external = s.href !== '#' ? 'target="_blank" rel="noopener noreferrer"' : '';
        return `<a href="${s.href}" class="mt-social-link" title="${s.type}" ${external}><i class="${icon}"></i></a>`;
    }).join('');

    const flagHtml = driver.flag
        ? `<img src="/images/flags/flag-${driver.flag}.png" class="mt-driver-flag" alt="${driver.flag}">`
        : '';

    const photoHtml = driver.photo
        ? `<img src="${driver.photo}" alt="${driver.name}" style="width:100%;height:100%;object-fit:cover;object-position:50% 40%;">`
        : '';

    return `<div class="mt-carousel-item">
        <div class="mt-driver-card">
            <div class="mt-driver-portrait${!driver.photo ? ' mt-driver-portrait--blank' : ''}">
                ${photoHtml}
                <span class="mt-badge mt-badge--game">${gameBadgeLabel(driver.cat)}</span>
                <span class="mt-badge mt-badge--platform ${platformBadgeClass(driver.platform)}">${driver.platformLabel}</span>
                <div class="mt-driver-socials">${socialsHtml}</div>
            </div>
            <div class="mt-driver-info">
                <div class="mt-driver-name-row">
                    <span class="mt-driver-name">${driver.name}</span>
                    ${flagHtml}
                </div>
                <div class="mt-driver-role ${roleClass(driver.role)}">${roleLabel(driver.role)}</div>
            </div>
        </div>
    </div>`;
}

const moreCard = `<div class="mt-carousel-item">
    <div class="mt-driver-card mt-driver-card--more">
        <span class="mt-more-count">+29</span>
        <span class="mt-more-label">&amp; MORE</span>
        <a href="/teams" class="mt-more-link">View full roster →</a>
    </div>
</div>`;

export function initMeetTeam() {
    const section = document.querySelector('[data-meet-team]');
    if (!section) return;

    const track      = section.querySelector('[data-carousel-track]');
    const wrapper    = section.querySelector('[data-carousel-wrapper]');
    const prevBtn    = section.querySelector('[data-carousel-prev]');
    const nextBtn    = section.querySelector('[data-carousel-next]');
    const filterBtns = section.querySelectorAll('[data-filter-btn]');

    let filter  = 'all';
    let current = 0;

    const getPerPage    = () => window.innerWidth >= 768 ? 5 : 2;
    const getFiltered   = () => filter === 'all' ? drivers : drivers.filter(d => d.cat === filter);
    const getMaxCurrent = () => Math.max(0, getFiltered().length + 1 - getPerPage());

    function updateTrack() {
        const itemWidth = wrapper ? wrapper.offsetWidth / getPerPage() : 0;
        track.style.transform  = `translateX(-${current * itemWidth}px)`;
        track.style.transition = 'transform 0.3s ease';
    }

    function updateArrows() {
        if (prevBtn) prevBtn.style.display = current > 0              ? '' : 'none';
        if (nextBtn) nextBtn.style.display = current < getMaxCurrent() ? '' : 'none';
    }

    function render() {
        if (!track) return;
        track.innerHTML = getFiltered().map(renderCard).join('') + moreCard;
        updateTrack();
        updateArrows();
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filter  = btn.dataset.filterVal;
            current = 0;
            filterBtns.forEach(b => b.classList.toggle('mt-filter-btn--active', b === btn));
            render();
        });
    });

    prevBtn?.addEventListener('click', () => {
        if (current > 0) { current--; updateArrows(); updateTrack(); }
    });

    nextBtn?.addEventListener('click', () => {
        if (current < getMaxCurrent()) { current++; updateArrows(); updateTrack(); }
    });

    window.addEventListener('resize', () => {
        current = Math.min(current, getMaxCurrent());
        updateArrows();
        updateTrack();
    });

    render();
    filterBtns.forEach(btn => btn.classList.toggle('mt-filter-btn--active', btn.dataset.filterVal === 'all'));
}
