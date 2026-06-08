@extends('layouts.admin')

@section('title', 'Race Management')
@section('page-title', 'Race Management')

@section('page-actions')
    <a href="{{ route('admin.races.create') }}"
       class="btn btn-sm btn-outline-secondary fw-bold text-uppercase">
        + Single Race
    </a>
@endsection

@push('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

{{-- DataTable card --}}
<div class="admin-card">

    {{-- Game filters --}}
    <div class="d-flex align-items-center gap-2 px-4 py-3 border-bottom flex-wrap">
        <span class="fw-bold text-uppercase me-1" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Filter:</span>
        <button onclick="filterGame('')"
                id="filter-all"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#111827;color:white;border:1px solid #111827">
            All
        </button>
        <button onclick="filterGame('ACC Console')"
                id="filter-acc"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb">
            ACC Console
        </button>
        <button onclick="filterGame('Le Mans Ultimate')"
                id="filter-lmu"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb">
            Le Mans Ultimate
        </button>
        <button onclick="filterGame('iRacing')"
                id="filter-iracing"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb">
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
                            <a href="{{ route('admin.races.show', $race) }}"
                               class="btn btn-sm fw-bold text-uppercase text-white"
                               style="background:#7c3aed;font-size:.72rem;padding:5px 12px;border-radius:6px">
                                See Event
                            </a>
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

        const filterIds = {
            '':                 'filter-all',
            'ACC Console':      'filter-acc',
            'Le Mans Ultimate': 'filter-lmu',
            'iRacing':          'filter-iracing',
        };

        function filterGame(game) {
            table.column(1).search(game, false, false).draw();

            Object.entries(filterIds).forEach(([key, id]) => {
                const btn    = document.getElementById(id);
                const active = key === game;
                btn.style.background  = active ? '#111827' : '#f3f4f6';
                btn.style.borderColor = active ? '#111827' : '#e5e7eb';
                btn.style.color       = active ? 'white'   : '#374151';
            });
        }
    </script>
@endpush