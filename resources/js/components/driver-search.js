export function initDriverSearch() {
    document.querySelectorAll('[data-driver-search]').forEach(input => {
        const source = input.closest('[data-driver-names]');
        if (!source || input.dataset.driverSearchInit) return;
        input.dataset.driverSearchInit = '1';

        let names = [];
        try { names = JSON.parse(source.dataset.driverNames); } catch { names = []; }

        const wrap = document.createElement('div');
        wrap.className = 'driver-search-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        const menu = document.createElement('div');
        menu.className = 'driver-search-menu';
        wrap.appendChild(menu);

        let matches     = [];
        let activeIndex = -1;

        function render() {
            menu.innerHTML = '';
            matches.forEach((name, i) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'driver-search-item' + (i === activeIndex ? ' active' : '');
                item.textContent = name;
                item.addEventListener('mousedown', e => {
                    e.preventDefault();
                    select(name);
                });
                menu.appendChild(item);
            });
            menu.classList.toggle('driver-search-menu--open', matches.length > 0);
        }

        function select(name) {
            input.value = name;
            close();
        }

        function close() {
            matches     = [];
            activeIndex = -1;
            render();
        }

        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            if (!q) { close(); return; }
            matches = names.filter(n => n.toLowerCase().includes(q)).slice(0, 8);
            activeIndex = -1;
            render();
        });

        input.addEventListener('keydown', e => {
            if (!matches.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, matches.length - 1);
                render();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                render();
            } else if (e.key === 'Enter') {
                if (activeIndex >= 0) {
                    e.preventDefault();
                    select(matches[activeIndex]);
                }
            } else if (e.key === 'Escape') {
                close();
            }
        });

        input.addEventListener('blur', () => {
            // Let a mousedown selection register before the menu closes.
            setTimeout(close, 120);
        });
    });
}