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

import Alpine from 'alpinejs';
import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import xcMediaPicker from './components/media-picker.js';
import eventTags from './components/event-tags.js';
import eventsFilter from './components/events-filter.js';
import { eventsSidebar, countdownTimer } from './components/events-sidebar.js';
import mediaManager from './components/media-manager.js';
import fileBrowser from './components/file-browser.js';
import { xcToasts, xcDeleteSubmit, ratingRow, testConnection, previewAvatar } from './xcl-admin.js';

window.Alpine    = Alpine;
window.bootstrap = bootstrap;
window.Swal      = Swal;
window.xcDeleteSubmit = xcDeleteSubmit;
window.testConnection = testConnection;
window.previewAvatar  = previewAvatar;

Alpine.data('xcMediaPicker',  xcMediaPicker);
Alpine.data('eventTags',      eventTags);
Alpine.data('xcToasts',       xcToasts);
Alpine.data('eventsFilter',   eventsFilter);
Alpine.data('eventsSidebar',  eventsSidebar);
Alpine.data('countdownTimer', countdownTimer);
Alpine.data('mediaManager',   mediaManager);
Alpine.data('fileBrowser',    fileBrowser);
Alpine.data('ratingRow',      ratingRow);

Alpine.start();
