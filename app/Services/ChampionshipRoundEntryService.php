<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceTeamEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// A championship entry is entered into every upcoming round automatically — a
// solo driver as a RaceRegistration, a team car as a RaceTeamEntry (+ one
// RaceRegistration per driver) with the car number/model/starting driver picked
// once at championship registration. Both championship registration modes
// (settings.format.team_registration_scope, labelled "Round registration") work
// this way; they only differ in whether the event page may sign a driver up for
// the championship (see RaceController::show()).
//
// A round the entrant left on their own (unregister on the event page) is
// remembered by its soft-deleted row, so no later sync — a new round, a resync,
// an approval — ever puts them back in; only they can, by registering again.
class ChampionshipRoundEntryService
{
    // Called after a registration starts counting (registered without manual
    // approval, approved, or promoted off the championship waiting list), from
    // Admin\ChampionshipWizardController when a round is added, and by
    // championships:sync-round-entries.
    public function syncRoundEntry(ChampionshipRegistration $registration, Race $race): bool
    {
        if (! $this->isUpcoming($race) || $registration->is_spectator || $registration->isPending()) {
            return false;
        }

        return $registration->racing_team_id
            ? $this->syncTeamEntry($registration, $race)
            : $this->syncSoloEntry($registration, $race);
    }

    // Takes the Championship explicitly rather than $registration->championship:
    // the registering driver isn't a member of the league (that's the whole point
    // of a public championship), so the tenant-scoped relation would silently
    // resolve to null for them, same class of bug documented on
    // ChampionshipController::discordMembershipFailure().
    public function syncAllExistingRounds(ChampionshipRegistration $registration, Championship $championship): void
    {
        foreach ($this->upcomingRounds($championship) as $race) {
            $this->syncRoundEntry($registration, $race);
        }
    }

    // Every counting entry into one round — a round that was just added.
    public function syncRound(Championship $championship, Race $race): int
    {
        $added = 0;
        foreach ($this->countingRegistrations($championship) as $registration) {
            $added += (int) $this->syncRoundEntry($registration, $race);
        }

        return $added;
    }

    // Every counting entry into every upcoming round. Safe to run any number of
    // times: an entry already in a round, or one that left it, is left alone.
    public function syncChampionship(Championship $championship): int
    {
        $rounds = $this->upcomingRounds($championship);
        $added = 0;

        foreach ($this->countingRegistrations($championship) as $registration) {
            foreach ($rounds as $race) {
                $added += (int) $this->syncRoundEntry($registration, $race);
            }
        }

        return $added;
    }

    // The reverse: an entry withdrawn from the championship leaves every round
    // that's still open. Deleted for good rather than soft-deleted, so the
    // "left this round on purpose" marker doesn't keep them out should they
    // register for the championship again.
    public function withdrawFromOpenRounds(ChampionshipRegistration $registration, Championship $championship): void
    {
        $openRoundIds = $championship->rounds()->reorder()->where('status', 'open')->select('races.id');

        if (! $registration->racing_team_id) {
            RaceRegistration::withTrashed()
                ->where('user_id', $registration->user_id)
                ->whereNull('team_entry_id')
                ->whereIn('race_id', $openRoundIds)
                ->forceDelete();

            return;
        }

        if ($registration->car_number === null) {
            return;
        }

        RaceTeamEntry::withTrashed()
            ->where('racing_team_id', $registration->racing_team_id)
            ->where('car_number', $registration->car_number)
            ->whereIn('race_id', $openRoundIds)
            ->get()
            ->each(function (RaceTeamEntry $entry) {
                $entry->registrations()->withTrashed()->forceDelete();
                $entry->forceDelete();
            });
    }

    // The championship car a round's team entry stands for (same team, same number).
    public function carRegistrationFor(RaceTeamEntry $entry, Championship $championship): ?ChampionshipRegistration
    {
        if ($entry->car_number === null) {
            return null;
        }

        return $championship->registrations()->approved()
            ->where('racing_team_id', $entry->racing_team_id)
            ->where('car_number', $entry->car_number)
            ->first();
    }

