@extends('layouts.admin')

@section('title', $league->name)
@section('page-title', 'League — ' . $league->name)

@section('page-actions')
    <a href="{{ route('admin.leagues.championships.index', $league) }}" class="btn btn-sm fw-black text-uppercase text-white px-3"
       style="background:#7c3aed;font-size:.78rem">
        Championships
    </a>
    <a href="{{ route('admin.leagues.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

{{-- League context banner — always visible, so whoever is looking at this screen
     knows exactly which league they are acting in. --}}
<div class="admin-card mb-4" style="overflow:hidden">
    <div class="d-flex align-items-center gap-3 px-4 py-3" style="background:linear-gradient(90deg, {{ $league->primary_color }}18, {{ $league->accent_color }}10)">
        @if($league->logo_url)
        <img src="{{ $league->logo_url }}" alt="" style="width:44px;height:44px;object-fit:contain;border-radius:8px;background:#fff;padding:4px">
        @else
        <div style="width:44px;height:44px;border-radius:8px;background:{{ $league->primary_color }};flex-shrink:0"></div>
        @endif
        <div class="flex-grow-1">
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">{{ $league->name }}</div>
            <div class="text-secondary" style="font-size:.75rem;font-family:monospace">/{{ $league->slug }}</div>
        </div>
        @php $sc = ['active' => '#16a34a', 'draft' => '#f59e0b', 'archived' => '#6b7280'][$league->status] ?? '#6b7280'; @endphp
        <span class="badge" style="background:{{ $sc }}22;color:{{ $sc }};font-size:.72rem;padding:5px 10px;border-radius:6px;font-weight:700">
            {{ ucfirst($league->status) }}
        </span>
        @unless($isAdmin)
        <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.68rem;padding:5px 10px;border-radius:6px;font-weight:700">
            You are acting as {{ $canEdit ? 'League Manager' : 'League Steward' }}
        </span>
        @endunless
    </div>
</div>

@if($league->trashed())
<div class="admin-card mb-4 px-4 py-3" style="border-left:4px solid #6b7280">
    <p class="fw-bold mb-1" style="font-size:.85rem">This league is archived.</p>
    <p class="text-secondary mb-0" style="font-size:.8rem">
        Restore it from the <a href="{{ route('admin.leagues.index') }}">Leagues list</a> before making changes.
    </p>
</div>
@endif

