@extends('layouts.admin')

@section('title', 'Create Race')
@section('page-title', 'Create Race')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<div style="max-width:680px;margin:0 auto">
    <div class="admin-form-card">
        <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1.1rem">Race Details</h2>

        <form action="{{ route('admin.races.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="form-control @error('title') is-invalid @enderror"
                       placeholder="e.g. Round 1 — Monza Sprint">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Game</label>
                    <select name="game" class="form-select @error('game') is-invalid @enderror">
                        <option value="">Select game...</option>
                        <option value="acc"     {{ old('game') === 'acc'     ? 'selected' : '' }}>ACC Console</option>
                        <option value="lmu"     {{ old('game') === 'lmu'     ? 'selected' : '' }}>Le Mans Ultimate</option>
                        <option value="iracing" {{ old('game') === 'iracing' ? 'selected' : '' }}>iRacing</option>
                        <option value="ac"      {{ old('game') === 'ac'      ? 'selected' : '' }}>AC Rally</option>
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

            <div class="mb-3">
                <label class="form-label">Rating Multiplier</label>
                <select name="duration_key" class="form-select @error('duration_key') is-invalid @enderror">
                    <option value="">Default (1.0×)</option>
                    <option value="15"   {{ old('duration_key') === '15'   ? 'selected' : '' }}>15 min sprint — 0.6×</option>
                    <option value="20"   {{ old('duration_key') === '20'   ? 'selected' : '' }}>20 min sprint — 0.8×</option>
                    <option value="30"   {{ old('duration_key') === '30'   ? 'selected' : '' }}>30 min sprint — 1.0×</option>
                    <option value="30+"  {{ old('duration_key') === '30+'  ? 'selected' : '' }}>30 min championship sprint — 1.2×</option>
                    <option value="30++" {{ old('duration_key') === '30++' ? 'selected' : '' }}>30 min endurance sprint — 1.3×</option>
                    <option value="45"   {{ old('duration_key') === '45'   ? 'selected' : '' }}>45 min race — 1.5×</option>
                    <option value="45+"  {{ old('duration_key') === '45+'  ? 'selected' : '' }}>45 min race+ — 1.6×</option>
                    <option value="60"   {{ old('duration_key') === '60'   ? 'selected' : '' }}>60 min race — 2.0×</option>
                    <option value="60+"  {{ old('duration_key') === '60+'  ? 'selected' : '' }}>60 min race+ — 2.1×</option>
                    <option value="90"   {{ old('duration_key') === '90'   ? 'selected' : '' }}>90 min race — 2.5×</option>
                    <option value="90+"  {{ old('duration_key') === '90+'  ? 'selected' : '' }}>90 min race+ — 2.6×</option>
                </select>
                @error('duration_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Event Tag --}}
            <script>window.__xclTags = @json($tags->map(fn($t) => ['slug'=>$t->slug,'name'=>$t->name,'color'=>$t->color]));</script>
            <div class="mb-3" x-data="{
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
                    if (!confirm('Delete this tag?')) return;
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
                            style="font-size:.72rem;padding:3px 10px;background:rgba(124,58,237,.1);color:#7c3aed;border:1px solid rgba(124,58,237,.3);border-radius:6px">
                        <span x-text="adding ? '✕ Cancel' : '+ New tag'"></span>
                    </button>
                </div>

                <select name="event_tag" class="form-select @error('event_tag') is-invalid @enderror">
                    <option value="">Select tag...</option>
                    <template x-for="tag in tags" :key="tag.slug">
                        <option :value="tag.slug"
                                :selected="tag.slug === '{{ old('event_tag') }}'"
                                x-text="tag.name"></option>
                    </template>
                </select>
                @error('event_tag') <div class="invalid-feedback">{{ $message }}</div> @enderror

                {{-- Inline add tag panel (no nested form) --}}
                <div x-show="adding" x-transition style="display:none">
                    <div class="mt-2 p-3 rounded-2" style="background:#f8f5ff;border:1px solid rgba(124,58,237,.2)">
                        <p class="fw-bold text-uppercase mb-2" style="font-size:.72rem;color:#7c3aed">Add new tag</p>
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
                                <input type="color" x-model="tagColor"
                                       class="form-control form-control-sm form-control-color"
                                       style="width:46px;padding:2px">
                            </div>
                            <button type="button" @click="saveTag()" :disabled="saving"
                                    class="btn btn-sm fw-bold text-white"
                                    style="background:#7c3aed;white-space:nowrap"
                                    x-text="saving ? 'Saving…' : 'Add tag'">
                            </button>
                        </div>

                        <template x-if="tags.length > 0">
                            <div class="mt-2 d-flex flex-wrap gap-1">
                                <template x-for="tag in tags" :key="tag.slug">
                                    <div class="d-flex align-items-center gap-1 rounded-2 px-2 py-1"
                                         :style="`background:${tag.color}22;border:1px solid ${tag.color}55;font-size:.75rem`">
                                        <span class="fw-bold" :style="`color:${tag.color}`" x-text="tag.name"></span>
                                        <button type="button" @click="deleteTag(tag.slug)"
                                                :style="`background:none;border:none;color:${tag.color};opacity:.6;padding:0;line-height:1;font-size:.8rem;cursor:pointer`"
                                                title="Delete">&times;</button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Date & Time (UTC)</label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', $prefillDate ? $prefillDate . 'T20:00' : '') }}"
                           class="form-control @error('scheduled_at') is-invalid @enderror">
                    @error('scheduled_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Max Drivers <span class="text-secondary fw-normal normal-case" style="text-transform:none">(optional)</span></label>
                    <input type="number" name="max_drivers" value="{{ old('max_drivers') }}"
                           class="form-control @error('max_drivers') is-invalid @enderror"
                           min="1" placeholder="No limit">
                    @error('max_drivers') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span></label>
                <textarea name="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Additional race info...">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <x-media-picker name="image" label="Background Image" />

            <x-media-picker name="icon" label="Event Icon" currentType="icon" filterDefault="icon" />

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Create Race
                </button>
                <a href="{{ route('admin.races.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection