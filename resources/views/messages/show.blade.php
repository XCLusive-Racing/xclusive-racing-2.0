@extends('layouts.app')

@section('title', $message->title . ' - ' . config('xcl.name'))

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
                <div style="color:{{ $message->typeColor() }}">
                    <i class="fa-solid {{ $message->typeIcon() }}" style="font-size:1.2rem"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-black text-uppercase mb-0" style="font-size:.95rem;letter-spacing:.04em">{{ $message->title }}</h5>
                    <span class="text-secondary" style="font-size:.75rem">{{ $message->created_at->format('d M Y, H:i') }}</span>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-4 py-4" style="font-size:.92rem;line-height:1.7;color:#1f2937">
                {!! nl2br(e($message->body)) !!}
            </div>

            {{-- Footer --}}
            <div class="px-4 pb-4">
                <form method="POST" action="{{ route('messages.destroy', $message) }}"
                      onsubmit="return confirm('Delete this message?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm fw-bold text-danger border-danger">
                        <i class="fa-solid fa-trash me-1"></i> Delete message
                    </button>
                </form>
            </div>
        </div>

    </div>
</main>
@endsection
