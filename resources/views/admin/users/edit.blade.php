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
            <div class="text-secondary" style="font-size:.8rem">Member since {{ $user->created_at->format('d M Y') }}</div>
        </div>
    </div>
</div>

<form action="{{ route('admin.users.update', $user) }}" method="POST">
    @csrf @method('PUT')

    {{-- Identity --}}
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">Identity</div>
        </div>
        <div class="p-4">
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="form-control @error('name') is-invalid @enderror" style="border-color:#e5e7eb" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="form-control @error('email') is-invalid @enderror" style="border-color:#e5e7eb" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Roles</label>
                    @if($user->id === auth()->id())
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @foreach($user->roles->sortBy('id') as $role)
                                <span class="badge fw-bold" style="background:#f3f4f6;color:#374151;font-size:.78rem;padding:4px 10px;border-radius:6px">{{ $role->name }}</span>
                            @endforeach
                        </div>
                        <p class="text-secondary mt-1 mb-0" style="font-size:.75rem">You cannot change your own roles.</p>
                    @else
                        @php $userRoles = old('roles', $user->roles->pluck('slug')->all()); @endphp

                        {{-- Desktop: checkboxes --}}
                        <div class="d-none d-md-flex flex-wrap gap-3 mt-1">
                            @foreach($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]"
                                       value="{{ $role->slug }}" id="role_{{ $role->slug }}"
                                       {{ in_array($role->slug, $userRoles) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="role_{{ $role->slug }}" style="font-size:.85rem">
                                    {{ $role->name }}
                                </label>
                            </div>
                            @endforeach
                        </div>

                        {{-- Mobile: read-only badges + hidden inputs to preserve roles on submit --}}
                        <div class="d-md-none mt-1">
                            <div class="d-flex flex-wrap gap-1">
                                @forelse($user->roles->sortBy('id') as $role)
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
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Country</label>
                    <input type="text" name="country" value="{{ old('country',$user->country) }}"
                           class="form-control" style="border-color:#e5e7eb" placeholder="Netherlands">
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Team</label>
                    <input type="text" name="team" value="{{ old('team',$user->team) }}"
                           class="form-control" style="border-color:#e5e7eb">
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Banner URL</label>
                    <input type="text" name="banner" value="{{ old('banner',$user->banner) }}"
                           class="form-control" style="border-color:#e5e7eb" placeholder="https://... or images/avatars/...">
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Display Name</label>
                    <select name="display_name_preference" class="form-select" style="border-color:#e5e7eb">
                        <option value="gamertag" {{ old('display_name_preference',$user->display_name_preference??'gamertag')==='gamertag'?'selected':'' }}>Gamertag (platform ID)</option>
                        <option value="name"     {{ old('display_name_preference',$user->display_name_preference??'gamertag')==='name'    ?'selected':'' }}>Real name ({{ $user->name }})</option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-bold text-dark d-block" style="font-size:.82rem">Supporter</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="is_supporter" id="is_supporter" value="1"
                               {{ old('is_supporter', $user->is_supporter) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-secondary" for="is_supporter" style="font-size:.82rem">
                            Supporter badge
                        </label>
                    </div>
                </div>
                <div class="col-sm-6" x-data="{ suspended: {{ old('is_suspended', $user->is_suspended) ? 'true' : 'false' }} }">
                    <label class="form-label fw-bold text-dark d-block" style="font-size:.82rem">
                        Suspended
                        @if($user->is_suspended)
                            @if(!$user->suspended_until)
                                <span class="badge ms-1" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.7rem">Indefinite</span>
                            @elseif($user->suspended_until->isFuture())
                                <span class="badge ms-1" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;font-size:.7rem">Until {{ $user->suspended_until->format('d M Y H:i') }}</span>
                            @else
                                <span class="badge ms-1" style="background:#f9fafb;color:#6b7280;border:1px solid #e5e7eb;font-size:.7rem">Expired {{ $user->suspended_until->format('d M Y') }}</span>
                            @endif
                        @endif
                    </label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="is_suspended" id="is_suspended" value="1"
                               x-model="suspended"
                               {{ old('is_suspended', $user->is_suspended) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-secondary" for="is_suspended" style="font-size:.82rem">
                            Block from registrations
                        </label>
                    </div>
                    <div x-show="suspended" x-transition style="display:none" class="mt-2 d-flex flex-column gap-2">
                        <div>
                            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Suspended until <span class="fw-normal text-secondary">(leave empty for indefinite)</span></label>
                            <input type="datetime-local" name="suspended_until"
                                   value="{{ old('suspended_until', $user->suspended_until?->format('Y-m-d\TH:i')) }}"
                                   class="form-control form-control-sm @error('suspended_until') is-invalid @enderror"
                                   style="border-color:#fca5a5">
                            @error('suspended_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Reason <span class="fw-normal text-secondary">(admins only)</span></label>
                            <textarea name="suspension_reason" rows="2"
                                      class="form-control form-control-sm @error('suspension_reason') is-invalid @enderror"
                                      style="border-color:#fca5a5;font-size:.82rem"
                                      placeholder="e.g. Unsportsmanlike conduct in Round 4">{{ old('suspension_reason', $user->suspension_reason) }}</textarea>
                            @error('suspension_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Driver info --}}
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">Driver Info</div>
        </div>
        <div class="p-4">
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Racing Number</label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold" style="border-color:#e5e7eb;background:#f9fafb">#</span>
                        <input type="number" name="car_number" value="{{ old('car_number',$user->car_number) }}"
                               class="form-control" style="border-color:#e5e7eb" min="1" max="9999" placeholder="69">
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Car Model</label>
                    <input type="text" name="car_model" value="{{ old('car_model',$user->car_model) }}"
                           class="form-control" style="border-color:#e5e7eb" placeholder="Lamborghini Huracán GT3">
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Primary Game</label>
                    <select name="game" class="form-select" style="border-color:#e5e7eb">
                        <option value="">— None —</option>
                        @foreach(['acc'=>'ACC Console','lmu'=>'Le Mans Ultimate','iracing'=>'iRacing'] as $val=>$label)
                        <option value="{{ $val }}" {{ old('game',$user->game)===$val?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Platform --}}
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">Platform</div>
        </div>
        <div class="p-4">
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">Platform</label>
                    <select name="platform" class="form-select" style="border-color:#e5e7eb">
                        <option value="">— None —</option>
                        @foreach(['steam'=>'Steam / PC','ps5'=>'PlayStation 5','xbox'=>'Xbox Series X/S'] as $val=>$label)
                        <option value="{{ $val }}" {{ old('platform',$user->platform)===$val?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-8">
                    <label class="form-label fw-bold text-dark" style="font-size:.82rem">
                        XUID / PSID / Steam ID
                        <span class="text-secondary fw-normal">(gPortal player ID voor resultaten koppeling)</span>
                    </label>
                    <input type="text" name="platform_id" value="{{ old('platform_id',$user->platform_id) }}"
                           class="form-control" style="border-color:#e5e7eb;font-family:monospace"
                           placeholder="M12345 / P67890 / 76561198...">
                </div>
            </div>
        </div>
    </div>

    {{-- ELO --}}
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:1rem">ELO Ratings</div>
            <span class="text-secondary" style="font-size:.78rem">Manual override</span>
        </div>
        <div class="p-4">
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label fw-bold" style="font-size:.82rem;color:#7c3aed">ACC Console</label>
                    <input type="number" name="elo_acc" value="{{ old('elo_acc',$user->elo_acc) }}"
                           class="form-control" style="border-color:#e5e7eb" min="0" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-bold" style="font-size:.82rem;color:#db2777">Le Mans Ultimate</label>
                    <input type="number" name="elo_lmu" value="{{ old('elo_lmu',$user->elo_lmu) }}"
                           class="form-control" style="border-color:#e5e7eb" min="0" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-bold" style="font-size:.82rem;color:#2563eb">iRacing</label>
                    <input type="number" name="elo_iracing" value="{{ old('elo_iracing',$user->elo_iracing) }}"
                           class="form-control" style="border-color:#e5e7eb" min="0" required>
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

</div>
</div>
@endsection
