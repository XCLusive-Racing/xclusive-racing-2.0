@extends('layouts.admin')

@section('title', 'Results')
@section('page-title', 'Results')

@section('content')

<div class="team-event-page-grid">

    {{-- ── Create form ──────────────────────────────────────────────────── --}}
    <div>
        <div class="admin-form-card p-4">
            <h2 class="fw-black text-uppercase fst-italic text-dark mb-4" style="font-size:1rem">+ Add Result</h2>

            <form data-result-form action="{{ route('admin.results.store') }}" method="POST">
                @csrf

                @if($errors->any())
                <div class="alert alert-danger py-2" style="font-size:.82rem">
                    <strong>Couldn't save:</strong>
                    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
                @endif

                {{-- Subject --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Driver / Team <span class="text-danger">*</span></label>
                    <select name="subject" data-driver-picker-select class="form-select @error('subject') is-invalid @enderror" style="font-size:.9rem" required>
                        <option value="">— Select —</option>
                        <optgroup label="Professional Drivers">
                            @foreach(['dirk-schouten' => 'Dirk Schouten', 'mats-van-rooijen' => 'Mats van Rooijen'] as $val => $label)
                            <option value="{{ $val }}" {{ old('subject') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Esports Teams">
                            @foreach(['acc-team' => 'ACC Team', 'lmu-team' => 'LMU Team', 'iracing-team' => 'iRacing Team'] as $val => $label)
                            <option value="{{ $val }}" {{ old('subject') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Pro fields --}}
                <div data-result-fields="pro" style="display:none">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">Year <span class="text-danger">*</span></label>
                        <input type="number" name="year" value="{{ old('year', now()->year) }}" min="2000" max="2100"
                               class="form-control @error('year') is-invalid @enderror">
                        @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">Championship / Series <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="e.g. Porsche Carrera Cup Benelux">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">
                            Season Standing
                            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
                        </label>
                        <input type="text" name="standing" value="{{ old('standing') }}"
                               class="form-control @error('standing') is-invalid @enderror"
                               placeholder="e.g. P3 Rookie · P15 Overall">
                        @error('standing') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold" style="font-size:.82rem">Races <span class="text-danger">*</span></label>
                        <div data-race-repeater>
                            <div data-race-row class="d-flex gap-2 align-items-start mb-2">
                                <input type="text" name="races[0][track]" placeholder="Track" class="form-control" style="flex:1.4">
                                <input type="text" name="races[0][class]" placeholder="Class (optional)" class="form-control" style="flex:1">
                                <input type="text" name="races[0][positions]" placeholder="Positions, e.g. P3, P3" class="form-control" style="flex:1">
                                <button type="button" data-race-remove class="btn btn-sm" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca">&times;</button>
                            </div>
                        </div>
                        <button type="button" data-race-add class="btn btn-sm fw-bold text-uppercase mt-1"
                                style="font-size:.7rem;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe">
                            + Add Race
                        </button>
                        <template data-race-template>
                            <div data-race-row class="d-flex gap-2 align-items-start mb-2">
                                <input type="text" name="races[__INDEX__][track]" placeholder="Track" class="form-control" style="flex:1.4">
                                <input type="text" name="races[__INDEX__][class]" placeholder="Class (optional)" class="form-control" style="flex:1">
                                <input type="text" name="races[__INDEX__][positions]" placeholder="Positions, e.g. P3, P3" class="form-control" style="flex:1">
                                <button type="button" data-race-remove class="btn btn-sm" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca">&times;</button>
                            </div>
                        </template>
                        @error('races') <div class="text-danger" style="font-size:.78rem">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Esports fields --}}
                @include('admin.results._esports-fields', ['esportsDriversByGame' => $esportsDriversByGame])

                <button type="submit"
                        class="btn fw-black text-uppercase text-white px-4"
                        style="background:#7c3aed">
                    Create Result
                </button>
            </form>
        </div>
    </div>

    {{-- ── Results list ──────────────────────────────────────────────────── --}}
    <div>
        <div class="admin-form-card p-0" data-tabs data-default-tab="pro">

            {{-- Tab nav --}}
            <div class="d-flex border-bottom px-2" style="background:#f9fafb">
                <button data-tab-btn="pro"
                        class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                        style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent">
                    Pro Results
                    @if($proResults->isNotEmpty())
                    <span class="badge ms-1" style="background:#7c3aed;color:white;font-size:.65rem;padding:2px 7px;border-radius:10px">
                        {{ $proResults->count() }}
                    </span>
                    @endif
                </button>
                <button data-tab-btn="esports"
                        class="btn btn-link fw-black text-uppercase text-decoration-none py-3 px-3"
                        style="font-size:.78rem;border-radius:0;letter-spacing:.05em;transition:color .15s;border-bottom:2px solid transparent">
                    Esports Results
                    @if($esportsResults->isNotEmpty())
                    <span class="badge ms-1" style="background:#9ca3af;color:white;font-size:.65rem;padding:2px 7px;border-radius:10px">
                        {{ $esportsResults->count() }}
                    </span>
                    @endif
                </button>
            </div>

            <div data-tab-panel="pro" class="p-4" style="display:none">
                @include('admin.results._result-list', [
                    'results'      => $proResults,
                    'subjects'     => $subjects,
                    'emptyMessage' => 'No Pro results yet. Add one on the left.',
                ])
            </div>

            <div data-tab-panel="esports" class="p-4" style="display:none">
                @include('admin.results._result-list', [
                    'results'      => $esportsResults,
                    'subjects'     => $subjects,
                    'emptyMessage' => 'No Esports results yet. Add one on the left.',
                ])
            </div>

        </div>
    </div>

</div>

@endsection
