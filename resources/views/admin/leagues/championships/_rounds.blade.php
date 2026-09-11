<div class="admin-card mb-4">
    <div class="px-4 pt-4 pb-3 d-flex align-items-center justify-content-between">
        <p class="fw-black text-uppercase fst-italic mb-0" style="font-size:.85rem">Rounds</p>
        <a href="{{ route('admin.leagues.championships.rounds.create', [$league, $championship]) }}"
           class="btn btn-sm fw-black text-uppercase text-white px-3" style="background:#7c3aed;font-size:.72rem">
            + Add Round
        </a>
    </div>

    <div class="px-4 pb-4">
        <p class="text-secondary mb-3" style="font-size:.8rem">
            Set which days you race, on which tracks, in what conditions, and how long practice/qualifying/race run —
            each round can override the schedule and session defaults you set on the Basics step.
        </p>

        @php
            $pushStatusColors = ['pending' => '#9ca3af', 'pushed' => '#16a34a', 'failed' => '#dc2626'];
        @endphp
        @forelse($championship->rounds as $round)
        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid #f3f4f6">
            <div>
                <div class="fw-bold text-dark" style="font-size:.85rem">R{{ $round->round_number }} — {{ $round->title }}</div>
                <div class="text-secondary" style="font-size:.72rem">
                    {{ $round->track }} · {{ $round->scheduledAtUk()->format('d M Y, H:i T') }}
                    @if($round->weather) · {{ ucfirst($round->weather) }} @endif
                    @if($round->ftp_server_id)
                    · <span style="color:{{ $pushStatusColors[$round->config_push_status] ?? '#9ca3af' }}">
                        config {{ $round->config_push_status ?? 'pending' }}
                    </span>
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                {{-- No manual "Push Config" button — gportal:push-configs already
                     auto-pushes every race's config on a schedule, championship
                     rounds included, the same as a regular event. Results import
                     the same way (gportal:import-results, also global). Status is
                     still surfaced above (config pending/pushed/failed) so a
                     manager can see it, just nothing to click here to force it. --}}
                <a href="{{ route('admin.leagues.championships.rounds.edit', [$league, $championship, $round]) }}"
                   class="btn btn-sm fw-bold" style="background:transparent;color:#374151;font-size:.72rem">
                    Edit
                </a>
                <form action="{{ route('admin.leagues.championships.rounds.destroy', [$league, $championship, $round]) }}" method="POST" onsubmit="return false">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-sm fw-bold" style="background:transparent;color:#dc2626;font-size:.72rem"
                            onclick="xcDeleteSubmit(this.closest('form'), 'Remove round {{ addslashes($round->title) }}?')">
                        Remove
                    </button>
                </form>
            </div>
        </div>
        @empty
        <p class="text-secondary mb-0" style="font-size:.82rem">No rounds scheduled yet — add your first one above.</p>
        @endforelse
    </div>
</div>

<a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'format']) }}"
   class="btn fw-black text-uppercase text-white px-4" style="background:#7c3aed">
    Continue →
</a>
