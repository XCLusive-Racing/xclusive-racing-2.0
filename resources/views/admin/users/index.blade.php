@extends('layouts.admin')

@section('title', 'User Management')
@section('page-title', 'User Management')

@push('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

<div class="admin-card">
    <div class="table-responsive">
        <table id="users-table" class="table table-hover align-middle mb-0 w-100" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">ID</th>
                    <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Connected</th>
                    <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Status</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Joined</th>
                    <th class="fw-bold text-uppercase d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Team / Quote</th>
                    <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Role</th>
                    <th class="pe-4" style="min-width:100px"></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            const table = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.users.data') }}',
                    data: (d) => { d.roles_only = $('#filter-roles-only').is(':checked') ? 1 : 0; },
                },
                pageLength: 25,
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: 0, className: 'ps-4' },
                    { targets: 1, className: 'd-none d-md-table-cell' },
                    { targets: 2, className: 'd-none d-sm-table-cell', orderable: false },
                    { targets: 3, className: 'd-none d-sm-table-cell', orderable: false },
                    { targets: 4, className: 'd-none d-md-table-cell' },
                    { targets: 5, className: 'd-none d-lg-table-cell' },
                    { targets: 6, className: 'd-none d-sm-table-cell', orderable: false },
                    { targets: 7, className: 'pe-4', orderable: false },
                ],
                language: {
                    search: '', searchPlaceholder: 'Search users…',
                    lengthMenu: 'Show _MENU_ users',
                    info: 'Showing _START_ to _END_ of _TOTAL_ users',
                    paginate: { previous: '‹', next: '›' },
                    processing: 'Loading…',
                },
            });

            $('#filter-roles-only').on('change', () => table.ajax.reload());
        });
    </script>
@endpush