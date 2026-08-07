@extends('layouts.app')

@section('title', 'Inbox - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:720px;position:relative;z-index:1">

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

        <form id="bulk-form" method="POST" action="{{ route('messages.bulk-destroy') }}">
            @csrf
            @method('DELETE')

            {{-- Toolbar --}}
            <div class="d-flex align-items-center gap-3 mb-3">
                <label class="d-flex align-items-center gap-2 mb-0 user-select-none" style="cursor:pointer;font-size:.83rem;font-weight:600;color:#374151">
                    <input type="checkbox" id="select-all" class="form-check-input m-0" style="width:16px;height:16px;cursor:pointer">
                    Select all
                </label>
                <button type="submit" id="bulk-delete-btn"
                        class="btn btn-sm fw-bold text-white ms-auto"
                        style="background:#dc2626;display:none"
                        onclick="return confirm('Delete selected messages?')">
                    <i class="fa-solid fa-trash me-1"></i>
                    Delete selected (<span id="selected-count">0</span>)
                </button>
            </div>

            {{-- Message list --}}
            <div class="d-flex flex-column gap-2">
                @foreach($inbox as $entry)
                    @if($entry['kind'] === 'message')
                        @php $msg = $entry['item']; @endphp
                        <div class="d-flex align-items-center gap-2">
                            <input type="checkbox"
                                   name="ids[]"
                                   value="{{ $msg->id }}"
                                   class="inbox-cb form-check-input m-0 flex-shrink-0"
                                   style="width:16px;height:16px;cursor:pointer">
                            <a href="{{ route('messages.show', $msg) }}" class="text-decoration-none flex-grow-1">
                                <div class="bg-white rounded-3 shadow-sm px-4 py-3 d-flex align-items-center gap-3 inbox-row {{ $entry['unread'] ? 'inbox-row--unread' : '' }}">
                                    <div class="flex-shrink-0" style="color:{{ $msg->typeColor() }};width:28px;text-align:center">
                                        <i class="fa-solid {{ $msg->typeIcon() }}" style="font-size:1.05rem"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($entry['unread'])<span class="inbox-unread-dot"></span>@endif
                                            <span class="fw-bold text-dark text-truncate" style="font-size:.9rem">{{ $msg->title }}</span>
                                        </div>
                                        <p class="text-secondary mb-0 text-truncate" style="font-size:.8rem">{{ Str::limit($msg->body, 80) }}</p>
                                    </div>
                                    <div class="text-secondary flex-shrink-0" style="font-size:.75rem;white-space:nowrap">
                                        {{ $msg->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </a>
                        </div>
                    @else
                        @php $ann = $entry['item']; @endphp
                        <div class="d-flex align-items-center gap-2">
                            {{-- Placeholder so announcements align with messages --}}
                            <span style="width:16px;flex-shrink:0"></span>
                            <a href="{{ route('announcements.show', $ann) }}" class="text-decoration-none flex-grow-1">
                                <div class="bg-white rounded-3 shadow-sm px-4 py-3 d-flex align-items-center gap-3 inbox-row {{ $entry['unread'] ? 'inbox-row--unread' : '' }}">
                                    <div class="flex-shrink-0" style="color:#db2877;width:28px;text-align:center">
                                        <i class="fa-solid fa-newspaper" style="font-size:1.05rem"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($entry['unread'])<span class="inbox-unread-dot"></span>@endif
                                            <span class="fw-bold text-dark text-truncate" style="font-size:.9rem">{{ $ann->title }}</span>
                                        </div>
                                        <p class="text-secondary mb-0 text-truncate" style="font-size:.8rem">{{ Str::limit($ann->body, 80) }}</p>
                                    </div>
                                    <div class="text-secondary flex-shrink-0" style="font-size:.75rem;white-space:nowrap">
                                        {{ $ann->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </form>

        @endif
    </div>
</main>

<style>
.inbox-row {
    border-left: 3px solid transparent;
    transition: box-shadow .15s, border-color .15s;
}
.inbox-row:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08) !important; }
.inbox-row--unread { border-left-color: #7c3aed; background: #faf8ff !important; }
.inbox-unread-dot {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #7c3aed;
    flex-shrink: 0;
}
</style>

<script>
(function () {
    const selectAll = document.getElementById('select-all');
    const deleteBtn = document.getElementById('bulk-delete-btn');
    const countSpan = document.getElementById('selected-count');
    const checkboxes = () => [...document.querySelectorAll('.inbox-cb')];

    function update() {
        const all     = checkboxes();
        const checked = all.filter(c => c.checked);
        countSpan.textContent      = checked.length;
        deleteBtn.style.display    = checked.length > 0 ? '' : 'none';
        selectAll.checked          = checked.length === all.length && all.length > 0;
        selectAll.indeterminate    = checked.length > 0 && checked.length < all.length;
    }

    selectAll.addEventListener('change', function () {
        checkboxes().forEach(c => c.checked = this.checked);
        update();
    });

    document.querySelectorAll('.inbox-cb').forEach(c => c.addEventListener('change', update));
})();
</script>
@endsection
