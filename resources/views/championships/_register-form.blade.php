{{-- The championship sign-up form (driver, team car, or spectator). Shared by the
     championship page and a "per round" championship's event page, where signing
     up for the round enters the championship (ChampionshipRoundEntryService puts
     the new entry into every upcoming round, this one included). Needs
     $championship (classes + league loaded), $accent, $discordRequiredHere and
     $myTeamCars; $fromRound hides the spectator button and words it for a round. --}}
@php $fromRound = $fromRound ?? false; @endphp
@php
    $driverSwaps    = $championship->settings->format->driver_swaps_enabled ?? false;
    $driverFull     = $championship->isFull() && !$championship->waitlistEnabled();
    $spectatorOpen  = $championship->spectatorSlots() > 0 && !$championship->isSpectatorFull();
    $ownedTeam      = $driverSwaps ? auth()->user()->manageableRacingTeam() : null;
@endphp

@if($discordRequiredHere)
<div class="mb-3 p-2" style="background:#5865F21a;border:1px solid #5865F244;border-radius:8px">
    <p class="mb-0" style="color:#c7d2fe;font-size:.78rem">
        <strong>Discord membership required.</strong> You must be a member of
        {{ $championship->league->name }}'s Discord server to register
        @if($championship->league->discord_invite_url)
        — <a href="{{ $championship->league->discord_invite_url }}" target="_blank" rel="noopener" style="color:#a5b4fc">join here</a>
        @endif.
    </p>
</div>
@endif

@if($driverFull && !$spectatorOpen)
<p style="color:#f59e0b;font-size:.875rem;font-weight:700">This championship is full.</p>
@endif

{{-- Driver swaps = a team championship: drivers race as part of a team
     car, so only a team owner/manager can enter (ChampionshipController::register()). --}}
@if(!$driverFull && $driverSwaps && !$ownedTeam)
<p style="color:#9ca3af;font-size:.82rem" class="mb-0">
    This is a team championship — teams are registered by their owner or manager.
    Ask yours to register your team, or <a href="{{ route('racing-teams.index') }}" style="color:#e5e7eb;text-decoration:underline">create a team</a>.
</p>
@elseif(!$driverFull)
{{-- Outside the register form: the cars' Withdraw forms can't be
     nested in it — a browser drops a nested <form>, so its DELETE
     would submit to the register route instead. --}}
