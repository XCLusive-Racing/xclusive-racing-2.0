@extends('layouts.app')

@section('title', 'News — ' . config('xcl.name'))
@section('no-sidebar')

@push('head')
<style>
/* ── News page – white topo background, TRTN red #cc0000 ────────── */
.news-page {
    background-color: #ffffff;
    background-image: url('/topo.png');
    background-attachment: fixed;
    background-repeat: repeat;
    background-size: auto;
    min-height: 100vh;
    padding-top: 80px;
}

/* Hero bar */
.news-hero {
    border-left: 4px solid #cc0000;
    padding: 1.25rem 0 1.25rem 1.25rem;
}
.news-hero__heading {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 900;
    text-transform: uppercase;
    font-style: italic;
    color: #1a1a2e;
    letter-spacing: -.02em;
    line-height: 1;
    margin-bottom: .25rem;
}
.news-hero__sub {
    color: #555;
    font-size: .9rem;
    margin: 0;
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

/* ── Featured article ───────────────────────────────────────────── */
.news-featured {
    position: relative;
    width: 100%;
    height: 400px;
    border-radius: 12px;
    overflow: hidden;
    display: block;
    text-decoration: none;
    color: inherit;
}
.news-featured__bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    background-color: #1a1a2e;
    transition: transform .4s ease;
}
.news-featured:hover .news-featured__bg {
    transform: scale(1.02);
}
.news-featured__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,.82) 0%, rgba(0,0,0,.3) 55%, rgba(0,0,0,.1) 100%);
}
.news-featured__content {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1.75rem;
}
.news-featured__tags {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-bottom: .75rem;
}
.news-featured__tag {
    background: #cc0000;
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .25rem .65rem;
    border-radius: 4px;
}
.news-featured__title {
    font-size: clamp(1.3rem, 3.5vw, 2rem);
    font-weight: 900;
    color: #fff;
    line-height: 1.2;
    text-transform: uppercase;
    font-style: italic;
    margin-bottom: .5rem;
    flex: 1;
}
.news-featured__meta {
    font-size: .78rem;
    color: rgba(255,255,255,.65);
    margin-bottom: 0;
}
.news-featured__cta {
    display: inline-block;
    background: #cc0000;
    color: #fff;
    font-size: .75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .55rem 1.1rem;
    border-radius: 6px;
    text-decoration: none;
    white-space: nowrap;
    flex-shrink: 0;
    align-self: flex-end;
    transition: background .15s;
}
.news-featured__cta:hover {
    background: #aa0000;
    color: #fff;
}
.news-featured__bottom {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
}

