@extends('layouts.admin')

@section('title', 'Race Management')
@section('page-title', 'Race Management')

@section('page-actions')
    <a href="{{ route('admin.races.bulk-create') }}"
       class="btn btn-sm fw-black text-uppercase text-white px-3"
       style="background:#7c3aed">
        + Schedule Series
    </a>
    <a href="{{ route('admin.races.create') }}"
       class="btn btn-sm btn-outline-secondary fw-bold text-uppercase">
        + Single Race
    </a>
@endsection

@push('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        #races-table_wrapper .dataTables_filter input {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: .85rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        #races-table_wrapper .dataTables_filter input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124,58,237,.1);
        }
        #races-table_wrapper .dataTables_length select {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 5px 28px 5px 10px;
            font-size: .85rem;
        }
        #races-table_wrapper .dataTables_info,
        #races-table_wrapper .dataTables_length,
        #races-table_wrapper .dataTables_filter {
            font-size: .8rem;
            color: #9ca3af;
            padding: 1rem 1.5rem;
        }
        #races-table_wrapper .dataTables_paginate {
            padding: .75rem 1.5rem;
        }
        #races-table_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
            font-size: .78rem;
            font-weight: 700;
            padding: 4px 10px !important;
            border: none !important;
        }
        #races-table_wrapper .dataTables_paginate .paginate_button.current {
            background: #7c3aed !important;
            color: white !important;
        }
        #races-table_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
            background: #f3f4f6 !important;
            color: #374151 !important;
        }
        #races-table thead th { cursor: pointer; user-select: none; }
        #races-table thead th.sorting_asc::after  { content: ' ↑'; color: #7c3aed; font-size:.7rem; }
        #races-table thead th.sorting_desc::after { content: ' ↓'; color: #7c3aed; font-size:.7rem; }
        #races-table thead th.sorting::after      { content: ' ↕'; color: #d1d5db; font-size:.7rem; }
        div.dataTables_wrapper div.dataTables_filter { text-align: right; }
    </style>
@endpush

@section('content')

