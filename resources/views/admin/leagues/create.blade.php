@extends('layouts.admin')

@section('title', 'Add League')
@section('page-title', 'Add League')

@section('page-actions')
    <a href="{{ route('admin.leagues.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<form action="{{ route('admin.leagues.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-7">

            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-2">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Identity</p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label">League Name</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug') }}"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   style="font-family:monospace" placeholder="nlrl">
                            <div class="form-text" style="font-size:.72rem;color:#9ca3af">Used in the league's public URL.</div>
                            @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft — not visible publicly</option>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Branding</p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Primary Colour</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" data-color-pick="primary_color_hex" class="form-control form-control-color" style="width:46px;padding:2px" value="{{ old('primary_color', '#7c3aed') }}">
                                <input type="text" name="primary_color" id="primary_color_hex" value="{{ old('primary_color', '#7c3aed') }}"
                                       class="form-control @error('primary_color') is-invalid @enderror" style="font-family:monospace">
                            </div>
                            @error('primary_color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Accent Colour</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" data-color-pick="accent_color_hex" class="form-control form-control-color" style="width:46px;padding:2px" value="{{ old('accent_color', '#db2777') }}">
                                <input type="text" name="accent_color" id="accent_color_hex" value="{{ old('accent_color', '#db2777') }}"
                                       class="form-control @error('accent_color') is-invalid @enderror" style="font-family:monospace">
                            </div>
                            @error('accent_color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" accept="image/*" class="form-control @error('logo') is-invalid @enderror">
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Banner</label>
                            <input type="file" name="banner" accept="image/*" class="form-control @error('banner') is-invalid @enderror">
                            @error('banner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Links</p>

                    <div class="mb-3">
                        <label class="form-label">Discord Invite URL</label>
                        <input type="url" name="discord_invite_url" value="{{ old('discord_invite_url') }}"
                               class="form-control @error('discord_invite_url') is-invalid @enderror"
                               placeholder="https://discord.gg/...">
                        @error('discord_invite_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Website URL</label>
                        <input type="url" name="website_url" value="{{ old('website_url') }}"
                               class="form-control @error('website_url') is-invalid @enderror"
                               placeholder="https://...">
                        @error('website_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Same locked grey pill as the edit page's copy of this exact
                         option (admin/leagues/edit.blade.php) and the championship
                         wizard's (_field.blade.php) -- not operational yet (no XCL
                         Discord bot installed), so a brand new league can't turn it on
                         any more than an existing one can. No existing value to
                         preserve at creation time, so the hidden input is always 0. --}}
                    <div>
                        <label class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-bold"
                               style="cursor:not-allowed;user-select:none;font-size:.82rem;border:2px solid #e5e7eb;background:#f3f4f6;color:#9ca3af">
                            <input type="checkbox" class="d-none" disabled>
                            Require Discord membership to register
                            <span class="fw-normal">(Off)</span>
                        </label>
                        <input type="hidden" name="requires_discord_membership" value="0">
                        <div class="form-text mt-1" style="font-size:.72rem;color:#9ca3af">
                            Temporarily locked — XCL is still finishing the operational Discord bot setup. Coming soon.
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Add League
                </button>
                <a href="{{ route('admin.leagues.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>

        </div>
    </div>
</form>

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
