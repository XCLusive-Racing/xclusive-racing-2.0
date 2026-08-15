@if($events->isEmpty())
    <p class="text-secondary" style="font-size:.88rem">{{ $emptyMessage }}</p>
@else
    <div style="display:flex;flex-direction:column;gap:.75rem">
        @foreach($events as $ev)
        <div class="d-flex align-items-start gap-3 p-3 rounded-2"
             style="background:#f9fafb;border:1px solid #e5e7eb">

            {{-- Thumbnail --}}
            @if($ev->image_url)
            <div class="flex-shrink-0" style="width:64px;height:48px;border-radius:6px;overflow:hidden;background:#111">
                <img src="{{ $ev->image_url }}" alt="{{ $ev->title }}"
                     style="width:100%;height:100%;object-fit:cover">
            </div>
            @endif

            {{-- Date block --}}
            <div class="text-center flex-shrink-0"
                 style="width:48px;background:#7c3aed;border-radius:8px;padding:6px 4px;color:white">
                <div style="font-size:.6rem;font-weight:800;letter-spacing:.08em;opacity:.8">
                    {{ strtoupper($ev->starts_at->timezone('Europe/London')->format('M')) }}
                </div>
                <div style="font-size:1.2rem;font-weight:900;line-height:1">
                    {{ $ev->starts_at->timezone('Europe/London')->format('d') }}
                </div>
            </div>

            {{-- Info --}}
            <div class="flex-grow-1" style="min-width:0">
                <div class="fw-black text-dark" style="font-size:.88rem;white-space:normal;overflow:visible">
                    {{ $ev->title }}
                </div>
                @if($ev->subtitle)
                <div class="text-secondary" style="font-size:.75rem;white-space:normal;overflow:visible">
                    {{ $ev->subtitle }}
                </div>
                @endif
                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                    <span class="badge fw-bold text-uppercase" style="background:#ede9fe;color:#6d28d9;font-size:.6rem;white-space:normal">
                        {{ $subjects[$ev->subject] ?? $ev->subject }}
                    </span>
                    <span style="font-size:.78rem;color:#6b7280">
                        {{ $ev->starts_at->timezone('Europe/London')->format('d M Y · H:i T') }}
                        @if($ev->ends_at)
                            &rarr; {{ $ev->ends_at->timezone('Europe/London')->format('H:i T') }}
                        @endif
                    </span>
                    @if($ev->isLive())
                    <span class="badge fw-bold text-uppercase" style="background:#cc0000;color:white;font-size:.6rem">LIVE</span>
                    @endif
                    @if($ev->watch_url)
                    <a href="{{ $ev->watch_url }}" target="_blank"
                       style="font-size:.7rem;color:#7c3aed;font-weight:700;text-decoration:none">
                        ▶ Watch
                    </a>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex flex-column gap-1">
                <a href="{{ route('admin.team-events.edit', $ev) }}"
                   class="btn btn-sm fw-bold text-uppercase"
                   style="font-size:.68rem;padding:4px 10px;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe;white-space:nowrap">
                    Edit
                </a>
                <form action="{{ route('admin.team-events.destroy', $ev) }}" method="POST"
                      onsubmit="return confirm('Delete this event?')">
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
