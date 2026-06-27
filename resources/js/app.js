// Fonts (self-hosted, served from build output — no external DNS lookups)
import '@fontsource/poppins/400.css';
import '@fontsource/poppins/500.css';
import '@fontsource/poppins/600.css';
import '@fontsource/poppins/700.css';
import '@fontsource/poppins/800.css';
import '@fontsource/poppins/900.css';
import '@fontsource/barlow-condensed/400.css';
import '@fontsource/barlow-condensed/600.css';
import '@fontsource/barlow-condensed/700.css';
import '@fontsource/barlow-condensed/800-italic.css';
import '@fontsource/rajdhani/500.css';
import '@fontsource/rajdhani/600.css';
import '@fontsource/rajdhani/700.css';
import '@fontsource/dm-sans/400.css';
import '@fontsource/dm-sans/500.css';
import '@fontsource/dm-sans/600.css';
import '@fontsource/dm-sans/700.css';

// Font Awesome — solid + brands only (not all.min.css)
import '@fortawesome/fontawesome-free/css/fontawesome.min.css';
import '@fortawesome/fontawesome-free/css/solid.min.css';
import '@fortawesome/fontawesome-free/css/brands.min.css';

import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';

import { xcDeleteSubmit, testConnection, previewAvatar } from './xcl-admin.js';

// Vanilla JS modules
import { init as initAdminLayout } from './pages/admin/layout.js';
import { initNavbar } from './pages/navbar.js';
import { initEventsFilter } from './components/events-filter.js';
import { initEventTags } from './components/event-tags.js';
import { initCountdownTimers } from './components/countdown-timer.js';
import { initPasswordToggles } from './components/password-toggle.js';
import { initCheckboxToggles } from './components/toggle.js';
import { initTabs, initAccordions, initActivateTab } from './components/tabs.js';
import { initRegister } from './pages/auth/register.js';
import { initTeamCards } from './pages/team.js';
import { initImageUploads } from './components/image-upload.js';
import { initCalendar } from './pages/calendar.js';
import { initMeetTeam } from './components/meet-team-carousel.js';
import { initMulticlass } from './components/multiclass.js';
import { initBulkCreate } from './pages/admin/bulk-create.js';
import { initMediaIndex } from './pages/admin/media.js';
import { initRatingRows } from './pages/admin/rating.js';
import { initFileBrowser } from './pages/admin/file-browser.js';
import { initEventsSidebar } from './components/events-sidebar.js';
import { initMediaPickers } from './components/media-picker.js';

window.bootstrap      = bootstrap;
window.Swal           = Swal;
window.xcDeleteSubmit = xcDeleteSubmit;
window.testConnection = testConnection;
window.previewAvatar  = previewAvatar;

// Toast system — listens for 'toast' custom events dispatched by JS modules and the flash bridge
window.addEventListener('toast', e => {
    Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
    }).fire({
        icon: e.detail?.type === 'success' ? 'success' : 'error',
        title: e.detail?.message ?? '',
    });
});

// Vanilla JS init — runs on every page after DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initAdminLayout();
    initNavbar();
    initEventsFilter();
    initCountdownTimers();
    initPasswordToggles();
    initCheckboxToggles();
    document.querySelectorAll('[data-tags-wrap]').forEach(el => initEventTags(el));
    document.querySelectorAll('[data-tabs]').forEach(wrap => {
        initTabs(wrap, wrap.dataset.defaultTab);
    });
    document.querySelectorAll('[data-accordions]').forEach(wrap => {
        initAccordions(wrap);
    });
    initActivateTab();
    initRegister();
    initTeamCards();
    initImageUploads();
    initCalendar();
    initMeetTeam();
    document.querySelectorAll('[data-multiclass-wrap]').forEach(el => initMulticlass(el));
    document.querySelectorAll('[data-bulk-wrap]').forEach(el => initBulkCreate(el));
    initMediaIndex();
    initRatingRows();
    initFileBrowser();
    initEventsSidebar();
    initMediaPickers();

    // Generic: show/hide element based on a select's current value
    document.querySelectorAll('[data-select-conditional]').forEach(select => {
        const scope = select.closest('[data-select-conditional-wrap]') || select.parentElement;
        function applyConditional() {
            scope.querySelectorAll('[data-show-when]').forEach(el => {
                el.style.display = el.dataset.showWhen === select.value ? '' : 'none';
            });
        }
        select.addEventListener('change', applyConditional);
        applyConditional();
    });

    // Ballast live display (bops form)
    document.querySelectorAll('[data-ballast-wrap]').forEach(wrap => {
        const input   = wrap.querySelector('[data-ballast-input]');
        const display = wrap.querySelector('[data-ballast-display]');
        if (!input || !display) return;
        function updateBallast() {
            const v = parseFloat(input.value) || 0;
            display.textContent = v > 0 ? '+' + v + ' kg' : v + ' kg';
            display.style.color = v > 0 ? '#ef4444' : (v < 0 ? '#10b981' : '#9ca3af');
        }
        input.addEventListener('input', updateBallast);
        updateBallast();
    });
});
