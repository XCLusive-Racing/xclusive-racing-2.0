@extends('layouts.app')

@section('title', 'Edit Profile - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:700px">

        {{-- Header --}}
        <div class="d-flex align-items-center gap-3 py-4 mb-2">
            <a href="{{ route('profile') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
                ← Back
            </a>
            <h1 class="fs-4 fw-black text-uppercase fst-italic text-dark mb-0">Edit Profile</h1>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            {{-- Identity --}}
            <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
                <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1rem">Identity</h2>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">Display Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">Country</label>
                        <input type="text" name="country" value="{{ old('country', $user->country) }}"
                               class="form-control" placeholder="Netherlands">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">Team / Quote</label>
                        <input type="text" name="team" value="{{ old('team', $user->team) }}"
                               class="form-control" placeholder="Team XCLusive">
                    </div>
                </div>
            </div>

            {{-- Appearance --}}
            <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
                <h2 class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:1rem">Avatar</h2>
                <p class="text-secondary mb-3" style="font-size:.82rem">JPEG, PNG, WebP — max 4 MB</p>
                <div class="d-flex align-items-center gap-3">
                    {{-- Current avatar preview --}}
                    <div id="avatar-preview-wrap" class="flex-shrink-0">
                        @if($user->banner)
                            <img id="avatar-preview" src="{{ $user->avatarUrl() }}" alt=""
                                 class="rounded-circle" style="width:64px;height:64px;object-fit:cover">
                        @else
                            <div id="avatar-initials" class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black"
                                 style="width:64px;height:64px;font-size:1.3rem;background:linear-gradient(135deg,#7c3aed,#db2777)">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <label class="btn fw-bold text-uppercase text-white mb-1"
                               style="background:#7c3aed;font-size:.78rem;padding:6px 16px;border-radius:6px;cursor:pointer">
                            Choose Photo
                            <input type="file" name="avatar" accept="image/*" class="d-none" id="avatar-input"
                                   onchange="previewAvatar(this)">
                        </label>
                        <div id="avatar-filename" class="text-secondary mt-1" style="font-size:.75rem">No file chosen</div>
                        @error('avatar')<div class="text-danger mt-1" style="font-size:.78rem">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Racing --}}
            <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
                <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1rem">Racing</h2>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">Racing Number</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold" style="background:#f9fafb">#</span>
                            <input type="number" name="car_number" value="{{ old('car_number', $user->car_number) }}"
                                   class="form-control" min="1" max="9999" placeholder="69">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">Primary Game</label>
                        <select name="game" class="form-select">
                            <option value="">— None —</option>
                            @foreach(['acc' => 'ACC Console', 'lmu' => 'Le Mans Ultimate', 'iracing' => 'iRacing'] as $val => $label)
                            <option value="{{ $val }}" {{ old('game', $user->game) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">Favourite Car</label>
                        <input type="text" name="car_model" value="{{ old('car_model', $user->car_model) }}"
                               class="form-control" placeholder="Lamborghini Huracán GT3">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">Platform</label>
                        <input type="text" value="{{ strtoupper($user->platform ?? '—') }} · {{ $user->platform_id ?? '—' }}"
                               class="form-control" disabled style="background:#f9fafb;font-family:monospace;font-size:.82rem">
                        <div class="text-secondary mt-1" style="font-size:.72rem">Platform ID cannot be changed. Contact an admin if needed.</div>
                    </div>
                </div>
            </div>

            {{-- Password --}}
            <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
                <h2 class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:1rem">Change Password</h2>
                <p class="text-secondary mb-3" style="font-size:.82rem">Leave blank to keep your current password</p>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">Current Password</label>
                        <input type="password" name="current_password" autocomplete="current-password"
                               class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">New Password</label>
                        <input type="password" name="new_password" autocomplete="new-password"
                               class="form-control @error('new_password') is-invalid @enderror">
                        @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-bold text-dark" style="font-size:.82rem">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" autocomplete="new-password"
                               class="form-control">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4 py-2"
                        style="background:#7c3aed">Save Changes</button>
                <a href="{{ route('profile') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4 py-2">
                    Cancel
                </a>
            </div>

        </form>

        {{-- Connected Accounts --}}
        <div class="bg-white rounded-3 shadow-sm p-4 mt-4">
            <h2 class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:1rem">Connected Accounts</h2>
            <p class="text-secondary mb-4" style="font-size:.82rem">Link your gaming and social accounts to your profile.</p>

            @if(session('success'))
            <div class="alert py-2 px-3 mb-3 rounded-2" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:.82rem">
                {{ session('success') }}
            </div>
            @endif
            @error('discord')<div class="alert py-2 px-3 mb-3 rounded-2" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:.82rem">{{ $message }}</div>@enderror
            @error('steam')<div class="alert py-2 px-3 mb-3 rounded-2" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:.82rem">{{ $message }}</div>@enderror

            <div class="d-flex flex-column gap-3">

                {{-- Discord --}}
                @php $discord = $user->connectedAccount('discord') @endphp
                <div class="d-flex align-items-center gap-3 p-3 rounded-2" style="border:1px solid #e5e7eb">
                    <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                         style="width:40px;height:40px;background:#5865F2;color:#fff">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.002.022.015.042.032.055a19.874 19.874 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size:.88rem">Discord</div>
                        @if($discord)
                        <div class="text-secondary" style="font-size:.78rem">{{ $discord->username }}</div>
                        @else
                        <div class="text-secondary" style="font-size:.78rem">Not connected</div>
                        @endif
                    </div>
                    @if($discord)
                    <form action="{{ route('connected-accounts.destroy', $discord) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                                style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.72rem">
                            Disconnect
                        </button>
                    </form>
                    @else
                    <a href="{{ route('auth.discord') }}" class="btn btn-sm fw-bold text-uppercase text-white"
                       style="background:#5865F2;font-size:.72rem">Connect</a>
                    @endif
                </div>

                {{-- Steam --}}
                @php $steam = $user->connectedAccount('steam') @endphp
                <div class="d-flex align-items-center gap-3 p-3 rounded-2" style="border:1px solid #e5e7eb">
                    <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                         style="width:40px;height:40px;background:#1b2838;color:#c7d5e0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.912-.59.063 0 .125.004.188.006l2.861-4.142V8.91c0-2.495 2.028-4.524 4.524-4.524 2.494 0 4.524 2.031 4.524 4.527s-2.03 4.525-4.524 4.525h-.105l-4.076 2.911c0 .052.004.105.004.159 0 1.875-1.515 3.396-3.39 3.396-1.635 0-3.016-1.173-3.331-2.727L.436 15.27C1.862 20.307 6.486 24 11.979 24c6.627 0 11.999-5.373 11.999-12S18.606 0 11.979 0z"/></svg>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size:.88rem">Steam</div>
                        @if($steam)
                        <div class="text-secondary" style="font-size:.78rem">{{ $steam->username }}</div>
                        @else
                        <div class="text-secondary" style="font-size:.78rem">Not connected</div>
                        @endif
                    </div>
                    @if($steam)
                    <form action="{{ route('connected-accounts.destroy', $steam) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                                style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.72rem">
                            Disconnect
                        </button>
                    </form>
                    @else
                    <a href="{{ route('auth.steam') }}" class="btn btn-sm fw-bold text-uppercase text-white"
                       style="background:#1b2838;font-size:.72rem">Connect</a>
                    @endif
                </div>

                {{-- Xbox --}}
                @php $xbox = $user->connectedAccount('xbox') @endphp
                <div class="d-flex align-items-start gap-3 p-3 rounded-2" style="border:1px solid #e5e7eb"
                     x-data="{ open: {{ $xbox ? 'false' : 'false' }} }">
                    <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                         style="width:40px;height:40px;background:#107c10;color:#fff">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M4.102 5.481C5.553 3.636 7.404 2.214 9.535 1.357c.077-.031.161.028.163.112.044 1.748.76 3.36 1.886 4.574-2.24.68-4.205 1.98-5.652 3.68-.116.138-.332.08-.367-.09-.225-1.097-.076-2.268.537-4.152zM12 4.667c-.453-1.47-1.312-2.778-2.441-3.784a.155.155 0 0 0-.199.005 10.46 10.46 0 0 0-4.035 6.73c-.024.179.177.299.322.19C7.465 6.26 9.626 5.083 12 4.667zM19.898 5.481C18.447 3.636 16.596 2.214 14.465 1.357c-.077-.031-.161.028-.163.112-.044 1.748-.76 3.36-1.886 4.574 2.24.68 4.205 1.98 5.652 3.68.116.138.332.08.367-.09.225-1.097.076-2.268-.537-4.152zM12 4.667c.453-1.47 1.312-2.778 2.441-3.784a.155.155 0 0 1 .199.005 10.46 10.46 0 0 1 4.035 6.73c.024.179-.177.299-.322.19C16.535 6.26 14.374 5.083 12 4.667zM5.652 9.68C4.342 11.27 3.5 13.278 3.5 15.5c0 2.346.934 4.475 2.454 6.043.126.131.333.087.404-.078C7.695 18.447 9.682 16.59 12 15.5c2.318 1.09 4.305 2.947 5.642 5.965.071.165.278.209.404.078C19.566 19.975 20.5 17.846 20.5 15.5c0-2.222-.842-4.23-2.152-5.82C16.78 8.075 14.51 7 12 7s-4.78 1.075-6.348 2.68z"/></svg>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size:.88rem">Xbox</div>
                        @if($xbox)
                        <div class="text-secondary" style="font-size:.78rem">{{ $xbox->username }}</div>
                        @else
                        <div class="text-secondary mb-2" style="font-size:.78rem">Not connected</div>
                        <form action="{{ route('connected-accounts.store') }}" method="POST" class="d-flex gap-2">
                            @csrf
                            <input type="hidden" name="provider" value="xbox">
                            <input type="text" name="username" placeholder="Gamertag"
                                   class="form-control form-control-sm @error('username') is-invalid @enderror"
                                   style="max-width:200px;font-size:.8rem">
                            <button type="submit" class="btn btn-sm fw-bold text-uppercase text-white flex-shrink-0"
                                    style="background:#107c10;font-size:.72rem">Connect</button>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </form>
                        @endif
                    </div>
                    @if($xbox)
                    <form action="{{ route('connected-accounts.destroy', $xbox) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                                style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.72rem">
                            Disconnect
                        </button>
                    </form>
                    @endif
                </div>

                {{-- PlayStation --}}
                @php $psn = $user->connectedAccount('psn') @endphp
                <div class="d-flex align-items-start gap-3 p-3 rounded-2" style="border:1px solid #e5e7eb">
                    <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                         style="width:40px;height:40px;background:#00439c;color:#fff">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8.984 2.596v14.347l3.924 1.205V6.67c0-.69.304-1.151.794-.991.636.18.76.814.76 1.503v4.129c1.947.691 4.72.226 4.72-3.686 0-3.81-2.04-5.285-5.123-6.034-1.358-.33-2.95-.521-4.075.005zm6.852 14.5c-2.354.826-4.935.406-6.852-.51v2.454l-5.817-1.799V15.76l5.82 1.793v-1.89l-5.82-1.796V12.38l5.82 1.793V9.85L2.965 8.057V6.57L9.987 8.89V7.17L3.966 5.377V3.89l5.018 1.545V3.59L2.965 1.8V.313l12.871 3.971v12.812z"/></svg>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size:.88rem">PlayStation</div>
                        @if($psn)
                        <div class="text-secondary" style="font-size:.78rem">{{ $psn->username }}</div>
                        @else
                        <div class="text-secondary mb-2" style="font-size:.78rem">Not connected</div>
                        <form action="{{ route('connected-accounts.store') }}" method="POST" class="d-flex gap-2">
                            @csrf
                            <input type="hidden" name="provider" value="psn">
                            <input type="text" name="username" placeholder="PSN Online ID"
                                   class="form-control form-control-sm @error('username') is-invalid @enderror"
                                   style="max-width:200px;font-size:.8rem">
                            <button type="submit" class="btn btn-sm fw-bold text-uppercase text-white flex-shrink-0"
                                    style="background:#00439c;font-size:.72rem">Connect</button>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </form>
                        @endif
                    </div>
                    @if($psn)
                    <form action="{{ route('connected-accounts.destroy', $psn) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                                style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.72rem">
                            Disconnect
                        </button>
                    </form>
                    @endif
                </div>

            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
function previewAvatar(input) {
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
</script>
@endpush