@if($broadcasts->isEmpty())
    <p class="text-secondary" style="font-size:.88rem">{{ $emptyMessage }}</p>
@else
    <div style="display:flex;flex-direction:column;gap:.75rem">
        @foreach($broadcasts as $b)
        <div class="d-flex align-items-start gap-3 p-3 rounded-2"
             style="background:#f9fafb;border:1px solid #e5e7eb">

            {{-- Date block --}}
            <div class="text-center flex-shrink-0"
                 style="width:48px;background:{{ $b->color }};border-radius:8px;padding:6px 4px;color:white">
                <div style="font-size:.6rem;font-weight:800;letter-spacing:.08em;opacity:.8">
                    {{ strtoupper($b->starts_at->timezone('Europe/London')->format('M')) }}
                </div>
                <div style="font-size:1.2rem;font-weight:900;line-height:1">
                    {{ $b->starts_at->timezone('Europe/London')->format('d') }}
                </div>
            </div>

            {{-- Info --}}
            <div class="flex-grow-1" style="min-width:0">
                <div class="fw-black text-dark" style="font-size:.88rem;white-space:normal;overflow:visible">
                    {{ $b->title }}
                </div>
                @if($b->subtitle)
                <div class="text-secondary" style="font-size:.75rem;white-space:normal;overflow:visible">
                    {{ $b->subtitle }}
                </div>
                @endif
                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                    @if($b->series)
                    <span class="badge fw-bold text-uppercase" style="background:{{ $b->color }}25;color:{{ $b->color }};font-size:.6rem;white-space:normal">
                        {{ $b->series }}
                    </span>
                    @endif
                    <span style="font-size:.78rem;color:#6b7280">
                        {{ $b->starts_at->timezone('Europe/London')->format('d M Y · H:i T') }}
                        &rarr; {{ $b->ends_at->timezone('Europe/London')->format('H:i T') }}
                    </span>
                    @if($b->isLive())
                    <span class="badge fw-bold text-uppercase" style="background:#cc0000;color:white;font-size:.6rem">LIVE</span>
                    @endif
                    <a href="{{ $b->watch_url }}" target="_blank"
                       style="font-size:.7rem;color:#7c3aed;font-weight:700;text-decoration:none">
                        ▶ Watch link
                    </a>
                </div>
                <div class="text-secondary mt-1" style="font-size:.7rem">
                    Added by {{ $b->author->name ?? 'Unknown' }}
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex flex-column gap-1">
                <a href="{{ route('admin.broadcasts.edit', $b) }}"
                   class="btn btn-sm fw-bold text-uppercase"
                   style="font-size:.68rem;padding:4px 10px;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe;white-space:nowrap">
                    Edit
                </a>
                <form action="{{ route('admin.broadcasts.destroy', $b) }}" method="POST"
                      onsubmit="return confirm('Delete this broadcast?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="btn btn-sm fw-bold text-uppercase w-100"
                            style="font-size:.68rem;padding:4px 10px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;white-space:nowrap">
                        Delete
                    </button>
                </form>
            </div>

        </div>
        @endforeach
    </div>
@endif
