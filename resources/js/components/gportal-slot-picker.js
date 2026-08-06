export function initGportalSlotPicker() {
    const serverEl = document.getElementById('gp-server');
    if (!serverEl) return;

    const slotPicker  = document.getElementById('gp-slot-picker');
    const schedNote   = document.getElementById('gp-scheduled-note');
    const slotGrid    = document.getElementById('gp-slot-grid');
    const slotValue   = document.getElementById('gp-slot-value');
    const slotLabel   = document.getElementById('gp-slot-selected');

    let serverSlots = {};
    try { serverSlots = JSON.parse(serverEl.dataset.serverSlots || '{}'); } catch { serverSlots = {}; }

    const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    function toLocalTime(utcSlot) {
        const d = new Date(utcSlot.replace(' ', 'T') + ':00Z');
        return d.toLocaleTimeString('en-GB', { timeZone: 'Europe/London', hour: '2-digit', minute: '2-digit' });
    }

    function toLocalDayKey(utcSlot) {
        const d = new Date(utcSlot.replace(' ', 'T') + ':00Z');
        return d.toLocaleDateString('en-CA', { timeZone: 'Europe/London' });
    }

    function toLocalDayHeader(utcSlot) {
        const d = new Date(utcSlot.replace(' ', 'T') + ':00Z');
        const weekday = d.toLocaleDateString('en-GB', { timeZone: 'Europe/London', weekday: 'short' });
        const day     = d.toLocaleDateString('en-GB', { timeZone: 'Europe/London', day: '2-digit', month: '2-digit' });
        return weekday + ' ' + day;
    }

    function buildSlotGrid(serverId) {
        slotGrid.innerHTML = '';
        const data = serverSlots[serverId];
        if (!data || !data.slots.length) return;

        const taken    = data.takenSlots || [];
        const selected = slotValue ? slotValue.value : '';

        const byDate  = {};
        const headers = {};
        data.slots.forEach(slot => {
            const key = toLocalDayKey(slot);
            if (!byDate[key]) { byDate[key] = []; headers[key] = toLocalDayHeader(slot); }
            byDate[key].push(slot);
        });

        Object.entries(byDate).forEach(([dateKey, slots]) => {
            const col = document.createElement('div');
            col.style.cssText = 'min-width:90px';

            const dayLabel = document.createElement('div');
            dayLabel.className = 'fw-black text-uppercase mb-1';
            dayLabel.style.cssText = 'font-size:.68rem;letter-spacing:.06em;color:#9ca3af';
            dayLabel.textContent = headers[dateKey];
            col.appendChild(dayLabel);

            slots.forEach(slot => {
                const displayTime = toLocalTime(slot);
                const isTaken     = taken.includes(slot);
                const isSelected  = selected === slot;

                const btn = document.createElement('button');
                btn.type        = 'button';
                btn.textContent = displayTime;
                btn.className   = 'btn btn-sm fw-bold mb-1 d-block w-100';
                btn.style.cssText = 'font-size:.72rem;border-radius:6px;padding:3px 6px;' + (
                    isTaken    ? 'background:#f3f4f6;color:#d1d5db;cursor:not-allowed;border:1px solid #e5e7eb' :
                    isSelected ? 'background:#7c3aed;color:#fff;border:1px solid #7c3aed' :
                                 'background:#f8f5ff;color:#7c3aed;border:1px solid rgba(124,58,237,.3)'
                );

                if (!isTaken) {
                    btn.addEventListener('click', () => {
                        slotValue.value = slot;
                        slotLabel.textContent   = '✓ ' + displayTime + ' (GMT/BST)';
                        slotLabel.style.display = '';
                        buildSlotGrid(serverId);
                    });
                } else {
                    btn.disabled = true;
                    btn.title    = 'Already taken';
                }

                col.appendChild(btn);
            });

            slotGrid.appendChild(col);
        });
    }

    function onServerChange() {
        const opt  = serverEl.options[serverEl.selectedIndex];
        const type = opt ? opt.dataset.type : null;
        const id   = serverEl.value;

        slotPicker.style.display = '';
        schedNote.style.display  = 'none';
        slotGrid.innerHTML       = '';

        if (!id) {
            slotPicker.style.display = 'none';
            return;
        }

        if (type === 'scheduled') {
            slotPicker.style.display = 'none';
            schedNote.style.display  = '';
        } else {
            slotPicker.style.display = '';
            buildSlotGrid(id);
        }
    }

    serverEl.addEventListener('change', onServerChange);
    onServerChange();
}