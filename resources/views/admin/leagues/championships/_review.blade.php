@php
    use App\Settings\ChampionshipSettingsSchema;
    $settings = $championship->settings;
@endphp

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Basics</div>
    </div>
    <div class="px-4 py-3">
        <div class="row g-2" style="font-size:.85rem">
            <div class="col-sm-6"><span class="text-secondary">Name:</span> {{ $championship->name }}</div>
            <div class="col-sm-6"><span class="text-secondary">Slug:</span> {{ $championship->slug ?: 'Not set' }}</div>
            <div class="col-sm-6"><span class="text-secondary">Game:</span> {{ strtoupper($championship->game) }}</div>
            <div class="col-sm-6"><span class="text-secondary">Platform:</span> {{ $championship->platform ? ucfirst($championship->platform) : 'Not set' }}</div>
            <div class="col-sm-6"><span class="text-secondary">Visibility:</span> {{ ucfirst($championship->visibility) }}</div>
        </div>
    </div>
</div>

@foreach(['format' => 'Format', 'sessions' => 'Sessions', 'scoring' => 'Scoring', 'requirements' => 'Requirements', 'penalties' => 'Penalties', 'balance' => 'Balance'] as $group => $label)
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">{{ $label }}</div>
    </div>
    <div class="px-4 py-3">
        <div class="row g-2" style="font-size:.85rem">
            @foreach(ChampionshipSettingsSchema::fieldsForGroup($group) as $field)
            <div class="col-sm-6">
                <span class="text-secondary">{{ $field['label'] }}:</span>
                {{ ChampionshipSettingsSchema::humanValue($field, $settings->{$group}->{$field['key']} ?? $field['default']) }}
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">Rounds</div>
        <a href="{{ route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']) }}"
           class="fw-bold" style="color:#7c3aed;font-size:.78rem">
            Manage rounds →
        </a>
    </div>
    <div class="px-4 py-3">
        @forelse($championship->rounds as $round)
        <div class="py-2" style="border-bottom:1px solid #f3f4f6;font-size:.85rem">
            <span class="fw-bold text-dark">R{{ $round->round_number }} — {{ $round->title }}</span>
            <span class="text-secondary"> · {{ $round->track }} · {{ $round->scheduledAtUk()->format('d M Y, H:i T') }}</span>
        </div>
        @empty
        <p class="text-secondary mb-0" style="font-size:.82rem">No rounds scheduled yet.</p>
        @endforelse
    </div>
</div>

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <div class="fw-black text-uppercase fst-italic text-dark" style="font-size:.9rem">XCL Rating</div>
    </div>
    <div class="px-4 py-3">
        @if($championship->xcl_rating_enabled)
        <p class="mb-2" style="font-size:.85rem">
            <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.7rem;padding:4px 8px;border-radius:6px;font-weight:700">Enabled</span>
            Approved by {{ $championship->ratingApprovedBy?->name ?? 'an XCL admin' }} on {{ $championship->xcl_rating_approved_at?->format('d M Y') }}.
        </p>
        @if($canApproveRating)
        <form action="{{ route('admin.leagues.championships.revoke-rating', [$league, $championship]) }}" method="POST" onsubmit="return false">
            @csrf
            <button type="button" class="btn btn-sm btn-outline-danger fw-bold" onclick="xcDeleteSubmit(this.closest('form'), 'Revoke XCL Rating for {{ addslashes($championship->name) }}?')">
                Revoke
            </button>
        </form>
        @endif
        @elseif($settings->penalties->xcl_rating_requested ?? false)
        <p class="mb-2" style="font-size:.85rem">
            <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.7rem;padding:4px 8px;border-radius:6px;font-weight:700">Requested</span>
            Waiting for an XCL admin to review.
        </p>
        @if($canApproveRating)
        <form action="{{ route('admin.leagues.championships.approve-rating', [$league, $championship]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm fw-black text-uppercase text-white" style="background:#16a34a;font-size:.75rem">
                Approve XCL Rating
            </button>
        </form>
        @endif
        @else
        <p class="text-secondary mb-0" style="font-size:.85rem">Not requested. Raise a request from the Penalties &amp; Balance step.</p>
        @endif
    </div>
</div>

<div class="d-flex gap-2">
    @if($championship->status === 'draft')
    <form action="{{ route('admin.leagues.championships.publish', [$league, $championship]) }}" method="POST">
        @csrf
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#16a34a">
            Publish
        </button>
    </form>
    @elseif(in_array($championship->status, ['published', 'registration_closed']))
    <form action="{{ route('admin.leagues.championships.open-registration', [$league, $championship]) }}" method="POST">
        @csrf
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#16a34a">
            Open Registration
        </button>
    </form>
    @elseif($championship->status === 'registration_open')
    <form action="{{ route('admin.leagues.championships.close-registration', [$league, $championship]) }}" method="POST">
        @csrf
        <button type="submit" class="btn fw-black text-uppercase text-white px-4" style="background:#dc2626">
            Close Registration
        </button>
    </form>
    @endif
</div>