<div class="row g-4 align-items-start">
    <div class="col-12 col-lg-7">

        @if($canEdit && !$league->trashed())
        <form action="{{ route('admin.leagues.update', $league) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="admin-card mb-4">
                @if($isAdmin)
                <div class="px-4 pt-4 pb-2">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Identity</p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label">League Name</label>
                            <input type="text" name="name" value="{{ old('name', $league->name) }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $league->slug) }}"
                                   class="form-control @error('slug') is-invalid @enderror" style="font-family:monospace">
                            @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="draft" {{ old('status', $league->status) === 'draft' ? 'selected' : '' }}>Draft — not visible publicly</option>
                            <option value="active" {{ old('status', $league->status) === 'active' ? 'selected' : '' }}>Active</option>
                        </select>
                        <div class="form-text" style="font-size:.72rem">
                            Archiving is a separate, owner-only action from the Leagues list — not a status you set here.
                        </div>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                @else
                <div class="px-4 pt-4 pb-2">
                    <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Identity</p>
                    <p class="text-secondary mb-0" style="font-size:.78rem">
                        Name, slug and status are set by XCL. You can edit branding, description and links below.
                    </p>
                </div>
                @endif

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Branding</p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Primary Colour</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" data-color-pick="primary_color_hex" class="form-control form-control-color" style="width:46px;padding:2px" value="{{ old('primary_color', $league->primary_color) }}">
                                <input type="text" name="primary_color" id="primary_color_hex" value="{{ old('primary_color', $league->primary_color) }}"
                                       class="form-control @error('primary_color') is-invalid @enderror" style="font-family:monospace">
                            </div>
                            @error('primary_color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Accent Colour</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" data-color-pick="accent_color_hex" class="form-control form-control-color" style="width:46px;padding:2px" value="{{ old('accent_color', $league->accent_color) }}">
                                <input type="text" name="accent_color" id="accent_color_hex" value="{{ old('accent_color', $league->accent_color) }}"
                                       class="form-control @error('accent_color') is-invalid @enderror" style="font-family:monospace">
                            </div>
                            @error('accent_color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Logo</label>
                            @if($league->logo_url)
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <img src="{{ $league->logo_url }}" alt="" style="width:32px;height:32px;object-fit:contain;border-radius:6px;background:#f9fafb">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="logo_remove" id="logo_remove" value="1">
                                    <label class="form-check-label text-secondary" for="logo_remove" style="font-size:.78rem">Remove current logo</label>
                                </div>
                            </div>
                            @endif
                            <input type="file" name="logo" accept="image/*" class="form-control @error('logo') is-invalid @enderror">
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Banner</label>
                            @if($league->banner_url)
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <img src="{{ $league->banner_url }}" alt="" style="width:48px;height:32px;object-fit:cover;border-radius:6px;background:#f9fafb">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="banner_remove" id="banner_remove" value="1">
                                    <label class="form-check-label text-secondary" for="banner_remove" style="font-size:.78rem">Remove current banner</label>
                                </div>
                            </div>
                            @endif
                            <input type="file" name="banner" accept="image/*" class="form-control @error('banner') is-invalid @enderror">
                            @error('banner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Description</p>
                    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $league->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Links</p>

                    <div class="mb-3">
                        <label class="form-label">Discord Invite URL</label>
                        <input type="url" name="discord_invite_url" value="{{ old('discord_invite_url', $league->discord_invite_url) }}"
                               class="form-control @error('discord_invite_url') is-invalid @enderror" placeholder="https://discord.gg/...">
                        @error('discord_invite_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Website URL</label>
                        <input type="url" name="website_url" value="{{ old('website_url', $league->website_url) }}"
                               class="form-control @error('website_url') is-invalid @enderror" placeholder="https://...">
                        @error('website_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($isAdmin)
                    <div class="mb-3">
                        <label class="form-label">Discord Server (Guild) ID</label>
                        <input type="text" name="discord_guild_id" value="{{ old('discord_guild_id', $league->discord_guild_id) }}"
                               class="form-control @error('discord_guild_id') is-invalid @enderror" placeholder="e.g. 123456789012345678"
                               style="font-family:monospace">
                        <div class="form-text" style="font-size:.72rem;color:#9ca3af">
                            Right-click the league's Discord server icon (Developer Mode on) → Copy Server ID. Needed for membership checks —
                            the invite link above is just a "join" link and doesn't give XCL's bot access on its own.
                        </div>
                        @error('discord_guild_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($league->discord_guild_id)
                    <div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
                        @if($discordBotInGuild === true)
                        <span class="badge fw-bold" style="background:#dcfce7;color:#166534;font-size:.7rem;padding:4px 10px;border-radius:6px">Bot installed</span>
                        @elseif($discordBotInGuild === false)
                        <span class="badge fw-bold" style="background:#fee2e2;color:#991b1b;font-size:.7rem;padding:4px 10px;border-radius:6px">Bot not in this server yet</span>
                        @else
                        <span class="badge fw-bold" style="background:#f3f4f6;color:#6b7280;font-size:.7rem;padding:4px 10px;border-radius:6px">Couldn't check right now</span>
                        @endif
                        @if($league->discordBotInviteUrl())
                        <a href="{{ $league->discordBotInviteUrl() }}" target="_blank" rel="noopener"
                           class="btn btn-sm fw-bold text-uppercase" style="background:#5865F2;color:#fff;font-size:.72rem">
                            Invite bot to this server →
                        </a>
                        @endif
                    </div>
                    @endif

                    <div class="form-check mb-0" style="opacity:.55">
                        <input class="form-check-input" type="checkbox" id="requires_discord" disabled
                               {{ $league->requires_discord_membership ? 'checked' : '' }}>
                        {{-- Disabled checkboxes submit nothing — this hidden field carries the
                             real, unchanged value through the save instead. --}}
                        <input type="hidden" name="requires_discord_membership" value="{{ $league->requires_discord_membership ? '1' : '0' }}">
                        <label class="form-check-label fw-bold text-dark" for="requires_discord" style="font-size:.82rem">
                            Require Discord membership to register
                        </label>
                        <div class="form-text" style="font-size:.72rem">
                            Temporarily locked — XCL is still finishing the operational Discord bot setup. Coming soon.
                        </div>
                    </div>
                    @else
                    <div class="form-text" style="font-size:.72rem;color:#9ca3af">
                        Whether Discord membership is required to register is set by XCL.
                        Currently {{ $league->requires_discord_membership ? 'required' : 'not required' }}.
                    </div>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Save Changes
                </button>
            </div>
        </form>
        @else
        {{-- Read-only view for a league steward — no edit rights on the league itself. --}}
        <div class="admin-card mb-4">
            <div class="px-4 py-4">
                <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Description</p>
                <p class="text-dark mb-4" style="font-size:.85rem">{{ $league->description ?: 'No description yet.' }}</p>

                <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Links</p>
                <p class="mb-1" style="font-size:.85rem">
                    Discord:
                    @if($league->discord_invite_url)
                    <a href="{{ $league->discord_invite_url }}" target="_blank" rel="noopener">{{ $league->discord_invite_url }}</a>
                    @else
                    <span class="text-secondary">Not set</span>
                    @endif
                </p>
                <p class="mb-0" style="font-size:.85rem">
                    Website:
                    @if($league->website_url)
                    <a href="{{ $league->website_url }}" target="_blank" rel="noopener">{{ $league->website_url }}</a>
                    @else
                    <span class="text-secondary">Not set</span>
                    @endif
                </p>
            </div>
        </div>
        @endif

    </div>

    @if($canEdit)
    <div class="col-12 col-lg-5">
        @if($isAdmin)
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Members</div>
            </div>

            <div class="px-4 py-3">
                @forelse($members as $member)
                <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid #f3f4f6">
                    <div>
                        <div class="fw-bold text-dark" style="font-size:.85rem">{{ $member->user->name }}</div>
                        <div class="text-secondary text-uppercase" style="font-size:.68rem;letter-spacing:.05em">{{ ucfirst($member->role) }}</div>
                    </div>
                    <form action="{{ route('admin.leagues.members.destroy', [$league, $member]) }}" method="POST" onsubmit="return false">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-sm fw-bold" style="background:transparent;color:#dc2626;font-size:.72rem"
                                onclick="xcDeleteSubmit(this.closest('form'), 'Remove {{ addslashes($member->user->name) }} from {{ addslashes($league->name) }}?')">
                            Remove
                        </button>
                    </form>
                </div>
                @empty
                <p class="text-secondary mb-0" style="font-size:.82rem">No members yet.</p>
                @endforelse
            </div>

            <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Add Member</p>
                <form action="{{ route('admin.leagues.members.store', $league) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <select name="user_id" class="form-select form-select-sm @error('user_id') is-invalid @enderror" required>
                            <option value="">Select a user…</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <select name="role" class="form-select form-select-sm @error('role') is-invalid @enderror" required>
                            <option value="manager">Manager</option>
                            <option value="steward">Steward</option>
                        </select>
                        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-sm fw-black text-uppercase text-white w-100" style="background:#7c3aed;font-size:.78rem">
                        Add Member
                    </button>
                </form>
            </div>
        </div>
        @endif

        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">FTP Servers</div>
            </div>
            <div class="px-4 py-3">
                <p class="text-secondary mb-3" style="font-size:.78rem">Championship rounds pushed for this league will race on one of these servers.</p>
                @forelse($servers as $server)
                <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid #f3f4f6">
                    <div class="fw-bold text-dark" style="font-size:.85rem">{{ $server->name }}</div>
                    <form action="{{ route('admin.leagues.servers.destroy', [$league, $server]) }}" method="POST" onsubmit="return false">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-sm fw-bold" style="background:transparent;color:#dc2626;font-size:.72rem"
                                onclick="xcDeleteSubmit(this.closest('form'), 'Remove {{ addslashes($server->name) }} from {{ addslashes($league->name) }}?')">
                            Remove
                        </button>
                    </form>
                </div>
                @empty
                <p class="text-secondary mb-0" style="font-size:.82rem">No servers yet.</p>
                @endforelse
            </div>

            <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Add Your Own Server</p>
                <form action="{{ route('admin.leagues.servers.create', $league) }}" method="POST">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <input type="text" name="name" placeholder="Server name" class="form-control form-control-sm @error('name') is-invalid @enderror" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-5">
                            <select name="server_type" class="form-select form-select-sm" required>
                                <option value="rolling">Rolling restart</option>
                                <option value="scheduled">Manual restart</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <select name="game" class="form-select form-select-sm" required>
                                <option value="acc">ACC</option>
                                <option value="lmu">Le Mans Ultimate</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <select name="platform" class="form-select form-select-sm" required>
                                <option value="console">Console</option>
                                <option value="pc">PC</option>
                                <option value="cross">Crossplay</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <input type="text" name="host" placeholder="Host / IP" class="form-control form-control-sm @error('host') is-invalid @enderror" required>
                            @error('host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-4">
                            <input type="number" name="port" placeholder="Port" class="form-control form-control-sm @error('port') is-invalid @enderror" required>
                            @error('port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="text" name="username" placeholder="FTP username" class="form-control form-control-sm @error('username') is-invalid @enderror" required>
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <input type="password" name="password" placeholder="FTP password" class="form-control form-control-sm @error('password') is-invalid @enderror" required>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="text" name="path" placeholder="Config path" class="form-control form-control-sm @error('path') is-invalid @enderror" required>
                            @error('path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <input type="text" name="cfg_path" placeholder="cfg_path (optional)" class="form-control form-control-sm @error('cfg_path') is-invalid @enderror">
                            @error('cfg_path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <input type="number" name="reset_start_hour" placeholder="Reset start hour (0-23)" class="form-control form-control-sm @error('reset_start_hour') is-invalid @enderror">
                            @error('reset_start_hour') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <input type="number" name="reset_interval_minutes" placeholder="Reset interval (min)" class="form-control form-control-sm @error('reset_interval_minutes') is-invalid @enderror">
                            @error('reset_interval_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm fw-black text-uppercase text-white w-100" style="background:#7c3aed;font-size:.78rem">
                        Add Server
                    </button>
                </form>
            </div>

            @if($isAdmin && $unassignedServers->isNotEmpty())
            <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Assign Server</p>
                <form action="{{ route('admin.leagues.servers.store', $league) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <select name="ftp_server_id" class="form-select form-select-sm @error('ftp_server_id') is-invalid @enderror" required>
                            <option value="">Select a server…</option>
                            @foreach($unassignedServers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('ftp_server_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-sm fw-black text-uppercase text-white w-100" style="background:#7c3aed;font-size:.78rem">
                        Assign Server
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-color-pick]').forEach(function (picker) {
        var text = document.getElementById(picker.dataset.colorPick);
        if (!text) return;
        picker.addEventListener('input', function () { text.value = picker.value; });
        text.addEventListener('input', function () {
            if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
        });
    });
})();
</script>
@endpush

@endsection
