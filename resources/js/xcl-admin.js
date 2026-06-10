export function xcToasts() {
    return {
        add({ message, type = 'success' }) {
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            }).fire({
                icon: type === 'success' ? 'success' : 'error',
                title: message,
            });
        },
    };
}

export async function xcDeleteSubmit(form, title, text = '') {
    const result = await Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
    });
    if (result.isConfirmed) form.submit();
}

export function ratingRow(key, initialValue, step) {
    return {
        editing: false,
        tempValue: initialValue,
        saving: false,
        error: '',
        inputStep: step,
        async save() {
            this.saving = true;
            this.error  = '';
            try {
                const r = await fetch(`/admin/rating-config/${key}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ value: parseFloat(this.tempValue) }),
                });
                const data = await r.json();
                if (r.ok) {
                    this.tempValue = data.value;
                    this.editing   = false;
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Saved', type: 'success' } }));
                } else {
                    this.error = data.message || 'Failed to save';
                }
            } catch {
                this.error = 'Network error';
            } finally {
                this.saving = false;
            }
        },
        cancel() {
            this.editing = false;
            this.error   = '';
        },
    };
}

export function testConnection(id, btn) {
    btn.textContent = 'Testing…';
    btn.disabled = true;

    fetch(`/admin/servers/${id}/test`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.textContent = '✓ Online';
            btn.style.cssText = 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;font-size:.68rem;padding:3px 10px';
        } else {
            btn.textContent = '✗ Offline';
            btn.style.cssText = 'background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.68rem;padding:3px 10px';
        }
        btn.disabled = false;
    })
    .catch(() => {
        btn.textContent = '✗ Error';
        btn.disabled = false;
    });
}

export function previewAvatar(input) {
    const filename = document.getElementById('avatar-filename');
    filename.textContent = input.files[0]?.name ?? 'No file chosen';

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.getElementById('avatar-preview-wrap');
            wrap.innerHTML = `<img id="avatar-preview" src="${e.target.result}" class="rounded-circle" style="width:64px;height:64px;object-fit:cover">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
