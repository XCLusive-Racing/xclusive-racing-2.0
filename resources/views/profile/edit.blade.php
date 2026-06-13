@extends('layouts.app')

@section('title', 'Edit Profile - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png');"></div>
    <div class="container" style="max-width:1100px;position:relative;z-index:1">

        {{-- Header --}}
        <div class="d-flex align-items-center gap-3 py-4 mb-2">
            <a href="{{ route('profile') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
                ← Back
            </a>
            <h1 class="fs-4 fw-black text-uppercase fst-italic text-dark mb-0">Edit Profile</h1>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="row g-4">

                {{-- LEFT COLUMN --}}
                <div class="col-12 col-lg-6 d-flex flex-column gap-4">

                    {{-- Identity --}}
                    <div class="bg-white rounded-3 shadow-sm p-4">
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

                    {{-- Avatar --}}
                    <div class="bg-white rounded-3 shadow-sm p-4">
                        <h2 class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:1rem">Avatar</h2>
                        <p class="text-secondary mb-3" style="font-size:.82rem">JPEG, PNG, WebP — max 4 MB</p>
                        <div class="d-flex align-items-center gap-3">
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
                    <div class="bg-white rounded-3 shadow-sm p-4">
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

                </div>

                {{-- RIGHT COLUMN --}}
                <div class="col-12 col-lg-6 d-flex flex-column gap-4">

                    {{-- Password --}}
                    <div class="bg-white rounded-3 shadow-sm p-4">
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

                    {{-- Connected Accounts --}}
                    <div class="bg-white rounded-3 shadow-sm p-4 flex-grow-1">
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
                                     style="width:40px;height:40px;background:#5865F2;color:#fff;font-size:1.2rem">
                                    <i class="fa-brands fa-discord"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" style="font-size:.88rem">Discord</div>
                                    <div class="text-secondary" style="font-size:.78rem">{{ $discord?->username ?? 'Not connected' }}</div>
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
                                     style="width:40px;height:40px;background:#1b2838;color:#c7d5e0;font-size:1.2rem">
                                    <i class="fa-brands fa-steam"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" style="font-size:.88rem">Steam</div>
                                    <div class="text-secondary" style="font-size:.78rem">{{ $steam?->username ?? 'Not connected' }}</div>
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
                            <div class="d-flex align-items-start gap-3 p-3 rounded-2" style="border:1px solid #e5e7eb">
                                <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                                     style="width:40px;height:40px;background:#107c10;color:#fff;font-size:1.2rem">
                                    <i class="fa-brands fa-xbox"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" style="font-size:.88rem">Xbox</div>
                                    @if($xbox)
                                    <div class="text-secondary" style="font-size:.78rem">{{ $xbox->username }}</div>
                                    @else
                                    <div class="text-secondary mb-2" style="font-size:.78rem">Not connected</div>
                                    <form action="{{ route('connected-accounts.store') }}" method="POST" class="d-flex gap-2 flex-wrap">
                                        @csrf
                                        <input type="hidden" name="provider" value="xbox">
                                        <input type="text" name="username" placeholder="Gamertag"
                                               class="form-control form-control-sm @error('xbox_username') is-invalid @enderror"
                                               style="font-size:.8rem">
                                        <button type="submit" class="btn btn-sm fw-bold text-uppercase text-white flex-shrink-0"
                                                style="background:#107c10;font-size:.72rem">Connect</button>
                                        @error('xbox_username')<div class="invalid-feedback w-100">{{ $message }}</div>@enderror
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
                                     style="width:40px;height:40px;background:#00439c;color:#fff;font-size:1.2rem">
                                    <i class="fa-brands fa-playstation"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" style="font-size:.88rem">PlayStation</div>
                                    @if($psn)
                                    <div class="text-secondary" style="font-size:.78rem">{{ $psn->username }}</div>
                                    @else
                                    <div class="text-secondary mb-2" style="font-size:.78rem">Not connected</div>
                                    <form action="{{ route('connected-accounts.store') }}" method="POST" class="d-flex gap-2 flex-wrap">
                                        @csrf
                                        <input type="hidden" name="provider" value="psn">
                                        <input type="text" name="username" placeholder="PSN Online ID"
                                               class="form-control form-control-sm @error('psn_username') is-invalid @enderror"
                                               style="font-size:.8rem">
                                        <button type="submit" class="btn btn-sm fw-bold text-uppercase text-white flex-shrink-0"
                                                style="background:#00439c;font-size:.72rem">Connect</button>
                                        @error('psn_username')<div class="invalid-feedback w-100">{{ $message }}</div>@enderror
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
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4 py-2"
                        style="background:#7c3aed">Save Changes</button>
                <a href="{{ route('profile') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4 py-2">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</main>
@endsection

