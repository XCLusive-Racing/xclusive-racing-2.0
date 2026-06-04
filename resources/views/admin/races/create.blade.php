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

            {{-- Event Tag --}}
            <div class="mb-3" x-data="{ adding: false }">
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
                    @foreach($tags as $tag)
                        <option value="{{ $tag->slug }}"
                                {{ old('event_tag') === $tag->slug ? 'selected' : '' }}>
                            {{ $tag->name }}
                        </option>
                    @endforeach
                </select>
                @error('event_tag') <div class="invalid-feedback">{{ $message }}</div> @enderror

                {{-- Inline add tag form --}}
                <div x-show="adding" x-transition style="display:none">
                    <div class="mt-2 p-3 rounded-2" style="background:#f8f5ff;border:1px solid rgba(124,58,237,.2)">
                        <p class="fw-bold text-uppercase mb-2" style="font-size:.72rem;color:#7c3aed">Add new tag</p>
                        @if(session('tag_success'))
                            <div class="alert alert-success py-1 px-2 mb-2" style="font-size:.8rem">{{ session('tag_success') }}</div>
                        @endif
                        <form action="{{ route('admin.event-tags.store') }}" method="POST">
                            @csrf
                            <div class="d-flex gap-2 align-items-end">
                                <div class="flex-grow-1">
                                    <label class="form-label" style="font-size:.78rem">Name</label>
                                    <input type="text" name="name" placeholder="e.g. Endurance"
                                           class="form-control form-control-sm @error('name') is-invalid @enderror">
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div>
                                    <label class="form-label" style="font-size:.78rem">Color</label>
                                    <input type="color" name="color" value="#7B2FBE"
                                           class="form-control form-control-sm form-control-color"
                                           style="width:46px;padding:2px">
                                </div>
                                <button type="submit" class="btn btn-sm fw-bold text-white"
                                        style="background:#7c3aed;white-space:nowrap">
                                    Add tag
                                </button>
                            </div>
                        </form>

                        {{-- Existing tags with delete --}}
                        @if($tags->isNotEmpty())
                        <div class="mt-2 d-flex flex-wrap gap-1">
                            @foreach($tags as $tag)
                            <div class="d-flex align-items-center gap-1 rounded-2 px-2 py-1"
                                 style="background:{{ $tag->color }}22;border:1px solid {{ $tag->color }}55;font-size:.75rem">
                                <span class="fw-bold" style="color:{{ $tag->color }}">{{ $tag->name }}</span>
                                <form action="{{ route('admin.event-tags.destroy', $tag) }}" method="POST"
                                      onsubmit="return confirm('Delete tag \'{{ $tag->name }}\'?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            style="background:none;border:none;color:{{ $tag->color }};opacity:.6;padding:0;line-height:1;font-size:.8rem;cursor:pointer"
                                            title="Delete">&times;</button>
                                </form>
                            </div>
                            @endforeach
                        </div>
                        @endif
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

            <x-media-picker name="image" label="Event Media" />

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