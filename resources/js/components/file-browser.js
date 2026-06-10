export default function fileBrowser(config) {
    return {
        showUpload: config.showUpload ?? false,
        showMkdir: config.showMkdir ?? false,
        renameModal: false,
        renamePath: '',
        renameName: '',
        deleteConfirm: null,
        viewModal: false,
        viewName: '',
        viewPath: '',
        viewContent: '',
        viewLoading: false,
        viewError: '',
        viewSaving: false,
        viewSaved: false,
        viewSaveError: '',
        openRename(path, name) {
            this.renamePath = path;
            this.renameName = name;
            this.renameModal = true;
            this.$nextTick(() => { if (this.$refs.renameInput) this.$refs.renameInput.focus(); });
        },
        async openView(path, name) {
            this.viewName      = name;
            this.viewPath      = path;
            this.viewContent   = '';
            this.viewError     = '';
            this.viewSaved     = false;
            this.viewSaveError = '';
            this.viewLoading   = true;
            this.viewModal     = true;
            try {
                const url = config.viewUrl + '?path=' + encodeURIComponent(path);
                const res = await fetch(url);
                const text = await res.text();
                if (!res.ok) {
                    const err = JSON.parse(text);
                    this.viewError = err.error ?? 'Could not load file.';
                } else {
                    this.viewContent = text;
                }
            } catch {
                this.viewError = 'Network error.';
            }
            this.viewLoading = false;
        },
        async saveFile() {
            this.viewSaving    = true;
            this.viewSaved     = false;
            this.viewSaveError = '';
            try {
                const res = await fetch(config.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ path: this.viewPath, content: this.viewContent }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.viewSaveError = data.error ?? 'Save failed.';
                } else {
                    this.viewSaved = true;
                }
            } catch {
                this.viewSaveError = 'Network error.';
            }
            this.viewSaving = false;
        },
    };
}
