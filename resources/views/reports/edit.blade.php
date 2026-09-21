@extends('layouts.app')

@section('title', 'Edit Report - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:640px;position:relative;z-index:1">

        <div class="mb-4">
            <a href="{{ route('reports.index') }}" class="text-decoration-none fw-bold text-secondary" style="font-size:.8rem">&larr; Back to reports</a>
            <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1 mt-2">Edit Report</h1>
            <p class="text-secondary mb-0">You can change your report until a steward starts investigating it.</p>
        </div>

        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-bottom" style="background:#fafafa">
                <div class="fw-bold text-dark" style="font-size:.88rem">vs {{ $report->reported_driver_name }}</div>
                @if($report->race)
                <div class="text-secondary" style="font-size:.75rem">{{ $report->race->title }}</div>
                @endif
                <div class="text-secondary mt-1" style="font-size:.72rem">
                    The race and the reported driver can&rsquo;t be changed. To change those, retract this report and file a new one.
                </div>
            </div>

            <form method="POST" action="{{ route('reports.update', $report) }}" class="p-4">
                @csrf
                @method('PUT')

                @include('reports._incident-fields', ['report' => $report])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn fw-bold text-white flex-grow-1" style="background:#7c3aed;font-size:.85rem">Save changes</button>
                    <a href="{{ route('reports.index') }}" class="btn fw-bold" style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-size:.85rem">Cancel</a>
                </div>
            </form>
        </div>

    </div>
</main>
@endsection
