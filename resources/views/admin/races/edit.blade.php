@extends('layouts.admin')

@section('title', 'Edit Race')
@section('page-title', 'Edit Race')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<form action="{{ route('admin.races.update', $race) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-4 align-items-start">

        {{-- Left: form fields --}}
        <div class="col-lg-8">

            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-2">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Race Info</p>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" value="{{ old('title', $race->title) }}"
                               class="form-control @error('title') is-invalid @enderror">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Game</label>
                            <select name="game" class="form-select @error('game') is-invalid @enderror">
                                <option value="acc"     {{ old('game', $race->game) === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                                <option value="lmu"     {{ old('game', $race->game) === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                                <option value="iracing" {{ old('game', $race->game) === 'iracing' ? 'selected' : '' }}>iRacing</option>
                                <option value="ac"      {{ old('game', $race->game) === 'ac'      ? 'selected' : '' }}>AC Rally</option>
                            </select>
                            @error('game') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Track</label>
                            <input type="text" name="track" value="{{ old('track', $race->track) }}"
                                   class="form-control @error('track') is-invalid @enderror">
                            @error('track') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6;margin-top:.5rem">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Schedule & Status</p>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Date & Time (BST)</label>
                            <input type="datetime-local" name="scheduled_at"
                                   value="{{ old('scheduled_at', $race->scheduledAtUk()->format('Y-m-d\TH:i')) }}"
                                   class="form-control @error('scheduled_at') is-invalid @enderror">
                            @error('scheduled_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="open"     {{ old('status', $race->status) === 'open'     ? 'selected' : '' }}>Open</option>
                                <option value="closed"   {{ old('status', $race->status) === 'closed'   ? 'selected' : '' }}>Closed</option>
                                <option value="finished" {{ old('status', $race->status) === 'finished' ? 'selected' : '' }}>Finished</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Max Drivers <span class="fw-normal text-secondary" style="text-transform:none">(optional)</span></label>
                            <input type="number" name="max_drivers" value="{{ old('max_drivers', $race->max_drivers) }}"
                                   class="form-control @error('max_drivers') is-invalid @enderror"
                                   min="1" placeholder="No limit">
                            @error('max_drivers') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Settings</p>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Rating Multiplier</label>
                            <select name="duration_key" class="form-select @error('duration_key') is-invalid @enderror">
                                <option value="">1.0× (default)</option>
                                <option value="15"   {{ old('duration_key', $race->duration_key) === '15'   ? 'selected' : '' }}>0.6×</option>
                                <option value="20"   {{ old('duration_key', $race->duration_key) === '20'   ? 'selected' : '' }}>0.8×</option>
                                <option value="30"   {{ old('duration_key', $race->duration_key) === '30'   ? 'selected' : '' }}>1.0×</option>
                                <option value="30+"  {{ old('duration_key', $race->duration_key) === '30+'  ? 'selected' : '' }}>1.2×</option>
                                <option value="30++" {{ old('duration_key', $race->duration_key) === '30++' ? 'selected' : '' }}>1.3×</option>
                                <option value="45"   {{ old('duration_key', $race->duration_key) === '45'   ? 'selected' : '' }}>1.5×</option>
                                <option value="45+"  {{ old('duration_key', $race->duration_key) === '45+'  ? 'selected' : '' }}>1.6×</option>
                                <option value="60"   {{ old('duration_key', $race->duration_key) === '60'   ? 'selected' : '' }}>2.0×</option>
                                <option value="60+"  {{ old('duration_key', $race->duration_key) === '60+'  ? 'selected' : '' }}>2.1×</option>
                                <option value="90"   {{ old('duration_key', $race->duration_key) === '90'   ? 'selected' : '' }}>2.5×</option>
                                <option value="90+"  {{ old('duration_key', $race->duration_key) === '90+'  ? 'selected' : '' }}>2.6×</option>
                            </select>
                            @error('duration_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            {{-- Event Tag --}}
                            <script>window.__xclTags = @json($tags->map(fn($t) => ['slug'=>$t->slug,'name'=>$t->name,'color'=>$t->color]));</script>
                            <div x-data="{
                                adding: false,
                                tagName: '',
                                tagColor: '#7B2FBE',
                                saving: false,
                                tagError: '',
                                tagSuccess: '',
                                tags: window.__xclTags || [],
                                async saveTag() {
                                    if (!this.tagName.trim()) return;
                                    this.saving = true; this.tagError = ''; this.tagSuccess = '';
                                    try {
                                        const r = await fetch('{{ route('admin.event-tags.store') }}', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                            body: JSON.stringify({ name: this.tagName, color: this.tagColor })
                                        });
                                        const data = await r.json();
                                        if (r.ok) {
                                            this.tags.push({ slug: data.slug, name: data.name, color: data.color });
                                            this.tagSuccess = data.name + ' added!';
                                            this.tagName = ''; this.tagColor = '#7B2FBE';
                                            setTimeout(() => { this.adding = false; this.tagSuccess = ''; }, 1200);
                                        } else {
                                            this.tagError = data.errors?.name?.[0] || data.message || 'Failed to save tag.';
                                        }
                                    } catch { this.tagError = 'Network error.'; }
                                    finally { this.saving = false; }
                                },
                                async deleteTag(slug) {
                                    const res = await Swal.fire({ title: 'Delete tag?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Delete', cancelButtonText: 'Cancel', reverseButtons: true });
                                    if (!res.isConfirmed) return;
                                    const r = await fetch('/admin/event-tags/' + slug, {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-HTTP-Method-Override': 'DELETE', 'Accept': 'application/json' }
                                    });
                                    if (r.ok) this.tags = this.tags.filter(t => t.slug !== slug);
                                }
                            }">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label mb-0">Event Tag</label>
                                    <button type="button" @click="adding = !adding"
                                            class="btn btn-sm fw-bold text-uppercase"
                                            style="font-size:.68rem;padding:2px 8px;background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.3);border-radius:6px">
                                        <span x-text="adding ? '✕ Cancel' : '+ New'"></span>
                                    </button>
                                </div>
                                <select name="event_tag" class="form-select @error('event_tag') is-invalid @enderror">
                                    <option value="">Select tag...</option>
                                    <template x-for="tag in tags" :key="tag.slug">
                                        <option :value="tag.slug" :selected="tag.slug === '{{ old('event_tag', $race->event_tag) }}'" x-text="tag.name"></option>
                                    </template>
                                </select>
                                @error('event_tag') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                <div x-show="adding" x-transition style="display:none">
                                    <div class="mt-2 p-3 rounded-2" style="background:#f8f5ff;border:1px solid rgba(124,58,237,.2)">
                                        <div x-show="tagSuccess" class="alert alert-success py-1 px-2 mb-2" style="font-size:.8rem" x-text="tagSuccess"></div>
                                        <div x-show="tagError"   class="alert alert-danger  py-1 px-2 mb-2" style="font-size:.8rem" x-text="tagError"></div>
                                        <div class="d-flex gap-2 align-items-end">
                                            <div class="flex-grow-1">
                                                <label class="form-label" style="font-size:.78rem">Name</label>
                                                <input type="text" x-model="tagName" placeholder="e.g. Endurance"
                                                       class="form-control form-control-sm" @keydown.enter.prevent="saveTag()">
                                            </div>
                                            <div>
                                                <label class="form-label" style="font-size:.78rem">Color</label>
                                                <input type="color" x-model="tagColor" class="form-control form-control-sm form-control-color" style="width:46px;padding:2px">
                                            </div>
                                            <button type="button" @click="saveTag()" :disabled="saving"
                                                    class="btn btn-sm fw-bold text-white" style="background:#7c3aed;white-space:nowrap"
                                                    x-text="saving ? 'Saving…' : 'Add'"></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Event Format <span class="fw-normal" style="text-transform:none">(optional)</span></p>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <label class="form-label">Practice <span class="fw-normal text-secondary">(min)</span></label>
                            <input type="number" name="practice_duration" value="{{ old('practice_duration', $race->practice_duration) }}"
                                   class="form-control" min="1" max="999" placeholder="e.g. 15">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Qualifying <span class="fw-normal text-secondary">(min)</span></label>
                            <input type="number" name="qualifying_duration" value="{{ old('qualifying_duration', $race->qualifying_duration) }}"
                                   class="form-control" min="1" max="999" placeholder="e.g. 10">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Race <span class="fw-normal text-secondary">(min)</span></label>
                            <input type="number" name="race_duration" value="{{ old('race_duration', $race->race_duration) }}"
                                   class="form-control" min="1" max="999" placeholder="e.g. 20">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Car Class</label>
                            <input type="text" name="car_class" value="{{ old('car_class', $race->car_class) }}"
                                   class="form-control" placeholder="e.g. GT3, GT4, LMP2">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Weather</label>
                            <select name="weather" class="form-select">
                                <option value="">— Not set —</option>
                                <option value="dry"    {{ old('weather', $race->weather) === 'dry'    ? 'selected' : '' }}>Dry</option>
                                <option value="wet"    {{ old('weather', $race->weather) === 'wet'    ? 'selected' : '' }}>Wet</option>
                                <option value="mixed"  {{ old('weather', $race->weather) === 'mixed'  ? 'selected' : '' }}>Mixed</option>
                                <option value="random" {{ old('weather', $race->weather) === 'random' ? 'selected' : '' }}>Random</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Time of Day</label>
                            <select name="time_of_day" class="form-select">
                                <option value="">— Not set —</option>
                                <option value="day"     {{ old('time_of_day', $race->time_of_day) === 'day'     ? 'selected' : '' }}>Day</option>
                                <option value="dusk"    {{ old('time_of_day', $race->time_of_day) === 'dusk'    ? 'selected' : '' }}>Dusk</option>
                                <option value="night"   {{ old('time_of_day', $race->time_of_day) === 'night'   ? 'selected' : '' }}>Night</option>
                                <option value="dynamic" {{ old('time_of_day', $race->time_of_day) === 'dynamic' ? 'selected' : '' }}>Dynamic</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Description <span class="fw-normal" style="text-transform:none">(optional)</span></p>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Additional race info...">{{ old('description', $race->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Save Changes
                </button>
                <a href="{{ route('admin.races.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>

        </div>

        {{-- Right: media --}}
        <div class="col-lg-4">
            <div class="admin-card mb-4">
                <div class="px-4 pt-4 pb-3">
                    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Media</p>
                    <x-media-picker name="image" label="Background Image" :current="$race->image" />
                    <div class="mt-3">
                        <x-media-picker name="icon" label="Event Icon" :current="$race->icon" currentType="icon" filterDefault="icon" />
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

@endsection