/* ── Search bar ─────────────────────────────────────────────────── */
.news-search-wrap { position: relative; }
.news-search-wrap .search-icon {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #cc0000;
    pointer-events: none;
}
.news-search {
    padding-left: 2.4rem !important;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
    color: #1a1a2e;
    transition: border-color .2s, box-shadow .2s;
}
.news-search:focus {
    outline: none;
    border-color: #cc0000;
    box-shadow: 0 0 0 3px rgba(204,0,0,.08);
}
.news-search::placeholder { color: #9ca3af; }

/* ── Tag pills ──────────────────────────────────────────────────── */
.news-tag-pill {
    display: inline-block;
    padding: .3rem .9rem;
    border-radius: 999px;
    font-size: .72rem;
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

/* ── Article cards ──────────────────────────────────────────────── */
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
    height: 100%;
    transition: border-top-color .2s, box-shadow .2s, transform .2s;
}
.news-card:hover {
    border-top-color: #cc0000;
    box-shadow: 0 8px 32px rgba(0,0,0,.12);
    transform: translateY(-2px);
    color: inherit;
    text-decoration: none;
}
.news-card__cover {
    width: 100%;
    aspect-ratio: 16/9;
    object-fit: cover;
}
.news-card__cover-placeholder {
    width: 100%;
    aspect-ratio: 16/9;
    background: linear-gradient(135deg, #1a1a2e 0%, #2d2d44 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}
.news-card__body {
    padding: 1.1rem 1.25rem 1.25rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.news-card__tags {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-bottom: .65rem;
}
.news-card__tag-primary {
    background: #cc0000;
    color: #fff;
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: .2rem .55rem;
    border-radius: 4px;
}
.news-card__tag-other {
    background: #374151;
    color: #fff;
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: .2rem .5rem;
    border-radius: 4px;
}
.news-card__title {
    font-size: .95rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1.35;
    margin-bottom: .5rem;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.news-card__excerpt {
    font-size: .8rem;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: .85rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.news-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
}
.news-card__meta {
    font-size: .7rem;
    color: #9ca3af;
}
.news-card__read-more {
    font-size: .72rem;
    font-weight: 800;
    color: #cc0000;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.news-card__read-more:hover { color: #aa0000; }

/* ── Pagination ─────────────────────────────────────────────────── */
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

/* ── Navbar NEWS accent ─────────────────────────────────────────── */
.xcl-nav-news:hover,
.xcl-nav-news.active {
    color: #cc0000 !important;
    text-decoration: underline;
    text-decoration-color: #cc0000;
    text-underline-offset: 4px;
}
</style>
@endpush

@section('content')
<div class="news-page">
<div class="container-xl px-3 px-md-4" style="padding-top:60px;padding-bottom:60px;position:relative;z-index:1">

    {{-- ── Page header ──────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div class="news-hero">
            <h1 class="news-hero__heading">NEWS</h1>
            <p class="news-hero__sub">Latest from XCLusive Racing</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="news-trtn-badge">
                <img src="/images/trtn/TRTN Logo 1.png" alt="TRTN" height="20"
                     style="object-fit:contain;filter:brightness(0) invert(1)">
            </div>
            <span class="news-trtn-powered">Powered by TRTN</span>
        </div>
    </div>

    {{-- ── Featured article ─────────────────────────────────────── --}}
    @if($featured)
    <a href="{{ route('news.show', $featured->slug) }}" class="news-featured mb-4 d-block"
       style="margin-bottom:40px!important">
        <div class="news-featured__bg"
             style="background-image:url('{{ $featured->cover_image ?: '/images/home/brand/xclusive_racing_logo_lion.png' }}')"></div>
        <div class="news-featured__overlay"></div>
        <div class="news-featured__content">
            @if($featured->tags->isNotEmpty())
            <div class="news-featured__tags">
                @foreach($featured->tags as $tag)
                    <span class="news-featured__tag">{{ $tag->name }}</span>
                @endforeach
            </div>
            @endif
            <div class="news-featured__bottom">
                <div>
                    <div class="news-featured__title">{{ $featured->title }}</div>
                    <p class="news-featured__meta">
                        {{ $featured->author->name ?? 'XCL' }} · {{ $featured->published_at?->format('d M Y') }}
                    </p>
                </div>
                <span class="news-featured__cta">Read More →</span>
            </div>
        </div>
    </a>
    @endif

    {{-- ── Search + tag filters ─────────────────────────────────── --}}
    <div class="row g-3 mb-4 align-items-center">
        <div class="col-md-4">
            <form method="GET" action="{{ route('news.index') }}">
                @if($activeTag)
                    <input type="hidden" name="tag" value="{{ $activeTag }}">
                @endif
                <div class="news-search-wrap">
                    <svg class="search-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control news-search"
                           placeholder="Search articles…">
                </div>
            </form>
        </div>
        <div class="col-md-8">
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

    {{-- ── Article grid ──────────────────────────────────────────── --}}
    @if($articles->isEmpty() && !$featured)
        <div class="text-center py-5" style="color:#9ca3af">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3 d-block mx-auto" style="opacity:.35">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            <p class="fw-bold mb-0" style="font-size:.95rem">No articles found.</p>
        </div>
    @elseif($articles->isNotEmpty())
    <div class="row g-4 mb-5">
        @foreach($articles as $article)
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('news.show', $article->slug) }}" class="news-card text-decoration-none d-flex">
                @if($article->cover_image)
                    <img src="{{ $article->cover_image }}" alt="{{ $article->title }}" class="news-card__cover">
                @else
                    <div class="news-card__cover-placeholder">
                        <img src="/images/home/brand/xclusive_racing_logo_lion.png" alt="" height="40"
                             style="opacity:.2;filter:invert(1)">
                    </div>
                @endif

                <div class="news-card__body">
                    @if($article->tags->isNotEmpty())
                    <div class="news-card__tags">
                        @foreach($article->tags->take(1) as $tag)
                            <span class="news-card__tag-primary">{{ $tag->name }}</span>
                        @endforeach
                        @foreach($article->tags->skip(1)->take(2) as $tag)
                            <span class="news-card__tag-other">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                    @endif

                    <div class="news-card__title">{{ $article->title }}</div>

                    @if($article->excerpt)
                        <div class="news-card__excerpt">{{ $article->excerpt }}</div>
                    @endif

                    <div class="news-card__footer">
                        <span class="news-card__meta">
                            {{ $article->author->name ?? 'XCL' }} · {{ $article->published_at?->format('d M Y') }}
                        </span>
                        <span class="news-card__read-more">Read more →</span>
                    </div>
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
