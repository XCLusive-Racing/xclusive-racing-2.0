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
                    <th class="fw-bold text-uppercase text-center d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Rating</th>
                    <th class="fw-bold text-uppercase text-center d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">SR</th>
                    <th class="fw-bold text-uppercase d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Team / Quote</th>
                    <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Role</th>
                    <th class="pe-4" style="min-width:100px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            @if($user->banner)
                                <img src="{{ $user->avatarUrl() }}" alt=""
                                     class="rounded-circle flex-shrink-0"
                                     style="width:32px;height:32px;object-fit:cover">
                            @else
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0"
                                     style="width:32px;height:32px;font-size:.75rem;background:linear-gradient(135deg,#7c3aed,#db2777)">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="fw-bold text-dark">{{ $user->name }}</div>
                                <div class="text-secondary" style="font-size:.72rem">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        @if($user->platform_id)
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge fw-bold" style="background:#f3f4f6;color:#6b7280;font-size:.65rem;padding:2px 6px">
                                    {{ strtoupper($user->platform ?? '?') }}
                                </span>
                                <code style="font-size:.75rem;color:#374151">{{ $user->platform_id }}</code>
                            </div>
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>
                    <td class="text-center fw-bold d-none d-sm-table-cell" style="color:#7c3aed;font-size:.85rem">
                        {{ $user->elo_acc ?? '—' }}
                    </td>
                    <td class="text-center fw-bold d-none d-md-table-cell" style="font-size:.85rem">
                        @if($user->sr_acc)
                            @php $grade = $user->srGrade('acc'); @endphp
                            <span style="color:{{ $grade['color'] }}">{{ number_format($user->sr_acc, 2) }}</span>
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>
                    <td class="text-secondary d-none d-lg-table-cell" style="font-size:.82rem">{{ $user->team ?? '—' }}</td>
                    <td class="d-none d-sm-table-cell">
                        @php
                            $roleColors = ['owner'=>['#f3e8ff','#7c3aed'],'admin'=>['#fce7f3','#db2777'],'moderator'=>['#dbeafe','#2563eb'],'event_manager'=>['#fef3c7','#d97706'],'steward'=>['#e0f2fe','#0891b2'],'driver'=>['#d1fae5','#059669']];
                        @endphp
                        <div class="d-flex flex-wrap gap-1">
                        @foreach($user->roles->sortBy('id') as $role)
                            @php $rc = $roleColors[$role->slug] ?? ['#f3f4f6','#374151']; @endphp
                            <span class="badge fw-bold" style="background:{{ $rc[0] }};color:{{ $rc[1] }};font-size:.7rem;padding:4px 8px;border-radius:6px">
                                {{ $role->name }}
                            </span>
                        @endforeach
                        </div>
                    </td>
                    <td class="pe-4">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="btn btn-sm btn-outline-secondary fw-bold text-uppercase"
                               style="font-size:.72rem;padding:5px 12px;border-radius:6px">
                                Edit
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
        $(function () {
            $('#users-table').DataTable({
                pageLength: 25,
                order: [[5, 'asc'], [0, 'asc']],
                columnDefs: [{ orderable: false, targets: 6 }],
                language: {
                    search: '', searchPlaceholder: 'Search users…',
                    lengthMenu: 'Show _MENU_ users',
                    info: 'Showing _START_ to _END_ of _TOTAL_ users',
                    paginate: { previous: '‹', next: '›' },
                },
            });
        });
    </script>
@endpush