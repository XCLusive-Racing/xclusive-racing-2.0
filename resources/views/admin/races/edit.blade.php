@extends('layouts.admin')

@section('title', 'Edit Race')
@section('page-title', 'Edit Race')

@section('page-actions')
    <a href="{{ route('admin.races.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<div style="max-width:680px;margin:0 auto">
    <div class="admin-form-card">
        <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1.1rem">{{ $race->title }}</h2>

        <form action="{{ route('admin.races.update', $race) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" value="{{ old('title', $race->title) }}"
                       class="form-control @error('title') is-invalid @enderror">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3 mb-3">
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
                                :selected="tag.slug === '{{ old('event_tag', $race->event_tag) }}'"
                                x-text="tag.name"></option>
                    </template>
                </select>
                @error('event_tag') <div class="invalid-feedback">{{ $message }}</div> @enderror

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
                <div class="col-sm-4">
                    <label class="form-label">Date & Time (UTC)</label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', $race->scheduled_at->format('Y-m-d\TH:i')) }}"
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
                    <label class="form-label">Max Drivers</label>
                    <input type="number" name="max_drivers"
                           value="{{ old('max_drivers', $race->max_drivers) }}"
                           class="form-control @error('max_drivers') is-invalid @enderror"
                           min="1" placeholder="No limit">
                    @error('max_drivers') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $race->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <x-media-picker name="image" label="Background Image" :current="$race->image" />

            {{-- Event Icon --}}
            @php $currentIconUrl = $race->icon_url ?? ''; @endphp
            <div class="mb-4" x-data="{
                iconPreview: '{{ $currentIconUrl }}',
                iconPath: '{{ $race->icon ?? '' }}',
                onIconChange(e) {
                    const f = e.target.files[0];
                    if (!f) return;
                    this.iconPreview = URL.createObjectURL(f);
                    this.iconPath = '';
                },
                clearIcon() {
                    this.iconPreview = '';
                    this.iconPath = '';
                    this.$refs.iconInput.value = '';
                }
            }">
                <label class="form-label">Event Icon <span class="text-secondary fw-normal" style="text-transform:none;font-size:.85em">(optional)</span></label>

                <div class="d-flex align-items-center gap-3">
                    <div style="width:88px;height:88px;border:2px dashed #e5e7eb;border-radius:10px;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#f9fafb;cursor:pointer;transition:border-color .15s"
                         :style="iconPreview ? 'border:2px solid #7c3aed;background:#f5f3ff' : ''"
                         @click="$refs.iconInput.click()">
                        <template x-if="iconPreview">
                            <img :src="iconPreview" style="width:100%;height:100%;object-fit:contain;padding:8px;display:block">
                        </template>
                        <template x-if="!iconPreview">
                            <svg width="26" height="26" fill="none" stroke="#d1d5db" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                        </template>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="button" @click="$refs.iconInput.click()"
                                class="btn btn-sm fw-bold text-uppercase"
                                style="font-size:.72rem;background:#f3f4f6;border:1px solid #e5e7eb;color:#374151"
                                x-text="iconPreview ? 'Replace icon' : 'Upload icon'">
                        </button>
                        <button type="button" @click="clearIcon()" x-show="iconPreview"
                                class="btn btn-sm fw-bold text-uppercase"
                                style="font-size:.72rem;background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;display:none">
                            Remove
                        </button>
                        <p class="text-secondary mb-0" style="font-size:.72rem">PNG, SVG, WebP · max 4 MB</p>
                    </div>
                </div>

                <input type="file" name="icon" accept="image/png,image/svg+xml,image/webp,image/jpeg,image/gif"
                       x-ref="iconInput" class="d-none" @change="onIconChange">
                <input type="hidden" name="icon_path" x-model="iconPath">

                @error('icon')
                    <div class="text-danger mt-1" style="font-size:.85rem">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
                    Save Changes
                </button>
                <a href="{{ route('admin.races.index') }}" class="btn btn-outline-secondary fw-bold text-uppercase px-4">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection