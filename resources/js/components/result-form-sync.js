// Inputs inside a hidden section (the other category's fields, the other result
// types, unselected drivers' rows) must not be submitted — several share names
// like "title", and a hidden empty one would otherwise overwrite the visible one.
export function syncDisabled(form) {
    form.querySelectorAll('input, select, textarea').forEach(el => {
        if (['_token', '_method', 'subject'].includes(el.name)) return;

        let hidden = false;
        for (let node = el; node && node !== form; node = node.parentElement) {
            if (node.style && node.style.display === 'none') { hidden = true; break; }
        }
        el.disabled = hidden;
    });
}
