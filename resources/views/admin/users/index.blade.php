@extends('layouts.admin')

@section('title', 'User Management')
@section('page-title', 'User Management')

@push('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        #users-table_wrapper .dataTables_filter input { border:1px solid #e5e7eb;border-radius:8px;padding:6px 12px;font-size:.85rem;outline:none;transition:border-color .15s,box-shadow .15s; }
        #users-table_wrapper .dataTables_filter input:focus { border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1); }
        #users-table_wrapper .dataTables_length select { border:1px solid #e5e7eb;border-radius:8px;padding:5px 28px 5px 10px;font-size:.85rem; }
        #users-table_wrapper .dataTables_info, #users-table_wrapper .dataTables_length, #users-table_wrapper .dataTables_filter { font-size:.8rem;color:#9ca3af;padding:1rem 1.5rem; }
        #users-table_wrapper .dataTables_paginate { padding:.75rem 1.5rem; }
        #users-table_wrapper .dataTables_paginate .paginate_button { border-radius:6px!important;font-size:.78rem;font-weight:700;padding:4px 10px!important;border:none!important; }
        #users-table_wrapper .dataTables_paginate .paginate_button.current { background:#7c3aed!important;color:white!important; }
        #users-table_wrapper .dataTables_paginate .paginate_button:hover:not(.current) { background:#f3f4f6!important;color:#374151!important; }
        div.dataTables_wrapper div.dataTables_filter { text-align:right; }
    </style>
@endpush

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach(['super_admin'=>['Super Admins','#7c3aed','#f3e8ff'],'admin'=>['Admins','#db2777','#fce7f3'],'manager'=>['Managers','#2563eb','#dbeafe'],'driver'=>['Drivers','#059669','#d1fae5']] as $role=>[$label,$color,$bg])
    <div class="col-sm-6 col-xl-3">
        <div class="metric-card">
            <div class="metric-icon" style="background:{{ $bg }}">
                <svg width="22" height="22" fill="none" stroke="{{ $color }}" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <div class="metric-value">{{ $users->where('role',$role)->count() }}</div>
                <div class="metric-label">{{ $label }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table id="users-table" class="table table-hover align-middle mb-0 w-100" style="font-size:.875rem">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Driver</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">ID</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Rating</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">SR</th>
                    <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Flag</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Team / Quote</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Role</th>
                    <th class="pe-4" style="min-width:100px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            @if($user->banner)
                                <img src="{{ $user->banner }}" alt=""
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
                    <td>
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
                    <td class="text-center fw-bold" style="color:#7c3aed;font-size:.85rem">
                        {{ $user->elo_acc ?? '—' }}
                    </td>
                    <td class="text-center fw-bold" style="font-size:.85rem">
                        @if($user->sr_acc)
                            @php $grade = $user->srGrade('acc'); @endphp
                            <span style="color:{{ $grade['color'] }}">{{ number_format($user->sr_acc, 2) }}</span>
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>
                    <td class="text-center" style="font-size:.85rem">
                        {{ $user->flag ? strtoupper($user->flag) : '—' }}
                    </td>
                    <td class="text-secondary" style="font-size:.82rem">{{ $user->team ?? '—' }}</td>
                    <td>
                        @php
                            $rc = ['super_admin'=>['#f3e8ff','#7c3aed'],'admin'=>['#fce7f3','#db2777'],'manager'=>['#dbeafe','#2563eb'],'driver'=>['#d1fae5','#059669']][$user->role] ?? ['#f3f4f6','#374151'];
                        @endphp
                        <span class="badge fw-bold" style="background:{{ $rc[0] }};color:{{ $rc[1] }};font-size:.7rem;padding:4px 10px;border-radius:6px">
                            {{ ucfirst(str_replace('_',' ',$user->role)) }}
                        </span>
                    </td>
                    <td class="pe-4">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="btn btn-sm btn-outline-secondary fw-bold text-uppercase"
                               style="font-size:.72rem;padding:5px 12px;border-radius:6px">
                                Edit
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                  onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                                        style="font-size:.72rem;padding:5px 12px;border-radius:6px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca">
                                    Delete
                                </button>
                            </form>
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
        $(function () {
            $('#users-table').DataTable({
                pageLength: 25,
                order: [[6, 'asc'], [0, 'asc']],
                columnDefs: [{ orderable: false, targets: 7 }],
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