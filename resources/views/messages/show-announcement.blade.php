@extends('layouts.app')

@section('title', $announcement->title . ' - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3 bg-light">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container" style="max-width:720px;position:relative;z-index:1">

        <div class="mb-4">
            <a href="{{ route('messages.index') }}" class="text-decoration-none text-secondary fw-bold" style="font-size:.85rem">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Inbox
            </a>
        </div>

        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
            {{-- Header --}}
            <div class="px-4 py-3 border-bottom d-flex align-items-center gap-3" style="background:#fafafa">
                <div style="color:#db2877">
                    <i class="fa-solid fa-newspaper" style="font-size:1.2rem"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-black text-uppercase mb-0" style="font-size:.95rem;letter-spacing:.04em">{{ $announcement->title }}</h5>
                    <span class="text-secondary" style="font-size:.75rem">{{ $announcement->created_at->format('d M Y, H:i') }}</span>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-4 py-4" style="font-size:.92rem;line-height:1.7;color:#1f2937">
                {!! nl2br(e($announcement->body)) !!}
            </div>

            {{-- Link to article --}}
            @if($announcement->newsArticle)
            <div class="px-4 pb-3">
                <a href="{{ route('news.show', $announcement->newsArticle->slug) }}"
                   class="btn btn-sm fw-bold text-white"
                   style="background:#db2877">
                    <i class="fa-solid fa-arrow-right me-1"></i> Read full article
                </a>
            </div>
            @endif

            {{-- Footer --}}
            <div class="px-4 pb-4">
                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}"
                      onsubmit="return confirm('Remove this from your inbox?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm fw-bold text-danger border-danger">
                        <i class="fa-solid fa-trash me-1"></i> Remove from inbox
                    </button>
                </form>
            </div>
        </div>

    </div>
</main>
@endsection
