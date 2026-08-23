@extends('layouts.app')

@section('title', 'My Team - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png');"></div>
    <div class="container" style="max-width:860px;position:relative;z-index:1">

        <div class="d-flex align-items-center gap-3 py-4 mb-2">
            <h1 class="fs-4 fw-black text-uppercase fst-italic text-dark mb-0">My Team</h1>
        </div>

        @if(session('team_success'))
        <div class="alert py-2 px-3 mb-3 rounded-2" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:.82rem">
            {{ session('team_success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="alert py-2 px-3 mb-3 rounded-2" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:.82rem">
            {{ $errors->first() }}
        </div>
        @endif

        <div class="d-flex flex-column gap-4">

            {{-- Team I own --}}
            @if($user->ownedRacingTeams->isNotEmpty())
            @php $myTeam = $user->ownedRacingTeams->first(); @endphp
            <div class="bg-white rounded-3 shadow-sm p-4">
                <div class="d-flex align-items-start justify-content-between mb-4">
                    <div>
                        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.4rem">
                            [{{ $myTeam->tag }}] {{ $myTeam->name }}
                        </div>
                        <span class="badge rounded-pill fw-bold mt-1" style="background:#7c3aed22;color:#7c3aed;font-size:.72rem">You are the owner</span>
                    </div>
                    <form action="{{ route('racing-teams.destroy', $myTeam) }}" method="POST"
                          onsubmit="return confirm('Delete team {{ addslashes($myTeam->name) }}? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm fw-bold text-uppercase"
                                style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.72rem">
                            Delete team
                        </button>
                    </form>
                </div>

                {{-- Members --}}
                <h3 class="fw-black text-uppercase fst-italic text-dark mb-3" style="font-size:.85rem;letter-spacing:.05em">Members</h3>

                @if($myTeam->members->isEmpty() && $myTeam->invitations->isEmpty())
                <p class="text-secondary mb-3" style="font-size:.82rem">No members yet. Invite your co-driver below.</p>
                @else
                <div class="d-flex flex-column gap-1 mb-3">
                    {{-- Accepted members --}}
                    @foreach($myTeam->members as $member)
                    <div class="d-flex align-items-center justify-content-between py-2 px-3 rounded-2"
                         style="background:#f9fafb;border:1px solid #f3f4f6">
                        <div class="d-flex align-items-center gap-2">
                            @if($member->avatarUrl())
                            <img src="{{ $member->avatarUrl() }}" width="28" height="28"
                                 style="border-radius:50%;object-fit:cover">
                            @else
                            <div style="width:28px;height:28px;border-radius:50%;background:#7c3aed22;display:flex;align-items:center;justify-content:center">
                                <span style="font-size:.65rem;font-weight:900;color:#7c3aed">{{ strtoupper(substr($member->displayName(), 0, 1)) }}</span>
                            </div>
                            @endif
                            <span style="font-size:.88rem;font-weight:600">{{ $member->displayName() }}</span>
                        </div>
                        <form action="{{ route('racing-teams.members.remove', [$myTeam, $member]) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm fw-bold text-uppercase"
                                    style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.68rem;padding:2px 8px">
                                Remove
                            </button>
                        </form>
                    </div>
                    @endforeach

                    {{-- Pending invites --}}
                    @foreach($myTeam->invitations as $invite)
                    <div class="d-flex align-items-center justify-content-between py-2 px-3 rounded-2"
                         style="background:#fffbeb;border:1px solid #fde68a">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:.88rem;font-weight:600">{{ $invite->user->displayName() }}</span>
                            <span class="badge fw-bold" style="background:#f59e0b22;color:#b45309;font-size:.65rem">PENDING</span>
                        </div>
                        <span class="text-secondary" style="font-size:.72rem">Awaiting response</span>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Add member with live search --}}
                <div class="d-flex flex-column gap-2">
                    <label class="fw-bold text-dark" style="font-size:.82rem">Add Member</label>
                    <div class="d-flex gap-2">
                        <div style="position:relative;flex:1">
                            <input type="text"
                                   id="member-search-input"
                                   placeholder="Search by username…"
                                   class="form-control"
                                   style="font-size:.85rem"
                                   autocomplete="off">
                            <div id="member-search-results"
                                 style="display:none;position:absolute;top:100%;left:0;right:0;z-index:50;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);margin-top:2px">
                            </div>
                        </div>
                        <form action="{{ route('racing-teams.members.add', $myTeam) }}" method="POST" id="add-member-form">
                            @csrf
                            <input type="hidden" name="user_id" id="selected-user-id">
                            <button type="submit" id="add-member-btn" disabled
                                    class="btn fw-black text-uppercase text-white flex-shrink-0"
                                    style="background:#7c3aed;font-size:.78rem;opacity:.5;cursor:not-allowed">
                                Add
                            </button>
                        </form>
                    </div>
                    <div id="selected-user-label" style="display:none;font-size:.78rem;color:#6b7280">
                        Selected: <strong id="selected-user-name"></strong>
                    </div>
                </div>
            </div>
            @endif

            {{-- Pending invites for me --}}
            @if($user->racingTeamInvitations->isNotEmpty())
            <div class="bg-white rounded-3 shadow-sm p-4">
                <h2 class="fw-black text-uppercase fst-italic text-dark mb-3" style="font-size:1rem">Team Invites</h2>
                <div class="d-flex flex-column gap-2">
                    @foreach($user->racingTeamInvitations as $invite)
                    <div class="d-flex align-items-center justify-content-between py-2 px-3 rounded-2"
                         style="background:#f9fafb;border:1px solid #f3f4f6">
                        <div>
                            <span style="font-size:.9rem;font-weight:700">[{{ $invite->team->tag }}] {{ $invite->team->name }}</span>
                            <span class="text-secondary d-block" style="font-size:.75rem">from {{ $invite->team->owner->displayName() }}</span>
                        </div>
                        <div class="d-flex gap-2">
                            <form action="{{ route('racing-teams.invite.accept', $invite) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm fw-black text-uppercase text-white"
                                        style="background:#7c3aed;font-size:.72rem">Accept</button>
                            </form>
                            <form action="{{ route('racing-teams.invite.decline', $invite) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm fw-bold text-uppercase"
                                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.72rem">Decline</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Teams I'm a member of --}}
            @foreach($user->racingTeams as $team)
            <div class="bg-white rounded-3 shadow-sm p-4">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1.1rem">
                            [{{ $team->tag }}] {{ $team->name }}
                        </div>
                        <span class="text-secondary" style="font-size:.78rem">Owner: {{ $team->owner->displayName() }}</span>
                    </div>
                    <form action="{{ route('racing-teams.leave', $team) }}" method="POST"
                          onsubmit="return confirm('Leave team {{ addslashes($team->name) }}?')">
                        @csrf
                        <button class="btn btn-sm fw-bold text-uppercase"
                                style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.72rem">
                            Leave
                        </button>
                    </form>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill fw-bold" style="background:#7c3aed22;color:#7c3aed;font-size:.72rem">
                        {{ $team->owner->displayName() }} (owner)
                    </span>
                    @foreach($team->members as $m)
                    <span class="badge rounded-pill fw-bold" style="background:#f3f4f6;color:#374151;font-size:.72rem">
                        {{ $m->displayName() }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endforeach

            {{-- Create team (only if not already an owner) --}}
            @if($user->ownedRacingTeams->isEmpty())
            <div class="bg-white rounded-3 shadow-sm p-4">
                <h2 class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:1rem">Create a Racing Team</h2>
                <p class="text-secondary mb-3" style="font-size:.82rem">You can own one team. You can still be a member of other teams.</p>
                <form action="{{ route('racing-teams.store') }}" method="POST" class="d-flex flex-column gap-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label fw-bold text-dark" style="font-size:.82rem">Team Name <span class="fw-normal text-secondary">(max 40)</span></label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Apex Racing" maxlength="40">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold text-dark" style="font-size:.82rem">Tag <span class="fw-normal text-secondary">(max 6)</span></label>
                            <input type="text" name="tag" value="{{ old('tag') }}"
                                   class="form-control @error('tag') is-invalid @enderror"
                                   placeholder="APX" maxlength="6"
                                   oninput="this.value=this.value.toUpperCase()">
                            @error('tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div>
                        <button class="btn fw-black text-uppercase text-white px-4 py-2"
                                style="background:#7c3aed">Create Team</button>
                    </div>
                </form>
            </div>
            @endif

        </div>
    </div>
</main>

<script>
(function () {
    const searchUrl = '{{ route('racing-teams.search') }}';
    const teamId    = {{ $myTeam->id ?? 'null' }};
    if (!teamId) return;

    const input     = document.getElementById('member-search-input');
    const dropdown  = document.getElementById('member-search-results');
    const hiddenId  = document.getElementById('selected-user-id');
    const addBtn    = document.getElementById('add-member-btn');
    const label     = document.getElementById('selected-user-label');
    const labelName = document.getElementById('selected-user-name');

    let debounceTimer = null;

    function setSelected(user) {
        hiddenId.value    = user.id;
        input.value       = user.name;
        labelName.textContent = user.name;
        label.style.display   = 'block';
        addBtn.disabled       = false;
        addBtn.style.opacity  = '1';
        addBtn.style.cursor   = 'pointer';
        dropdown.style.display = 'none';
        dropdown.innerHTML    = '';
    }

    function clearSelected() {
        hiddenId.value        = '';
        label.style.display   = 'none';
        addBtn.disabled       = true;
        addBtn.style.opacity  = '.5';
        addBtn.style.cursor   = 'not-allowed';
    }

    input.addEventListener('input', function () {
        clearSelected();
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 2) { dropdown.style.display = 'none'; return; }

        debounceTimer = setTimeout(async () => {
            const res   = await fetch(searchUrl + '?q=' + encodeURIComponent(q) + '&team_id=' + teamId);
            const users = await res.json();

            dropdown.innerHTML = '';
            if (!users.length) { dropdown.style.display = 'none'; return; }

            users.forEach(u => {
                const btn = document.createElement('button');
                btn.type      = 'button';
                btn.textContent = u.name;
                btn.className = 'w-100 px-3 py-2 text-start';
                btn.style.cssText = 'background:none;border:none;border-bottom:1px solid #f3f4f6;font-size:.85rem;font-weight:600;cursor:pointer';
                btn.addEventListener('click', () => setSelected(u));
                dropdown.appendChild(btn);
            });

            dropdown.style.display = 'block';
        }, 200);
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== input) {
            dropdown.style.display = 'none';
        }
    });
})();
</script>
@endsection