@extends('layouts.admin')

@section('title', 'News Articles')

@section('page-actions')
    <a href="{{ route('admin.news.create') }}" class="btn btn-sm fw-bold text-uppercase text-white bg-xcl-purple">
        + New Article
    </a>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4 rounded-2" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filters --}}
<div class="admin-card mb-4">
    <form method="GET" action="{{ route('admin.news.index') }}" class="d-flex flex-wrap align-items-end gap-3 px-4 py-3">
        <div>
            <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Status</label>
            <select name="status" class="form-select form-select-sm" style="min-width:130px">
                <option value="">All</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
            </select>
        </div>
        <div>
            <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Tag</label>
            <select name="tag" class="form-select form-select-sm" style="min-width:150px">
                <option value="">All Tags</option>
                @foreach($tags as $tag)
                    <option value="{{ $tag->slug }}" {{ request('tag') === $tag->slug ? 'selected' : '' }}>{{ $tag->name }}</option>
                @endforeach
            </select>
        </div>
        @if($authors->isNotEmpty())
        <div>
            <label class="form-label mb-1" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Author</label>
            <select name="author" class="form-select form-select-sm" style="min-width:160px">
                <option value="">All Authors</option>
                @foreach($authors as $author)
                    <option value="{{ $author->id }}" {{ request('author') == $author->id ? 'selected' : '' }}>{{ $author->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm fw-bold text-uppercase" style="background:#f3f4f6;border:1px solid #e5e7eb">Filter</button>
            <a href="{{ route('admin.news.index') }}" class="btn btn-sm fw-bold text-uppercase" style="background:#f3f4f6;border:1px solid #e5e7eb">Reset</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 w-100">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                <tr>
                    <th class="fw-bold text-uppercase ps-4" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Title</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Author</th>
                    <th class="fw-bold text-uppercase d-none d-lg-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Tags</th>
                    <th class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Status</th>
                    <th class="fw-bold text-uppercase d-none d-md-table-cell" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Published</th>
                    <th class="fw-bold text-uppercase pe-4" style="font-size:.72rem;letter-spacing:.06em;color:#6b7280">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark" style="font-size:.875rem">{{ $article->title }}</div>
                        @if($article->excerpt)
                            <div class="text-secondary" style="font-size:.75rem;max-width:360px" class="text-truncate">{{ Str::limit($article->excerpt, 80) }}</div>
                        @endif
                    </td>
                    <td class="d-none d-md-table-cell" style="font-size:.82rem">{{ $article->author->name ?? '—' }}</td>
                    <td class="d-none d-lg-table-cell">
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($article->tags as $tag)
                                <span class="badge rounded-pill fw-bold" style="background:{{ $tag->color }}20;color:{{ $tag->color }};border:1px solid {{ $tag->color }}40;font-size:.65rem">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        @if($article->status === 'published')
                            <span class="badge fw-bold rounded-pill" style="background:#dcfce7;color:#16a34a;font-size:.7rem">Published</span>
                        @else
                            <span class="badge fw-bold rounded-pill" style="background:#f3f4f6;color:#6b7280;font-size:.7rem">Draft</span>
                        @endif
                    </td>
                    <td class="d-none d-md-table-cell" style="font-size:.82rem;color:#6b7280">
                        {{ $article->published_at?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="pe-4">
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.news.edit', $article) }}"
                               class="btn btn-sm fw-bold text-uppercase"
                               style="background:#f3f4f6;border:1px solid #e5e7eb;font-size:.7rem">Edit</a>
                            <form action="{{ route('admin.news.destroy', $article) }}" method="POST"
                                  onsubmit="return confirm('Delete this article?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm fw-bold text-uppercase"
                                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:.7rem">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-secondary" style="font-size:.875rem">No articles found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
    <div class="px-4 py-3 border-top">
        {{ $articles->links() }}
    </div>
    @endif
</div>

@endsection
