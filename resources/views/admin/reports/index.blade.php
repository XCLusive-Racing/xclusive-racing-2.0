@extends('layouts.admin')

@section('title', 'Incident Reports')
@section('page-title', 'Incident Reports')

@section('content')

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif

@php
    $totalReports = $statusCounts->sum();
    $filterPill = function (?string $value, string $label, int $count) use ($status) {
        $active = $status === $value;
        $url    = request()->fullUrlWithQuery(['status' => $value]);
        $bg     = $active ? '#7c3aed' : '#f3f4f6';
        $color  = $active ? '#fff' : '#374151';

        return '<a href="' . e($url) . '" class="text-decoration-none fw-bold text-uppercase d-inline-block me-2 mb-2"'
            . ' style="font-size:.7rem;letter-spacing:.05em;background:' . $bg . ';color:' . $color . ';padding:5px 12px;border-radius:20px">'
            . e($label) . ' <span style="opacity:.75">' . $count . '</span></a>';
    };
@endphp
<div class="mb-3">
    {!! $filterPill(null, 'All', $totalReports) !!}
    @foreach(\App\Models\Report::statuses() as $slug => $meta)
        {!! $filterPill($slug, $meta['label'], $statusCounts->get($slug, 0)) !!}
    @endforeach
</div>

<div class="admin-card p-0 overflow-hidden">
    @if($reports->isEmpty())
    <p class="text-secondary text-center py-5 mb-0" style="font-size:.85rem">No reports {{ $status ? 'with this status' : 'submitted yet' }}.</p>
    @else
    @php
        // Builds a clickable column header: sorting by a not-yet-active column starts
        // ascending, clicking the already-active column flips its direction.
        $sortHeader = function (string $column, string $label) use ($sort, $dir) {
            $active   = $sort === $column;
            $nextDir  = $active && $dir === 'asc' ? 'desc' : 'asc';
            $url      = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir]);
            $arrow    = $active ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
            $color    = $active ? '#7c3aed' : '#6b7280';

            return '<a href="' . e($url) . '" class="text-decoration-none fw-bold text-uppercase d-inline-block"'
                . ' style="font-size:.68rem;letter-spacing:.06em;color:' . $color . '">' . e($label) . $arrow . '</a>';
        };
    @endphp
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.83rem">
            <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                <tr>
                    <th class="ps-4 py-3">{!! $sortHeader('reporter', 'Reporter') !!}</th>
                    <th class="py-3">{!! $sortHeader('reported', 'Reported') !!}</th>
                    <th class="py-3 d-none d-md-table-cell">{!! $sortHeader('race', 'Race') !!}</th>
                    <th class="py-3 text-center">{!! $sortHeader('status', 'Status') !!}</th>
                    <th class="py-3 text-center d-none d-md-table-cell">{!! $sortHeader('penalty', 'Penalty') !!}</th>
                    <th class="fw-bold text-uppercase text-secondary py-3 text-center d-none d-lg-table-cell" style="font-size:.68rem;letter-spacing:.06em">Stewards</th>
                    <th class="py-3 text-center d-none d-lg-table-cell">{!! $sortHeader('processed', 'Processed') !!}</th>
                    <th class="py-3 d-none d-lg-table-cell">{!! $sortHeader('submitted', 'Submitted') !!}</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                @php $meta = $report->statusMeta(); @endphp
                <tr style="border-bottom:1px solid #f9fafb;transition:background .12s"
                    onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td class="ps-4 fw-bold text-dark">{{ $report->user->name ?? '—' }}</td>
                    <td class="fw-bold" style="color:#374151">{{ $report->reported_driver_name }}</td>
                    <td class="text-secondary d-none d-md-table-cell" style="max-width:180px">
                        <span class="text-truncate d-block">{{ $report->race?->title ?? '—' }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge fw-bold text-white" style="background:{{ $meta['color'] }};font-size:.68rem">
                            {{ $meta['label'] }}
                        </span>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        @if($report->final_penalty)
                        <span class="badge fw-bold" style="background:#ede9fe;color:#5b21b6;font-size:.68rem">
                            {{ $report->final_penalty }}@if($report->final_multiplier) &times;{{ (float) $report->final_multiplier }}@endif
                        </span>
                        @else
                        <span class="text-secondary">—</span>
                        @endif
                    </td>
                    <td class="text-center d-none d-lg-table-cell" style="font-size:.78rem">
                        {{ $report->verdicts_count }}/2
                    </td>
                    <td class="text-center d-none d-lg-table-cell">
                        @if($report->processed_at)
                        <span class="badge fw-bold text-white" style="background:#16a34a;font-size:.65rem">✓ Processed</span>
                        @else
                        <span class="text-secondary" style="font-size:.75rem">—</span>
                        @endif
                    </td>
                    <td class="text-secondary d-none d-lg-table-cell" style="font-size:.78rem">
                        {{ $report->created_at->timezone('Europe/London')->format('d M Y') }}
                    </td>
                    <td class="text-end pe-3">
                        <a href="{{ route('admin.reports.show', $report) }}"
                           class="btn btn-xs btn-outline-secondary fw-bold" style="font-size:.7rem;padding:2px 8px">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection