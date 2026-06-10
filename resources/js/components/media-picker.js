export default function xcMediaPicker(config) {
    return {
        preview: config.preview || '',
        previewType: config.previewType || 'image',
        mediaPath: config.mediaPath || '',
        galleryUrl: config.galleryUrl,
        uploadUrl: config.uploadUrl,
        deleteBaseUrl: config.deleteBaseUrl,
        csrfToken: config.csrfToken,
        galleryOpen: false,
        galleryItems: [],
        gallerySearch: '',
        galleryFilter: config.filterDefault || 'all',
        galleryLoading: false,
        modalUploading: false,

        async openGallery() {
            this.galleryOpen = true;
            if (!this.galleryItems.length) {
                this.galleryLoading = true;
                try {
                    const r = await fetch(this.galleryUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    this.galleryItems = await r.json();
                } finally {
                    this.galleryLoading = false;
                }
            }
        },

        selectGallery(item) {
            this.preview = item.url;
            this.previewType = item.type || 'image';
            this.mediaPath = item.path;
            this.$refs.fileInput.value = '';
            this.galleryOpen = false;
        },

        onFileChange(e) {
            const f = e.target.files[0];
            if (!f) return;
            this.preview = URL.createObjectURL(f);
            this.previewType = f.type.startsWith('video/') ? 'video' : 'image';
            this.mediaPath = '';
        },

        async onModalUpload(e) {
            const f = e.target.files[0];
            if (!f) return;
            this.preview = URL.createObjectURL(f);
            this.previewType = f.type.startsWith('video/') ? 'video' : 'image';
            this.modalUploading = true;
            try {
                const fd = new FormData();
                fd.append('file', f);
                fd.append('_token', this.csrfToken);
                const r = await fetch(this.uploadUrl, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (r.ok) {
                    const data = await r.json();
                    this.mediaPath = data.path;
                    this.preview = data.url;
                    this.previewType = data.type || 'image';
                    this.galleryItems = [];
                }
            } finally {
                this.modalUploading = false;
                this.galleryOpen = false;
            }
        },

        clear() {
            this.preview = '';
            this.previewType = 'image';
            this.mediaPath = '';
            this.$refs.fileInput.value = '';
        },

        async deleteMedia(item) {
            if (!confirm('Delete "' + (item.title || item.original_name) + '" from the library? This cannot be undone.')) return;
            const r = await fetch(this.deleteBaseUrl + item.id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            if (r.ok) {
                this.galleryItems = this.galleryItems.filter(i => i.id !== item.id);
                if (this.mediaPath === item.path) this.clear();
            }
        },

        get filtered() {
            return this.galleryItems.filter(i => {
                const matchType = this.galleryFilter === 'all' || i.type === this.galleryFilter;
                if (!this.gallerySearch) return matchType;
                const q = this.gallerySearch.toLowerCase();
                return matchType && (
                    (i.original_name || '').toLowerCase().includes(q) ||
                    (i.title || '').toLowerCase().includes(q)
                );
            });
        },
    };
}
