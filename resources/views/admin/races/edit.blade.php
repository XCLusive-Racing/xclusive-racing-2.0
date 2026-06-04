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

            <x-media-picker name="image" label="Event Media" :current="$race->image" />

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