@extends('layouts.admin')

@section('title', 'Report #' . $report->id)
@section('page-title', 'Report #' . $report->id)

@section('page-actions')
    <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">← Back</a>
@endsection

@section('content')

@php
    $meta        = $report->statusMeta();
    $currentUser = auth()->user();
    $canVerdict  = $currentUser->hasAnyRole(['owner', 'admin', 'steward']);
    $canProcess  = $currentUser->hasAnyRole(['owner', 'admin']);
    $agreement   = $report->agreementState();
    $bothSlotsFilled = $report->steward_1_id && $report->steward_2_id;

    // Build the list of verdict panels: slot 1, slot 2, then any additional stewards
    // who've submitted a verdict without holding a numbered slot, then — if both slots
    // are taken and the viewer hasn't submitted anything yet — one open panel for them.
    $panels = [];
    foreach ([1, 2] as $slot) {
        $steward = $report->{'steward' . $slot};
        $verdict = $steward ? $report->verdicts->firstWhere('steward_id', $steward->id) : null;
        $panels[] = ['slot' => $slot, 'steward' => $steward, 'verdict' => $verdict];
    }
    foreach ($report->verdicts as $v) {
        if ($v->steward_id !== $report->steward_1_id && $v->steward_id !== $report->steward_2_id) {
            $panels[] = ['slot' => null, 'steward' => $v->steward, 'verdict' => $v];
        }
    }
    $viewerHasPanel = collect($panels)->contains(fn($p) => $p['steward']?->id === $currentUser->id);
    if ($bothSlotsFilled && ! $viewerHasPanel && $canVerdict && in_array($report->status, ['investigating'])) {
        $panels[] = ['slot' => null, 'steward' => null, 'verdict' => null, 'isNew' => true];
    }
@endphp

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#dc2626">{{ session('error') }}</div>
@endif

