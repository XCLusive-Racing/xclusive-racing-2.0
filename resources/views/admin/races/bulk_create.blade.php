@extends('layouts.admin')

@section('title', 'Championship Scheduler')
@section('page-title', 'Championship Scheduler')

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
    interval: '{{ old('interval', 'weekly') }}',
    selectedDays: {!! json_encode(array_map('strval', old('days', []))) !!},
    tracks: {!! json_encode(old('tracks', [])) !!},

    isBST(date) {
        const y = date.getFullYear();
        const bstStart = new Date(y, 2, 31); bstStart.setDate(31 - bstStart.getDay());
        const bstEnd   = new Date(y, 9, 31); bstEnd.setDate(31 - bstEnd.getDay());
        return date >= bstStart && date < bstEnd;
    },

    get preview() {
        if (!this.startDate || !this.startTime || this.rounds < 1) return [];
        const result = [];
        const [y, m, d] = this.startDate.split('-').map(Number);
        const [h, min]  = this.startTime.split(':').map(Number);
        const base  = new Date(y, m - 1, d, h, min);
        const count = Math.min(Math.max(parseInt(this.rounds) || 0, 0), 366);

        const label = () => this.title.trim() || '…';
        const entry = (date) => ({
            date:  date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }),
            time:  this.startTime + ' ' + (this.isBST(date) ? 'BST' : 'GMT'),
            label: label(),
        });

        if (this.interval === 'weekly' && this.selectedDays.length > 0) {
            const days    = this.selectedDays.map(Number).sort((a, b) => a - b);
            const current = new Date(base);
            let   safety  = 0;
            while (result.length < count && safety < 730) {
                if (days.includes(current.getDay())) result.push(entry(new Date(current)));
                current.setDate(current.getDate() + 1);
                safety++;
            }
        } else {
            for (let i = 0; i < count; i++) {
                const date = new Date(base);
                if (this.interval === 'daily')        date.setDate(date.getDate() + i);
                else if (this.interval === 'monthly') date.setMonth(date.getMonth() + i);
                else                                  date.setDate(date.getDate() + i * 7);
                result.push(entry(date));
            }
        }

        return result;
    }
}"
x-init="$watch('preview', val => { while (tracks.length < val.length) tracks.push(''); })">

    {{-- Form --}}
    <div class="col-lg-7">
        <div class="admin-form-card">
            <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1.1rem">Event Details</h2>

            <form action="{{ route('admin.races.bulk-store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" x-model="title"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="e.g. ACC Weekly Championship">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Game</label>
                    <select name="game" class="form-select @error('game') is-invalid @enderror">
                        <option value="">Select game...</option>
                        <option value="acc"     {{ old('game') === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                        <option value="lmu"     {{ old('game') === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                        <option value="iracing" {{ old('game') === 'iracing' ? 'selected' : '' }}>iRacing</option>
                    </select>
                    @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @error('tracks') <div class="alert alert-danger py-2 small mb-3">{{ $message }}</div> @enderror
                @error('tracks.*') <div class="alert alert-danger py-2 small mb-3">Fill in a track for every round.</div> @enderror

                <template x-for="(t, i) in tracks" :key="i">
                    <input type="hidden" :name="'tracks[' + i + ']'" :value="t">
                </template>

                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" x-model="startDate"
                               class="form-control @error('start_date') is-invalid @enderror">
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Start Time (BST/GMT)</label>
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
                        <label class="form-label">Number of Events</label>
                        <input type="number" name="rounds" x-model.number="rounds"
                               class="form-control @error('rounds') is-invalid @enderror"
                               min="1" max="366">
                        @error('rounds') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Interval</label>
                        <select name="interval" x-model="interval"
                                class="form-select @error('interval') is-invalid @enderror">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        @error('interval') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Day picker — only for weekly --}}
                <div x-show="interval === 'weekly'" x-cloak class="mb-3">
                    <label class="form-label">Days <span class="text-secondary fw-normal" style="text-transform:none">(optional — leave empty for any day)</span></label>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach(['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 0] as $label => $value)
                        <button type="button"
                                @click="selectedDays.includes('{{ $value }}') ? selectedDays.splice(selectedDays.indexOf('{{ $value }}'), 1) : selectedDays.push('{{ $value }}')"
                                :style="selectedDays.includes('{{ $value }}')
                                    ? 'background:#7c3aed;color:white;border-color:#7c3aed'
                                    : 'background:white;color:#6b7280;border-color:#e5e7eb'"
                                style="width:42px;height:42px;border-radius:50%;border:1px solid;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;transition:background .1s,color .1s;cursor:pointer;padding:0">
                            {{ $label }}
                        </button>
                        <template x-if="selectedDays.includes('{{ $value }}')">
                            <input type="hidden" name="days[]" value="{{ $value }}">
                        </template>
                        @endforeach
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Description <span class="text-secondary fw-normal" style="text-transform:none">(optional — applied to all events)</span></label>
                    <textarea name="description" rows="2"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Additional info...">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn fw-black text-uppercase text-white px-4 bg-xcl-purple">
                    Schedule Events
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
                      x-text="preview.length + (preview.length === 1 ? ' event' : ' events')">
                </span>
            </div>

            {{-- Empty state --}}
            <template x-if="preview.length === 0">
                <div class="p-5 text-center">
                    <div class="mb-2" style="font-size:1.8rem">📅</div>
                    <p class="text-secondary mb-0" style="font-size:.85rem">
                        Fill in a start date and number of events to see the schedule here.
                    </p>
                </div>
            </template>

            {{-- Event list --}}
            <template x-if="preview.length > 0">
                <div style="max-height:460px;overflow-y:auto">
                    <template x-for="(item, i) in preview" :key="i">
                        <div class="d-flex align-items-start gap-3 px-4 py-3"
                             :style="{ borderBottom: i < preview.length - 1 ? '1px solid #f3f4f6' : 'none' }">

                            <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-black flex-shrink-0"
                                 style="width:28px;height:28px;font-size:.7rem;background:#7c3aed;margin-top:2px">
                                <span x-text="i + 1"></span>
                            </div>

                            <div style="min-width:0;flex:1">
                                <div class="fw-bold text-dark text-truncate" style="font-size:.85rem" x-text="item.label"></div>
                                <div class="text-secondary" style="font-size:.72rem" x-text="item.date + '  ·  ' + item.time"></div>
                                <input type="text"
                                       x-model="tracks[i]"
                                       placeholder="Track name..."
                                       class="form-control form-control-sm mt-1"
                                       style="font-size:.75rem;border-color:#e5e7eb">
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

</div>

@endsection