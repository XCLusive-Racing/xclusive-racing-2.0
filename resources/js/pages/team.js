export function initTeamCards() {
    document.querySelectorAll('[data-hover-card]').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.classList.add('events-platform-card--active');
            card.querySelector('.events-platform-card__desc')
                ?.classList.add('events-platform-card__desc--visible');
        });
        card.addEventListener('mouseleave', () => {
            card.classList.remove('events-platform-card--active');
            card.querySelector('.events-platform-card__desc')
                ?.classList.remove('events-platform-card__desc--visible');
        });
    });
}
