@extends('layouts.app')

@section('title', 'Championships — ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        <div class="pt-4 mb-5">
            <h1 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">CHAMPIONSHIPS</h1>
            <div class="section-divider" style="margin-left:0"></div>
            <p class="mt-3" style="color:#9ca3af;font-size:.9rem">Select a league to see the championships it runs.</p>
        </div>

        @if($leagues->isEmpty())
        <div class="text-center py-5" style="color:#9ca3af">
            <p class="fw-bold" style="font-size:1.1rem">No leagues available yet.</p>
        </div>
        @else

        <div class="row g-4">
            @foreach($leagues as $league)
            <div class="col-12 col-sm-6 col-xl-4">
                <a href="{{ route('championships.index', ['league' => $league->slug]) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 overflow-hidden" style="background:#111827;border-radius:12px;transition:transform .2s,box-shadow .2s"
                         onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,.4)'"
                         onmouseleave="this.style.transform='';this.style.boxShadow=''">

                        <div style="height:140px;overflow:hidden;position:relative;background:linear-gradient(135deg,{{ $league->primary_color }}33,{{ $league->accent_color }}66)">
                            @if($league->banner_url)
                            <img src="{{ $league->banner_url }}" alt="{{ $league->name }}"
                                 style="width:100%;height:100%;object-fit:cover;opacity:.7">
                            @endif
                            <div style="position:absolute;inset:0;background:linear-gradient(to top,#111827 0%,transparent 60%)"></div>

                            @if($league->logo_url)
                            <div style="position:absolute;bottom:10px;left:12px">
                                <img src="{{ $league->logo_url }}" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:6px;background:#fff;padding:2px">
                            </div>
                            @endif
                        </div>

                        <div class="p-3">
                            <h3 class="fw-black mb-2 text-white" style="font-size:.95rem;line-height:1.3">{{ $league->name }}</h3>

                            <div style="font-size:.8rem;color:#9ca3af">
                                <span class="fw-bold text-white">{{ $league->championships_count }}</span>
                                {{ Str::plural('championship', $league->championships_count) }}
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>

        @endif
    </div>
</main>
@endsection
