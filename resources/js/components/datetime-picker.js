import flatpickr from 'flatpickr';

export function initDateTimePickers() {
    document.querySelectorAll('[data-flatpickr]').forEach(el => {
        if (el._flatpickr) return;

        flatpickr(el, {
            enableTime: true,
            time_24hr: true,
            allowInput: true,
            minuteIncrement: 5,
            dateFormat: 'Y-m-d\\TH:i',
            altInput: true,
            altFormat: 'D, d M Y \\a\\t H:i',
            minDate: el.dataset.minToday === 'true' ? 'today' : undefined,
        });
    });
}