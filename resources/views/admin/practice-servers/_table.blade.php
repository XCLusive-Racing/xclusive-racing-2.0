@php
$statusStyles = [
    'scheduled' => ['#eff6ff', '#1d4ed8', '#bfdbfe'],
    'pushing'   => ['#fefce8', '#a16207', '#fde68a'],
    'live'      => ['#f0fdf4', '#16a34a', '#bbf7d0'],
    'completed' => ['#f9fafb', '#6b7280', '#e5e7eb'],
    'failed'    => ['#fef2f2', '#dc2626', '#fecaca'],
    'cancelled' => ['#f9fafb', '#9ca3af', '#e5e7eb'],
    'too_late'  => ['#fff7ed', '#c2410c', '#fed7aa'],
];
@endphp

@if($sessions->isEmpty())
<p class="text-secondary text-center py-4 mb-0" style="font-size:.85rem">{{ $emptyMessage }}</p>
@else
<div class="table-responsive">
    <table class="table align-middle mb-0" style="font-size:.85rem">
        <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
            <tr>
                <th class="fw-bold text-uppercase ps-4 py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Event</th>
                <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Window</th>
                <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Status</th>
                <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Entries</th>
                <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Error</th>
                <th class="fw-bold text-uppercase pe-4 py-3 text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($sessions as $s)
            @php [$bg, $color, $border] = $statusStyles[$s->status] ?? ['#f9fafb', '#374151', '#e5e7eb']; @endphp
            <tr style="border-bottom:1px solid #f9fafb">
                <td class="ps-4 fw-bold text-dark">{{ $s->race->title ?? 'Deleted event' }}</td>
                <td class="text-secondary" style="font-size:.78rem">
                    {{ $s->window_start->timezone('Europe/London')->format('d M H:i') }}
                    &rarr;
                    {{ $s->window_end->timezone('Europe/London')->format('H:i T') }}
                </td>
                <td>
                    <span class="badge fw-bold text-uppercase" style="background:{{ $bg }};color:{{ $color }};border:1px solid {{ $border }};font-size:.65rem;padding:3px 8px">
                        {{ strtoupper($s->status) }}
                    </span>
                </td>
                <td class="text-secondary">{{ $s->entry_count ?? '—' }}</td>
                <td class="text-secondary" style="font-size:.75rem;max-width:220px;white-space:normal">
                    {{ $s->last_error ? \Illuminate\Support\Str::limit($s->last_error, 80) : '—' }}
                </td>
                <td class="pe-4 text-end">
                    @if(in_array($s->status, ['scheduled', 'failed', 'live'], true))
                    <form action="{{ route('admin.practice-servers.push', $s) }}" method="POST"
                          onsubmit="return confirm('Push this practice config now?')">
                        @csrf
                        <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                                style="font-size:.68rem;padding:4px 10px;background:#f3f0ff;color:#7c3aed;border:1px solid #ddd6fe;white-space:nowrap">
                            Push Now
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
