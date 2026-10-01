{{-- A driver-swap championship round's team sign-up. The team's championship cars
     are entered automatically (ChampionshipRoundEntryService); a car the team took
     out of this round can be put back. A team without a championship entry signs
     up for the championship here ("per round") or on the championship page. --}}
@if($race->registrationOpen())
    @foreach($championshipSkippedCars as $car)
    <form action="{{ route('events.register-team', $race) }}" method="POST"
          class="d-flex align-items-center justify-content-between gap-2 mt-2 p-2"
          style="background:#1f293766;border:1px solid #374151;border-radius:8px">
        @csrf
        <input type="hidden" name="championship_registration_id" value="{{ $car->id }}">
        <span style="font-size:.8rem;color:#9ca3af;min-width:0">
            <span style="color:#e5e7eb;font-weight:700">#{{ $car->car_number }}</span>
            @if($car->car_model)— {{ $car->car_model }}@endif
            <span class="d-block" style="font-size:.72rem">Skipping this round</span>
        </span>
        <button type="submit" class="xcl-event-reg-btn flex-shrink-0" style="background:{{ $race->gameColor() }};font-size:.7rem;padding:4px 10px">
            RE-ENTER →
        </button>
    </form>
    @endforeach

    @if($championshipTeamCars->isEmpty())
        @if($championshipTeamPending)
            <p class="xcl-event-card__text mb-0" style="font-size:.82rem">Your team's championship entry is waiting for approval by the league.</p>
        @elseif($championshipSignupForm)
            @include('championships._register-form', [
                'championship' => $roundChampionship,
                'accent' => $roundChampionship->league?->primary_color ?? $race->gameColor(),
                'discordRequiredHere' => ($roundChampionship->league?->requires_discord_membership) || ($roundChampionship->settings->requirements->discord_membership_required ?? false),
                'myTeamCars' => collect(),
                'fromRound' => true,
            ])
        @else
            <p class="xcl-event-card__text mb-0" style="font-size:.82rem">
                Register your team for the
                <a href="{{ route('championships.show', $race->championship_id) }}" style="color:#e5e7eb;text-decoration:underline">championship</a>
                to enter this round — every round is entered automatically once your team is in.
            </p>
        @endif
    @endif
@endif
