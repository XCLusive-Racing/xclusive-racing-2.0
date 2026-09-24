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

import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

import 'flatpickr/dist/flatpickr.min.css';

import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';

import { xcDeleteSubmit, testConnection, previewAvatar } from './xcl-admin.js';

// Vanilla JS modules
import { init as initAdminLayout } from './pages/admin/layout.js';
import { initNavbar } from './pages/navbar.js';
import { initHeaderHeight } from './components/header-height.js';
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
import { initPointsSystem } from './components/points-system.js';
import { initBulkCreate } from './pages/admin/bulk-create.js';
import { initImportExport } from './pages/admin/import-export.js';
import { initMediaIndex } from './pages/admin/media.js';
import { initRatingRows } from './pages/admin/rating.js';
import { initFileBrowser } from './pages/admin/file-browser.js';
import { initEventsSidebar } from './components/events-sidebar.js';
import { initMediaPickers } from './components/media-picker.js';
import { initCoachingFilter } from './components/coaching-filter.js';
import { initDriverSearch } from './components/driver-search.js';
import { initDateTimePickers } from './components/datetime-picker.js';
import { initGportalSlotPicker } from './components/gportal-slot-picker.js';
import { initTeamEventDriverPicker } from './components/team-event-driver-picker.js';
import { initResultSubjectToggle } from './components/result-subject-toggle.js';
import { initResultDriverPositions } from './components/result-driver-positions.js';
import { initResultRaceRepeater } from './components/result-race-repeater.js';

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
    initHeaderHeight();
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
    document.querySelectorAll('[data-points-wrap]').forEach(el => initPointsSystem(el));
    document.querySelectorAll('[data-bulk-wrap]').forEach(el => initBulkCreate(el));
    document.querySelectorAll('[data-import-export-wrap]').forEach(el => initImportExport(el));
    initMediaIndex();
    initRatingRows();
    initFileBrowser();
    initEventsSidebar();
    initMediaPickers();
    initCoachingFilter();
    initDriverSearch();
    initDateTimePickers();
    initGportalSlotPicker();
    initTeamEventDriverPicker();
    initResultSubjectToggle();
    initResultDriverPositions();
    initResultRaceRepeater();

    // EasyMDE rich text editor
    const richEl = document.querySelector('.rich-editor');
    if (richEl) {
        new EasyMDE({
            element: richEl,
            spellChecker: false,
            autosave: { enabled: false },
            toolbar: [
                'bold', 'italic', 'heading', '|',
                'quote', 'unordered-list', 'ordered-list', '|',
                'link', 'image', '|',
                'preview', 'side-by-side', 'fullscreen', '|',
                'guide',
            ],
        });
    }

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

    // Server picker follows the selected game's ACC platform (BOP push) — ACC PC only
    // lists PC servers, ACC console only console ones (FtpServer::supportsRaceGame()).
    document.querySelectorAll('[data-server-platform-select]').forEach(serverSelect => {
        const gameSelect = serverSelect.form?.querySelector('[data-server-game-select]');
        if (!gameSelect) return;
        function updateServers() {
            const game = gameSelect.value;
            Array.from(serverSelect.options).forEach(o => {
                const fits = !o.value || (game !== 'acc' && game !== 'ac')
                    || (o.dataset.platform === 'pc') === (game === 'ac');
                o.hidden = !fits;
                o.disabled = !fits;
            });
            if (serverSelect.selectedOptions[0]?.disabled) serverSelect.value = '';
        }
        gameSelect.addEventListener('change', updateServers);
        updateServers();
    });

    // Car model suggestions follow the selected game (bops form) — ACC console and
    // ACC PC each have their own car list, other games have none (free text).
    document.querySelectorAll('[data-bop-car-input]').forEach(input => {
        const gameSelect = input.form?.querySelector('select[name="game"]');
        if (!gameSelect) return;
        function updateCarList() {
            const list = document.getElementById('bop-cars-' + gameSelect.value);
            if (list) {
                input.setAttribute('list', list.id);
            } else {
                input.removeAttribute('list');
            }
        }
        gameSelect.addEventListener('change', updateCarList);
        updateCarList();
    });
});
