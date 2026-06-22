@extends('layouts.app')

@section('title', 'Championships — ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        <div class="pt-4 mb-5">
            <h1 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">CHAMPIONSHIPS</h1>
            <div class="section-divider" style="margin-left:0"></div>
        </div>

        @if($championships->isEmpty())
        <div class="text-center py-5" style="color:#9ca3af">
            <p class="fw-bold" style="font-size:1.1rem">No championships available yet.</p>
        </div>
        @else

        <div class="row g-4">
            @foreach($championships as $c)
            <div class="col-12 col-sm-6 col-xl-4">
                <a href="{{ route('championships.show', $c) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 overflow-hidden" style="background:#111827;border-radius:12px;transition:transform .2s,box-shadow .2s"
                         onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,.4)'"
                         onmouseleave="this.style.transform='';this.style.boxShadow=''">

                        {{-- Banner image --}}
                        <div style="height:140px;overflow:hidden;position:relative;background:linear-gradient(135deg,{{ $c->gameColor() }}33,{{ $c->gameColor() }}66)">
                            @if($c->image_url)
                            <img src="{{ $c->image_url }}" alt="{{ $c->name }}"
                                 style="width:100%;height:100%;object-fit:cover;opacity:.7">
                            @endif
                            <div style="position:absolute;inset:0;background:linear-gradient(to top,#111827 0%,transparent 60%)"></div>

                            {{-- Status badge --}}
                            @php $sc = ['active'=>'#16a34a','finished'=>'#6b7280','draft'=>'#f59e0b'][$c->status] ?? '#6b7280'; @endphp
                            <div style="position:absolute;top:10px;right:10px">
                                <span class="badge fw-bold" style="background:{{ $sc }};font-size:.65rem;padding:3px 8px;border-radius:20px">
                                    {{ ucfirst($c->status) }}
                                </span>
                            </div>

                            @if($c->icon_url)
                            <div style="position:absolute;bottom:10px;left:12px">
                                <img src="{{ $c->icon_url }}" alt="" style="width:36px;height:36px;object-fit:contain">
                            </div>
                            @endif
                        </div>

                        <div class="p-3">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <h3 class="fw-black mb-0 text-white" style="font-size:.95rem;line-height:1.3">{{ $c->name }}</h3>
                                <span class="badge text-white fw-bold flex-shrink-0"
                                      style="background:{{ $c->gameColor() }};font-size:.62rem;padding:3px 8px;border-radius:5px">
                                    {{ $c->gameLabel() }}
                                </span>
                            </div>

                            <div style="font-size:.75rem;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.75rem">
                                Season {{ $c->season }}
                                @if($c->is_multiclass)
                                  · <span style="color:#db2777">Multiclass</span>
                                @endif
                            </div>

                            <div class="d-flex gap-3" style="font-size:.8rem;color:#9ca3af">
                                <span>
                                    <span class="fw-bold text-white">{{ $c->rounds_count }}</span> rounds
                                </span>
                                <span>
                                    <span class="fw-bold text-white">{{ $c->registrations_count }}</span>
                                    {{ $c->max_drivers ? '/ ' . $c->max_drivers : '' }} drivers
                                </span>
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
