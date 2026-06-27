export function initTabs(wrap, defaultTab) {
    if (!wrap) return;

    const buttons = wrap.querySelectorAll(':scope > * [data-tab-btn], [data-tab-nav] [data-tab-btn]');
    const panels  = wrap.querySelectorAll(':scope > [data-tab-panel]');

    function activateTab(tabId) {
        wrap.querySelectorAll('[data-tab-btn]').forEach(btn => {
            const active = btn.dataset.tabBtn === tabId;
            if (btn.dataset.tabActiveClass) {
                btn.classList.toggle(btn.dataset.tabActiveClass, active);
            } else if (btn.dataset.tabActiveStyle !== undefined) {
                btn.style.cssText = active
                    ? btn.dataset.tabActiveStyle
                    : (btn.dataset.tabInactiveStyle || '');
                btn.classList.toggle('border-bottom-0', active);
                btn.classList.toggle('text-secondary', !active);
            } else {
                btn.style.color        = active ? (btn.dataset.tabColor || '#7c3aed') : '#9ca3af';
                btn.style.borderBottom = active ? `2px solid ${btn.dataset.tabColor || '#7c3aed'}` : '2px solid transparent';
            }
        });
        wrap.querySelectorAll('[data-tab-panel]').forEach(panel => {
            panel.style.display = panel.dataset.tabPanel === tabId ? '' : 'none';
        });
        wrap.dataset.activeTab = tabId;
    }

    wrap.querySelectorAll('[data-tab-btn]').forEach(btn => {
        btn.addEventListener('click', () => activateTab(btn.dataset.tabBtn));
    });

    activateTab(defaultTab || wrap.querySelector('[data-tab-btn]')?.dataset.tabBtn);
}

export function initAccordions(wrap) {
    if (!wrap) return;

    wrap.querySelectorAll('[data-accordion]').forEach(item => {
        const header = item.querySelector('[data-accordion-header]');
        const body   = item.querySelector('[data-accordion-body]');
        const arrow  = item.querySelector('[data-accordion-arrow]');
        let open     = item.dataset.accordion === 'open';

        function apply() {
            if (body)  body.style.display   = open ? '' : 'none';
            if (arrow) arrow.style.transform = open ? 'rotate(90deg)' : '';
            item.querySelectorAll('[data-show-when-open]').forEach(el => el.style.display = open ? '' : 'none');
            item.querySelectorAll('[data-show-when-closed]').forEach(el => el.style.display = open ? 'none' : '');
        }

        apply();

        header?.addEventListener('click', () => {
            open = !open;
            apply();
        });
    });
}

export function initActivateTab() {
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-activate-tab]');
        if (!btn) return;
        const tabId = btn.dataset.activateTab;
        const wrap  = btn.closest('[data-tabs]');
        if (!wrap) return;
        const tabBtn = wrap.querySelector(`[data-tab-btn="${tabId}"]`);
        tabBtn?.click();
    });
}
