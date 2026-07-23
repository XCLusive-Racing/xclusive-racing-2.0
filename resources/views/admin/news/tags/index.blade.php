@extends('layouts.admin')

@section('title', 'News Tags')

@section('page-actions')
    <a href="{{ route('admin.news.index') }}" class="btn btn-sm fw-bold text-uppercase" style="background:#f3f4f6;border:1px solid #e5e7eb">
        ← Articles
    </a>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4 rounded-2" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">

    {{-- Tag list --}}
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <tr>
                            <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Tag</th>
                            <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Colour</th>
                            <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Articles</th>
                            <th class="fw-bold text-uppercase pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tags as $tag)
                        <tr>
                            <td class="ps-4">
                                <span class="badge rounded-pill fw-bold" style="background:{{ $tag->color }}20;color:{{ $tag->color }};border:1px solid {{ $tag->color }}40">
                                    {{ $tag->name }}
                                </span>
                                <div class="text-secondary mt-1" style="font-size:.72rem">{{ $tag->slug }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle flex-shrink-0" style="width:16px;height:16px;background:{{ $tag->color }}"></div>
                                    <span class="font-monospace" style="font-size:.78rem">{{ $tag->color }}</span>
                                </div>
                            </td>
                            <td style="font-size:.82rem;color:#6b7280">{{ $tag->articles_count }}</td>
                            <td class="pe-4">
                                <form action="{{ route('admin.news.tags.destroy', $tag) }}" method="POST"
                                      onsubmit="return confirm('Delete tag \'{{ $tag->name }}\'?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm fw-bold text-uppercase"
                                            style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.7rem">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-secondary" style="font-size:.875rem">No tags yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Create tag --}}
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">New Tag</p>

                <form action="{{ route('admin.news.tags.store') }}" method="POST">
                @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Announcements">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">Colour <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="color" name="color" value="{{ old('color', '#7c3aed') }}"
                                   class="form-control form-control-color @error('color') is-invalid @enderror"
                                   style="width:48px;height:38px;padding:2px">
                            <input type="text" id="color-hex" value="{{ old('color', '#7c3aed') }}"
                                   class="form-control font-monospace" placeholder="#7c3aed" style="max-width:110px"
                                   readonly>
                        </div>
                        @error('color')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn w-100 fw-bold text-uppercase text-white bg-xcl-purple">
                        Create Tag
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    const colorPicker = document.querySelector('input[type="color"]');
    const colorHex    = document.getElementById('color-hex');

    colorPicker.addEventListener('input', () => { colorHex.value = colorPicker.value; });
</script>
@endpush
