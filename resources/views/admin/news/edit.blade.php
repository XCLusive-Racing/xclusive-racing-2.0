@extends('layouts.admin')

@section('title', 'Edit Article')

@section('page-actions')
    <a href="{{ route('admin.news.index') }}" class="btn btn-sm fw-bold text-uppercase" style="background:#f3f4f6;border:1px solid #e5e7eb">
        ← Back
    </a>
@endsection

@section('content')

<form action="{{ route('admin.news.update', $newsArticle) }}" method="POST" id="article-form" enctype="multipart/form-data">
@csrf @method('PUT')

<div class="row g-4">

    {{-- Left: main content --}}
    <div class="col-lg-8">

        {{-- Title & Slug --}}
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Article</p>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title-input"
                           value="{{ old('title', $newsArticle->title) }}"
                           class="form-control @error('title') is-invalid @enderror">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Slug</label>
                    <input type="text" name="slug" id="slug-input"
                           value="{{ old('slug', $newsArticle->slug) }}"
                           class="form-control font-monospace @error('slug') is-invalid @enderror">
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-1">
                    <label class="form-label fw-bold" style="font-size:.82rem">Excerpt <span class="text-secondary fw-normal">(max 200 chars)</span></label>
                    <textarea name="excerpt" rows="2" maxlength="200"
                              class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt', $newsArticle->excerpt) }}</textarea>
                    @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Content</p>
                <textarea name="body" id="body" rows="20"
                          class="form-control rich-editor @error('body') is-invalid @enderror">{{ old('body', $newsArticle->body) }}</textarea>
                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

    </div>

    {{-- Right: meta --}}
    <div class="col-lg-4">

        {{-- Publish --}}
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Publish</p>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.82rem">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" id="status-select">
                        <option value="draft"     {{ old('status', $newsArticle->status) === 'draft'     ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $newsArticle->status) === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div id="published-at-wrap" style="{{ old('status', $newsArticle->status) === 'published' ? '' : 'display:none' }}">
                    <label class="form-label fw-bold" style="font-size:.82rem">Publish Date</label>
                    <input type="datetime-local" name="published_at"
                           value="{{ old('published_at', $newsArticle->published_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                           class="form-control @error('published_at') is-invalid @enderror">
                    @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn w-100 fw-bold text-uppercase text-white bg-xcl-purple">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

        {{-- Cover Image --}}
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Cover Image</p>
                <x-media-picker
                    name="cover_image"
                    label="Cover Image"
                    :current="old('cover_image_path', $newsArticle->cover_image)"
                    folder="news"
                    filterDefault="image" />
            </div>
        </div>

        {{-- Tags --}}
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Tags</p>
                @php $selectedTags = old('tags', $newsArticle->tags->pluck('id')->toArray()); @endphp
                <div class="d-flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <label class="d-flex align-items-center gap-1" style="cursor:pointer;font-size:.82rem">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                   {{ in_array($tag->id, $selectedTags) ? 'checked' : '' }}>
                            <span class="badge rounded-pill fw-bold" style="background:{{ $tag->color }}20;color:{{ $tag->color }};border:1px solid {{ $tag->color }}40">{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('tags')<div class="text-danger mt-2" style="font-size:.8rem">{{ $message }}</div>@enderror
                <a href="{{ route('admin.news.tags.index') }}" class="d-block mt-3" style="font-size:.75rem">+ Manage tags</a>
            </div>
        </div>

        {{-- Author (admin/owner only) --}}
        @if($authors->isNotEmpty())
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Author</p>
                <select name="author_id" class="form-select @error('author_id') is-invalid @enderror">
                    @foreach($authors as $author)
                        <option value="{{ $author->id }}"
                            {{ old('author_id', $newsArticle->author_id) == $author->id ? 'selected' : '' }}>
                            {{ $author->name }}{{ $author->id === auth()->id() ? ' (you)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('author_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        @endif

        {{-- Meta info --}}
        <div class="admin-card mb-4">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Info</p>
                <div style="font-size:.78rem;color:#6b7280">
                    <div>Created: {{ $newsArticle->created_at->format('d M Y, H:i') }}</div>
                    <div>Updated: {{ $newsArticle->updated_at->format('d M Y, H:i') }}</div>
                </div>
            </div>
        </div>

    </div>
</div>

</form>

@endsection

@push('scripts')
<script>
    const statusSelect    = document.getElementById('status-select');
    const publishedAtWrap = document.getElementById('published-at-wrap');

    statusSelect.addEventListener('change', () => {
        publishedAtWrap.style.display = statusSelect.value === 'published' ? '' : 'none';
    });


</script>
@endpush
