export default function mediaManager(config = {}) {
    return {
        uploadOpen: config.openUpload ?? false,
        typeFilter: 'all',
        categoryFilter: 'all',
        newFolderMode: false,
        newFolderName: '',

        matches(type, category) {
            const typeOk     = this.typeFilter === 'all' || this.typeFilter === type;
            const categoryOk = this.categoryFilter === 'all'
                || (this.categoryFilter === '__none__' && !category)
                || this.categoryFilter === category;
            return typeOk && categoryOk;
        },

        async confirmDelete(url) {
            const r = await Swal.fire({
                title: 'Delete file?',
                text: 'This permanently removes the file from storage. Any races referencing it will lose the media.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            });
            if (r.isConfirmed) {
                this.$refs.deleteForm.action = url;
                this.$refs.deleteForm.submit();
            }
        },
    };
}
