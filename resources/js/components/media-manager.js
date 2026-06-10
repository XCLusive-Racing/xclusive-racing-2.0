export default function mediaManager(config = {}) {
    return {
        uploadOpen: config.openUpload ?? false,
        typeFilter: 'all',
        categoryFilter: 'all',
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
