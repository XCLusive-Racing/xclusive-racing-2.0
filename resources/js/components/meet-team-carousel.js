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

    // ── ESPORTS (LMU / ACC / iRacing mixed, sorted by last name) ──
    { name: 'Nat Benett',       cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/Bennett.png', flag: 'united%20kingdom', role: 'esports', socials: [] },
    { name: 'Lucas Britton',    cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Britton.png', flag: 'united%20kingdom', role: 'esports', socials: [
        { type: 'instagram', href: 'https://www.instagram.com/lucas_baaaaada/' },
    ] },
    {
        name: 'Thomas Cauberghe',
        cat: 'lmu', platform: 'pc', platformLabel: 'PC',
        photo: '/images/drivers/Cauberghe.png',
        flag: 'belgium', role: 'esports',
        socials: [
            { type: 'tiktok', href: 'https://www.tiktok.com/@thomascauberghe?_r=1&_t=ZG-98h5kheJfzl' },
            { type: 'twitch', href: 'http://twitch.tv/thomascauberghee' },
        ],
    },
    { name: 'James Curtin',     cat: 'iracing', platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Curtin.png', flag: 'usa',              role: 'esports', socials: [] },
    { name: 'André Damrat',     cat: 'iracing', platform: 'pc',      platformLabel: 'PC',      photo: null, flag: 'germany',          role: 'esports', socials: [] },
    { name: 'Giuseppe Dinoia',  cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Dinoia.png', flag: 'italy',            role: 'esports', socials: [
        { type: 'twitch',    href: 'https://www.twitch.tv/giuseppedinoia_21' },
        { type: 'instagram', href: 'https://www.instagram.com/xcl_giuseppedinoia?igsh=MWRxcTBldTNqd2Y3YQ==' },
        { type: 'tiktok',    href: 'https://www.tiktok.com/@giuseppe_dinoia?_r=1&_t=ZN-95QVprGxrMJ' },
    ] },
    { name: 'Denis Ebert',      cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/d.ebert.png', flag: 'germany',          role: 'esports', socials: [
        { type: 'instagram', href: 'https://www.instagram.com/ebert_racing?igsh=MWY3N2ZwNnhmNWE4YQ==' },
    ] },
    { name: 'Joakim Eriksson',  cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/Eriksson.png', flag: null,              role: 'esports', socials: [] },
    { name: 'Fabio Faar',       cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/f.faar.png', flag: 'italy',           role: 'esports', socials: [] },
    { name: 'CJ Farish',        cat: 'iracing', platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Mrshlk.png', flag: 'usa',              role: 'esports', socials: [] },
    { name: 'James Farish',     cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/J.Farish.png', flag: 'united%20kingdom', role: 'esports', socials: [] },
    { name: 'Will Friedmann',   cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/friedmann.png', flag: 'france',         role: 'esports', socials: [] },
    { name: 'José García',      cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/Garcia.png', flag: null,              role: 'esports', socials: [] },
    { name: 'Mario García',     cat: 'iracing', platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Mare.png', flag: null,                role: 'esports', socials: [] },
    { name: 'Wilson Gigé',      cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/W.Gige.png', flag: 'france',           role: 'esports', socials: [
        { type: 'twitch',    href: 'https://www.twitch.tv/rxspectpapy' },
        { type: 'instagram', href: 'https://www.instagram.com/rxspect_papy?igsh=Y21hMGYzOWRtemEy' },
        { type: 'tiktok',    href: 'https://www.tiktok.com/@rxspect.papy?_r=1&_t=ZN-95QWmWjw0s2' },
    ] },
    { name: 'Jake Goldman',     cat: 'iracing', platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Goldman2.png', flag: 'usa',            role: 'esports', socials: [] },
    { name: 'Luca Gönnheimer',  cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/goenni.png', flag: 'germany',          role: 'esports', socials: [
        { type: 'youtube',   href: 'https://www.youtube.com/' },
        { type: 'instagram', href: 'https://www.instagram.com/goenni98?igsh=Mzk3OW5oamxpbnR2' },
    ] },
    { name: 'Sergio Hernández', cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/Hernández.png', flag: null,             role: 'esports', socials: [] },
    { name: 'Kyan Heyninck',    cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/heyninck.png', flag: 'belgium',        role: 'esports', socials: [
        { type: 'youtube',   href: 'https://www.youtube.com/@kyanheyninck' },
        { type: 'instagram', href: 'https://www.instagram.com/kyan.heyninck/?hl=nl' },
    ] },
    { name: 'Brody Lawless',    cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/brodylaweless.png', flag: 'united%20kingdom', role: 'esports', socials: [
        { type: 'tiktok',    href: 'https://www.tiktok.com/@brodyl00' },
        { type: 'instagram', href: 'https://www.instagram.com/brody.l1/' },
    ] },
    { name: 'Marcus Libz',      cat: 'iracing', platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Libz.png', flag: 'canada',             role: 'esports', socials: [] },
    { name: 'Alex Lucky',       cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/A.Lucky.png', flag: 'italy',           role: 'esports', socials: [
        { type: 'instagram', href: 'https://www.instagram.com/alexxluckyy?igsh=NWRleW9jbnRhaGlj' },
        { type: 'tiktok',    href: 'https://www.tiktok.com/@alexxluckyy?_r=1&_t=ZN-95QVr8UQG06' },
    ] },
    { name: 'Michael Martinz',  cat: 'iracing', platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Martinz.png', flag: 'austria',          role: 'esports', socials: [] },
    { name: 'Matteo Mastromauro', cat: 'acc',   platform: 'console', platformLabel: 'Console', photo: '/images/drivers/matteomastromauro.png', flag: 'italy', role: 'esports', socials: [] },
    { name: 'Danny Meeldijk',   cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/Danny.png', flag: 'netherlands',      role: 'esports', socials: [] },
    { name: 'Elmārs Miķelsons', cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/elmars.png', flag: 'latvia',           role: 'esports', socials: [] },
    { name: 'Melvin Milasten',  cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/m.milasten.png', flag: 'sweden',        role: 'esports', socials: [
        { type: 'tiktok',    href: 'https://www.tiktok.com/@mellemelon6823' },
        { type: 'instagram', href: 'https://www.instagram.com/melvinmilasten' },
        { type: 'twitch',    href: 'https://www.twitch.tv/melle234353' },
    ] },
    { name: 'Paul Möller',      cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: null, flag: 'germany',          role: 'esports', socials: [
        { type: 'instagram', href: 'https://www.instagram.com/p.moeller787?igsh=bWh4Z3VpZjV0bDBk' },
    ] },
    { name: 'Thato Motubatse',  cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/motubatse.png', flag: 'south%20africa',   role: 'esports', socials: [] },
    { name: 'Florian Ochsmann', cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/ochsmann.png', flag: 'germany',        role: 'esports', socials: [] },
    { name: 'Lukas Oesterreich', cat: 'lmu',    platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Louk.png', flag: 'germany',            role: 'esports', socials: [
        { type: 'youtube',   href: 'https://www.youtube.com/@Louky99' },
        { type: 'instagram', href: 'https://www.instagram.com/speedlukas?igsh=OHRsbzFzMzA1OHl3' },
    ] },
    { name: 'Menno Peters',     cat: 'iracing', platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/PetersM.png', flag: 'netherlands',     role: 'esports', socials: [] },
    { name: 'Phil Soucy',       cat: 'acc',     platform: 'console', platformLabel: 'Console', photo: '/images/drivers/p.soucy.png', flag: 'canada',           role: 'esports', socials: [] },
    { name: 'Parker Soukup',    cat: 'iracing', platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/P.Soukup.png', flag: 'usa',            role: 'esports', socials: [] },
    { name: 'Jure Artač Vičič', cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/jure.png', flag: null,                role: 'esports', socials: [
        { type: 'twitch',    href: 'https://www.twitch.tv/jure_av' },
        { type: 'youtube',   href: 'https://www.youtube.com/@JureAV' },
        { type: 'youtube',   href: 'https://www.youtube.com/@JureAV2' },
        { type: 'instagram', href: 'https://www.instagram.com/jure_av/' },
    ] },
    { name: 'Gianluca Walczak', cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Walczak.png', flag: 'germany',         role: 'esports', socials: [] },
    { name: 'Kyle Williams',    cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Williams.png', flag: 'south%20africa',  role: 'esports', socials: [
        { type: 'instagram', href: 'https://www.instagram.com/kyle.williams55?igsh=MXRhOWl1cmF5NjIwMA==' },
    ] },
    { name: 'Aidan Winchester', cat: 'lmu',     platform: 'pc',      platformLabel: 'PC',      photo: '/images/drivers/Winchester.png', flag: 'united%20kingdom', role: 'esports', socials: [
        { type: 'twitch',    href: 'https://www.twitch.tv/aidannn66' },
        { type: 'instagram', href: 'https://www.instagram.com/aidanwinchester66?igsh=bnZ5OWU4cHdwdWdn' },
    ] },
    { name: 'Gianluca Zambione', cat: 'acc',    platform: 'console', platformLabel: 'Console', photo: '/images/drivers/Gianluca.png', flag: 'italy',           role: 'esports', socials: [] },
    { name: 'Federico Zamblera', cat: 'acc',    platform: 'console', platformLabel: 'Console', photo: '/images/drivers/Zamby.png', flag: 'italy',             role: 'esports', socials: [] },

    // ── Coaches (DriveLab) ───────────────────────────────────────
    { name: 'Nikodem Wisniewski', cat: 'coach', platform: 'pc',      platformLabel: 'PC',      photo: '/images/coaches/nikodem.avif', flag: null, role: 'coach', socials: [] },
    { name: 'Dominik Blajer',     cat: 'coach', platform: 'pc',      platformLabel: 'PC',      photo: '/images/coaches/dominik.avif', flag: null, role: 'coach', socials: [] },
    { name: 'Przemysław Lemanek', cat: 'coach', platform: 'pc',      platformLabel: 'PC',      photo: '/images/coaches/przemek.avif', flag: null, role: 'coach', socials: [] },
    { name: 'Dorian Castelli',    cat: 'coach', platform: 'console', platformLabel: 'Console', photo: '/images/coaches/dorian.png',   flag: null, role: 'coach', socials: [], photoStyle: 'transform:scale(1.45);transform-origin:50% 0%;' },
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
    ({ lmu: 'LMU', acc: 'ACC', iracing: 'IRACING', pro: 'PRO', staff: 'STAFF', coach: 'COACH' }[cat] || cat.toUpperCase());

const platformBadgeClass = platform =>
    ({ pc: 'mt-badge--pc', hybrid: 'mt-badge--hybrid', xbox: 'mt-badge--xbox', ps5: 'mt-badge--ps5', console: 'mt-badge--console' }[platform] || '');

const roleLabel = role =>
    ({ esports: 'Esports Driver', racing: 'Professional Driver', staff: 'Staff', coach: 'Coach' }[role] || role);

const roleClass = role =>
    ({ esports: 'mt-driver-role--esports', racing: 'mt-driver-role--racing', staff: 'mt-driver-role--staff', coach: 'mt-driver-role--coach' }[role] || '');

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
        ? `<img src="${driver.photo}" alt="${driver.name}" style="width:100%;height:100%;object-fit:cover;object-position:50% 40%;${driver.photoStyle || ''}">`
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
        <a href="/team" class="mt-more-link">View full roster →</a>
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
    const getFiltered   = () => (filter === 'all' ? drivers : drivers.filter(d => d.role === filter)).filter(d => d.photo);
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
