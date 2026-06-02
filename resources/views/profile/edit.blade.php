@extends('layouts.app')

@section('title', 'Edit Profile - XCLusive Racing')

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
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
                            <img id="avatar-preview" src="{{ asset($user->banner) }}" alt=""
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
                    <div class="col-sm-8">
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