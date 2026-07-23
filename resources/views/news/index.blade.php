@extends('layouts.app')

@section('title', 'News — ' . config('xcl.name'))
@section('no-sidebar')

@push('head')
<style>
/* ── News page – TRTN red #cc0000 accent ────────────────────────── */
.news-page {
    background-color: #ffffff;
    background-image: url('/topo.png');
    background-attachment: fixed;
    background-repeat: repeat;
    background-size: auto;
    min-height: 100vh;
    padding-top: 100px; /* offset fixed navbar */
}

/* Hero bar */
.news-hero {
    border-left: 4px solid #cc0000;
    padding: 1.5rem 0 1.5rem 1.5rem;
    margin-bottom: 2rem;
}
.news-hero__heading {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 900;
    text-transform: uppercase;
    font-style: italic;
    color: #0d0d14;
    letter-spacing: -.02em;
    line-height: 1;
}
.news-trtn-badge {
    background: #cc0000;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .35rem .9rem;
}
.news-trtn-powered {
    color: #cc0000;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
}

/* Search bar */
.news-search-wrap {
    position: relative;
}
.news-search-wrap .search-icon {
    position: absolute;
    left: .9rem;
    top: 50%;
    transform: translateY(-50%);
    color: #cc0000;
    pointer-events: none;
}
.news-search {
    padding-left: 2.4rem !important;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    transition: border-color .2s;
}
.news-search:focus {
    outline: none;
    border-color: #cc0000;
    box-shadow: 0 0 0 3px rgba(204,0,0,.08);
}

/* Tag pills */
.news-tag-pill {
    display: inline-block;
    padding: .3rem .9rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    text-decoration: none;
    border: 1.5px solid #d1d5db;
    color: #374151;
    background: #fff;
    transition: border-color .15s, color .15s;
    white-space: nowrap;
}
.news-tag-pill:hover {
    border-color: #cc0000;
    color: #cc0000;
}
.news-tag-pill.active {
    background: #cc0000;
    border-color: #cc0000;
    color: #fff;
}

/* Article cards */
.news-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    border-top: 3px solid transparent;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    transition: border-top-color .2s, box-shadow .2s, transform .2s;
}
.news-card:hover {
    border-top-color: #cc0000;
    box-shadow: 0 8px 32px rgba(0,0,0,.10);
    transform: translateY(-2px);
    color: inherit;
}
.news-card__cover {
    width: 100%;
    aspect-ratio: 16/9;
    object-fit: cover;
    background: #f3f4f6;
}
.news-card__cover-placeholder {
    width: 100%;
    aspect-ratio: 16/9;
    background: linear-gradient(135deg, #0d0d14 0%, #1f1f2e 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}
.news-card__body {
    padding: 1.25rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.news-card__primary-tag {
    display: inline-block;
    background: #cc0000;
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .2rem .6rem;
    border-radius: 4px;
    margin-bottom: .6rem;
}
.news-card__other-tag {
    display: inline-block;
    background: #374151;
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .2rem .5rem;
    border-radius: 4px;
    margin-bottom: .6rem;
}
.news-card__title {
    font-size: 1rem;
    font-weight: 800;
    color: #0d0d14;
    line-height: 1.35;
    margin-bottom: .5rem;
    flex: 1;
}
.news-card__excerpt {
    font-size: .82rem;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: .75rem;
}
.news-card__meta {
    font-size: .72rem;
    color: #9ca3af;
    margin-bottom: .75rem;
}
.news-card__read-more {
    font-size: .78rem;
    font-weight: 700;
    color: #cc0000;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.news-card__read-more:hover {
    color: #aa0000;
}

/* Pagination */
.news-pagination .page-link {
    border-color: #e5e7eb;
    color: #374151;
    font-weight: 600;
}
.news-pagination .page-item.active .page-link {
    background: #cc0000;
    border-color: #cc0000;
    color: #fff;
}
.news-pagination .page-link:hover {
    border-color: #cc0000;
    color: #cc0000;
    background: #fff8f8;
}

/* NEWS nav link accent */
.xcl-nav-news:hover,
.xcl-nav-news.active {
    color: #cc0000 !important;
    text-decoration: underline;
    text-underline-offset: 4px;
}
</style>
@endpush

@section('content')
<div class="news-page">
<div class="container-xl px-3 px-md-4 py-5" style="position:relative;z-index:1">

    {{-- Hero bar --}}
    <div class="news-hero d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="news-hero__heading mb-1">NEWS</h1>
            <p class="mb-0" style="color:#6b7280;font-size:.85rem">Latest from XCLusive Racing</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="news-trtn-badge">
                <img src="/images/trtn/TRTN Logo 1.png" alt="TRTN" height="22" style="object-fit:contain;filter:brightness(0) invert(1)">
            </div>
            <span class="news-trtn-powered">Powered by TRTN</span>
        </div>
    </div>

    {{-- Search + filters --}}
    <div class="row g-3 mb-4 align-items-center">
        <div class="col-md-5">
            <form method="GET" action="{{ route('news.index') }}">
                @if($activeTag)
                    <input type="hidden" name="tag" value="{{ $activeTag }}">
                @endif
                <div class="news-search-wrap">
                    <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control news-search"
                           placeholder="Search articles…">
                </div>
            </form>
        </div>
        <div class="col-md-7">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('news.index', request()->except('tag')) }}"
                   class="news-tag-pill {{ !$activeTag ? 'active' : '' }}">All</a>
                @foreach($tags as $tag)
                    <a href="{{ route('news.index', array_merge(request()->except('tag'), $activeTag === $tag->slug ? [] : ['tag' => $tag->slug])) }}"
                       class="news-tag-pill {{ $activeTag === $tag->slug ? 'active' : '' }}">
                        {{ $tag->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Article grid --}}
    @if($articles->isEmpty())
        <div class="text-center py-5" style="color:#9ca3af">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3 d-block mx-auto" style="opacity:.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            <p class="fw-bold" style="font-size:.95rem">No articles found.</p>
        </div>
    @else
    <div class="row g-4 mb-5">
        @foreach($articles as $article)
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('news.show', $article->slug) }}" class="news-card h-100 d-block text-decoration-none">
                @if($article->cover_image)
                    <img src="{{ $article->cover_image }}" alt="{{ $article->title }}" class="news-card__cover">
                @else
                    <div class="news-card__cover-placeholder">
                        <img src="/images/home/brand/xclusive_racing_logo_lion.png" alt="" height="48" style="opacity:.3;filter:invert(1)">
                    </div>
                @endif

                <div class="news-card__body">
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        @foreach($article->tags->take(1) as $tag)
                            <span class="news-card__primary-tag">{{ $tag->name }}</span>
                        @endforeach
                        @foreach($article->tags->skip(1)->take(2) as $tag)
                            <span class="news-card__other-tag">{{ $tag->name }}</span>
                        @endforeach
                    </div>

                    <div class="news-card__title">{{ $article->title }}</div>

                    @if($article->excerpt)
                        <div class="news-card__excerpt">{{ $article->excerpt }}</div>
                    @endif

                    <div class="news-card__meta">
                        {{ $article->author->name ?? 'XCL' }}
                        · {{ $article->published_at?->format('d M Y') }}
                    </div>

                    <span class="news-card__read-more">Read more →</span>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($articles->hasPages())
        <div class="d-flex justify-content-center news-pagination">
            {{ $articles->links() }}
        </div>
    @endif
    @endif

</div>
</div>
@endsection