    // Who of the car's line-up + reserve isn't driving this round's entry — the
    // driver a "Swap with …" button brings in. Normally the reserve; after a swap,
    // the driver they replaced.
    public function benchDriverIds(RaceTeamEntry $entry, ChampionshipRegistration $car): array
    {
        $inCar = $entry->registrations()->pluck('user_id')->all();

        return array_values(array_diff($car->carDriverIds(), $inCar));
    }

    // One round only: $outId leaves the car, $inId (from the bench) takes their seat,
    // and the starting spot when they had it. The championship line-up is untouched,
    // so later rounds still get the regular drivers.
    public function swapDriver(RaceTeamEntry $entry, int $outId, int $inId): void
    {
        DB::transaction(function () use ($entry, $outId, $inId) {
            RaceRegistration::where('race_id', $entry->race_id)->where('user_id', $outId)
                ->where('team_entry_id', $entry->id)->forceDelete();

            $seat = RaceRegistration::withTrashed()->firstOrNew(['race_id' => $entry->race_id, 'user_id' => $inId]);
            $seat->team_entry_id = $entry->id;
            $seat->race_class_id = null;
            $seat->deleted_at = null;
            $seat->save();

            if ((int) $entry->starting_driver_id === $outId) {
                $entry->update(['starting_driver_id' => $inId]);
            }
        });
    }

    private function syncSoloEntry(ChampionshipRegistration $registration, Race $race): bool
    {
        // On the championship's waiting list: not racing yet.
        $user = $registration->user;
        if (! $user || Championship::withoutTenantScope()->find($registration->championship_id)?->isRegistrationWaitlisted($user)) {
            return false;
        }

        // Already in, or left this round on their own.
        if (RaceRegistration::withTrashed()->where('race_id', $race->id)->where('user_id', $registration->user_id)->exists()) {
            return false;
        }

        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $registration->user_id]);

        return true;
    }

    private function syncTeamEntry(ChampionshipRegistration $registration, Race $race): bool
    {
        // An entry from before cars were picked at championship registration.
        if ($registration->car_number === null) {
            return false;
        }

        // Matched on the car number too: a team can have several cars in one round.
        // A soft-deleted entry is a car the team took out of this round itself.
        if (RaceTeamEntry::withTrashed()->where('race_id', $race->id)->where('racing_team_id', $registration->racing_team_id)
            ->where('car_number', $registration->car_number)->exists()) {
            return false;
        }

        if (RaceTeamEntry::where('race_id', $race->id)->where('car_number', $registration->car_number)->exists()) {
            Log::warning('Championship team auto-entry skipped: car number already taken in this round', [
                'race_id' => $race->id, 'racing_team_id' => $registration->racing_team_id, 'car_number' => $registration->car_number,
            ]);

            return false;
        }

        $team = $registration->racingTeam;
        if (! $team) {
            return false;
        }

        $entry = RaceTeamEntry::create([
            'race_id' => $race->id,
            'racing_team_id' => $team->id,
            'car_number' => $registration->car_number,
            'car_model' => $registration->car_model,
            'starting_driver_id' => $registration->starting_driver_id,
        ]);

        // Set by hand rather than updateOrCreate(): deleted_at isn't fillable, so a
        // driver's earlier, cancelled row would otherwise stay cancelled.
        foreach ($registration->driverIds() as $userId) {
            $driverRegistration = RaceRegistration::withTrashed()->firstOrNew(['race_id' => $race->id, 'user_id' => $userId]);
            $driverRegistration->team_entry_id = $entry->id;
            $driverRegistration->race_class_id = null;
            $driverRegistration->deleted_at = null;
            $driverRegistration->save();
        }

        return true;
    }

    private function isUpcoming(Race $race): bool
    {
        return $race->status === 'open' && $race->scheduled_at?->isFuture();
    }

    private function upcomingRounds(Championship $championship)
    {
        return $championship->rounds()->where('status', 'open')->where('scheduled_at', '>', now())->get();
    }

    private function countingRegistrations(Championship $championship)
    {
        return $championship->registrations()->approved()->where('is_spectator', false)->with('user', 'racingTeam')->get();
    }
}