{{-- Incident details --}}
<div class="admin-card mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <h6 class="fw-black text-uppercase mb-0" style="font-size:.78rem;letter-spacing:.06em">Incident Details</h6>
        <div class="d-flex align-items-center gap-2">
            @if($report->session_type)
            <span class="badge fw-bold" style="background:#e5e7eb;color:#374151;font-size:.72rem">{{ $report->sessionLabel() }}</span>
            @endif
            <span class="badge fw-bold text-white" style="background:{{ $meta['color'] }};font-size:.72rem">{{ $meta['label'] }}</span>
            @if($report->ban_review_flagged)
            <span class="badge fw-bold text-white" style="background:#7c2d12;font-size:.72rem">⚑ Ban Review</span>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Reporter</div>
            <div class="fw-bold mt-1">{{ $report->user->name ?? '—' }}</div>
        </div>
        <div class="col-sm-6">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Submitted Against</div>
            <div class="fw-bold mt-1">{{ $report->reported_driver_name }}</div>
            @if($reportedUser)
            <div class="text-secondary" style="font-size:.72rem">Linked account: {{ $reportedUser->name }} &middot; XCL Rating {{ (int) $reportedRating }}</div>
            @else
            <div class="text-secondary" style="font-size:.72rem">No linked account found — rating changes cannot be applied automatically.</div>
            @endif
        </div>
        <div class="col-sm-6">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Race</div>
            <div class="mt-1">{{ $report->race?->title ?? '—' }} @if($report->race) <span class="text-secondary" style="font-size:.75rem">({{ $report->race->gameLabel() }})</span>@endif</div>
        </div>
        <div class="col-sm-3">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Lap</div>
            <div class="mt-1">{{ $report->lap_number ?? '—' }}</div>
        </div>
        <div class="col-sm-3">
            <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af">Corner</div>
            <div class="mt-1">{{ $report->incident_corner ?? '—' }}</div>
        </div>
    </div>

    <div class="mb-4">
        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af" class="mb-2">Description</div>
        <div class="p-3 rounded-2" style="background:#f9fafb;font-size:.85rem;line-height:1.6;border:1px solid #f3f4f6">
            {{ $report->description }}
        </div>
    </div>

    @if($report->video_url || $report->clip_good_driver_url || $report->clip_bad_driver_url || $report->clip_heli_url)
    <div class="mb-4">
        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af" class="mb-2">Evidence</div>
        <div class="d-flex flex-wrap gap-2">
            @if($report->video_url)
            <a href="{{ $report->video_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary fw-bold" style="font-size:.78rem">Video / Clip →</a>
            @endif
            @if($report->clip_good_driver_url)
            <a href="{{ $report->clip_good_driver_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary fw-bold" style="font-size:.78rem">Clip 1 →</a>
            @endif
            @if($report->clip_bad_driver_url)
            <a href="{{ $report->clip_bad_driver_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary fw-bold" style="font-size:.78rem">Clip 2 →</a>
            @endif
            @if($report->clip_heli_url)
            <a href="{{ $report->clip_heli_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary fw-bold" style="font-size:.78rem">Clip 3 →</a>
            @endif
        </div>
    </div>
    @endif

    @if($report->status === 'dismissed' && $report->dismissal_reason)
    <div class="p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af" class="mb-1">Dismissal Reason</div>
        <div style="font-size:.85rem">{{ $report->dismissal_reason }}</div>
    </div>
    @elseif($report->status === 'resolved')
    <div class="p-3 rounded-2 text-white" style="background:#16a34a">
        <div class="fw-bold" style="font-size:.85rem">
            Processed — final penalty {{ $report->final_penalty }} ({{ $report->final_multiplier }}x) by {{ $report->processedBy->name ?? '—' }}
            on {{ $report->processed_at?->timezone('Europe/London')->format('d M Y H:i') }}
        </div>
    </div>
    @elseif($canVerdict && in_array($report->status, ['pending', 'investigating']) && ! $bothSlotsFilled && ! $report->isStewardAssigned($currentUser))
    <form method="POST" action="{{ route('admin.reports.start-investigating', $report) }}">
        @csrf
        <button type="submit" class="btn fw-bold text-white" style="background:#f59e0b;font-size:.85rem">
            Start Investigating
        </button>
    </form>
    @endif
</div>

