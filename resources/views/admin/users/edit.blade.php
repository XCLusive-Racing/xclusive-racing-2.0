@extends('layouts.admin')

@section('title', 'Edit User — ' . $user->name)
@section('page-title', 'Edit User')


@section('page-actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-8">

{{-- User strip --}}
<div class="admin-card mb-4 p-0 overflow-hidden">
    <div class="d-flex align-items-center gap-3 p-4">
        @if($user->banner)
            <img src="{{ $user->avatarUrl() }}" alt="" class="rounded-circle flex-shrink-0" style="width:48px;height:48px;object-fit:cover">
        @else
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0"
                 style="width:48px;height:48px;font-size:1.1rem;background:linear-gradient(135deg,#7c3aed,#db2777)">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
        @endif
        <div>
            <div class="fw-black text-dark" style="font-size:1rem">{{ $user->name }}</div>
            <div class="text-secondary" style="font-size:.8rem">Member since {{ $user->created_at->timezone('Europe/London')->format('d M Y') }}</div>
        </div>
    </div>
</div>

<form action="{{ route('admin.users.update', $user) }}" method="POST">
    @csrf @method('PUT')

    <div class="admin-form-card mb-4">

        {{-- Identity --}}
        <div class="px-4 pt-4 pb-3">
            <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Identity</p>

            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Roles</label>
                    @if($user->id === auth()->id())
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @foreach($user->roles->sortBy('sort_order') as $role)
                                <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.78rem;padding:4px 10px;border-radius:6px">{{ $role->name }}</span>
                            @endforeach
                        </div>
                        <p class="text-secondary mt-1 mb-0" style="font-size:.75rem">You cannot change your own roles.</p>
                    @else
                        @php
                            $userRoles = old('roles', $user->roles->pluck('slug')->all());
                            $roleColors = ['owner' => ['#f3e8ff', '#7c3aed'], 'admin' => ['#fce7f3', '#db2777'], 'moderator' => ['#dbeafe', '#2563eb'], 'event_manager' => ['#fef3c7', '#d97706'], 'steward' => ['#e0f2fe', '#0891b2'], 'driver' => ['#d1fae5', '#059669'], 'broadcaster' => ['#ffe4e6', '#e11d48']];
                        @endphp

                        {{-- Desktop: toggle pills --}}
                        <div class="d-none d-md-flex flex-wrap gap-2 mt-1" data-role-pills>
                            @foreach($roles as $role)
                            @php $rc = $roleColors[$role->slug] ?? ['#f3f4f6', '#374151']; @endphp
                            <label data-role-pill
                                   class="d-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-bold"
                                   style="cursor:pointer;user-select:none;font-size:.85rem;transition:all .15s;{{ in_array($role->slug, $userRoles) ? 'border:2px solid '.$rc[1].';background:'.$rc[1].'18;color:'.$rc[1] : 'border:2px solid #e5e7eb;background:#fff;color:#374151' }}">
                                <input type="checkbox" name="roles[]" value="{{ $role->slug }}"
                                       data-role-color="{{ $rc[1] }}" class="d-none"
                                       {{ in_array($role->slug, $userRoles) ? 'checked' : '' }}>
                                {{ $role->name }}
                            </label>
                            @endforeach
                        </div>

                        {{-- Mobile: read-only badges + hidden inputs to preserve roles on submit --}}
                        <div class="d-md-none mt-1">
                            <div class="d-flex flex-wrap gap-1">
                                @forelse($user->roles->sortBy('sort_order') as $role)
                                    <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.78rem;padding:4px 10px;border-radius:6px">{{ $role->name }}</span>
                                @empty
                                    <span class="text-secondary" style="font-size:.8rem">No roles assigned</span>
                                @endforelse
                            </div>
                            <p class="text-secondary mt-1 mb-0" style="font-size:.72rem">Role changes available on desktop.</p>
                            @foreach($userRoles as $roleSlug)
                                <input type="hidden" name="roles[]" value="{{ $roleSlug }}">
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" value="{{ old('country',$user->country) }}"
                           class="form-control" placeholder="Netherlands">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Team</label>
                    <input type="text" name="team" value="{{ old('team',$user->team) }}"
                           class="form-control">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Banner URL</label>
                    <input type="text" name="banner" value="{{ old('banner',$user->banner) }}"
                           class="form-control" placeholder="https://... or images/avatars/...">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Display Name</label>
                    <select name="display_name_preference" class="form-select">
                        <option value="gamertag" {{ old('display_name_preference',$user->display_name_preference??'gamertag')==='gamertag'?'selected':'' }}>Gamertag (platform ID)</option>
                        <option value="name"     {{ old('display_name_preference',$user->display_name_preference??'gamertag')==='name'    ?'selected':'' }}>Real name ({{ $user->name }})</option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label d-block">Supporter</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="is_supporter" id="is_supporter" value="1"
                               {{ old('is_supporter', $user->is_supporter) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-secondary" for="is_supporter" style="font-size:.82rem;text-transform:none;letter-spacing:normal">
                            Supporter badge
                        </label>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label d-block">
                        Suspended
                        @if($user->is_suspended)
                            @if(!$user->suspended_until)
                                <span class="badge ms-1" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.7rem;text-transform:none">Indefinite</span>
                            @elseif($user->suspended_until->isFuture())
                                <span class="badge ms-1" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;font-size:.7rem;text-transform:none">Until {{ $user->suspended_until->timezone('Europe/London')->format('d M Y H:i T') }}</span>
                            @else
                                <span class="badge ms-1" style="background:#f9fafb;color:#6b7280;border:1px solid #e5e7eb;font-size:.7rem;text-transform:none">Expired {{ $user->suspended_until->timezone('Europe/London')->format('d M Y') }}</span>
                            @endif
                        @endif
                    </label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="is_suspended" id="is_suspended" value="1"
                               data-controls="suspension-details"
                               {{ old('is_suspended', $user->is_suspended) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-secondary" for="is_suspended" style="font-size:.82rem;text-transform:none;letter-spacing:normal">
                            Block from registrations
                        </label>
                    </div>
                    <div id="suspension-details" class="mt-2 d-flex flex-column gap-2"
                         style="display:{{ old('is_suspended', $user->is_suspended) ? 'flex' : 'none' }} !important">
                        <div>
                            <label class="form-label mb-1" style="text-transform:none;letter-spacing:normal">Suspended until <span class="fw-normal text-secondary">(leave empty for indefinite)</span></label>
                            <input type="datetime-local" name="suspended_until"
                                   value="{{ old('suspended_until', $user->suspended_until?->timezone('Europe/London')->format('Y-m-d\TH:i')) }}"
                                   class="form-control form-control-sm @error('suspended_until') is-invalid @enderror">
                            @error('suspended_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label mb-1" style="text-transform:none;letter-spacing:normal">Reason <span class="fw-normal text-secondary">(admins only)</span></label>
                            <textarea name="suspension_reason" rows="2"
                                      class="form-control form-control-sm @error('suspension_reason') is-invalid @enderror"
                                      style="font-size:.82rem"
                                      placeholder="e.g. Unsportsmanlike conduct in Round 4">{{ old('suspension_reason', $user->suspension_reason) }}</textarea>
                            @error('suspension_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Driver info --}}
        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Driver Info</p>

            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label">Racing Number</label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold" style="background:#f9fafb">#</span>
                        <input type="number" name="car_number" value="{{ old('car_number',$user->car_number) }}"
                               class="form-control" min="1" max="9999" placeholder="69">
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Car Model</label>
                    <input type="text" name="car_model" value="{{ old('car_model',$user->car_model) }}"
                           class="form-control" placeholder="Lamborghini Huracán GT3">
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Primary Game</label>
                    <select name="game" class="form-select">
                        <option value="">— None —</option>
                        @foreach(['acc'=>'ACC Console','lmu'=>'Le Mans Ultimate','iracing'=>'iRacing'] as $val=>$label)
                        <option value="{{ $val }}" {{ old('game',$user->game)===$val?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Platform --}}
        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Platform</p>

            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label">Platform</label>
                    <select name="platform" class="form-select">
                        <option value="">— None —</option>
                        @foreach(['steam'=>'Steam / PC','ps5'=>'PlayStation 5','xbox'=>'Xbox Series X/S'] as $val=>$label)
                        <option value="{{ $val }}" {{ old('platform',$user->platform)===$val?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-8">
                    <label class="form-label">
                        XUID / PSID / Steam ID
                        <span class="fw-normal text-secondary" style="text-transform:none">(gPortal player ID voor resultaten koppeling)</span>
                    </label>
                    <input type="text" name="platform_id" value="{{ old('platform_id',$user->platform_id) }}"
                           class="form-control" style="font-family:monospace"
                           placeholder="M12345 / P67890 / 76561198...">
                </div>
            </div>
        </div>

        {{-- Ratings --}}
        <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <p class="fw-black text-uppercase fst-italic mb-0" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Ratings</p>
                <span class="text-secondary" style="font-size:.72rem">Manual override</span>
            </div>

            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label" style="color:#7c3aed">ACC Console — ELO</label>
                    <input type="number" name="elo_acc" value="{{ old('elo_acc',$user->elo_acc) }}"
                           class="form-control" min="0" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label" style="color:#db2777">Le Mans Ultimate — ELO</label>
                    <input type="number" name="elo_lmu" value="{{ old('elo_lmu',$user->elo_lmu) }}"
                           class="form-control" min="0" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label" style="color:#2563eb">iRacing — ELO</label>
                    <input type="number" name="elo_iracing" value="{{ old('elo_iracing',$user->elo_iracing) }}"
                           class="form-control" min="0" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label" style="color:#7c3aed">ACC Console — Safety Rating</label>
                    <input type="number" name="sr_acc" value="{{ old('sr_acc',$user->sr_acc) }}"
                           class="form-control" min="0" max="9.99" step="0.01" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label" style="color:#db2777">Le Mans Ultimate — Safety Rating</label>
                    <input type="number" name="sr_lmu" value="{{ old('sr_lmu',$user->sr_lmu) }}"
                           class="form-control" min="0" max="9.99" step="0.01" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label" style="color:#2563eb">iRacing — Safety Rating</label>
                    <input type="number" name="sr_iracing" value="{{ old('sr_iracing',$user->sr_iracing) }}"
                           class="form-control" min="0" max="9.99" step="0.01" required>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
            Save Changes
        </button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
            Cancel
        </a>
    </div>
</form>

<script>
(function () {
    document.querySelectorAll('[data-role-pill]').forEach(label => {
        const cb = label.querySelector('input[type=checkbox]');
        if (!cb) return;
        const color = cb.dataset.roleColor;

        function applyStyle() {
            if (cb.checked) {
                label.style.border     = '2px solid ' + color;
                label.style.background = color + '18';
                label.style.color      = color;
            } else {
                label.style.border     = '2px solid #e5e7eb';
                label.style.background = '#fff';
                label.style.color      = '#374151';
            }
        }

        cb.addEventListener('change', applyStyle);
    });
})();
</script>

</div>
</div>
@endsection