@extends('layouts.admin')

@php $editing = $event->exists; @endphp

@section('title', $editing ? 'Edit Time Trial' : 'New Time Trial')
@section('page-title', $editing ? 'Edit Time Trial' : 'New Time Trial')

@section('page-actions')
    <a href="{{ route('admin.time-trials.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">&larr; Back</a>
@endsection

@section('content')
<div class="admin-card" style="max-width:640px">
    <form method="POST" action="{{ $editing ? route('admin.time-trials.update', $event) : route('admin.time-trials.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="px-4 pt-4 pb-3 border-bottom">
            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem" for="tt-track">Track <span class="text-danger">*</span></label>
            <select id="tt-track" name="track" class="form-select @error('track') is-invalid @enderror" style="font-size:.85rem">
                @foreach($tracks as $key => $name)
                <option value="{{ $key }}" @selected(old('track', $event->track) === $key)>{{ $name }}</option>
                @endforeach
            </select>
            @error('track')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="px-4 py-3 border-bottom">
            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem" for="tt-class">Car class</label>
            <select id="tt-class" name="car_class" class="form-select @error('car_class') is-invalid @enderror" style="font-size:.85rem;max-width:220px">
                <option value="">All classes</option>
                @foreach($classes as $class)
                <option value="{{ $class }}" @selected(old('car_class', $event->car_class) === $class)>{{ $class }}</option>
                @endforeach
            </select>
            <div class="text-secondary mt-1" style="font-size:.75rem">The server only lets cars of this class join. Drivers pick their own car.</div>
            @error('car_class')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="px-4 py-3 border-bottom">
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem" for="tt-start">Starts (UK time) <span class="text-danger">*</span></label>
                    <input id="tt-start" type="datetime-local" name="starts_at"
                           value="{{ old('starts_at', $event->starts_at?->timezone('Europe/London')->format('Y-m-d\TH:i')) }}"
                           class="form-control @error('starts_at') is-invalid @enderror" style="font-size:.85rem">
                    @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem" for="tt-end">Ends (UK time) <span class="text-danger">*</span></label>
                    <input id="tt-end" type="datetime-local" name="ends_at"
                           value="{{ old('ends_at', $event->ends_at?->timezone('Europe/London')->format('Y-m-d\TH:i')) }}"
                           class="form-control @error('ends_at') is-invalid @enderror" style="font-size:.85rem">
                    @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="text-secondary mt-1" style="font-size:.75rem">Pick whole hours: the server restarts every hour, and each hour is one session.</div>
        </div>

        <div class="px-4 py-3 border-bottom">
            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem" for="tt-server">Server <span class="text-danger">*</span></label>
            <select id="tt-server" name="ftp_server_id" class="form-select @error('ftp_server_id') is-invalid @enderror" style="font-size:.85rem">
                @foreach($servers as $server)
                <option value="{{ $server->id }}" @selected((int) old('ftp_server_id', $event->ftp_server_id) === $server->id)>{{ $server->name }}</option>
                @endforeach
            </select>
            @error('ftp_server_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="px-4 py-3 border-bottom">
            <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem" for="tt-title">Title</label>
            <input id="tt-title" type="text" name="title" value="{{ old('title', $event->title) }}"
                   class="form-control @error('title') is-invalid @enderror" style="font-size:.85rem"
                   placeholder="Optional. Defaults to the track name plus Time Trial">
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="px-4 py-3 border-bottom">
            <div class="form-check">
                <input type="hidden" name="is_published" value="0">
                <input class="form-check-input" type="checkbox" name="is_published" value="1" id="tt-published"
                       @checked(old('is_published', $event->is_published))>
                <label class="form-check-label fw-bold text-dark" for="tt-published" style="font-size:.85rem">Published</label>
            </div>
            <div class="text-secondary mt-1" style="font-size:.75rem">Only published Time Trials show on the site, take signups and are pushed to the server.</div>
        </div>

        <div class="px-4 py-3">
            <button type="submit" class="btn fw-bold text-white" style="background:#7c3aed;font-size:.85rem">{{ $editing ? 'Save changes' : 'Create Time Trial' }}</button>
        </div>
    </form>
</div>
@endsection
