@extends('layouts.app')

@section('title', 'Incident Reports - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:900px;position:relative;z-index:1">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">Incident Reports</h1>
                <p class="text-secondary mb-0">Submit and track your incident reports</p>
            </div>
        </div>

        @if(session('success'))
        <div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">
            {{ session('success') }}
        </div>
        @endif

        @guest
        <div class="bg-white rounded-3 shadow-sm p-5 text-center">
            <div class="text-secondary mb-3" style="font-size:.9rem">Sign in to submit and view your incident reports.</div>
            <a href="{{ route('login') }}" class="btn fw-bold text-white px-4" style="background:#7c3aed">Sign In</a>
        </div>
        @else

        <div class="row g-4">

            {{-- Submit form --}}
            <div class="col-lg-5">
                <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-bottom" style="background:#fafafa">
                        <span class="fw-black text-uppercase" style="font-size:.78rem;letter-spacing:.06em">Submit Report</span>
                    </div>
                    <form method="POST" action="{{ route('reports.store') }}" class="p-4">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.8rem">Race (optional)</label>
                            <select name="race_id" class="form-select form-select-sm @error('race_id') is-invalid @enderror">
                                <option value="">— Not race-specific —</option>
                                @foreach($races as $race)
                                <option value="{{ $race->id }}" {{ old('race_id') == $race->id ? 'selected' : '' }}>
                                    {{ $race->title }} ({{ $race->scheduledAtUk()->format('d M Y') }})
                                </option>
                                @endforeach
                            </select>
                            @error('race_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.8rem">Driver being reported <span class="text-danger">*</span></label>
                            <input type="text" name="reported_driver_name"
                                   value="{{ old('reported_driver_name') }}"
                                   class="form-control form-control-sm @error('reported_driver_name') is-invalid @enderror"
                                   placeholder="Gamertag / driver name">
                            @error('reported_driver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size:.8rem">Lap number</label>
                                <input type="number" name="lap_number" value="{{ old('lap_number') }}"
                                       class="form-control form-control-sm @error('lap_number') is-invalid @enderror"
                                       placeholder="e.g. 5" min="1" max="999">
                                @error('lap_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size:.8rem">Corner</label>
                                <input type="text" name="incident_corner" value="{{ old('incident_corner') }}"
                                       class="form-control form-control-sm @error('incident_corner') is-invalid @enderror"
                                       placeholder="e.g. T1, Raidillon">
                                @error('incident_corner')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.8rem">Description <span class="text-danger">*</span></label>
                            <textarea name="description" rows="4"
                                      class="form-control form-control-sm @error('description') is-invalid @enderror"
                                      placeholder="Describe what happened in detail (min. 20 characters)...">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold" style="font-size:.8rem">Video / clip URL</label>
                            <input type="url" name="video_url" value="{{ old('video_url') }}"
                                   class="form-control form-control-sm @error('video_url') is-invalid @enderror"
                                   placeholder="https://youtube.com/...">
                            @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn fw-bold text-white w-100" style="background:#7c3aed;font-size:.85rem">
                            Submit Report
                        </button>
                    </form>
                </div>
            </div>

            {{-- My reports --}}
            <div class="col-lg-7">
                <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-bottom" style="background:#fafafa">
                        <span class="fw-black text-uppercase" style="font-size:.78rem;letter-spacing:.06em">My Reports</span>
                    </div>
                    @if($reports->isEmpty())
                    <div class="text-center py-5 text-secondary" style="font-size:.85rem">
                        You haven't submitted any reports yet.
                    </div>
                    @else
                    <div>
                        @foreach($reports as $report)
                        @php $meta = $report->statusMeta(); @endphp
                        <div class="px-4 py-3 border-bottom">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                <span class="fw-bold text-dark" style="font-size:.88rem">vs {{ $report->reported_driver_name }}</span>
                                <span class="badge fw-bold text-white" style="background:{{ $meta['color'] }};font-size:.68rem">
                                    {{ $meta['label'] }}
                                </span>
                            </div>
                            @if($report->race)
                            <div class="text-secondary mb-1" style="font-size:.75rem">{{ $report->race->title }}</div>
                            @endif
                            <div class="text-secondary" style="font-size:.75rem">
                                {{ $report->created_at->format('d M Y') }}
                                @if($report->lap_number) &middot; Lap {{ $report->lap_number }} @endif
                                @if($report->incident_corner) &middot; {{ $report->incident_corner }} @endif
                            </div>
                            @if($report->admin_notes)
                            <div class="mt-2 p-2 rounded-2" style="background:#f9fafb;font-size:.78rem;color:#374151;border:1px solid #f3f4f6">
                                <span class="fw-bold text-uppercase" style="font-size:.65rem;letter-spacing:.05em;color:#9ca3af">Steward note: </span>
                                {{ $report->admin_notes }}
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

        </div>
        @endguest

    </div>
</main>
@endsection