@if($driverSwaps && $myTeamCars->isNotEmpty())
@include('championships._team-cars')
@endif
<form method="POST" action="{{ route('championships.register', $championship) }}">
    @csrf
    @if($driverSwaps)
    <input type="hidden" name="racing_team_id" value="{{ $ownedTeam->id }}">
    @if($myTeamCars->isNotEmpty())
    <p class="fw-black text-uppercase mb-2 mt-3" style="color:#9ca3af;font-size:.72rem;letter-spacing:.06em">Add another car</p>
    @else
    <p class="text-white mb-2" style="font-size:.82rem">
        Registering <strong>[{{ $ownedTeam->tag }}] {{ $ownedTeam->name }}</strong>
    </p>
    @endif
    @endif

    {{-- The class comes first: the car dropdown below only opens once one is picked,
         and then only offers that class's cars (server-side checked on submit). --}}
    @if($championship->is_multiclass && $championship->classes->isNotEmpty())
    <div class="mb-3">
        <label class="form-label text-white" style="font-size:.82rem">Select Class</label>
        <select name="championship_class_id" data-class-select class="form-select form-select-sm" required
                style="background:#1f2937;border-color:#374151;color:#e5e7eb">
            <option value="">Choose your class...</option>
            @foreach($championship->classes as $cls)
            <option value="{{ $cls->id }}" data-car-class="{{ $cls->car_class }}" {{ (string) old('championship_class_id') === (string) $cls->id ? 'selected' : '' }} @disabled($cls->isFull())>{{ $cls->name }}{{ $cls->car_class && $cls->car_class !== $cls->name ? ' (' . $cls->car_class . ')' : '' }}{{ $cls->isFull() ? ' — full' : '' }}</option>
            @endforeach
        </select>
    </div>
    <script>
    // No car until a class is picked; then only that class's cars. Runs once the
    // page is parsed: the car dropdown comes after this script.
    document.addEventListener('DOMContentLoaded', function () {
        var classSelect = document.querySelector('[data-class-select]');
        var carSelect   = document.querySelector('[data-car-select]');
        if (!classSelect || !carSelect) return;
        var placeholder = carSelect.options[0];
        function sync() {
            var wanted = classSelect.selectedOptions[0]?.dataset.carClass || '';
            carSelect.disabled = !classSelect.value;
            placeholder.textContent = classSelect.value ? '— Select car —' : '— Select a class first —';
            Array.from(carSelect.options).forEach(function (o) {
                o.hidden = o.disabled = !!(o.value && wanted && o.dataset.carClass !== wanted);
            });
            if (carSelect.selectedOptions[0]?.disabled) carSelect.value = '';
        }
        classSelect.addEventListener('change', sync);
        sync();
    });
    </script>
    @endif

    @if($driverSwaps && $ownedTeam)
    @php
        $teamDrivers = collect([$ownedTeam->owner])->concat($ownedTeam->members)->filter()->unique('id');
        $minDrivers  = (int) ($championship->settings->format->min_drivers_per_car ?? 0);
        $maxDrivers  = (int) ($championship->settings->format->max_drivers_per_car ?? 0);
        // A driver can only be in one car of this championship.
        $takenDriverIds = $championship->driverIdsInCars();
        $selectedDriverIds = collect(old('driver_ids', [auth()->id()]))->map(fn ($id) => (int) $id)->diff($takenDriverIds);
    @endphp
    <div id="teamEntryFields" class="mb-3 p-2" style="background:#1f293766;border:1px solid #374151;border-radius:8px">
        <p style="color:#9ca3af;font-size:.72rem" class="mb-2">
            Your drivers, car number, model and starting driver are entered into every round automatically. Your team can skip a round on its event page; only the league can change the car after you register.
        </p>
        <div class="mb-2">
            <label class="form-label text-white mb-1" style="font-size:.78rem">
                Drivers
                @if($minDrivers && $minDrivers === $maxDrivers)
                <span style="color:#9ca3af;font-weight:400">(pick {{ $minDrivers }})</span>
                @elseif($minDrivers || $maxDrivers)
                <span style="color:#9ca3af;font-weight:400">({{ $minDrivers ?: 1 }}–{{ $maxDrivers ?: $teamDrivers->count() }})</span>
                @endif
            </label>
            @foreach($teamDrivers as $driver)
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" name="driver_ids[]" value="{{ $driver->id }}"
                       id="champDriver{{ $driver->id }}" data-team-driver
                       {{ $selectedDriverIds->contains($driver->id) ? 'checked' : '' }}
                       {{ $takenDriverIds->contains($driver->id) ? 'disabled' : '' }}>
                <label class="form-check-label" for="champDriver{{ $driver->id }}" style="color:#e5e7eb;font-size:.82rem">
                    {{ $driver->displayName() }}@if($driver->id === $ownedTeam->owner_id) <span style="color:#6b7280">(owner)</span>@endif
                    @if($takenDriverIds->contains($driver->id)) <span style="color:#6b7280">(already in a car)</span>@endif
                </label>
            </div>
            @endforeach
        </div>
        <div class="mb-2">
            <label class="form-label text-white" style="font-size:.78rem">Car Number</label>
            <input type="number" name="car_number" min="0" max="999" class="form-control form-control-sm"
                   style="background:#1f2937;border-color:#374151;color:#e5e7eb">
        </div>
        <div class="mb-2">
            <label class="form-label text-white" style="font-size:.78rem">Car Model</label>
            @php
                // ACC's own car catalogue for the championship's platform, narrowed
                // to its single car class; with multiclass the class picker
                // narrows it further client-side (picked above) (and server-side on submit).
                $carOptions = collect(\App\Services\AccCarCatalog::namesWithClass($championship->game))
                    ->when(!$championship->is_multiclass && $championship->car_class,
                        fn ($cars) => $cars->filter(fn ($class) => $class === $championship->car_class));
            @endphp
            @if($carOptions->isNotEmpty())
            <select name="car_model" data-car-select class="form-select form-select-sm"
                    style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                <option value="">— Select car —</option>
                @foreach($carOptions as $carName => $carClass)
                <option value="{{ $carName }}" data-car-class="{{ $carClass }}" {{ old('car_model') === $carName ? 'selected' : '' }}>{{ $carName }}</option>
                @endforeach
            </select>
            @else
            <input type="text" name="car_model" class="form-control form-control-sm"
                   value="{{ old('car_model') }}"
                   style="background:#1f2937;border-color:#374151;color:#e5e7eb">
            @endif
        </div>
        <div class="mb-0">
            <label class="form-label text-white" style="font-size:.78rem">Starting Driver</label>
            <select name="starting_driver_id" data-starting-driver class="form-select form-select-sm"
                    style="background:#1f2937;border-color:#374151;color:#e5e7eb">
                @foreach($teamDrivers as $driver)
                <option value="{{ $driver->id }}" {{ (int) old('starting_driver_id') === $driver->id ? 'selected' : '' }}>{{ $driver->displayName() }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <script>
    // Only a picked driver can start the race.
    (function () {
        var boxes   = document.querySelectorAll('[data-team-driver]');
        var starter = document.querySelector('[data-starting-driver]');
        if (!starter) return;
        function sync() {
            var picked = Array.from(boxes).filter(function (b) { return b.checked; }).map(function (b) { return b.value; });
            Array.from(starter.options).forEach(function (o) { o.hidden = o.disabled = picked.indexOf(o.value) === -1; });
            if (starter.selectedOptions[0]?.disabled) {
                var first = Array.from(starter.options).find(function (o) { return !o.disabled; });
                starter.value = first ? first.value : '';
            }
        }
        boxes.forEach(function (b) { b.addEventListener('change', sync); });
        sync();
    })();
    </script>
    @endif

    {{-- A solo driver picks car and number once; every round forces that
         car, and only the league can change it (Entries page). --}}
    @if(!$driverSwaps && \App\Services\AccCarCatalog::supports($championship->game))
    @php
        $soloCarOptions = collect(\App\Services\AccCarCatalog::namesWithClass($championship->game))
            ->when(!$championship->is_multiclass && $championship->car_class,
                fn ($cars) => $cars->filter(fn ($class) => $class === $championship->car_class));
    @endphp
    <div class="mb-3">
        <label class="form-label text-white" style="font-size:.82rem">Car</label>
        <select name="car_model" data-car-select class="form-select form-select-sm" required
                style="background:#1f2937;border-color:#374151;color:#e5e7eb">
            <option value="">— Select car —</option>
            @foreach($soloCarOptions as $carName => $carClass)
            <option value="{{ $carName }}" data-car-class="{{ $carClass }}" @selected(old('car_model') === $carName)>{{ $carName }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-2">
        <label class="form-label text-white" style="font-size:.82rem">Car Number</label>
        <input type="number" name="car_number" min="0" max="999" required
               value="{{ old('car_number', auth()->user()->car_number) }}"
               class="form-control form-control-sm" style="background:#1f2937;border-color:#374151;color:#e5e7eb">
    </div>
    <p class="mb-3" style="color:#9ca3af;font-size:.72rem">
        You race this car and number in every round. Only the league can change them after you register.
    </p>
    @endif

    @if($championship->isFull())
    <p class="fw-bold mb-3" style="color:#f59e0b;font-size:.8rem">Full — you'll join the waiting list.</p>
    @endif

    @if($fromRound)
    <p class="mb-3" style="color:#9ca3af;font-size:.72rem">
        Signing up for this round enters you into
        <a href="{{ route('championships.show', $championship) }}" style="color:#e5e7eb;text-decoration:underline">{{ $championship->name }}</a>:
        you're entered into every round and can skip any of them on its event page.
    </p>
    @endif

    <button type="submit" class="btn fw-black text-uppercase text-white w-100"
            style="background:{{ $accent }};font-size:.82rem">
        {{ $championship->isFull() ? 'Join Waiting List' : ($myTeamCars->isNotEmpty() ? 'Add Car' : 'Register Now') }}
    </button>
</form>
@endif

@if($spectatorOpen && $myTeamCars->isEmpty() && ! $fromRound)
<form method="POST" action="{{ route('championships.register', $championship) }}" class="{{ $driverFull ? '' : 'mt-2' }}">
    @csrf
    <input type="hidden" name="is_spectator" value="1">
    <button type="submit" class="btn btn-sm fw-bold text-uppercase w-100"
            style="background:transparent;border:1px solid #374151;color:#9ca3af;font-size:.75rem">
        Register as Spectator
    </button>
</form>
@endif
