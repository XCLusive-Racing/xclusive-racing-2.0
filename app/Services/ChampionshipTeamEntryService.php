<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceTeamEntry;
use Illuminate\Support\Facades\Log;

// Refinement request: team registration needed an option for "per round" vs
// "whole championship" — today a team re-registers (RaceController::registerTeam())
// for every round even after registering once at the championship level
// (ChampionshipController::register()). When a championship's
// settings.format.team_registration_scope is "championship", this auto-creates
// the exact same RaceTeamEntry + RaceRegistration rows a manual per-round
// registration would, from the car number/model/starting driver captured once
// at championship-registration time.
class ChampionshipTeamEntryService
{
    // Called right after a team registers at the championship level, and from
    // Admin\ChampionshipWizardController when a new round is added — either
    // way, the same per-round entry gets created without anyone re-registering.
    public function syncRoundEntry(ChampionshipRegistration $registration, Race $race): void
    {
        if (!$registration->racing_team_id || $registration->car_number === null) {
            return;
        }

        if (RaceTeamEntry::where('race_id', $race->id)->where('racing_team_id', $registration->racing_team_id)->exists()) {
            return; // already entered this round (e.g. sync ran twice)
        }

        if (RaceTeamEntry::where('race_id', $race->id)->where('car_number', $registration->car_number)->exists()) {
            Log::warning('Championship team auto-entry skipped: car number already taken in this round', [
                'race_id' => $race->id, 'racing_team_id' => $registration->racing_team_id, 'car_number' => $registration->car_number,
            ]);
            return;
        }

        $team = $registration->racingTeam;
        if (!$team) {
            return;
        }

        $entry = RaceTeamEntry::create([
            'race_id'            => $race->id,
            'racing_team_id'     => $team->id,
            'car_number'         => $registration->car_number,
            'car_model'          => $registration->car_model,
            'starting_driver_id' => $registration->starting_driver_id,
        ]);

        $memberIds = $team->members->pluck('id')->push($team->owner_id)->unique();

        foreach ($memberIds as $userId) {
            RaceRegistration::withTrashed()->updateOrCreate(
                ['race_id' => $race->id, 'user_id' => $userId],
                ['team_entry_id' => $entry->id, 'deleted_at' => null]
            );
        }
    }

    // Called once, right after championship-level team registration — carries
    // the team into every round that already exists (rounds added afterwards
    // are handled at creation time instead, see syncRoundEntry() above).
    // Takes the Championship explicitly rather than $registration->championship:
    // the registering driver isn't a member of the league (that's the whole
    // point of a public championship), so the tenant-scoped relation would
    // silently resolve to null for them, same class of bug documented on
    // ChampionshipController::discordMembershipFailure().
    public function syncAllExistingRounds(ChampionshipRegistration $registration, Championship $championship): void
    {
        foreach ($championship->rounds as $race) {
            $this->syncRoundEntry($registration, $race);
        }
    }
}
