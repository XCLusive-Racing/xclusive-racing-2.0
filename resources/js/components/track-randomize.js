// A track <select data-track-randomize> carries a "Randomize" option: picking it
// selects a random real track straight away, so the admin sees which one it is
// (and can pick Randomize again to re-roll). Delegated, so rows the championship
// bulk round builder renders later work too.
export const RANDOM_TRACK = '__random__';

export function initTrackRandomize() {
    document.addEventListener('change', event => {
        const select = event.target;
        if (!(select instanceof HTMLSelectElement) || !select.matches('[data-track-randomize]') || select.value !== RANDOM_TRACK) {
            return;
        }

        const tracks = Array.from(select.options).filter(option => option.value && option.value !== RANDOM_TRACK && !option.disabled);
        if (!tracks.length) {
            select.value = '';
            return;
        }

        select.value = tracks[Math.floor(Math.random() * tracks.length)].value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
}
