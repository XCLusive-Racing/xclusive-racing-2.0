export function initRegister() {
    const wrap = document.querySelector('[data-register]');
    if (!wrap) return;

    const step1 = wrap.querySelector('[data-step="1"]');
    const step2 = wrap.querySelector('[data-step="2"]');
    const platformInput = wrap.querySelector('[data-platform-value]');
    const platformLabel = wrap.querySelector('[data-platform-label]');
    const gamertag = wrap.querySelector('[data-gamertag-input]');
    const steamHint = wrap.querySelector('[data-hint="steam"]');
    const xboxHint  = wrap.querySelector('[data-hint="xbox"]');
    const backBtn   = wrap.querySelector('[data-back]');

    function goToStep2(platform) {
        if (platformInput) platformInput.value = platform;
        step1.style.display = 'none';
        step2.style.display = '';

        const labels = { steam: 'Steam ID or Vanity URL', ps5: 'PSN Online ID', xbox: 'Xbox Gamertag' };
        if (platformLabel) platformLabel.textContent = labels[platform] || 'Username';

        const placeholders = {
            steam: 'SteamID64 or custom URL name',
            ps5:   'Your PSN Online ID',
            xbox:  'Your Xbox Gamertag',
        };
        if (gamertag) gamertag.placeholder = placeholders[platform] || '';

        if (steamHint) steamHint.style.display = platform === 'steam' ? '' : 'none';
        if (xboxHint)  xboxHint.style.display  = platform === 'xbox'  ? '' : 'none';
    }

    wrap.querySelectorAll('[data-select-platform]').forEach(btn => {
        btn.addEventListener('click', () => goToStep2(btn.dataset.selectPlatform));
    });

    backBtn?.addEventListener('click', () => {
        step1.style.display = '';
        step2.style.display = 'none';
    });
}
