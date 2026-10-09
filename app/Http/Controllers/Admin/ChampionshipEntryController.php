<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipDriverClass;
use App\Models\ChampionshipPenalty;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\Message;
use App\Models\Race;
use App\Models\User;
use App\Services\AccCarCatalog;
use App\Services\AuditLogger;
use App\Services\ChampionshipEntryMessage;
use App\Services\ChampionshipRoundEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// A championship's entry list for its league staff — and where entries wait when
// settings.requirements.manual_approval_required is on: approving one makes it
// count (standings, rounds); rejecting one removes it.
class ChampionshipEntryController extends Controller
{
    public function index(League $league, Championship $championship)
    {
        $this->authorizeChampionship($league, $championship);

        $entries = $championship->registrations()
            ->with(['user', 'racingTeam.members', 'championshipClass', 'driverClass'])
            ->orderByRaw('approved_at is not null')
            ->orderBy('created_at')
            ->get();

        // Everyone who can score: solo entrants and every driver of a team car.
        $driverIds = $entries->reject(fn ($entry) => $entry->is_spectator || $entry->isPending())
            ->flatMap(fn ($entry) => $entry->racing_team_id ? $entry->driverIds() : [$entry->user_id])
            ->unique();
        $drivers = User::whereIn('id', $driverIds)->orderBy('name')->get();

        $penalties = $championship->penalties()->with(['user', 'race'])->latest()->get();
        $rounds = $championship->rounds()->get(['id', 'round_number', 'title']);

        $driverClasses = $championship->usesDriverClasses()
            ? $championship->driverClasses()->withCount('registrations')->get()
            : collect();

        // Cars a solo entry can be switched to (updateCar()).
        $carOptions = AccCarCatalog::supports($championship->game)
            ? collect(AccCarCatalog::namesWithClass($championship->game))
                ->when(! $championship->is_multiclass && $championship->car_class, fn ($cars) => $cars->filter(fn ($class) => $class === $championship->car_class))
                ->keys()
            : collect();

        return view('admin.leagues.championships.entries', compact('league', 'championship', 'entries', 'drivers', 'penalties', 'rounds', 'driverClasses', 'carOptions'));
    }

