@extends('layouts.admin')

@section('title', $championship->name)
@section('page-title', $championship->name)

@section('page-actions')
    <a href="{{ route('admin.championships.edit', $championship) }}"
       class="btn btn-sm fw-bold text-uppercase"
       style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-size:.78rem">
        Edit
    </a>
    <a href="{{ route('admin.championships.index') }}"
       class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.78rem">
        ← Back
    </a>
@endsection

@section('content')

<div x-data="{ tab: 'overview' }">

    {{-- Tabs --}}
    <div class="d-flex gap-1 mb-4 flex-wrap" style="border-bottom:2px solid #e5e7eb;padding-bottom:0">
        @foreach(['overview' => 'Overview', 'rounds' => 'Rounds (' . $rounds->count() . ')', 'standings' => 'Standings', 'registrations' => 'Registrations (' . $championship->registrations->count() . ')', 'penalties' => 'Penalties'] as $key => $label)
        <button @click="tab='{{ $key }}'"
                :class="tab==='{{ $key }}' ? 'border-bottom-0' : 'text-secondary'"
                class="btn btn-sm fw-bold text-uppercase px-3 py-2"
                style="font-size:.72rem;border-radius:6px 6px 0 0;transition:none;margin-bottom:-2px;"
                :style="tab==='{{ $key }}' ? 'background:white;border:2px solid #e5e7eb;border-bottom:2px solid white;color:#111827' : 'background:transparent;border:none'">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- OVERVIEW --}}
    <div x-show="tab==='overview'">
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="admin-card">
                    <div class="px-4 pt-4 pb-3">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            @if($championship->icon_url)
                                <img src="{{ $championship->icon_url }}" alt="" style="width:56px;height:56px;object-fit:contain;border-radius:8px">
                            @endif
                            <div>
                                <h2 class="fw-black mb-1" style="font-size:1.3rem">{{ $championship->name }}</h2>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge text-white fw-bold" style="background:{{ $championship->gameColor() }};font-size:.7rem;padding:4px 10px;border-radius:6px">
                                        {{ $championship->gameLabel() }}
                                    </span>
                                    <span class="badge fw-bold" style="font-size:.7rem;padding:4px 10px;border-radius:6px;background:#f3f4f6;color:#374151">
                                        Season {{ $championship->season }}
                                    </span>
                                    @php $sc = ['draft'=>'#6b7280','active'=>'#16a34a','finished'=>'#2563eb'][$championship->status] ?? '#6b7280'; @endphp
                                    <span class="badge fw-bold" style="font-size:.7rem;padding:4px 10px;border-radius:6px;background:{{ $sc }}1a;color:{{ $sc }}">
                                        {{ ucfirst($championship->status) }}
                                    </span>
                                    @if($championship->is_multiclass)
                                    <span class="badge fw-bold" style="font-size:.7rem;padding:4px 10px;border-radius:6px;background:#db21771a;color:#db2777">
                                        Multiclass
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($championship->description)
                        <p class="text-secondary" style="font-size:.875rem">{{ $championship->description }}</p>
                        @endif

                        <div class="row g-3 mt-2" style="font-size:.82rem">
                            <div class="col-6 col-sm-3">
                                <div class="text-secondary fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em">Rounds</div>
                                <div class="fw-bold fs-5">{{ $rounds->count() }}</div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-secondary fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em">Drivers</div>
                                <div class="fw-bold fs-5">{{ $championship->registrations->count() }}{{ $championship->max_drivers ? ' / ' . $championship->max_drivers : '' }}</div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-secondary fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em">Drop Rounds</div>
                                <div class="fw-bold fs-5">{{ $championship->drop_rounds }}</div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-secondary fw-bold text-uppercase" style="font-size:.68rem;letter-spacing:.06em">Points</div>
                                <div class="fw-bold" style="font-size:.82rem">{{ implode(',', $championship->points_system ?? []) ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                @if($championship->image_url)
                <div class="admin-card">
                    <img src="{{ $championship->image_url }}" alt="" class="w-100 rounded" style="max-height:200px;object-fit:cover">
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ROUNDS --}}
    <div x-show="tab==='rounds'" style="display:none">
        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="admin-card mb-4">
                    <div class="px-4 pt-4 pb-2">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Rounds</p>

                        @if($rounds->isEmpty())
                            <p class="text-secondary" style="font-size:.875rem">No rounds yet.</p>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
                                <thead>
                                    <tr style="background:#f9fafb">
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">#</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Round</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Date</th>
                                        <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;color:#9ca3af">Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rounds as $round)
                                    <tr>
                                        <td class="fw-bold text-secondary">R{{ $round->round_number }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $round->title }}</div>
                                            <div class="text-secondary" style="font-size:.75rem">{{ $round->track }}</div>
                                        </td>
                                        <td class="text-secondary" style="font-size:.78rem">{{ $round->scheduledAtUk()->format('d M Y') }}</td>
                                        <td class="text-center">
                                            <span class="status-badge status-{{ $round->status }}">{{ ucfirst($round->status) }}</span>
                                        </td>
                                        <td class="text-end pe-2">
                                            <a href="{{ route('admin.races.show', $round) }}" class="btn btn-sm fw-bold text-uppercase text-white" style="background:#7c3aed;font-size:.68rem;padding:4px 10px;border-radius:5px">Open</a>
                                            <form method="POST" action="{{ route('admin.championships.rounds.destroy', [$championship, $round]) }}" class="d-inline" onsubmit="return confirm('Remove this round from the championship?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm fw-bold text-uppercase text-danger" style="font-size:.68rem;padding:4px 10px">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="admin-card">
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Add Round</p>

                        <form method="POST" action="{{ route('admin.championships.rounds.store', $championship) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Round 1 — Monza">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Track</label>
                                    <input type="text" name="track" class="form-control form-control-sm" placeholder="e.g. Monza">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Round #</label>
                                    <input type="number" name="round_number" class="form-control form-control-sm" placeholder="Auto" min="1">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date & Time (BST)</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-4">
                                    <label class="form-label">Practice <span class="text-secondary">(min)</span></label>
                                    <input type="number" name="practice_duration" class="form-control form-control-sm" placeholder="{{ $championship->practice_duration ?? '' }}">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">Quali <span class="text-secondary">(min)</span></label>
                                    <input type="number" name="qualifying_duration" class="form-control form-control-sm" placeholder="{{ $championship->qualifying_duration ?? '' }}">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">Race <span class="text-secondary">(min)</span></label>
                                    <input type="number" name="race_duration" class="form-control form-control-sm" placeholder="{{ $championship->race_duration ?? '' }}">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm fw-bold text-uppercase text-white w-100" style="background:#db2777">
                                Add Round
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- STANDINGS --}}
    <div x-show="tab==='standings'" style="display:none">
        <div class="admin-card">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Championship Standings</p>

                @if(empty($standings))
                    <p class="text-secondary" style="font-size:.875rem">No results yet.</p>
                @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size:.875rem">
                        <thead style="background:#f9fafb">
                            <tr>
                                <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Pos</th>
                                <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Driver</th>
                                @foreach($rounds->where('status','finished') as $r)
                                <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;color:#9ca3af" title="{{ $r->title }}">R{{ $r->round_number }}</th>
                                @endforeach
                                <th class="fw-bold text-uppercase text-center" style="font-size:.68rem;color:#9ca3af">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($standings as $i => $entry)
                            <tr>
                                <td class="fw-black" style="color:{{ $i === 0 ? '#db2777' : ($i === 1 ? '#7c3aed' : '#374151') }}">P{{ $i + 1 }}</td>
                                <td class="fw-bold">{{ $entry['user']?->name ?? 'Unknown' }}</td>
                                @foreach($rounds->where('status','finished') as $r)
                                    @php
                                        $roundData = collect($entry['rounds'])->firstWhere('race_id', $r->id);
                                        $isDropped = in_array($r->id, $entry['dropped']);
                                    @endphp
                                    <td class="text-center" style="{{ $isDropped ? 'color:#9ca3af;text-decoration:line-through' : '' }}">
                                        {{ $roundData ? $roundData['points'] : '—' }}
                                    </td>
                                @endforeach
                                <td class="text-center fw-black" style="color:#db2777">{{ $entry['total_points'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- REGISTRATIONS --}}
    <div x-show="tab==='registrations'" style="display:none">
        <div class="admin-card">
            <div class="px-4 pt-4 pb-3">
                <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Registered Drivers</p>

                @if($championship->registrations->isEmpty())
                    <p class="text-secondary" style="font-size:.875rem">No registrations yet.</p>
                @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size:.875rem">
                        <thead style="background:#f9fafb">
                            <tr>
                                <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Driver</th>
                                @if($championship->is_multiclass)
                                <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Class</th>
                                @endif
                                <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($championship->registrations as $reg)
                            <tr>
                                <td class="fw-bold">{{ $reg->user?->name }}</td>
                                @if($championship->is_multiclass)
                                <td>
                                    @if($reg->championshipClass)
                                    <span class="badge fw-bold" style="background:{{ $reg->championshipClass->color }}1a;color:{{ $reg->championshipClass->color }};font-size:.7rem">
                                        {{ $reg->championshipClass->name }}
                                    </span>
                                    @else
                                    <span class="text-secondary">—</span>
                                    @endif
                                </td>
                                @endif
                                <td class="text-secondary" style="font-size:.78rem">{{ $reg->created_at->format('d M Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- PENALTIES --}}
    <div x-show="tab==='penalties'" style="display:none">
        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="admin-card">
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Penalties</p>

                        @if($championship->penalties->isEmpty())
                            <p class="text-secondary" style="font-size:.875rem">No penalties.</p>
                        @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="font-size:.875rem">
                                <thead style="background:#f9fafb">
                                    <tr>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Driver</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Points</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Round</th>
                                        <th class="fw-bold text-uppercase" style="font-size:.68rem;color:#9ca3af">Reason</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($championship->penalties as $penalty)
                                    <tr>
                                        <td class="fw-bold">{{ $penalty->user?->name }}</td>
                                        <td class="fw-bold text-danger">{{ $penalty->points > 0 ? '+' : '' }}{{ $penalty->points }}</td>
                                        <td class="text-secondary" style="font-size:.78rem">{{ $penalty->race?->title ?? '—' }}</td>
                                        <td class="text-secondary" style="font-size:.78rem">{{ $penalty->reason ?? '—' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.championships.penalties.destroy', [$championship, $penalty]) }}" onsubmit="return confirm('Delete this penalty?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm text-danger fw-bold" style="font-size:.68rem">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="admin-card">
                    <div class="px-4 pt-4 pb-3">
                        <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Add Penalty</p>

                        <form method="POST" action="{{ route('admin.championships.penalties.store', $championship) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Driver</label>
                                <select name="user_id" class="form-select form-select-sm" required>
                                    <option value="">Select driver...</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Points <span class="text-secondary">(negative = penalty)</span></label>
                                    <input type="number" name="points" class="form-control form-control-sm" placeholder="-5" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Round <span class="text-secondary">(optional)</span></label>
                                    <select name="race_id" class="form-select form-select-sm">
                                        <option value="">— General —</option>
                                        @foreach($rounds as $round)
                                        <option value="{{ $round->id }}">R{{ $round->round_number }} — {{ $round->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason <span class="text-secondary">(optional)</span></label>
                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Causing a collision">
                            </div>
                            <button type="submit" class="btn btn-sm fw-bold text-uppercase text-white w-100" style="background:#dc2626">
                                Add Penalty
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
