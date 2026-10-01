{{-- Pushes settings.json, eventrules.json and assistrules.json built from this server's
     Config Defaults, plus a link to the files actually on the box (FTP browser) —
     shared by Configuration > Servers and a league's own server page. --}}
<div class="admin-card mb-4">
    <div class="px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <div class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:.82rem">Push Config to Server</div>
            <p class="text-secondary mb-0" style="font-size:.75rem">
                Pushes settings.json, eventrules.json and assistrules.json straight to this server. To review or edit first, use the file editors below. Browse Files shows the JSON files currently on the server.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.servers.browse', ['ftpServer' => $server->id, 'path' => $server->cfg_path ?: '/']) }}"
               class="btn btn-outline-secondary fw-black text-uppercase px-3" style="font-size:.78rem;white-space:nowrap">
                Browse Files
            </a>
            <form action="{{ route('admin.servers.push', $server) }}" method="POST">
                @csrf
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed;font-size:.78rem;white-space:nowrap">
                    Push Config →
                </button>
            </form>
        </div>
    </div>
</div>
