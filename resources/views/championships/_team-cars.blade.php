{{-- The manager's own team cars in this championship, each withdrawable on its
     own (ChampionshipController::unregister(), registration_id), and each with an
     Edit to pick its reserve driver (ChampionshipController::updateReserve()).
     $myTeamCars, $ownedTeam and $championship come from the parent view. --}}
@php
    $teamCarDrivers = \App\Models\User::whereIn('id', $myTeamCars->flatMap->carDriverIds())->get()->keyBy('id');
    $teamMembers = collect([$ownedTeam->owner])->concat($ownedTeam->members)->filter()->unique('id');
    $takenDriverIds = $championship->driverIdsInCars();
@endphp
<p class="text-white fw-bold mb-2" style="font-size:.82rem">
    [{{ $ownedTeam->tag }}] {{ $ownedTeam->name }} — {{ $myTeamCars->count() }} / {{ $championship->maxCarsPerTeam() }} {{ \Illuminate\Support\Str::plural('car', $championship->maxCarsPerTeam()) }}
</p>
@foreach($myTeamCars as $i => $car)
@php
    // Free for this car's reserve: team members in no car yet, plus its current reserve.
    $reserveOptions = $teamMembers->reject(fn ($member) => $takenDriverIds->contains($member->id) && $member->id !== $car->reserve_driver_id);
@endphp
<div class="mb-2 p-2" style="background:#1f293766;border:1px solid #374151;border-radius:8px">
    <div class="d-flex align-items-center justify-content-between gap-2">
        <div style="min-width:0">
            <div class="fw-bold text-white" style="font-size:.8rem">
                {{ $car->car_number !== null ? '#'.$car->car_number : 'Car '.($i + 1) }}
                @if($car->car_model)<span style="color:#9ca3af;font-weight:400"> — {{ $car->car_model }}</span>@endif
            </div>
            <div style="color:#9ca3af;font-size:.72rem">
                {{ collect($car->driverIds())->map(fn ($id) => $teamCarDrivers->get($id)?->displayName())->filter()->join(', ') }}
            </div>
            @if($car->reserve_driver_id)
            <div style="color:#9ca3af;font-size:.72rem">Reserve: <span class="text-white">{{ $teamCarDrivers->get($car->reserve_driver_id)?->displayName() }}</span></div>
            @endif
        </div>
        <div class="d-flex gap-1 flex-shrink-0">
            <button type="button" class="btn btn-sm fw-bold text-uppercase" data-bs-toggle="collapse" data-bs-target="#carEdit{{ $car->id }}"
                    style="background:#374151;border:1px solid #4b5563;color:#e5e7eb;font-size:.65rem;padding:2px 8px">Edit</button>
            {{-- Also for a team's only car — while it can still add more, this list is
                 the only place left to withdraw it. --}}
            <form method="POST" action="{{ route('championships.unregister', $championship) }}" onsubmit="return confirm('Withdraw this car from the championship?')">
                @csrf @method('DELETE')
                <input type="hidden" name="registration_id" value="{{ $car->id }}">
                <button class="btn btn-sm fw-bold text-uppercase text-danger" style="background:#fee2e2;border:1px solid #fca5a5;font-size:.65rem;padding:2px 8px">Withdraw</button>
            </form>
        </div>
    </div>
    <div class="collapse mt-2" id="carEdit{{ $car->id }}">
        <form method="POST" action="{{ route('championships.cars.reserve', [$championship, $car]) }}" class="d-flex gap-2 align-items-end">
            @csrf @method('PUT')
            <div class="flex-grow-1">
                <label class="form-label mb-1" style="color:#9ca3af;font-size:.72rem">Reserve driver — can swap in for one of your drivers on a round's event page</label>
                <select name="reserve_driver_id" class="form-select form-select-sm" style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                    <option value="">No reserve</option>
                    @foreach($reserveOptions as $member)
                    <option value="{{ $member->id }}" @selected($member->id === $car->reserve_driver_id)>{{ $member->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-sm fw-bold text-uppercase text-white" style="background:#7c3aed;font-size:.7rem">Save</button>
        </form>
    </div>
</div>
@endforeach