    // Points deducted from a driver's championship total (Championship::buildDriverStandings()
    // subtracts them) — the manual counterpart of a stewarding report's points penalty.
    public function storePenalty(Request $request, League $league, Championship $championship)
    {
        $this->authorizeChampionship($league, $championship);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:1|max:999',
            'race_id' => ['nullable', Rule::exists('races', 'id')->where('championship_id', $championship->id)],
            'reason' => 'nullable|string|max:255',
        ]);

        $penalty = $championship->penalties()->create($data);

        AuditLogger::record($request->user(), $championship, 'championship.penalty_added', $data);

        return back()->with('success', $penalty->points.' point(s) deducted from '.$penalty->user->displayName().'.');
    }

    public function destroyPenalty(Request $request, League $league, Championship $championship, ChampionshipPenalty $penalty)
    {
        $this->authorizeChampionship($league, $championship);
        abort_unless($penalty->championship_id === $championship->id, 404);

        $penalty->delete();

        AuditLogger::record($request->user(), $championship, 'championship.penalty_removed', ['penalty_id' => $penalty->id]);

        return back()->with('success', 'Penalty removed.');
    }

    public function approve(Request $request, League $league, Championship $championship, ChampionshipRegistration $registration)
    {
        $this->authorizeChampionship($league, $championship, $registration);
        abort_unless($registration->isPending(), 404);

        $registration->update(['approved_at' => now()]);

        // An entry is only carried into the rounds once it counts.
        app(ChampionshipRoundEntryService::class)->syncAllExistingRounds($registration, $championship);

        // The full welcome message (rounds, servers, passwords) a driver gets straight
        // away without manual approval.
        app(ChampionshipEntryMessage::class)->send($registration, $championship, 'Championship entry approved');
        AuditLogger::record($request->user(), $championship, 'championship.entry_approved', ['registration_id' => $registration->id]);

        return back()->with('success', $this->entrantName($registration).' approved.');
    }

    // Driver classes are assigned by the league by hand — a driver never picks one.
    public function assignDriverClass(Request $request, League $league, Championship $championship, ChampionshipRegistration $registration)
    {
        $this->authorizeChampionship($league, $championship, $registration);
        abort_if($registration->is_spectator, 404);

        $data = $request->validate([
            'driver_class_id' => ['nullable', Rule::exists('championship_driver_classes', 'id')->where('championship_id', $championship->id)],
        ]);

        $class = $data['driver_class_id'] ? ChampionshipDriverClass::find($data['driver_class_id']) : null;
        if ($class?->isFull($registration->id)) {
            return back()->with('error', $class->name.' is full ('.$class->max_entries.' entries).');
        }

        $registration->update(['driver_class_id' => $class?->id]);

        AuditLogger::record($request->user(), $championship, 'championship.driver_class_assigned', [
            'registration_id' => $registration->id,
            'driver_class_id' => $class?->id,
        ]);

        return back()->with('success', $this->entrantName($registration).($class ? ' is now in '.$class->name.'.' : ' is no longer in a class.'));
    }

    // Puts every solo entry still without a driver class into the one its driver's
    // XCL rank matches (Championship::autoAssignDriverClass()) — for entries from
    // before the classes had a rank range. Entries already in a class stay put.
    public function autoAssignDriverClasses(Request $request, League $league, Championship $championship)
    {
        $this->authorizeChampionship($league, $championship);

        $assigned = 0;
        $championship->registrations()->whereNull('driver_class_id')->orderBy('created_at')->with('user')->get()
            ->each(function (ChampionshipRegistration $registration) use ($championship, &$assigned) {
                $assigned += (int) $championship->autoAssignDriverClass($registration);
            });

        AuditLogger::record($request->user(), $championship, 'championship.driver_classes_auto_assigned', ['assigned' => $assigned]);

        return back()->with('success', $assigned === 1 ? '1 entry put in a class.' : $assigned.' entries put in a class.');
    }

    // Repair buttons for a round that was set up wrong (or before people entered):
    // every counting championship entry into every upcoming round, or into one
    // round — without waiting for each driver to sign up again. A round an entry
    // left on its own stays skipped (ChampionshipRoundEntryService).
    public function syncAllRounds(Request $request, League $league, Championship $championship)
    {
        $this->authorizeChampionship($league, $championship);

        $added = app(ChampionshipRoundEntryService::class)->syncChampionship($championship);
        AuditLogger::record($request->user(), $championship, 'championship.entries_synced_to_rounds', ['added' => $added]);

        return back()->with('success', $this->addedMessage($added, 'every upcoming round'));
    }

    public function syncRound(Request $request, League $league, Championship $championship, Race $race)
    {
        $this->authorizeChampionship($league, $championship);
        abort_unless($race->championship_id === $championship->id, 404);

        if ($race->status !== 'open' || ! $race->scheduled_at?->isFuture()) {
            return back()->with('error', 'Entries can only be added to an upcoming round that is still open.');
        }

        $added = app(ChampionshipRoundEntryService::class)->syncRound($championship, $race);
        AuditLogger::record($request->user(), $championship, 'championship.entries_synced_to_round', ['race_id' => $race->id, 'added' => $added]);

        return back()->with('success', $this->addedMessage($added, $race->title));
    }

    private function addedMessage(int $added, string $where): string
    {
        return $added === 0
            ? "Every entry is already in {$where} (or skipped it on purpose)."
            : ($added === 1 ? '1 entry' : "{$added} entries")." added to {$where}.";
    }

    // A solo driver's car and number are locked in at registration
    // (ChampionshipController::register()); only the league changes them, here.
    public function updateCar(Request $request, League $league, Championship $championship, ChampionshipRegistration $registration)
    {
        $this->authorizeChampionship($league, $championship, $registration);
        abort_if($registration->is_spectator || $registration->racing_team_id, 404);
        abort_unless(AccCarCatalog::supports($championship->game), 404);

        $data = $request->validate([
            'car_model' => ['required', 'string', Rule::in(array_keys(AccCarCatalog::namesWithClass($championship->game)))],
            'car_number' => [
                'required', 'integer', 'min:0', 'max:999',
                Rule::unique('championship_registrations', 'car_number')
                    ->where('championship_id', $championship->id)
                    ->ignore($registration->id),
            ],
        ], ['car_number.unique' => 'That car number is already taken in this championship.']);

        $registration->update($data);

        AuditLogger::record($request->user(), $championship, 'championship.entry_car_changed', [
            'registration_id' => $registration->id,
        ] + $data);

        return back()->with('success', $this->entrantName($registration).' now races the '.$data['car_model'].' #'.$data['car_number'].'.');
    }

    public function reject(Request $request, League $league, Championship $championship, ChampionshipRegistration $registration)
    {
        $this->authorizeChampionship($league, $championship, $registration);
        abort_unless($registration->isPending(), 404);

        $name = $this->entrantName($registration);
        $this->notify($registration, $championship, 'rejected', 'Your entry for '.$championship->name.' was not accepted by the league.');
        $registration->delete();

        AuditLogger::record($request->user(), $championship, 'championship.entry_rejected', ['registration_id' => $registration->id]);

        return back()->with('success', $name.' rejected.');
    }

    private function authorizeChampionship(League $league, Championship $championship, ?ChampionshipRegistration $registration = null): void
    {
        abort_unless($championship->league_id === $league->id, 404);
        abort_unless(! $registration || $registration->championship_id === $championship->id, 404);
        Gate::authorize('update', $championship);
    }

    private function notify(ChampionshipRegistration $registration, Championship $championship, string $outcome, string $body): void
    {
        Message::create([
            'user_id' => $registration->user_id,
            'title' => 'Championship entry '.$outcome.': '.$championship->name,
            'body' => $body,
            'type' => 'championship_entry',
            'related_id' => $championship->id,
            'related_type' => Championship::class,
        ]);
    }

    private function entrantName(ChampionshipRegistration $registration): string
    {
        return $registration->racingTeam?->name ?? $registration->user?->displayName() ?? 'Entry';
    }
}
