@extends('layouts.admin')

@section('title', 'Rating Config')
@section('page-title', 'Rating Config')

@section('content')

@php
$coreParams = [
    'K_FACTOR'        => ['default' => 50,   'step' => 1,    'desc' => 'Global scale factor — determines how hard ratings move. Higher = bigger swings per race.'],
    'STARTING_RATING' => ['default' => 1500, 'step' => 50,   'desc' => 'Starting rating for new drivers.'],
    'STOP_LOSS_FLOOR' => ['default' => 500,  'step' => 50,   'desc' => 'Drivers at or below this rating will not lose points on a normal finish. Only DNF/DNS/DC/DSQ still cause losses.'],
    'MIN_DRIVERS'     => ['default' => 8,    'step' => 1,    'desc' => 'Minimum number of classified finishers for a valid rating calculation. Races with fewer drivers are skipped.'],
];
$rHigh = isset($configs['R_HIGH']) ? (float) $configs['R_HIGH']->value : 1.18;
$rLow  = isset($configs['R_LOW'])  ? (float) $configs['R_LOW']->value  : -0.85;
$rFactorParams = [
    'R_HIGH' => ['value' => $rHigh, 'step' => 0.01, 'desc' => 'R Factor for P1 (winner). Fixed value regardless of driver count.'],
    'R_LOW'  => ['value' => $rLow,  'step' => 0.01, 'desc' => 'R Factor for last finisher. Fixed value — step per position is (R_HIGH – R_LOW) / (n_finishers – 1).'],
];
@endphp

{{-- Section 1: Core Parameters --}}
<div class="admin-card mb-4">
    <div class="px-4 pt-4 pb-2">
        <p class="fw-black text-uppercase fst-italic mb-0" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Core Parameters — K-Factor & Multipliers</p>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:200px">Parameter</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:180px">Value</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Description</th>
                    <th class="fw-bold text-uppercase pe-4 text-end" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:120px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($coreParams as $paramKey => $meta)
                @php $val = isset($configs[$paramKey]) ? (float) $configs[$paramKey]->value : $meta['default']; @endphp
                <tr x-data="ratingRow('{{ $paramKey }}', {{ $val }}, {{ $meta['step'] }})">
                    <td class="ps-4">
                        <code style="font-size:.8rem;font-weight:700;color:#7c3aed;background:#f5f3ff;padding:2px 8px;border-radius:4px">{{ $paramKey }}</code>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span x-show="!editing"
                                  x-text="tempValue"
                                  @click="editing = true; $nextTick(() => $refs.input.focus())"
                                  class="fw-bold"
                                  style="font-size:.95rem;cursor:pointer;min-width:40px"
                                  title="Click to edit"></span>
                            <div x-show="editing" class="d-flex align-items-center gap-1" style="display:none!important" x-cloak>
                                <input type="number"
                                       x-model="tempValue"
                                       :step="inputStep"
                                       @keydown.enter.prevent="save()"
                                       @keydown.escape="cancel()"
                                       class="form-control form-control-sm"
                                       style="width:90px;font-weight:700"
                                       x-ref="input">
                                <button type="button"
                                        @click="save()"
                                        :disabled="saving"
                                        class="btn btn-sm d-flex align-items-center justify-content-center fw-bold"
                                        style="background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;width:28px;height:28px;padding:0"
                                        x-text="saving ? '…' : '✓'"></button>
                            </div>
                        </div>
                        <div x-show="error" x-text="error" style="font-size:.72rem;color:#dc2626;margin-top:3px"></div>
                    </td>
                    <td class="text-secondary" style="font-size:.8rem">{{ $meta['desc'] }}</td>
                    <td class="pe-4 text-end">
                        <span style="background:#fef3c7;color:#92400e;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:3px 8px;border-radius:4px">adjustable</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Section 2: R Factor --}}
