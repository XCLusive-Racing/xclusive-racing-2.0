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


{{-- Bulk delete form --}}
<form id="bulk-form" action="{{ route('admin.races.bulk-destroy') }}" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

{{-- Bulk action bar (hidden until selection) --}}
<div id="bulk-bar"
     style="display:none;background:#111827;border:1px solid #374151;border-radius:10px;padding:12px 20px;margin-bottom:16px;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <span id="bulk-count" style="color:#f9fafb;font-size:.85rem;font-weight:700"></span>
    <div class="d-flex gap-2">
        <button type="button" onclick="clearSelection()"
                style="background:#1f2937;border:1px solid #374151;color:#9ca3af;font-size:.78rem;font-weight:700;text-transform:uppercase;padding:6px 14px;border-radius:6px;cursor:pointer">
            Deselect All
        </button>
        <button type="button" onclick="confirmBulkDelete()"
                style="background:#dc2626;border:none;color:white;font-size:.78rem;font-weight:700;text-transform:uppercase;padding:6px 14px;border-radius:6px;cursor:pointer">
            Delete Selected
        </button>
    </div>
</div>

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
                    <th class="ps-4" style="width:36px">
                        <input type="checkbox" id="select-all"
                               style="width:15px;height:15px;cursor:pointer;accent-color:#7c3aed">
                    </th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Race</th>
                    <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Game</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Date</th>
                    <th class="fw-bold text-uppercase text-center d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Drivers</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Status</th>
                    <th class="pe-4" style="min-width:100px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($races as $race)
                <tr>
                    <td class="ps-4">
                        <input type="checkbox" class="race-checkbox" value="{{ $race->id }}"
                               style="width:15px;height:15px;cursor:pointer;accent-color:#7c3aed">
                    </td>
                    <td>
                        <div class="fw-bold text-dark">{{ $race->title }}</div>
                        <div class="text-secondary" style="font-size:.78rem">{{ $race->track }}</div>
                        {{-- Mobile: game badge + date inline --}}
                        <div class="d-sm-none mt-1 d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge text-white fw-bold"
                                  style="background:{{ $race->gameColor() }};font-size:.65rem;padding:3px 7px;border-radius:5px">
                                {{ $race->gameLabel() }}
                            </span>
                            <span style="font-size:.72rem;color:#9ca3af">{{ $race->scheduledAtUk()->format('d M Y') }}</span>
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
                order: [[3, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [0, 6] },
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

        // Select all (across all pages)
        document.getElementById('select-all').addEventListener('change', function () {
            document.querySelectorAll('.race-checkbox').forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('race-checkbox')) {
                const all   = document.querySelectorAll('.race-checkbox');
                const checked = document.querySelectorAll('.race-checkbox:checked');
                document.getElementById('select-all').indeterminate = checked.length > 0 && checked.length < all.length;
                document.getElementById('select-all').checked = checked.length === all.length && all.length > 0;
                updateBulkBar();
            }
        });

        function updateBulkBar() {
            const count = document.querySelectorAll('.race-checkbox:checked').length;
            const bar   = document.getElementById('bulk-bar');
            bar.style.display = count > 0 ? 'flex' : 'none';
            document.getElementById('bulk-count').textContent =
                count + ' event' + (count !== 1 ? 's' : '') + ' selected';
        }

        function clearSelection() {
            document.querySelectorAll('.race-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('select-all').checked = false;
            document.getElementById('select-all').indeterminate = false;
            updateBulkBar();
        }

        async function confirmBulkDelete() {
            const checked = document.querySelectorAll('.race-checkbox:checked');
            if (!checked.length) return;
            const count = checked.length;

            const result = await Swal.fire({
                title: 'Delete ' + count + ' event' + (count !== 1 ? 's' : '') + '?',
                text: 'All registrations will be removed. Results are preserved.',
                icon: 'warning',
                background: '#111827',
                color: '#f9fafb',
                iconColor: '#ef4444',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#C8FF00',
                reverseButtons: true,
                focusCancel: true,
                didOpen: (popup) => {
                    popup.style.border = '1px solid #374151';
                    popup.style.borderRadius = '12px';
                    const cancel = popup.querySelector('.swal2-cancel');
                    if (cancel) { cancel.style.color = '#0B0B1A'; cancel.style.fontWeight = '800'; }
                },
            });
            if (!result.isConfirmed) return;

            const form = document.getElementById('bulk-form');
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = 'ids[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            form.submit();
        }

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
    </script>
@endpush
