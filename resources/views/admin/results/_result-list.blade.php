@if($results->isEmpty())
    <p class="text-secondary" style="font-size:.88rem">{{ $emptyMessage }}</p>
@else
    <div style="display:flex;flex-direction:column;gap:.75rem">
        @foreach($results as $result)
        @php
            $firstRace = $result->races->first();
        @endphp
        <div class="d-flex align-items-start gap-3 p-3 rounded-2"
             style="background:#f9fafb;border:1px solid #e5e7eb">

            {{-- Year block --}}
            <div class="text-center flex-shrink-0"
                 style="width:48px;background:#7c3aed;border-radius:8px;padding:8px 4px;color:white">
                <div style="font-size:1rem;font-weight:900;line-height:1">{{ $result->year }}</div>
            </div>

            {{-- Info --}}
            <div class="flex-grow-1" style="min-width:0">
                <div class="fw-black text-dark" style="font-size:.88rem">
                    {{ $result->title ?: ($firstRace->track ?? 'Untitled result') }}
                </div>
                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                    <span class="badge fw-bold text-uppercase" style="background:#ede9fe;color:#6d28d9;font-size:.6rem">
                        {{ $subjects[$result->subject] ?? $result->subject }}
                    </span>
                    @if($result->category === 'pro')
                        <span style="font-size:.78rem;color:#6b7280">
                            {{ $result->races->count() }} {{ \Illuminate\Support\Str::plural('race', $result->races->count()) }}
                            @if($result->standing) · {{ $result->standing }} @endif
                        </span>
                    @else
                        @if($result->type !== 'race')
                        <span class="badge fw-bold text-uppercase" style="background:#fef3c7;color:#b45309;font-size:.6rem">
                            {{ $result->type === 'standings' ? 'Live standings' : 'Final result' }}
                        </span>
                        @endif
                        <span style="font-size:.78rem;color:#6b7280">
                            {{ $firstRace?->track }}
                            @if($firstRace?->race_date) · {{ $firstRace->race_date->format('d M Y') }} @endif
                            @if($firstRace) · {{ $firstRace->positions->count() }} {{ \Illuminate\Support\Str::plural('driver', $firstRace->positions->count()) }} @endif
                        </span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex flex-column gap-1">
                <a href="{{ route('admin.results.edit', $result) }}"
                   class="btn btn-sm fw-bold text-uppercase"
                   style="font-size:.68rem;padding:4px 10px;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe;white-space:nowrap">
                    Edit
                </a>
                <form action="{{ route('admin.results.destroy', $result) }}" method="POST"
                      onsubmit="return confirm('Delete this result?')">
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