<div class="admin-card mb-4">
    <div class="px-4 pt-4 pb-2">
        <p class="fw-black text-uppercase fst-italic mb-0" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">R Factor — Position Reward</p>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:200px">Parameter</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:180px">Value</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Description</th>
                    <th class="fw-bold text-uppercase pe-4 text-end" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af;width:120px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rFactorParams as $paramKey => $meta)
                <tr x-data="ratingRow('{{ $paramKey }}', {{ $meta['value'] }}, {{ $meta['step'] }})">
                    <td class="ps-4">
                        <code style="font-size:.8rem;font-weight:700;color:#7c3aed;background:#f5f3ff;padding:2px 8px;border-radius:4px">{{ $paramKey }}</code>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span x-show="!editing"
                                  x-text="tempValue"
                                  @click="editing = true; $nextTick(() => $refs.input.focus())"
                                  class="fw-bold"
                                  style="font-size:.95rem;cursor:pointer;min-width:40px"
                                  title="Click to edit"></span>
                            <div x-show="editing" class="d-flex align-items-center gap-1" style="display:none!important" x-cloak>
                                <input type="number"
                                       x-model="tempValue"
                                       :step="inputStep"
                                       @keydown.enter.prevent="save()"
                                       @keydown.escape="cancel()"
                                       class="form-control form-control-sm"
                                       style="width:90px;font-weight:700"
                                       x-ref="input">
                                <button type="button"
                                        @click="save()"
                                        :disabled="saving"
                                        class="btn btn-sm d-flex align-items-center justify-content-center fw-bold"
                                        style="background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;width:28px;height:28px;padding:0"
                                        x-text="saving ? '…' : '✓'"></button>
                            </div>
                        </div>
                        <div x-show="error" x-text="error" style="font-size:.72rem;color:#dc2626;margin-top:3px"></div>
                    </td>
                    <td class="text-secondary" style="font-size:.8rem">{{ $meta['desc'] }}</td>
                    <td class="pe-4 text-end">
                        <span style="background:#fef3c7;color:#92400e;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:3px 8px;border-radius:4px">adjustable</span>
                    </td>
                </tr>
                @endforeach

                {{-- R_STEP: calculated, read-only --}}
                <tr>
                    <td class="ps-4">
                        <code style="font-size:.8rem;font-weight:700;color:#6b7280;background:#f3f4f6;padding:2px 8px;border-radius:4px">R_STEP</code>
                    </td>
                    <td>
                        <span class="fw-bold text-secondary" style="font-size:.95rem">{{ round(($rHigh - $rLow) / (36 - 1), 4) }}</span>
                        <span class="text-secondary" style="font-size:.75rem"> @ 36 drivers</span>
                    </td>
                    <td class="text-secondary" style="font-size:.8rem">
                        = (R_HIGH – R_LOW) / (n_finishers – 1). With 36 drivers: ({{ $rHigh }} – {{ $rLow }}) / 35 = {{ round(($rHigh - $rLow) / 35, 4) }} per position.
                    </td>
                    <td class="pe-4 text-end">
                        <span style="background:#d1fae5;color:#065f46;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:3px 8px;border-radius:4px">automatic</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Info block --}}
    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
        <div class="px-3 py-2 rounded-2" style="background:#eff6ff;border-left:3px solid #7c3aed">
            <p class="mb-0 text-secondary" style="font-size:.82rem">
                Break-even at ~P21 (with 36 drivers).
                R Factor midpoint = ({{ $rHigh }} + {{ $rLow }}) / 2 = <strong>{{ round(($rHigh + $rLow) / 2, 3) }}</strong>.
                The system is slightly inflationary: a driver finishing near the median still gains roughly +8 points.
            </p>
        </div>
    </div>

    {{-- Status R Factor cards --}}
    <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Fixed R Factor by Status (regardless of finishing position)</p>
        <div class="d-flex gap-3 flex-wrap">
            @foreach(['DNF' => -0.50, 'DNS' => -0.50, 'DC' => -0.20, 'DSQ' => -0.70] as $status => $factor)
            <div class="text-center px-4 py-3 rounded-2" style="background:#fef2f2;border:1px solid #fecaca;min-width:90px">
                <div class="fw-black text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">{{ $status }}</div>
                <div class="fw-black" style="font-size:1.15rem;color:#dc2626">{{ $factor }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function ratingRow(key, initialValue, step) {
    return {
        editing: false,
        tempValue: initialValue,
        saving: false,
        error: '',
        inputStep: step,
        async save() {
            this.saving = true;
            this.error = '';
            try {
                const r = await fetch(`/admin/rating-config/${key}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ value: parseFloat(this.tempValue) }),
                });
                const data = await r.json();
                if (r.ok) {
                    this.tempValue = data.value;
                    this.editing = false;
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Saved', type: 'success' } }));
                } else {
                    this.error = data.message || 'Failed to save';
                }
            } catch {
                this.error = 'Network error';
            } finally {
                this.saving = false;
            }
        },
        cancel() {
            this.editing = false;
            this.error = '';
        },
    };
}
</script>
@endpush