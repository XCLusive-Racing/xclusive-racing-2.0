{{-- The manager's own team cars in this championship, each withdrawable on its
     own (ChampionshipController::unregister(), registration_id). $myTeamCars,
     $ownedTeam and $championship come from the parent view. --}}
@php $teamCarDrivers = \App\Models\User::whereIn('id', $myTeamCars->flatMap->driverIds())->get()->keyBy('id'); @endphp
<p class="text-white fw-bold mb-2" style="font-size:.82rem">
    [{{ $ownedTeam->tag }}] {{ $ownedTeam->name }} — {{ $myTeamCars->count() }} / {{ $championship->maxCarsPerTeam() }} {{ \Illuminate\Support\Str::plural('car', $championship->maxCarsPerTeam()) }}
</p>
@foreach($myTeamCars as $i => $car)
<div class="d-flex align-items-center justify-content-between gap-2 mb-2 p-2" style="background:#1f293766;border:1px solid #374151;border-radius:8px">
    <div style="min-width:0">
        <div class="fw-bold text-white" style="font-size:.8rem">
            {{ $car->car_number !== null ? '#'.$car->car_number : 'Car '.($i + 1) }}
            @if($car->car_model)<span style="color:#9ca3af;font-weight:400"> — {{ $car->car_model }}</span>@endif
        </div>
        <div style="color:#9ca3af;font-size:.72rem">
            {{ collect($car->driverIds())->map(fn ($id) => $teamCarDrivers->get($id)?->displayName())->filter()->join(', ') }}
        </div>
    </div>
    @if($myTeamCars->count() > 1)
    <form method="POST" action="{{ route('championships.unregister', $championship) }}" onsubmit="return confirm('Withdraw this car from the championship?')" class="flex-shrink-0">
        @csrf @method('DELETE')
        <input type="hidden" name="registration_id" value="{{ $car->id }}">
        <button class="btn btn-sm fw-bold text-uppercase text-danger" style="background:#fee2e2;border:1px solid #fca5a5;font-size:.65rem;padding:2px 8px">Withdraw</button>
    </form>
    @endif
</div>
@endforeach
