@extends('layouts.admin')

@section('title', 'Edit Championship')
@section('page-title', 'Edit: ' . $championship->name)

@section('page-actions')
    <a href="{{ route('admin.championships.show', $championship) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<form action="{{ route('admin.championships.update', $championship) }}" method="POST" enctype="multipart/form-data"
      x-data="championshipForm()">
    @csrf
    @method('PUT')

    <div class="row g-4 align-items-start">

        {{-- Left column --}}
        <div class="col-12 col-lg-8">

            {{-- Basic Info --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Basic Info</p>

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ old('name', $championship->name) }}"
                               class="form-control @error('name') is-invalid @enderror">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Game</label>
                            <select name="game" class="form-select @error('game') is-invalid @enderror">
                                <option value="acc"     {{ old('game', $championship->game) === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                                <option value="lmu"     {{ old('game', $championship->game) === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                                <option value="iracing" {{ old('game', $championship->game) === 'iracing' ? 'selected' : '' }}>iRacing</option>
                                <option value="ac"      {{ old('game', $championship->game) === 'ac'      ? 'selected' : '' }}>AC Rally</option>
                            </select>
                            @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Season</label>
                            <input type="number" name="season" value="{{ old('season', $championship->season) }}"
                                   class="form-control @error('season') is-invalid @enderror" min="2020" max="2099">
                            @error('season') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft"    {{ old('status', $championship->status) === 'draft'    ? 'selected' : '' }}>Draft</option>
                                <option value="active"   {{ old('status', $championship->status) === 'active'   ? 'selected' : '' }}>Active</option>
                                <option value="finished" {{ old('status', $championship->status) === 'finished' ? 'selected' : '' }}>Finished</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Description <span class="fw-normal" style="text-transform:none">(optional)</span></p>
                    <textarea name="description" rows="3" class="form-control">{{ old('description', $championship->description) }}</textarea>
                </div>
            </div>

            {{-- Points System --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Points System</p>

                    <div class="mb-3">
                        <label class="form-label">Points per position <span class="fw-normal text-secondary">(comma-separated)</span></label>
                        <input type="text" name="points_system"
                               value="{{ old('points_system', implode(',', $championship->points_system ?? [])) }}"
                               class="form-control" placeholder="25,18,15,12,10,8,6,4,2,1">
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Bonus Fastest Lap</label>
                            <input type="number" name="bonus_fastest_lap"
                                   value="{{ old('bonus_fastest_lap', $championship->bonus_fastest_lap) }}"
                                   class="form-control" min="0">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Bonus Pole Position</label>
                            <input type="number" name="bonus_pole"
                                   value="{{ old('bonus_pole', $championship->bonus_pole) }}"
                                   class="form-control" min="0">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Standings Rules --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Standings Rules</p>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Drop Rounds</label>
                            <input type="number" name="drop_rounds"
                                   value="{{ old('drop_rounds', $championship->drop_rounds) }}"
                                   class="form-control" min="0">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Rounds Allowed to Miss</label>
                            <input type="number" name="max_missed_rounds"
                                   value="{{ old('max_missed_rounds', $championship->max_missed_rounds) }}"
                                   class="form-control" min="0" placeholder="No limit">
                        </div>
                        <div class="col-sm-4" x-data="{ action: '{{ old('missed_rounds_action', $championship->missed_rounds_action ?? 'none') }}' }">
                            <label class="form-label">If limit exceeded</label>
                            <select name="missed_rounds_action" class="form-select" x-model="action">
                                <option value="none">No penalty</option>
                                <option value="penalise">Penalty points</option>
                            </select>
                            <div x-show="action === 'penalise'" class="mt-2">
                                <input type="number" name="missed_rounds_penalty_points"
                                       value="{{ old('missed_rounds_penalty_points', $championship->missed_rounds_penalty_points) }}"
                                       class="form-control" min="1" placeholder="Points deducted per missed round">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Registration --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Registration</p>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="registration_open"
                                   id="registration_open" value="1"
                                   {{ old('registration_open', $championship->registration_open) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="registration_open">Registration Open</label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Deadline</label>
                            <input type="datetime-local" name="registration_deadline"
                                   value="{{ old('registration_deadline', $championship->registration_deadline?->format('Y-m-d\TH:i')) }}"
                                   class="form-control">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Max Drivers</label>
                            <input type="number" name="max_drivers"
                                   value="{{ old('max_drivers', $championship->max_drivers) }}"
                                   class="form-control" min="1" placeholder="No limit">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-sm-6">
                            <label class="form-label">SR Requirement</label>
                            <select name="sr_requirement" class="form-select">
                                <option value="">— No requirement —</option>
                                <option value="none"  {{ old('sr_requirement', $championship->sr_requirement) === 'none'  ? 'selected' : '' }}>None</option>
                                <option value="5"     {{ old('sr_requirement', $championship->sr_requirement) === '5'     ? 'selected' : '' }}>SR ≥ 5.0</option>
                                <option value="7"     {{ old('sr_requirement', $championship->sr_requirement) === '7'     ? 'selected' : '' }}>SR ≥ 7.0</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Minimum Rating Class</label>
                            <select name="min_rating" class="form-select">
                                <option value="">— No requirement —</option>
                                @foreach(['all','rookie','bronze','silver','gold','platinum','alien'] as $r)
                                <option value="{{ $r }}" {{ old('min_rating', $championship->min_rating) === $r ? 'selected' : '' }}>{{ ucfirst($r) }}+</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Race Defaults --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Race Defaults</p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-4" x-show="!multiclass">
                            <label class="form-label">Car Class</label>
                            <input type="text" name="car_class" value="{{ old('car_class', $championship->car_class) }}" class="form-control">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Weather</label>
                            <select name="weather" class="form-select">
                                <option value="">— Not set —</option>
                                @foreach(['dry','wet','mixed','random'] as $w)
                                <option value="{{ $w }}" {{ old('weather', $championship->weather) === $w ? 'selected' : '' }}>{{ ucfirst($w) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Time of Day</label>
                            <select name="time_of_day" class="form-select">
                                <option value="">— Not set —</option>
                                @foreach(['day','dusk','night','dynamic'] as $t)
                                <option value="{{ $t }}" {{ old('time_of_day', $championship->time_of_day) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Practice <span class="fw-normal text-secondary">(min)</span></label>
                            <input type="number" name="practice_duration" value="{{ old('practice_duration', $championship->practice_duration) }}" class="form-control" min="1" max="999">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Qualifying <span class="fw-normal text-secondary">(min)</span></label>
                            <input type="number" name="qualifying_duration" value="{{ old('qualifying_duration', $championship->qualifying_duration) }}" class="form-control" min="1" max="999">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Race <span class="fw-normal text-secondary">(min)</span></label>
                            <input type="number" name="race_duration" value="{{ old('race_duration', $championship->race_duration) }}" class="form-control" min="1" max="999">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Multiclass --}}
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Multiclass</p>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_multiclass"
                                   @change="multiclass = $event.target.checked"
                                   {{ old('is_multiclass', $championship->is_multiclass) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="is_multiclass">Enable Multiclass</label>
                        </div>
                        <input type="hidden" name="is_multiclass" :value="multiclass ? '1' : '0'">
                    </div>

                    <div x-show="multiclass" x-transition style="{{ $championship->is_multiclass ? '' : 'display:none' }}">
                        <template x-for="(cls, i) in classes" :key="i">
                            <div class="p-3 rounded-2 mb-2" style="background:#f9fafb;border:1px solid #e5e7eb">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold" style="font-size:.82rem" x-text="'Class ' + (i+1)"></span>
                                    <button type="button" @click="classes.splice(i,1)"
                                            class="btn btn-sm text-danger" style="font-size:.72rem;padding:2px 8px">Remove</button>
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-4">
                                        <input type="text" x-model="cls.name" class="form-control form-control-sm" placeholder="Name">
                                    </div>
                                    <div class="col-sm-2">
                                        <input type="color" x-model="cls.color" class="form-control form-control-sm form-control-color" style="width:100%;padding:2px">
                                    </div>
                                    <div class="col-sm-3">
                                        <input type="text" x-model="cls.car_class" class="form-control form-control-sm" placeholder="Car class">
                                    </div>
                                    <div class="col-sm-3">
                                        <input type="number" x-model="cls.max_drivers" class="form-control form-control-sm" placeholder="Max drivers" min="1">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <button type="button" @click="classes.push({name:'',color:'#db2777',car_class:'',max_drivers:''})"
                                class="btn btn-sm fw-bold text-uppercase"
                                style="background:rgba(219,39,119,.1);color:#db2777;border:1px solid rgba(219,39,119,.3);font-size:.72rem">
                            + Add Class
                        </button>

                        <input type="hidden" name="classes_json" :value="JSON.stringify(classes)">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#db2777">
                    Save Changes
                </button>
                <a href="{{ route('admin.championships.show', $championship) }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>

        </div>

        {{-- Right column: media --}}
        <div class="col-12 col-lg-4">
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Media</p>
                    <x-media-picker name="image" label="Background Image"
                        :current="$championship->image_url" :currentPath="$championship->image" />
                    <div class="mt-3">
                        <x-media-picker name="icon" label="Championship Icon" currentType="icon" filterDefault="icon"
                            :current="$championship->icon_url" :currentPath="$championship->icon" />
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script>
function championshipForm() {
    return {
        multiclass: {{ $championship->is_multiclass ? 'true' : 'false' }},
        classes: @json($championship->classes->map(fn($c) => ['name' => $c->name, 'color' => $c->color, 'car_class' => $c->car_class, 'max_drivers' => $c->max_drivers])),
    };
}
</script>
@endpush
