@extends('layouts.app')

@section('title', $article->title . ' — ' . config('xcl.name'))
@section('no-sidebar')

@push('head')
<style>
/* ── News detail – TRTN red #cc0000 accent ──────────────────────── */
.news-show-page {
    background-color: #ffffff;
    background-image: url('/topo.png');
    background-attachment: fixed;
    background-repeat: repeat;
    background-size: auto;
    min-height: 100vh;
    padding-top: 100px;
}

/* Cover image */
.news-cover {
    width: 100%;
    max-height: 480px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 2rem;
}

/* Article header */
.news-article-header {
    border-left: 4px solid #cc0000;
    padding-left: 1.25rem;
    margin-bottom: 1.75rem;
}
.news-article-title {
    font-size: clamp(1.6rem, 4vw, 2.5rem);
    font-weight: 900;
    color: #0d0d14;
    line-height: 1.2;
    text-transform: uppercase;
    font-style: italic;
}

/* Tag pills */
.news-detail-tag {
    display: inline-block;
    background: #cc0000;
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .25rem .7rem;
    border-radius: 4px;
    text-decoration: none;
}
.news-detail-tag:hover {
    background: #aa0000;
    color: #fff;
}

/* TRTN attribution bar */
.news-trtn-bar {
    background: #cc0000;
    border-radius: 8px;
    padding: .75rem 1.25rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 2rem;
    color: #fff;
    font-size: .8rem;
    font-weight: 700;
}

/* Article body */
.news-body {
    font-size: 1rem;
    line-height: 1.8;
    color: #1f2937;
    max-width: 780px;
}
.news-body blockquote {
    border-left: 4px solid #cc0000;
    margin: 1.5rem 0;
    padding: .75rem 1.25rem;
    background: #fff8f8;
    border-radius: 0 6px 6px 0;
    font-style: italic;
    color: #4b5563;
}
.news-body a {
    color: #cc0000;
    text-decoration: underline;
    text-underline-offset: 3px;
}
.news-body a:hover {
    color: #aa0000;
}
.news-body h1, .news-body h2, .news-body h3 {
    font-weight: 800;
    margin-top: 2rem;
    margin-bottom: .75rem;
    color: #0d0d14;
}
.news-body img {
    max-width: 100%;
    border-radius: 8px;
    margin: 1rem 0;
}
.news-body pre, .news-body code {
    background: #f3f4f6;
    border-radius: 4px;
    padding: .15rem .4rem;
    font-size: .88em;
}
.news-body pre {
    padding: 1rem;
    overflow-x: auto;
}
.news-body ul, .news-body ol {
    padding-left: 1.5rem;
}
.news-body hr {
    border-color: #e5e7eb;
    margin: 2rem 0;
}

/* Back link */
.news-back-link {
    font-size: .8rem;
    font-weight: 700;
    color: #cc0000;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: .05em;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-bottom: 1.5rem;
}
.news-back-link:hover { color: #aa0000; }
</style>
@endpush

@section('content')
<div class="news-show-page">
<div class="container-xl px-3 px-md-4 py-5" style="position:relative;z-index:1">
<div class="row justify-content-center">
<div class="col-lg-10 col-xl-9">

    <a href="{{ route('news.index') }}" class="news-back-link">← Back to News</a>

    {{-- Cover image --}}
    @if($article->cover_image)
        <img src="{{ $article->cover_image }}" alt="{{ $article->title }}" class="news-cover">
    @endif

    {{-- Tags --}}
    @if($article->tags->isNotEmpty())
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach($article->tags as $tag)
            <a href="{{ route('news.index', ['tag' => $tag->slug]) }}" class="news-detail-tag">{{ $tag->name }}</a>
        @endforeach
    </div>
    @endif

    {{-- Article header --}}
    <div class="news-article-header">
        <h1 class="news-article-title mb-2">{{ $article->title }}</h1>
        @if($article->excerpt)
            <p style="color:#6b7280;font-size:.95rem;margin-bottom:0">{{ $article->excerpt }}</p>
        @endif
    </div>

    {{-- TRTN attribution bar --}}
    <div class="news-trtn-bar">
        <img src="/images/trtn/TRTN Logo 1.png" alt="TRTN" height="24"
             style="object-fit:contain;filter:brightness(0) invert(1)">
        <div>
            <div style="font-weight:800;letter-spacing:.04em">PUBLISHED BY TRTN</div>
            <div style="font-weight:400;font-size:.75rem;opacity:.85">
                {{ $article->author->name ?? 'XCL Editorial' }}
                &nbsp;·&nbsp;
                {{ $article->published_at?->format('d F Y, H:i') }}
            </div>
        </div>
    </div>

    {{-- Article body --}}
    <article class="news-body">
        {!! nl2br(e($article->body)) !!}
    </article>

    {{-- Footer --}}
    <hr style="border-color:#e5e7eb;margin:2.5rem 0">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <a href="{{ route('news.index') }}" class="news-back-link">← Back to News</a>
        <div style="font-size:.78rem;color:#9ca3af">
            Last updated: {{ $article->updated_at->format('d M Y') }}
        </div>
    </div>

</div>
</div>
</div>
</div>
@endsection
