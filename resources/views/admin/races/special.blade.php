@extends('layouts.admin')

@section('title', 'Special Events')
@section('page-title', 'Special Events')


@section('content')

<div class="admin-card">

    {{-- Game filters --}}
    <div class="d-flex align-items-center gap-2 px-4 py-3 border-bottom flex-wrap">
        <span class="fw-bold text-uppercase me-1" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Filter:</span>
        <button onclick="filterGame('')" id="filter-all"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#111827;color:white;border:1px solid #111827">All</button>
        <button onclick="filterGame('ACC Console')" id="filter-acc"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb">ACC Console</button>
        <button onclick="filterGame('Le Mans Ultimate')" id="filter-lmu"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb">Le Mans Ultimate</button>
        <button onclick="filterGame('iRacing')" id="filter-iracing"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb">iRacing</button>
    </div>

    {{-- Status filters --}}
    <div class="d-flex align-items-center gap-2 px-4 py-3 border-bottom flex-wrap">
        <span class="fw-bold text-uppercase me-1" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Status:</span>
        <button onclick="filterStatus('upcoming')" id="status-upcoming"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb">Upcoming</button>
        <button onclick="filterStatus('past')" id="status-past"
                class="btn btn-sm fw-bold text-uppercase px-3"
                style="font-size:.72rem;border-radius:6px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb">Past Events</button>
    </div>

    <div class="table-responsive">
        <table id="special-table" class="table table-hover align-middle mb-0 w-100" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Race</th>
                    <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Type</th>
                    <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Game</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Date</th>
                    <th class="fw-bold text-uppercase text-center d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Entries</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Status</th>
                    <th class="pe-4" style="min-width:80px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($races as $race)
                @php
                    $isEndurance = (bool) $race->is_endurance;
                    $isCustom    = ! $race->event_format_id;
                @endphp
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $race->title }}</div>
                        <div class="text-secondary" style="font-size:.78rem">{{ $race->track }}</div>
                        <div class="d-sm-none mt-1 d-flex align-items-center gap-2 flex-wrap">
                            @if($isEndurance)
                            <span style="font-size:.65rem;font-weight:800;text-transform:uppercase;padding:2px 7px;border-radius:4px;background:#7c3aed22;color:#a78bfa;border:1px solid #7c3aed44">Endurance</span>
                            @endif
                            @if($isCustom)
                            <span style="font-size:.65rem;font-weight:800;text-transform:uppercase;padding:2px 7px;border-radius:4px;background:#f59e0b22;color:#fbbf24;border:1px solid #f59e0b44">Custom</span>
                            @endif
                        </div>
                    </td>
                    <td class="d-none d-sm-table-cell">
                        <div class="d-flex gap-1 flex-wrap">
                            @if($isEndurance)
                            <span style="font-size:.7rem;font-weight:800;text-transform:uppercase;padding:3px 9px;border-radius:5px;background:#7c3aed22;color:#a78bfa;border:1px solid #7c3aed44">Endurance</span>
                            @endif
                            @if($isCustom)
                            <span style="font-size:.7rem;font-weight:800;text-transform:uppercase;padding:3px 9px;border-radius:5px;background:#f59e0b22;color:#fbbf24;border:1px solid #f59e0b44">Custom</span>
                            @endif
                        </div>
                    </td>
                    <td class="d-none d-sm-table-cell">
                        <span class="badge text-white fw-bold"
                              style="background:{{ $race->gameColor() }};font-size:.7rem;padding:5px 10px;border-radius:6px">
                            {{ $race->gameLabel() }}
                        </span>
                    </td>
                    <td class="d-none d-md-table-cell text-secondary" style="font-size:.82rem" data-order="{{ $race->scheduled_at->timestamp }}">
                        {{ $race->scheduledAtUk()->format('d M Y') }}<br>
                        <span style="color:#9ca3af">{{ $race->scheduledAtUk()->format('H:i T') }}</span>
                    </td>
                    <td class="d-none d-lg-table-cell text-center fw-bold">
                        {{ $race->is_endurance ? $race->team_entries_count : $race->registrations_count }}{{ $race->max_drivers ? ' / ' . $race->max_drivers : '' }}
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
                        <div class="d-flex gap-1 gap-md-2 justify-content-end align-items-center flex-wrap">
                            <a href="{{ route('admin.races.show', $race) }}"
                               class="btn btn-sm fw-bold text-uppercase text-white"
                               style="background:#7c3aed;font-size:.72rem;padding:5px 12px;border-radius:6px">
                                Open
                            </a>
                            <form action="{{ route('admin.races.destroy', $race) }}" method="POST" style="margin:0">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                        onclick="xcDeleteSubmit(this.closest('form'), 'Delete event?', '\'{{ addslashes($race->title) }}\' and all registrations will be removed. Results are preserved.')"
                                        class="btn btn-sm fw-bold text-uppercase"
                                        style="background:#1f2937;border:1px solid #374151;color:#ef4444;font-size:.72rem;padding:5px 10px;border-radius:6px">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-secondary py-5" style="font-size:.9rem">
                        No special events yet. Create a custom race or mark a race as <strong>Endurance / Driver Swap</strong>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        let table;

        $(function () {
            table = $('#special-table').DataTable({
                pageLength: 25,
                order: [[3, 'desc']],
                columnDefs: [{ orderable: false, targets: [6] }],
                language: {
                    search: '',
                    searchPlaceholder: 'Search…',
                    lengthMenu: 'Show _MENU_ events',
                    info: 'Showing _START_ to _END_ of _TOTAL_ events',
                    infoEmpty: 'No events found',
                    zeroRecords: 'No matching events found',
                    paginate: { previous: '‹', next: '›' },
                },
            });

            filterStatus('upcoming');
        });

        const filterIds = {
            '':                 'filter-all',
            'ACC Console':      'filter-acc',
            'Le Mans Ultimate': 'filter-lmu',
            'iRacing':          'filter-iracing',
        };

        function filterGame(game) {
            table.column(2).search(game, false, false).draw();

            Object.entries(filterIds).forEach(([key, id]) => {
                const btn    = document.getElementById(id);
                const active = key === game;
                btn.style.background  = active ? '#111827' : '#f3f4f6';
                btn.style.borderColor = active ? '#111827' : '#e5e7eb';
                btn.style.color       = active ? 'white'   : '#374151';
            });
        }

        const statusFilterIds = { upcoming: 'status-upcoming', past: 'status-past' };

        function filterStatus(mode) {
            if (mode === 'past') {
                table.column(5).search('Finished', false, false);
                table.order([3, 'desc']);
            } else {
                table.column(5).search('^(?!.*Finished).*$', true, false);
                table.order([3, 'asc']);
            }
            table.draw();

            Object.entries(statusFilterIds).forEach(([key, id]) => {
                const btn    = document.getElementById(id);
                const active = key === mode;
                btn.style.background  = active ? '#111827' : '#f3f4f6';
                btn.style.borderColor = active ? '#111827' : '#e5e7eb';
                btn.style.color       = active ? 'white'   : '#374151';
            });
        }
    </script>
@endpush
