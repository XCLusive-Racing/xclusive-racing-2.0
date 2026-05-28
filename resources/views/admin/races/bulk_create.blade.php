@extends('layouts.admin')

@section('title', 'Schedule Series')
@section('page-title', 'Schedule Series')

@section('page-actions')
    <a href="{{ route('admin.calendar') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Calendar
    </a>
@endsection

@section('content')

<div class="row g-4" x-data="{
    title: '{{ old('title') }}',
    startDate: '{{ old('start_date') }}',
    startTime: '{{ old('start_time', '20:00') }}',
    rounds: {{ old('rounds', 4) }},
    intervalWeeks: {{ old('interval_weeks', 1) }},
    numberRounds: true,
    get preview() {
        if (!this.startDate || !this.startTime || this.rounds < 1) return [];
        const result = [];
        const [y, m, d] = this.startDate.split('-').map(Number);
        const [h, min] = this.startTime.split(':').map(Number);
        const base = new Date(y, m - 1, d, h, min);
        const count = Math.min(Math.max(parseInt(this.rounds) || 0, 0), 52);
        for (let i = 0; i < count; i++) {
            const date = new Date(base);
            date.setDate(date.getDate() + i * (parseInt(this.intervalWeeks) || 1) * 7);
            result.push({
                date: date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }),
                time: this.startTime + ' UTC',
                label: this.numberRounds
                    ? (this.title.trim() || '…') + ' — R' + (i + 1)
                    : (this.title.trim() || '…'),
            });
        }
        return result;
    }
}">

    {{-- Form --}}
    <div class="col-lg-7">
        <div class="admin-form-card">
            <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1.1rem">Series Details</h2>

            <form action="{{ route('admin.races.bulk-store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Series Title</label>
                    <input type="text" name="title" x-model="title"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="e.g. ACC Weekly Championship">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex align-items-center gap-2" style="cursor:pointer;text-transform:none;font-size:.85rem">
                        <input type="checkbox" name="number_rounds" x-model="numberRounds"
                               class="form-check-input mt-0" style="width:16px;height:16px;border-color:#7c3aed;cursor:pointer">
                        <span class="fw-bold" style="color:#374151">Auto-number rounds</span>
                        <span class="text-secondary fw-normal">— adds R1, R2, …</span>
                    </label>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label">Game</label>
                        <select name="game" class="form-select @error('game') is-invalid @enderror">
                            <option value="">Select game...</option>
                            <option value="acc"     {{ old('game') === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                            <option value="lmu"     {{ old('game') === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                            <option value="iracing" {{ old('game') === 'iracing' ? 'selected' : '' }}>iRacing</option>
                        </select>
                        @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Track</label>
                        <input type="text" name="track" value="{{ old('track') }}"
                               class="form-control @error('track') is-invalid @enderror"
                               placeholder="e.g. Monza">
                        @error('track') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" x-model="startDate"
                               class="form-control @error('start_date') is-invalid @enderror">
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Start Time (UTC)</label>
                        <input type="time" name="start_time" x-model="startTime"
                               class="form-control @error('start_time') is-invalid @enderror">
                        @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Max Drivers</label>
                        <input type="number" name="max_drivers" value="{{ old('max_drivers') }}"
                               class="form-control @error('max_drivers') is-invalid @enderror"
                               min="1" placeholder="No limit">
                        @error('max_drivers') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label">Number of Rounds</label>
                        <input type="number" name="rounds" x-model.number="rounds"
                               class="form-control @error('rounds') is-invalid @enderror"
                               min="1" max="52">
                        @error('rounds') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Interval</label>
                        <select name="interval_weeks" x-model.number="intervalWeeks"
                                class="form-select @error('interval_weeks') is-invalid @enderror">
                            <option value="1">Every week</option>
                            <option value="2">Every 2 weeks</option>
                            <option value="3">Every 3 weeks</option>
                            <option value="4">Every 4 weeks</option>
                        </select>
                        @error('interval_weeks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Description <span class="text-secondary fw-normal" style="text-transform:none">(optional — applied to all rounds)</span></label>
                    <textarea name="description" rows="2"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Additional info...">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Schedule <span x-text="rounds"></span> Races
                </button>
            </form>
        </div>
    </div>

    {{-- Live preview --}}
    <div class="col-lg-5">
        <div class="admin-card" style="position:sticky;top:88px">
            <div class="admin-card-header">
                <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.95rem">Schedule Preview</div>
                <span class="fw-bold rounded-pill px-3 py-1"
                      style="background:#f3f4f6;color:#374151;font-size:.72rem"
                      x-text="preview.length + (preview.length === 1 ? ' round' : ' rounds')">
                </span>
            </div>

            {{-- Empty state --}}
            <template x-if="preview.length === 0">
                <div class="p-5 text-center">
                    <div class="mb-2" style="font-size:1.8rem">📅</div>
                    <p class="text-secondary mb-0" style="font-size:.85rem">
                        Fill in a start date and rounds to see the schedule here.
                    </p>
                </div>
            </template>

            {{-- Round list --}}
            <template x-if="preview.length > 0">
                <div style="max-height:460px;overflow-y:auto">
                    <template x-for="(item, i) in preview" :key="i">
                        <div class="d-flex align-items-start gap-3 px-4 py-3"
                             :style="{ borderBottom: i < preview.length - 1 ? '1px solid #f3f4f6' : 'none' }">

                            {{-- Round number --}}
                            <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-black flex-shrink-0"
                                 style="width:28px;height:28px;font-size:.7rem;background:#7c3aed;margin-top:1px">
                                <span x-text="i + 1"></span>
                            </div>

                            {{-- Info --}}
                            <div style="min-width:0">
                                <div class="fw-bold text-dark text-truncate" style="font-size:.85rem" x-text="item.label"></div>
                                <div class="text-secondary mt-1" style="font-size:.75rem" x-text="item.date + '  ·  ' + item.time"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

</div>

@endsection