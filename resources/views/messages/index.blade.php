@extends('layouts.app')

@section('title', 'Inbox - ' . config('xcl.name'))

@push('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:920px;position:relative;z-index:1">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">Inbox</h1>
                <p class="text-secondary mb-0">Your messages and announcements</p>
            </div>
        </div>

        @if(session('success'))
        <div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#dc2626">
            {{ session('error') }}
        </div>
        @endif

        @if($inbox->isEmpty())
        <div class="bg-white rounded-3 shadow-sm p-5 text-center">
            <i class="fa-regular fa-envelope-open" style="font-size:2.5rem;color:#d1d5db"></i>
            <p class="text-secondary mt-3 mb-0">No messages yet.</p>
        </div>
        @else

        {{-- Bulk action bar (hidden until selection) --}}
        <div id="bulk-bar"
             style="display:none;background:#111827;border:1px solid #374151;border-radius:10px;padding:12px 20px;margin-bottom:16px;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <span id="bulk-count" style="color:#f9fafb;font-size:.85rem;font-weight:700"></span>
            <div class="d-flex gap-2">
                <button type="button" onclick="clearSelection()"
                        style="background:#1f2937;border:1px solid #374151;color:#9ca3af;font-size:.78rem;font-weight:700;text-transform:uppercase;padding:6px 14px;border-radius:6px;cursor:pointer">
                    Deselect All
                </button>
                <button type="button" onclick="submitBulkDelete()"
                        style="background:#dc2626;border:none;color:white;font-size:.78rem;font-weight:700;text-transform:uppercase;padding:6px 14px;border-radius:6px;cursor:pointer">
                    Remove Selected
                </button>
            </div>
        </div>

        <form id="bulk-form" method="POST" action="{{ route('messages.bulk-destroy') }}" style="display:none">
            @csrf
            @method('DELETE')
        </form>

        <div class="admin-card bg-white rounded-3 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table id="inbox-table" class="table table-hover align-middle mb-0 w-100" style="font-size:.875rem">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <tr>
                            <th class="ps-4" style="width:36px">
                                <input type="checkbox" id="select-all"
                                       style="width:15px;height:15px;cursor:pointer;accent-color:#7c3aed">
                            </th>
                            <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Item</th>
                            <th class="fw-bold text-uppercase d-none d-sm-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Type</th>
                            <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Date</th>
                            <th class="fw-bold text-uppercase text-center" style="font-size:.72rem;letter-spacing:.06em;color:#9ca3af">Status</th>
                            <th class="pe-4" style="min-width:90px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inbox as $entry)
                            @php
                                $isMessage = $entry['kind'] === 'message';
                                $item      = $entry['item'];
                                $showRoute = $isMessage ? route('messages.show', $item) : route('announcements.show', $item);
                                $destroyRoute = $isMessage ? route('messages.destroy', $item) : route('announcements.destroy', $item);
                                $cbName    = $isMessage ? 'message_ids[]' : 'announcement_ids[]';
                                $icon      = $isMessage ? $item->typeIcon() : 'fa-newspaper';
                                $color     = $isMessage ? $item->typeColor() : '#db2877';
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <input type="checkbox" class="inbox-cb" name="{{ $cbName }}" value="{{ $item->id }}"
                                           style="width:15px;height:15px;cursor:pointer;accent-color:#7c3aed">
                                </td>
                                <td>
                                    <a href="{{ $showRoute }}" class="text-decoration-none d-flex align-items-center gap-2">
                                        <span style="color:{{ $color }};width:22px;text-align:center;flex-shrink:0">
                                            <i class="fa-solid {{ $icon }}"></i>
                                        </span>
                                        <span class="overflow-hidden">
                                            <span class="d-flex align-items-center gap-2">
                                                @if($entry['unread'])<span class="inbox-unread-dot"></span>@endif
                                                <span class="fw-bold text-dark">{{ $item->title }}</span>
                                            </span>
                                            <div class="text-secondary text-truncate" style="font-size:.78rem;max-width:420px">{{ Str::limit($item->body, 90) }}</div>
                                        </span>
                                    </a>
                                </td>
                                <td class="d-none d-sm-table-cell">
                                    <span class="badge text-white fw-bold" style="background:{{ $color }};font-size:.68rem;padding:4px 9px;border-radius:6px">
                                        {{ $isMessage ? 'Message' : 'News' }}
                                    </span>
                                </td>
                                <td class="d-none d-md-table-cell text-secondary" style="font-size:.82rem" data-order="{{ $entry['date']->timestamp }}">
                                    {{ $entry['date']->format('d M Y') }}<br>
                                    <span style="color:#9ca3af">{{ $entry['date']->format('H:i') }}</span>
                                </td>
                                <td class="text-center">
                                    @if($entry['unread'])
                                        <span class="badge fw-bold" style="background:rgba(124,58,237,.1);color:#7c3aed;font-size:.68rem;padding:4px 9px;border-radius:6px">Unread</span>
                                    @else
                                        <span class="badge fw-bold" style="background:#f3f4f6;color:#9ca3af;font-size:.68rem;padding:4px 9px;border-radius:6px">Read</span>
                                    @endif
                                </td>
                                <td class="pe-4">
                                    <form action="{{ $destroyRoute }}" method="POST" style="margin:0"
                                          onsubmit="return confirm('Remove this from your inbox?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm fw-bold text-uppercase"
                                                style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.7rem;padding:5px 10px;border-radius:6px">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @endif
    </div>
</main>

<style>
.inbox-unread-dot {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #7c3aed;
    flex-shrink: 0;
}
</style>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        let table;

        $(function () {
            table = $('#inbox-table').DataTable({
                pageLength: 15,
                order: [[3, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [0, 5] },
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search inbox…',
                    lengthMenu: 'Show _MENU_ items',
                    info: 'Showing _START_ to _END_ of _TOTAL_ items',
                    infoEmpty: 'No items found',
                    zeroRecords: 'No matching items found',
                    paginate: { previous: '‹', next: '›' },
                },
            });
        });

        document.getElementById('select-all')?.addEventListener('change', function () {
            document.querySelectorAll('.inbox-cb').forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('inbox-cb')) {
                const all     = document.querySelectorAll('.inbox-cb');
                const checked = document.querySelectorAll('.inbox-cb:checked');
                const selectAll = document.getElementById('select-all');
                selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
                selectAll.checked       = checked.length === all.length && all.length > 0;
                updateBulkBar();
            }
        });

        function updateBulkBar() {
            const count = document.querySelectorAll('.inbox-cb:checked').length;
            const bar   = document.getElementById('bulk-bar');
            bar.style.display = count > 0 ? 'flex' : 'none';
            document.getElementById('bulk-count').textContent =
                count + ' item' + (count !== 1 ? 's' : '') + ' selected';
        }

        function clearSelection() {
            document.querySelectorAll('.inbox-cb').forEach(cb => cb.checked = false);
            const selectAll = document.getElementById('select-all');
            selectAll.checked = false;
            selectAll.indeterminate = false;
            updateBulkBar();
        }

        function submitBulkDelete() {
            const checked = document.querySelectorAll('.inbox-cb:checked');
            if (!checked.length) return;
            if (!confirm('Remove ' + checked.length + ' item(s) from your inbox?')) return;

            const form = document.getElementById('bulk-form');
            form.querySelectorAll('input[name="message_ids[]"], input[name="announcement_ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = cb.name;
                input.value = cb.value;
                form.appendChild(input);
            });
            form.submit();
        }
    </script>
@endpush