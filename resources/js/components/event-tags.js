export default function eventTags(config) {
    return {
        adding: false,
        tagName: '',
        tagColor: '#7B2FBE',
        saving: false,
        tagError: '',
        tagSuccess: '',
        tags: config.tags || [],
        storeUrl: config.storeUrl,
        deleteBaseUrl: config.deleteBaseUrl,
        csrfToken: config.csrfToken,

        async saveTag() {
            if (!this.tagName.trim()) return;
            this.saving = true; this.tagError = ''; this.tagSuccess = '';
            try {
                const r = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ name: this.tagName, color: this.tagColor }),
                });
                const data = await r.json();
                if (r.ok) {
                    this.tags.push({ slug: data.slug, name: data.name, color: data.color });
                    this.tagSuccess = data.name + ' added!';
                    this.tagName = ''; this.tagColor = '#7B2FBE';
                    setTimeout(() => { this.adding = false; this.tagSuccess = ''; }, 1200);
                } else {
                    this.tagError = data.errors?.name?.[0] || data.message || 'Failed to save tag.';
                }
            } catch { this.tagError = 'Network error.'; }
            finally { this.saving = false; }
        },

        async deleteTag(slug) {
            const res = await Swal.fire({
                title: 'Delete tag?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            });
            if (!res.isConfirmed) return;
            const r = await fetch(this.deleteBaseUrl + slug, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-HTTP-Method-Override': 'DELETE',
                    'Accept': 'application/json',
                },
            });
            if (r.ok) this.tags = this.tags.filter(t => t.slug !== slug);
        },
    };
}
