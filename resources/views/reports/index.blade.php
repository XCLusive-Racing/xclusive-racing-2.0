@extends('layouts.app')

@section('title', 'Incident Reports - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:900px;position:relative;z-index:1">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">Incident Reports</h1>
                <p class="text-secondary mb-0">Submit and track your incident reports</p>
            </div>
        </div>

        @if(session('success'))
        <div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">
            {{ session('success') }}
        </div>
        @endif

        @guest
        <div class="bg-white rounded-3 shadow-sm p-5 text-center">
            <div class="text-secondary mb-3" style="font-size:.9rem">Sign in to submit and view your incident reports.</div>
            <a href="{{ route('login') }}" class="btn fw-bold text-white px-4" style="background:#7c3aed">Sign In</a>
        </div>
        @else

        <div class="row g-4">

            {{-- Submit form --}}
            <div class="col-lg-5">
                <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-bottom" style="background:#fafafa">
                        <span class="fw-black text-uppercase" style="font-size:.78rem;letter-spacing:.06em">Submit Report</span>
                    </div>
                    <div class="px-4 pt-4">
                        <div class="d-flex gap-2 p-3 rounded-3" style="background:#eff6ff;border:1px solid #bfdbfe">
                            <i class="fa-solid fa-circle-info mt-1" style="color:#2563eb;font-size:.85rem"></i>
                            <div style="font-size:.78rem;line-height:1.5;color:#1e40af">
                                <strong>Reports open once results are final.</strong> A race only appears below after it has
                                finished and its results &amp; ratings have been fully processed &mdash; this can take a
                                little while after the chequered flag. Check back shortly if your race isn&rsquo;t listed yet.
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('reports.store') }}" class="p-4">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.8rem">Race <span class="text-danger">*</span></label>

                            @if($races->isEmpty())
                            <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:.8rem">
                                You have no completed races to report from yet. Races appear here once they've finished
                                and results &amp; ratings have been processed.
                            </div>
                            @else
                            <select name="race_id" id="race-select" required
                                    class="form-select form-select-sm @error('race_id') is-invalid @enderror">
                                <option value="">— Select a race —</option>
                                @foreach($races as $race)
                                <option value="{{ $race->id }}" {{ (string) old('race_id') === (string) $race->id ? 'selected' : '' }}>
                                    {{ $race->eventFormat->name ?? $race->gameLabel() }} — {{ $race->track }} — {{ $race->scheduledAtUk()->format('D j M Y') }}
                                </option>
                                @endforeach
                            </select>
                            @error('race_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.8rem">Submitted against <span class="text-danger">*</span></label>
                            <select name="reported_user_id" id="participant-select" required disabled
                                    class="form-select form-select-sm @error('reported_user_id') is-invalid @enderror">
                                <option value="">Select a race first</option>
                            </select>
                            <p id="participant-loading" class="text-muted mb-0 mt-1" style="font-size:.72rem;display:none">Loading drivers…</p>
                            @error('reported_user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.8rem">Session <span class="text-danger">*</span></label>
                            <select name="session_type" required class="form-select form-select-sm @error('session_type') is-invalid @enderror">
                                <option value="R" {{ old('session_type') === 'R' ? 'selected' : '' }}>Race</option>
                                <option value="Q" {{ old('session_type') === 'Q' ? 'selected' : '' }}>Qualifying</option>
                                <option value="P" {{ old('session_type') === 'P' ? 'selected' : '' }}>Practice</option>
                            </select>
                            <div class="form-text" style="font-size:.72rem">The session in which the incident occurred</div>
                            @error('session_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size:.8rem">Lap number</label>
                                <input type="number" name="lap_number" value="{{ old('lap_number') }}"
                                       class="form-control form-control-sm @error('lap_number') is-invalid @enderror"
                                       placeholder="e.g. 5" min="1" max="999">
                                @error('lap_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size:.8rem">Corner</label>
                                <input type="text" name="incident_corner" value="{{ old('incident_corner') }}"
                                       class="form-control form-control-sm @error('incident_corner') is-invalid @enderror"
                                       placeholder="e.g. T1, Raidillon">
                                @error('incident_corner')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.8rem">Description <span class="text-danger">*</span></label>
                            <textarea name="description" rows="4"
                                      class="form-control form-control-sm @error('description') is-invalid @enderror"
                                      placeholder="Describe what happened in detail (min. 20 characters)...">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.8rem">Clip Link <span class="text-danger">*</span></label>
                            <input type="url" name="video_url" value="{{ old('video_url') }}"
                                   class="form-control form-control-sm @error('video_url') is-invalid @enderror"
                                   placeholder="YouTube or Twitch clip URL (required)">
                            @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:.72rem">A clip is required for all incident reports. Reports without a valid clip link will not be reviewed.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.8rem">Clip 2 <span class="text-secondary fw-normal">(optional)</span></label>
                            <input type="url" name="clip_bad_driver_url" value="{{ old('clip_bad_driver_url') }}"
                                   class="form-control form-control-sm @error('clip_bad_driver_url') is-invalid @enderror"
                                   placeholder="https://youtube.com/...">
                            @error('clip_bad_driver_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:.72rem">Footage from the Accused driver&rsquo;s point of view.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold" style="font-size:.8rem">Clip 3 <span class="text-secondary fw-normal">(optional)</span></label>
                            <input type="url" name="clip_heli_url" value="{{ old('clip_heli_url') }}"
                                   class="form-control form-control-sm @error('clip_heli_url') is-invalid @enderror"
                                   placeholder="https://youtube.com/...">
                            @error('clip_heli_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:.72rem">Heli / overview footage of the incident.</div>
                        </div>

                        <button type="submit" class="btn fw-bold text-white w-100" style="background:#7c3aed;font-size:.85rem"
                                {{ $races->isEmpty() ? 'disabled' : '' }}>
                            Submit Report
                        </button>
                    </form>
                </div>
            </div>

            {{-- My reports --}}
            <div class="col-lg-7">
                <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                    <div class="d-flex border-bottom" style="background:#fafafa">
                        <button type="button" class="report-tab-btn active" data-tab-target="made">
                            My Reports
                            <span class="badge rounded-pill ms-1" style="background:#ede9fe;color:#5b21b6">{{ $reportsMade->count() }}</span>
                        </button>
                        <button type="button" class="report-tab-btn" data-tab-target="against">
                            Reports Against
                            <span class="badge rounded-pill ms-1" style="background:#ede9fe;color:#5b21b6">{{ $reportsAgainst->count() }}</span>
                        </button>
                    </div>

                    {{-- Reports I Made --}}
                    <div id="tab-made" class="report-tab-panel">
                        @if($reportsMade->isEmpty())
                        <div class="text-center py-5 text-secondary" style="font-size:.85rem">
                            You haven't submitted any reports yet.
                        </div>
                        @else
                        <div>
                            @foreach($reportsMade as $report)
                            @php $meta = $report->statusMeta(); @endphp
                            <div class="px-4 py-3 border-bottom">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                    <span class="fw-bold text-dark" style="font-size:.88rem">vs {{ $report->reported_driver_name }}</span>
                                    <span class="badge fw-bold text-white" style="background:{{ $meta['color'] }};font-size:.68rem">
                                        {{ $meta['label'] }}
                                    </span>
                                </div>
                                @if($report->race)
                                <div class="text-secondary mb-1" style="font-size:.75rem">{{ $report->race->title }}</div>
                                @endif
                                <div class="text-secondary" style="font-size:.75rem">
                                    {{ $report->created_at->format('d M Y') }}
                                    @if($report->session_type) &middot; {{ $report->sessionLabel() }} @endif
                                    @if($report->lap_number) &middot; Lap {{ $report->lap_number }} @endif
                                    @if($report->incident_corner) &middot; {{ $report->incident_corner }} @endif
                                </div>
                                @if($report->admin_notes)
                                <div class="mt-2 p-2 rounded-2" style="background:#f9fafb;font-size:.78rem;color:#374151;border:1px solid #f3f4f6">
                                    <span class="fw-bold text-uppercase" style="font-size:.65rem;letter-spacing:.05em;color:#9ca3af">Steward note: </span>
                                    {{ $report->admin_notes }}
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    {{-- Reports Against Me --}}
                    <div id="tab-against" class="report-tab-panel" style="display:none">
                        @if($reportsAgainst->isEmpty())
                        <div class="text-center py-5 text-secondary" style="font-size:.85rem">
                            No incident reports have been filed against you.
                        </div>
                        @else
                        <div>
                            @foreach($reportsAgainst as $report)
                            @php
                                $meta = $report->statusMeta();
                                $decided = in_array($report->status, ['resolved', 'dismissed']);
                                $reporterName = $decided ? ($report->user->displayName() ?? 'Unknown') : 'Anonymous';
                            @endphp
                            <div class="px-4 py-3 border-bottom">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                    <span class="fw-bold text-dark" style="font-size:.88rem">{{ $report->race?->title ?? 'Incident Report' }}</span>
                                    <span class="badge fw-bold text-white" style="background:{{ $meta['color'] }};font-size:.68rem">
                                        {{ $meta['label'] }}
                                    </span>
                                </div>
                                <div class="text-secondary mb-1" style="font-size:.75rem">
                                    Reported by: {{ $reporterName }}
                                    @if($report->session_type) &middot; {{ $report->sessionLabel() }} @endif
                                </div>
                                <div class="text-secondary" style="font-size:.75rem">
                                    {{ ($report->race?->scheduledAtUk() ?? $report->created_at)->format('d M Y') }}
                                </div>
                                @if($report->status === 'resolved' && $report->final_penalty)
                                <div class="mt-2 p-2 rounded-2" style="background:#f9fafb;font-size:.78rem;color:#374151;border:1px solid #f3f4f6">
                                    Final penalty: <strong>{{ $report->final_penalty }}</strong>
                                </div>
                                @elseif($report->status === 'dismissed')
                                <div class="mt-2 p-2 rounded-2" style="background:#f9fafb;font-size:.78rem;color:#6b7280;border:1px solid #f3f4f6">
                                    Dismissed
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
        @endguest

    </div>
</main>

<style>
    .report-tab-btn {
        flex: 1;
        border: none;
        background: transparent;
        padding: 14px 12px;
        font-weight: 800;
        text-transform: uppercase;
        font-size: .72rem;
        letter-spacing: .06em;
        color: #9ca3af;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        transition: color .15s, border-color .15s;
    }
    .report-tab-btn:hover { color: #5b21b6; }
    .report-tab-btn.active { color: #5b21b6; border-bottom-color: #7c3aed; }
</style>

@push('scripts')
<script>
(function () {
    const raceSelect = document.getElementById('race-select');
    const participantSelect = document.getElementById('participant-select');
    const loadingEl = document.getElementById('participant-loading');

    function renderParticipants(drivers, preselect) {
        if (!drivers.length) {
            participantSelect.innerHTML = '<option value="">No other participants found</option>';
            participantSelect.disabled = true;
            return;
        }
        participantSelect.innerHTML = '<option value="">— Select a driver —</option>' +
            drivers.map(function (d) {
                const selected = preselect && String(preselect) === String(d.id) ? ' selected' : '';
                return '<option value="' + d.id + '"' + selected + '>' + d.name + '</option>';
            }).join('');
        participantSelect.disabled = false;
    }

    function loadParticipants(raceId, preselect) {
        if (!raceId) {
            participantSelect.innerHTML = '<option value="">Select a race first</option>';
            participantSelect.disabled = true;
            return;
        }

        participantSelect.innerHTML = '<option value="">Loading…</option>';
        participantSelect.disabled = true;
        loadingEl.style.display = 'block';

        fetch('/api/race/' + raceId + '/participants', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (drivers) {
                loadingEl.style.display = 'none';
                renderParticipants(drivers, preselect);
            })
            .catch(function () {
                loadingEl.style.display = 'none';
                participantSelect.innerHTML = '<option value="">Failed to load drivers</option>';
            });
    }

    if (raceSelect && participantSelect) {
        raceSelect.addEventListener('change', function () {
            loadParticipants(this.value, null);
        });

        @if(old('race_id'))
        loadParticipants(@json(old('race_id')), @json(old('reported_user_id')));
        @endif
    }

    document.querySelectorAll('.report-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.report-tab-btn').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.report-tab-panel').forEach(function (p) { p.style.display = 'none'; });
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tabTarget).style.display = 'block';
        });
    });
})();
</script>
@endpush
@endsection
