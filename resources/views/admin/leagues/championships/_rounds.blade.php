{{-- Rounds step: the schedule Add Round suggests dates from, the default server,
     and the round list itself. --}}
<form action="{{ route('admin.leagues.championships.wizard.update', [$league, $championship, 'rounds']) }}" method="POST">
    @csrf @method('PUT')

    <div class="cw-card">
        <div class="cw-card__head">
            <h2 class="cw-card__title">Schedule &amp; Server</h2>
            <button type="submit" class="cw-btn-primary" style="padding:.4rem 1rem;font-size:.72rem">Save</button>
        </div>

        <div class="cw-section">
            <p class="cw-section__title">Schedule</p>
            <p class="cw-section__note">Add Round suggests each date from this. Every round can still be changed.</p>
            <div class="row g-3">
                @foreach(\App\Settings\ChampionshipSettingsSchema::fieldsForGroup('schedule') as $field)
                    @continue($field['key'] === 'server_restarts')
                    @include('admin.leagues.championships._field', ['field' => $field, 'col' => 'col-md-6 col-lg-3'])
                @endforeach
            </div>
        </div>

        <div class="cw-section">
            <p class="cw-section__title">Default Server</p>
            @if($servers->isEmpty())
            <p class="cw-section__note mb-0">{{ $league->name }} has no {{ $championship->gameLabel() }} server yet. An XCL admin assigns one on the League page.</p>
            @else
            <div class="row g-3">
                <div class="col-md-6">
                    <select name="ftp_server_id" class="form-select @error('ftp_server_id') is-invalid @enderror">
                        <option value="">No server</option>
                        @foreach($servers as $srv)
                        <option value="{{ $srv->id }}" @selected((string) old('ftp_server_id', $championship->ftp_server_id) === (string) $srv->id)>{{ $srv->name }}</option>
                        @endforeach
                    </select>
                    <div class="cw-help">Picked for every new round, still changeable per round.</div>
                    @error('ftp_server_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>
            @endif
            <div class="row g-3 mt-0">
                @include('admin.leagues.championships._field', [
                    'field' => collect(\App\Settings\ChampionshipSettingsSchema::fieldsForGroup('schedule'))->firstWhere('key', 'server_restarts'),
                    'col' => 'col-md-6',
                ])
            </div>
        </div>
    </div>
</form>

<div class="cw-card">
    <div class="cw-card__head">
        <h2 class="cw-card__title">Rounds <span class="cw-tag cw-tag--muted">{{ $championship->rounds->count() }}</span></h2>
        <a href="{{ route('admin.leagues.championships.rounds.create', [$league, $championship]) }}" class="cw-btn-primary text-decoration-none" style="padding:.4rem 1rem;font-size:.72rem">
            + Add Round
        </a>
    </div>

    <div class="cw-card__body py-2">
        @php $pushStatusColors = ['pending' => '#9ca3af', 'pushed' => '#16a34a', 'failed' => '#dc2626']; @endphp
        @forelse($championship->rounds as $round)
        <div class="cw-list-row">
            <div>
                <div class="cw-list-row__title">
                    R{{ $round->round_number }} · {{ $round->track }}
                    @if($round->round_type)<span class="cw-tag cw-tag--type">{{ $round->round_type }}</span>@endif
                    @if($multiplierLabel = $round->pointsMultiplierLabel())<span class="cw-tag cw-tag--points">{{ $multiplierLabel }}</span>@endif
                </div>
                <div class="cw-list-row__meta">
                    {{ $round->scheduledAtUk()->format('D d M Y, H:i T') }}
                    @if($round->weather) · {{ ucfirst($round->weather) }} @endif
                    {{-- Configs push automatically (gportal:push-configs), so only the status shows here. --}}
                    @if($round->ftp_server_id)
                    · <span style="color:{{ $pushStatusColors[$round->config_push_status] ?? '#9ca3af' }}">config {{ $round->config_push_status ?? 'pending' }}</span>
                    @else
                    · <span style="color:#dc2626">no server</span>
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                @if($round->status === 'open' && $round->scheduled_at->isFuture())
                <form action="{{ route('admin.leagues.championships.rounds.sync-entries', [$league, $championship, $round]) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="cw-link-btn cw-link-btn--accent"
                            title="Adds every approved championship entry to this round. Entries that skipped it stay out.">
                        Enter all
                    </button>
                </form>
                @endif
                <a href="{{ route('admin.leagues.championships.rounds.edit', [$league, $championship, $round]) }}" class="cw-link-btn text-decoration-none">Edit</a>
                <form action="{{ route('admin.leagues.championships.rounds.destroy', [$league, $championship, $round]) }}" method="POST" onsubmit="return false" class="m-0">
                    @csrf @method('DELETE')
                    <button type="button" class="cw-link-btn cw-link-btn--danger"
                            onclick="xcDeleteSubmit(this.closest('form'), 'Remove round {{ addslashes($round->title) }}? Without results it is deleted; with results it stays as a standalone race.')">
                        Remove
                    </button>
                </form>
            </div>
        </div>
        @empty
        <p class="text-secondary my-3" style="font-size:.82rem">No rounds yet. Add the first one above.</p>
        @endforelse
    </div>
</div>

<div class="cw-actions">
    @if($prevStepKey)
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, $prevStepKey]) }}" class="cw-btn-secondary text-decoration-none">← Back</a>
    @endif
    <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'review']) }}" class="cw-btn-primary text-decoration-none">Continue →</a>
    <span class="cw-actions__hint">Step {{ $stepIndex + 1 }} of {{ count($steps) }}</span>
</div>

<script>
// Race Day only matters for a weekly or bi-weekly schedule.
(function () {
    var recurrence = document.getElementById('f-schedule-recurrence');
    var dayWrap    = document.getElementById('f-schedule-day_of_week')?.parentElement;
    if (!recurrence || !dayWrap) return;

    function update() {
        dayWrap.hidden = !['weekly', 'biweekly'].includes(recurrence.value);
    }
    recurrence.addEventListener('change', update);
    update();
})();
</script>
