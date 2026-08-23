@extends('layouts.admin')

@section('title', 'Special Events')
@section('page-title', 'Special Events')

@section('page-actions')
    <a href="{{ route('admin.races.custom-create') }}" class="btn btn-sm fw-bold text-uppercase text-white"
       style="background:#7c3aed;font-size:.78rem;border-radius:6px;padding:6px 14px">
        + Custom Race
    </a>
@endsection

@section('content')

<div class="admin-card">

    <div class="px-4 py-3 border-bottom" style="background:#111827;border-radius:12px 12px 0 0">
        <p class="mb-0 text-secondary" style="font-size:.82rem">
            Endurance races and custom events (no standard format). Use the <strong style="color:#e5e7eb">Endurance / Driver Swap</strong> toggle on any race to include it here.
        </p>
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
                        <a href="{{ route('admin.races.show', $race) }}"
                           class="btn btn-sm fw-bold text-uppercase text-white"
                           style="background:#7c3aed;font-size:.72rem;padding:5px 12px;border-radius:6px">
                            Open
                        </a>
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

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#special-table').DataTable({
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
        });
    </script>
@endpush