@if($report->status === 'investigating' || (in_array($report->status, ['resolved', 'dismissed']) && $report->verdicts->count() > 0))
{{-- Steward verdict panels --}}
<div class="admin-card mb-4">
    <h6 class="fw-black text-uppercase mb-4" style="font-size:.78rem;letter-spacing:.06em">Steward Verdicts</h6>

    <div class="row g-3">
        @foreach($panels as $i => $panel)
        @php
            $steward   = $panel['steward'];
            $verdict   = $panel['verdict'];
            $isNew     = $panel['isNew'] ?? false;
            $editable  = $report->status === 'investigating' && (($steward && $steward->id === $currentUser->id) || $isNew);
            $label     = $panel['slot'] ? "Steward {$panel['slot']}" : ($steward ? $steward->name : 'Additional Steward');
        @endphp
        <div class="col-12 col-lg-6">
            <div class="p-3 rounded-3 h-100" style="border:1px solid #f3f4f6;background:{{ $verdict?->red_flag ? '#fef2f2' : '#fafafa' }}">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-black text-uppercase" style="font-size:.72rem;letter-spacing:.06em">{{ $label }}</span>
                    @if($verdict?->red_flag)
                    <span class="badge fw-bold text-white" style="background:#dc2626;font-size:.65rem">🚩 Red Flag</span>
                    @endif
                </div>
                <div class="text-secondary mb-3" style="font-size:.8rem">
                    {{ $steward->name ?? ($isNew ? $currentUser->name : 'Awaiting steward') }}
                </div>

                @if($editable)
                    @php $formId = 'verdict-form-' . $i; @endphp
                    <form method="POST" action="{{ route('admin.reports.verdict', $report) }}" id="{{ $formId }}" class="verdict-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.75rem">Penalty Code</label>
                            <select name="penalty" class="form-select form-select-sm penalty-select" required data-form="{{ $formId }}">
                                <option value="">— Select penalty —</option>
                                @foreach($penaltyCodes as $code => $cfg)
                                <option value="{{ $code }}" data-value="{{ $cfg['value'] }}" data-sr="{{ $cfg['sr'] }}"
                                    {{ $verdict?->penalty === $code ? 'selected' : '' }}>
                                    {{ $code }} — {{ $cfg['label'] }} (SR: -{{ number_format($cfg['sr'], 2) }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <label class="form-label fw-bold mb-0" style="font-size:.75rem">Multiplier</label>
                                <span tabindex="0"
                                      title="1x = Standard penalty. 2x = Repeated offence or elevated severity (SR loss ×1.1). 3x = Severe / multiple aggravating factors (SR loss ×1.2)."
                                      style="cursor:help;font-size:.7rem;width:15px;height:15px;border-radius:50%;background:#9ca3af;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:800">?</span>
                            </div>
                            <div class="btn-group w-100 multiplier-group" data-form="{{ $formId }}">
                                @foreach([1 => '1x', 2 => '2x', 3 => '3x'] as $mVal => $mLabel)
                                <input type="radio" class="btn-check multiplier-radio" name="multiplier" value="{{ $mVal }}"
                                       id="mult-{{ $i }}-{{ $mVal }}" autocomplete="off" data-form="{{ $formId }}"
                                       {{ (int)($verdict?->multiplier ?? 1) === $mVal ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary btn-sm fw-bold" for="mult-{{ $i }}-{{ $mVal }}">{{ $mLabel }}</label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-sm w-100 fw-bold red-flag-toggle" data-form="{{ $formId }}"
                                    data-active="{{ $verdict?->red_flag ? '1' : '0' }}"
                                    style="border:1px solid #dc2626;color:{{ $verdict?->red_flag ? '#fff' : '#dc2626' }};background:{{ $verdict?->red_flag ? '#dc2626' : 'transparent' }};font-size:.75rem">
                                🚩 {{ $verdict?->red_flag ? 'Red Flag Active' : 'Raise Red Flag' }}
                            </button>
                            <input type="hidden" name="red_flag" value="{{ $verdict?->red_flag ? '1' : '0' }}" class="red-flag-input">
                            <div class="form-text" style="font-size:.7rem">Requests discussion before this can be processed.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.75rem">Notes</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Optional notes...">{{ $verdict->notes ?? '' }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-sm w-100 fw-bold text-white" style="background:#7c3aed;font-size:.78rem">
                            Submit Verdict
                        </button>
                    </form>

                    <div class="mt-2 penalty-preview p-2 rounded-2 text-white" data-form="{{ $formId }}" style="background:#111827;font-size:.75rem;display:none">
                        <div class="fw-black text-uppercase mb-1" style="font-size:.65rem;letter-spacing:.06em;color:#a78bfa">Penalty Preview</div>
                        <div>XCL Rating deduction: <span class="fw-bold pv-rating">—</span></div>
                        <div>Rating return to reporter: <span class="fw-bold pv-return">—</span></div>
                        <div>SR deduction: <span class="fw-bold pv-sr">—</span></div>
                    </div>
                @elseif($verdict)
                    <div style="font-size:.8rem">
                        <div class="fw-bold mb-1">{{ $verdict->penalty }} — {{ $penaltyCodes[$verdict->penalty]['label'] ?? '' }}</div>
                        <div class="text-secondary mb-1">Multiplier: {{ (float) $verdict->multiplier }}x</div>
                        @if($verdict->notes)
                        <div class="text-secondary" style="font-size:.75rem">{{ $verdict->notes }}</div>
                        @endif
                    </div>
                @else
                    <div class="text-secondary" style="font-size:.8rem">Awaiting verdict.</div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Agreement banner --}}
<div class="admin-card mb-4">
    @if($agreement === 'agree')
        <div class="p-3 rounded-2 text-white fw-bold mb-3" style="background:#16a34a;font-size:.85rem">
            ✓ 2 stewards agree — ready to process
        </div>
        @if(! $report->ready_to_process)
        <form method="POST" action="{{ route('admin.reports.mark-ready', $report) }}">
            @csrf
            <button type="submit" class="btn fw-bold text-white" style="background:#16a34a;font-size:.85rem">Mark Ready</button>
        </form>
        @endif
    @elseif($agreement === 'red_flag')
        <div class="p-3 rounded-2 text-white fw-bold mb-0" style="background:#dc2626;font-size:.85rem">
            🚩 Red Flag active — verdict must be discussed before processing
        </div>
    @else
        <div class="p-3 rounded-2 fw-bold mb-0" style="background:#fef3c7;color:#92400e;font-size:.85rem">
            No matching verdicts yet — awaiting agreement
        </div>
    @endif

    @if($report->ready_to_process && ! $report->processed_at)
    <div class="mt-3 p-3 rounded-2" style="background:#f9fafb;border:1px solid #f3f4f6">
        <div class="fw-bold mb-1" style="font-size:.85rem">Ready to process</div>
        <div class="text-secondary mb-2" style="font-size:.8rem">
            Final penalty: <strong>{{ $report->final_penalty }}</strong> ({{ $report->final_multiplier }}x)
            &middot; Rating deduction: <strong>-{{ $report->xcl_rating_deduction }}</strong>
            &middot; Rating return: <strong>+{{ $report->xcl_rating_return }}</strong>
            &middot; SR deduction: <strong>-{{ $report->sr_deduction }}</strong>
        </div>
        @if($canProcess)
        <form method="POST" action="{{ route('admin.reports.process', $report) }}" onsubmit="return confirm('Apply this penalty? This cannot be undone.');">
            @csrf
            <button type="submit" class="btn fw-bold text-white" style="background:#111827;font-size:.85rem">PROCESS PENALTY</button>
        </form>
        @else
        <div class="text-secondary" style="font-size:.78rem">Only an admin or owner can process this penalty.</div>
        @endif
    </div>
    @endif
</div>
@endif

@if($canVerdict && ! in_array($report->status, ['resolved', 'dismissed']))
{{-- Dismiss --}}
<div class="admin-card mb-4">
    <button type="button" class="btn btn-sm btn-outline-danger fw-bold" id="dismiss-toggle" style="font-size:.78rem">Dismiss Report</button>
    <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}" id="dismiss-form" class="mt-3" style="display:none">
        @csrf
        <label class="form-label fw-bold" style="font-size:.8rem">Dismissal Reason <span class="text-danger">*</span></label>
        <textarea name="dismissal_reason" rows="3" required class="form-control form-control-sm mb-2" placeholder="Explain why this report is being dismissed..."></textarea>
        <button type="submit" class="btn btn-sm fw-bold text-white" style="background:#dc2626;font-size:.78rem">Confirm Dismissal</button>
    </form>
</div>
@endif

{{-- Penalty code reference --}}
<div class="admin-card mb-4" style="background:#111827;color:#e5e7eb">
    <button type="button" id="ref-toggle" class="btn w-100 d-flex align-items-center justify-content-between border-0 p-0" style="background:transparent;color:#e5e7eb">
        <span class="fw-black text-uppercase" style="font-size:.78rem;letter-spacing:.06em">Penalty Code Reference</span>
        <span id="ref-arrow" style="transition:transform .15s">▸</span>
    </button>

    <div id="ref-body" style="display:none" class="mt-4">
        <div class="table-responsive mb-4">
            <table class="table table-sm mb-0" style="font-size:.78rem;color:#e5e7eb">
                <thead>
                    <tr style="border-bottom:1px solid #374151">
                        <th class="text-secondary text-uppercase" style="font-size:.65rem;letter-spacing:.05em">Code</th>
                        <th class="text-secondary text-uppercase" style="font-size:.65rem;letter-spacing:.05em">Label</th>
                        <th class="text-secondary text-uppercase" style="font-size:.65rem;letter-spacing:.05em">SR</th>
                        <th class="text-secondary text-uppercase" style="font-size:.65rem;letter-spacing:.05em">Description</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $descriptions = [
                        'NONE' => 'Minimum requirements not met, or duplicate report by same driver.',
                        'INVALID' => 'Report does not meet the requirements to be actioned.',
                        'PENDING' => 'Awaiting steward review.',
                        'RI'   => 'Racing Incident — hard racing contact, no penalty warranted.',
                        'UI'   => 'Unnecessary Impeding — blocking in qualifying via blue flags or out-lap blocking.',
                        'WEV'  => 'Weaving — extreme weaving, not allowed in XCL.',
                        'MUB'  => 'Moving Under Braking — moving toward a driver in the braking zone while within radar/overtaking range.',
                        'DR'   => 'Dangerous Rejoin — rejoining from outside track limits causing time loss for others.',
                        'IBF'  => 'Ignoring Blue Flags — defending against a lapping car. See Blue Flag rules below.',
                        'CT'   => 'Contact.',
                        'DD'   => 'Dangerous Driving — highly unpredictable behaviour, danger to nearby drivers.',
                        'FDOT' => 'Forcing a Driver Off Track — pushing a driver alongside off track via contact or avoidance.',
                        'CASC' => 'Causing a Small Collision — minor contact, minor damage.',
                        'ULC'  => 'Unsportsmanlike Conduct — breaking event rules (split qualifying, pit lane white line violations, cutting track in qualifying).',
                        'CAC'  => 'Causing a Collision — mid-range damage and time loss.',
                        'CAHC' => 'Causing a Heavy Collision — heavy contact, large time loss.',
                        'CAIC' => 'Causing an Intentional Collision — intentional contact. Always results in ban review.',
                    ];
                    @endphp
                    @foreach($penaltyCodes as $code => $cfg)
                    <tr style="border-bottom:1px solid #1f2937">
                        <td class="fw-bold" style="color:#a78bfa">{{ $code }}</td>
                        <td>{{ $cfg['label'] }}</td>
                        <td>-{{ number_format($cfg['sr'], 2) }}</td>
                        <td class="text-secondary">{{ $descriptions[$code] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="button" id="bf-toggle" class="btn w-100 d-flex align-items-center justify-content-between border-0 p-0 mb-2" style="background:transparent;color:#e5e7eb">
            <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em">XCL Blue Flag Rules</span>
            <span id="bf-arrow" style="transition:transform .15s">▸</span>
        </button>
        <div id="bf-body" style="display:none">
            <div class="p-3 rounded-2 mb-2" style="background:#1f2937">
                <div class="fw-black text-uppercase mb-2" style="font-size:.68rem;letter-spacing:.06em;color:#a78bfa">For the Blue-Flagged Car</div>
                <p class="mb-0" style="font-size:.78rem;line-height:1.6;color:#d1d5db">
                    When the blue flag appears you must stay on your line and be predictable. You may lift on a straight or take a wider line
                    in a corner. When the faster driver shows clear intent to overtake (flashing lights, different line) you cannot defend or
                    drive the ideal line. You must stay on your side and provide a safe overtake. If you enter a corner on the inside together,
                    stay on that side until the overtake is complete. If the overtaking car loses distance, return to normal line until they
                    close in again.
                </p>
            </div>
            <div class="p-3 rounded-2" style="background:#1f2937">
                <div class="fw-black text-uppercase mb-2" style="font-size:.68rem;letter-spacing:.06em;color:#a78bfa">For the Overtaking Car</div>
                <p class="mb-0" style="font-size:.78rem;line-height:1.6;color:#d1d5db">
                    Keep your pace until within striking distance. Be patient and predictable. You cannot overtake aggressively or push the
                    blue-flagged driver wide — they are still in their own race. The responsibility for a safe pass is on the car behind. Show
                    your intended line clearly and the blue-flagged car should not defend. If you cannot pass immediately, focus on your exit
                    and the next corner.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const PENALTY_CODES = @json($penaltyCodes);
    const SESSION_TYPE = @json($report->session_type);
    const REPORTED_RATING = {{ $reportedRating }};

    function sessionMultiplier(s) {
        if (s === 'R') return 1.0;
        if (s === 'Q') return 0.5;
        if (s === 'P') return 0.2;
        return 0;
    }
    function srMultiplier(m) {
        if (m === 2) return 1.1;
        if (m === 3) return 1.2;
        return 1.0;
    }

    function updatePreview(formId) {
        const form = document.getElementById(formId);
        if (!form) return;
        const code = form.querySelector('.penalty-select').value;
        const checked = form.querySelector('.multiplier-radio:checked');
        const m = checked ? parseInt(checked.value, 10) : 1;
        const preview = document.querySelector('.penalty-preview[data-form="' + formId + '"]');
        if (!preview) return;

        if (!code || !PENALTY_CODES[code]) {
            preview.style.display = 'none';
            return;
        }

        const cfg = PENALTY_CODES[code];
        const p = parseFloat(cfg.value);
        const baseSr = parseFloat(cfg.sr);
        const s = sessionMultiplier(SESSION_TYPE);

        const ratingDeduction = (REPORTED_RATING / 100) * p * m * s;
        const ratingReturn = SESSION_TYPE === 'R' ? (p / 2.7) : 0;
        const srDeduction = baseSr * srMultiplier(m);

        preview.querySelector('.pv-rating').textContent = '-' + ratingDeduction.toFixed(2);
        preview.querySelector('.pv-return').textContent = '+' + ratingReturn.toFixed(2);
        preview.querySelector('.pv-sr').textContent = '-' + srDeduction.toFixed(2);
        preview.style.display = 'block';
    }

    document.querySelectorAll('.penalty-select').forEach(function (el) {
        el.addEventListener('change', function () { updatePreview(el.dataset.form); });
        updatePreview(el.dataset.form);
    });
    document.querySelectorAll('.multiplier-radio').forEach(function (el) {
        el.addEventListener('change', function () { updatePreview(el.dataset.form); });
    });

    document.querySelectorAll('.red-flag-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const form = document.getElementById(btn.dataset.form);
            const input = form.querySelector('.red-flag-input');
            const active = btn.dataset.active === '1';
            const next = !active;
            btn.dataset.active = next ? '1' : '0';
            input.value = next ? '1' : '0';
            btn.style.background = next ? '#dc2626' : 'transparent';
            btn.style.color = next ? '#fff' : '#dc2626';
            btn.textContent = '🚩 ' + (next ? 'Red Flag Active' : 'Raise Red Flag');
        });
    });

    const dismissToggle = document.getElementById('dismiss-toggle');
    if (dismissToggle) {
        dismissToggle.addEventListener('click', function () {
            const form = document.getElementById('dismiss-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });
    }

    function bindCollapse(toggleId, bodyId, arrowId) {
        const toggle = document.getElementById(toggleId);
        if (!toggle) return;
        toggle.addEventListener('click', function () {
            const body = document.getElementById(bodyId);
            const arrow = document.getElementById(arrowId);
            const open = body.style.display !== 'none';
            body.style.display = open ? 'none' : 'block';
            arrow.style.transform = open ? 'rotate(0deg)' : 'rotate(90deg)';
        });
    }
    bindCollapse('ref-toggle', 'ref-body', 'ref-arrow');
    bindCollapse('bf-toggle', 'bf-body', 'bf-arrow');
})();
</script>
@endpush

@endsection
