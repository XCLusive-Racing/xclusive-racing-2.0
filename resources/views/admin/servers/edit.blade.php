@extends('layouts.admin')

@section('title', 'Edit — ' . $server->name)
@section('page-title', 'Edit FTP Server')

@section('page-actions')
    <a href="{{ route('admin.servers.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<div class="row g-4 align-items-start">
    <div class="col-12 col-lg-7">

        {{-- Push Config to Server --}}
        <div class="admin-card mb-4">
            <div class="px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-black text-uppercase fst-italic text-dark mb-1" style="font-size:.82rem">Push Config to Server</div>
                    <p class="text-secondary mb-0" style="font-size:.75rem">
                        Pushes settings.json, eventrules.json and assistrules.json straight to this server. To review or edit first, use the file editors below.
                    </p>
                </div>
                <form action="{{ route('admin.servers.push', $server) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed;font-size:.78rem;white-space:nowrap">
                        Push Config →
                    </button>
                </form>
            </div>
        </div>

        <form action="{{ route('admin.servers.update', $server) }}" method="POST">
            @csrf @method('PUT')

            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-2">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Server Info</p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-9">
                            <label class="form-label">Server Name</label>
                            <input type="text" name="name" value="{{ old('name', $server->name) }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Server No. <span class="fw-normal text-secondary" style="text-transform:none">(gPortal)</span></label>
                            <input type="number" name="server_number" value="{{ old('server_number', $server->server_number) }}"
                                   min="1" max="9" placeholder="1–4"
                                   class="form-control @error('server_number') is-invalid @enderror">
                            @error('server_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Connection</p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-9">
                            <label class="form-label">IP Address</label>
                            <input type="text" name="host" value="{{ old('host', $server->host) }}"
                                   class="form-control @error('host') is-invalid @enderror"
                                   style="font-family:monospace">
                            @error('host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" value="{{ old('port', $server->port) }}"
                                   class="form-control @error('port') is-invalid @enderror">
                            @error('port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">FTP Username</label>
                            <input type="text" name="username" value="{{ old('username', $server->username) }}"
                                   class="form-control @error('username') is-invalid @enderror"
                                   autocomplete="off">
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">FTP Password</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   autocomplete="new-password"
                                   placeholder="Leave blank to keep current">
                            <div class="form-text" style="font-size:.72rem;color:#9ca3af">Leave blank to keep the existing password.</div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Configuration</p>

                    <div class="mb-3">
                        <label class="form-label">Results Path</label>
                        <input type="text" name="path" value="{{ old('path', $server->path) }}"
                               class="form-control @error('path') is-invalid @enderror"
                               style="font-family:monospace">
                        <div class="form-text" style="font-size:.72rem;color:#9ca3af">Directory where JSON result files are saved.</div>
                        @error('path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Config Path</label>
                        <input type="text" name="cfg_path" value="{{ old('cfg_path', $server->cfg_path) }}"
                               class="form-control @error('cfg_path') is-invalid @enderror"
                               style="font-family:monospace">
                        <div class="form-text" style="font-size:.72rem;color:#9ca3af">Directory where ACC config files are pushed (entrylist.json, configuration.json, settings.json).</div>
                        @error('cfg_path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                               {{ old('active', $server->active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark" for="active" style="font-size:.82rem">
                            Server is active
                        </label>
                        <div class="form-text" style="font-size:.72rem;color:#9ca3af">Inactive servers are hidden from the import selector.</div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Reset Schedule</p>

                    <div class="mb-3">
                        <label class="form-label">Server Type</label>
                        <select name="server_type" id="srv-type" class="form-select @error('server_type') is-invalid @enderror">
                            <option value="rolling" {{ old('server_type', $server->server_type) === 'rolling' ? 'selected' : '' }}>Rolling resets (SERVER 1 / 2 / 3)</option>
                            <option value="scheduled" {{ old('server_type', $server->server_type) === 'scheduled' ? 'selected' : '' }}>Manual restart (SERVER 4)</option>
                        </select>
                        @error('server_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div id="rolling-fields">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">First Reset Hour (UTC)</label>
                                <select name="reset_start_hour" class="form-select @error('reset_start_hour') is-invalid @enderror">
                                    @for($h = 0; $h < 24; $h++)
                                        <option value="{{ $h }}" {{ old('reset_start_hour', $server->reset_start_hour) == $h ? 'selected' : '' }}>
                                            {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00
                                        </option>
                                    @endfor
                                </select>
                                @error('reset_start_hour') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Reset Interval (minutes)</label>
                                <input type="number" name="reset_interval_minutes"
                                       value="{{ old('reset_interval_minutes', $server->reset_interval_minutes) }}"
                                       class="form-control @error('reset_interval_minutes') is-invalid @enderror"
                                       min="30" max="1440">
                                @error('reset_interval_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Config Defaults</p>
                    <p class="text-secondary mb-3" style="font-size:.75rem">
                        JSON defaults pushed to gPortal. Leave a file blank to use the built-in default.
                        <strong>track</strong> and session durations always come from the race/format — never from here.
                        <strong>serverName</strong>, <strong>password</strong>, <strong>maxCarSlots</strong>, <strong>safetyRatingRequirement</strong>, <strong>racecraftRatingRequirement</strong> and <strong>carGroup</strong> are always computed automatically.
                    </p>

                    @php
                        $service = app(\App\Services\AccServerConfigService::class);
                        $configFields = [
                            'event.json'       => ['field' => 'event_defaults',      'placeholder' => json_encode(['preRaceWaitingTimeSeconds' => 60, 'sessionOverTimeSeconds' => 120, 'ambientTemp' => 22, 'cloudLevel' => 0.1, 'rain' => 0, 'weatherRandomness' => 1], JSON_PRETTY_PRINT)],
                            'settings.json'    => ['field' => 'settings_defaults',   'placeholder' => json_encode($service->defaultSettings(), JSON_PRETTY_PRINT)],
                            'eventrules.json'  => ['field' => 'eventrules_defaults', 'placeholder' => json_encode($service->defaultEventRules(), JSON_PRETTY_PRINT)],
                            'assistrules.json' => ['field' => 'assistrules_defaults','placeholder' => json_encode($service->defaultAssistRules(), JSON_PRETTY_PRINT)],
                        ];
                    @endphp

                    <div data-accordions>
                        @foreach($configFields as $filename => $meta)
                        @php
                            $field       = $meta['field'];
                            $hasOverride = !empty($server->{$field});
                            $isFirst     = $loop->first;
                        @endphp
                        <div data-accordion="{{ $isFirst ? 'open' : 'closed' }}" style="border:1px solid #f3f4f6;border-radius:8px;margin-bottom:.6rem;overflow:hidden">
                            <div class="d-flex align-items-center justify-content-between px-3 py-2"
                                 data-accordion-header
                                 style="cursor:pointer;background:{{ $hasOverride ? '#fffbeb' : '#f9fafb' }}">
                                <div class="d-flex align-items-center gap-2">
                                    <svg data-accordion-arrow style="transition:transform .15s;flex-shrink:0;transform:{{ $isFirst ? 'rotate(90deg)' : '' }}" width="12" height="12" viewBox="0 0 20 20" fill="currentColor" class="text-secondary"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                    <span class="fw-black text-dark" style="font-family:monospace;font-size:.82rem">{{ $filename }}</span>
                                    @if($hasOverride)
                                        <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">custom</span>
                                    @else
                                        <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">built-in default</span>
                                    @endif
                                </div>
                            </div>
                            <div data-accordion-body style="{{ $isFirst ? '' : 'display:none' }}">
                                <textarea name="{{ $field }}" rows="16" spellcheck="false"
                                          class="@error($field) is-invalid @enderror"
                                          style="width:100%;font-family:monospace;font-size:.8rem;line-height:1.5;padding:1rem 1.25rem;border:none;border-top:1px solid #e5e7eb;background:{{ $hasOverride ? '#fffdf5' : 'white' }};resize:vertical;outline:none;display:block">{{ old($field, json_encode($server->{$field} ?? json_decode($meta['placeholder'], true), JSON_PRETTY_PRINT)) }}</textarea>
                                @error($field)<div class="invalid-feedback px-3">{{ $message }}</div>@enderror
                                <div class="px-3 py-2" style="font-size:.72rem;color:#9ca3af;background:#f9fafb;border-top:1px solid #f3f4f6">
                                    Prefilled with the built-in default. Edit and save to override it — or leave it as-is and just save to adopt these as your server default.
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Save Changes
                </button>
                <a href="{{ route('admin.servers.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@push('scripts')
<script>
(function () {
    const typeEl  = document.getElementById('srv-type');
    const rolling = document.getElementById('rolling-fields');
    if (!typeEl || !rolling) return;
    function toggle() { rolling.style.display = typeEl.value === 'rolling' ? '' : 'none'; }
    typeEl.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush

@endsection
