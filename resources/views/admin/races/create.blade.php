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

            <x-image-upload name="image" label="Event Image" />

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