export function initCoachingFilter() {
    const bar = document.querySelector('[data-coaching-filter]');
    if (!bar) return;

    const gameBtns     = bar.querySelectorAll('[data-game-filter]');
    const platformBtns = bar.querySelectorAll('[data-platform-filter]');
    const cards        = document.querySelectorAll('[data-coach-games]');

    let game     = 'all';
    let platform = 'pc';

    function apply() {
        cards.forEach(card => {
            const games     = card.dataset.coachGames.split(' ');
            const platforms = card.dataset.coachPlatforms.split(' ');
            const match      = (game === 'all' || games.includes(game))
                && platforms.includes(platform);
            card.style.display = match ? '' : 'none';
        });
    }

    gameBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            game = btn.dataset.gameFilter;
            gameBtns.forEach(b => b.classList.toggle('mt-filter-btn--active', b === btn));
            apply();
        });
    });

    platformBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            platform = btn.dataset.platformFilter;
            platformBtns.forEach(b => b.classList.toggle('mt-filter-btn--active', b === btn));
            apply();
        });
    });

    apply();
}