{{-- Metric cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="metric-card">
            <div class="metric-icon" style="background:#f3e8ff">
                <svg width="24" height="24" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6h18M3 14h10M3 18h6"/>
                </svg>
            </div>
            <div>
                <div class="metric-value">{{ $stats['total'] }}</div>
                <div class="metric-label">Total Races</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="metric-card">
            <div class="metric-icon" style="background:#d1fae5">
                <svg width="24" height="24" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <div class="metric-value">{{ $stats['open'] }}</div>
                <div class="metric-label">Open Events</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="metric-card">
            <div class="metric-icon" style="background:#dbeafe">
                <svg width="24" height="24" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                </svg>
            </div>
            <div>
                <div class="metric-value">{{ $stats['registrations'] }}</div>
                <div class="metric-label">Registrations</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="metric-card">
            <div class="metric-icon" style="background:#f3f4f6">
                <svg width="24" height="24" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/>
                </svg>
            </div>
            <div>
                <div class="metric-value">{{ $stats['finished'] }}</div>
                <div class="metric-label">Finished</div>
            </div>
        </div>
    </div>
</div>

{{-- DataTable card --}}
<div class="admin-card">

    {{-- Game filters --}}
    <div class="d-flex align-items-center gap-2 px-4 py-3 border-bottom flex-wrap">
        <span class="fw-bold text-uppercase me-1" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Filter:</span>
        <button onclick="filterGame('')"
                id="filter-all"
                class="btn btn-sm fw-bold text-uppercase px-3 active"
                style="font-size:.72rem;border-radius:6px;background:#111827;color:white;border:1px solid #111827">
            All
        </button>
        <button onclick="filterGame('ACC Console')"
                id="filter-acc"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:rgba(124,58,237,.08);color:#7c3aed;border:1px solid rgba(124,58,237,.2)">
            ACC Console
        </button>
        <button onclick="filterGame('Le Mans Ultimate')"
                id="filter-lmu"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:rgba(219,39,119,.08);color:#db2777;border:1px solid rgba(219,39,119,.2)">
            Le Mans Ultimate
        </button>
        <button onclick="filterGame('iRacing')"
                id="filter-iracing"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:rgba(37,99,235,.08);color:#2563eb;border:1px solid rgba(37,99,235,.2)">
            iRacing
        </button>
    </div>

    <div class="table-responsive">
        <table id="races-table" class="table table-hover align-middle mb-0 w-100" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Race</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Game</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Date</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Drivers</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Status</th>
                    <th class="pe-4" style="min-width:160px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($races as $race)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $race->title }}</div>
                        <div class="text-secondary" style="font-size:.78rem">{{ $race->track }}</div>
                    </td>
                    <td>
                        <span class="badge text-white fw-bold"
                              style="background:{{ $race->gameColor() }};font-size:.7rem;padding:5px 10px;border-radius:6px">
                            {{ $race->gameLabel() }}
                        </span>
                    </td>
                    <td class="text-secondary" style="font-size:.82rem" data-order="{{ $race->scheduled_at->timestamp }}">
                        {{ $race->scheduledAtUk()->format('d M Y') }}<br>
                        <span style="color:#9ca3af">{{ $race->scheduledAtUk()->format('H:i T') }}</span>
                    </td>
                    <td class="text-center fw-bold">
                        {{ $race->registrations_count }}{{ $race->max_drivers ? ' / ' . $race->max_drivers : '' }}
                    </td>
                    <td class="text-center">
                        <span class="status-badge status-{{ $race->status }}">
                            @if($race->status === 'open')
                                <svg width="7" height="7" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                            @endif
                            {{ ucfirst($race->status) }}
                        </span>
                    </td>
                    <td class="pe-4">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.races.results', $race) }}"
                               class="btn btn-sm fw-bold text-uppercase text-white"
                               style="background:#7c3aed;font-size:.72rem;padding:5px 12px;border-radius:6px">
                                Results
                            </a>
                            @if(!$race->isPast())
                            <a href="{{ route('admin.races.edit', $race) }}"
                               class="btn btn-sm btn-outline-secondary fw-bold text-uppercase"
                               style="font-size:.72rem;padding:5px 12px;border-radius:6px">
                                Edit
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        let table;

        $(function () {
            table = $('#races-table').DataTable({
                pageLength: 10,
                order: [[2, 'desc']],
                columnDefs: [
                    { orderable: false, targets: 5 },
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search races…',
                    lengthMenu: 'Show _MENU_ races',
                    info: 'Showing _START_ to _END_ of _TOTAL_ races',
                    infoEmpty: 'No races found',
                    zeroRecords: 'No matching races found',
                    paginate: { previous: '‹', next: '›' },
                },
            });
        });

        const filterStyles = {
            '':                { bg: '#f3f4f6',              border: '#e5e7eb',              color: '#374151' },
            'ACC Console':     { bg: 'rgba(124,58,237,.08)', border: 'rgba(124,58,237,.2)',  color: '#7c3aed' },
            'Le Mans Ultimate':{ bg: 'rgba(219,39,119,.08)', border: 'rgba(219,39,119,.2)',  color: '#db2777' },
            'iRacing':         { bg: 'rgba(37,99,235,.08)',  border: 'rgba(37,99,235,.2)',   color: '#2563eb' },
        };

        const filterIds = {
            '':                 'filter-all',
            'ACC Console':      'filter-acc',
            'Le Mans Ultimate': 'filter-lmu',
            'iRacing':          'filter-iracing',
        };

        function filterGame(game) {
            table.column(1).search(game, false, false).draw();

            Object.entries(filterIds).forEach(([key, id]) => {
                const btn = document.getElementById(id);
                const s   = filterStyles[key];
                const activeStyle = key === '' ? { bg: '#111827', border: '#111827', color: 'white' } : { bg: '#7c3aed', border: '#7c3aed', color: 'white' };
                if (key === game) {
                    btn.style.background   = activeStyle.bg;
                    btn.style.borderColor  = activeStyle.border;
                    btn.style.color        = activeStyle.color;
                } else {
                    btn.style.background   = s.bg;
                    btn.style.borderColor  = s.border;
                    btn.style.color        = s.color;
                }
            });
        }
    </script>
@endpush