<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\Message;
use App\Services\AuditLogger;
use App\Services\ChampionshipTeamEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// A championship's entry list for its league staff — and where entries wait when
// settings.requirements.manual_approval_required is on: approving one makes it
// count (standings, rounds); rejecting one removes it.
class ChampionshipEntryController extends Controller
{
    public function index(League $league, Championship $championship)
    {
        $this->authorizeChampionship($league, $championship);

        $entries = $championship->registrations()
            ->with(['user', 'racingTeam', 'championshipClass'])
            ->orderByRaw('approved_at is not null')
            ->orderBy('created_at')
            ->get();

        return view('admin.leagues.championships.entries', compact('league', 'championship', 'entries'));
    }

    public function approve(Request $request, League $league, Championship $championship, ChampionshipRegistration $registration)
    {
        $this->authorizeChampionship($league, $championship, $registration);
        abort_unless($registration->isPending(), 404);

        $registration->update(['approved_at' => now()]);

        // A "whole championship" team car is only carried into the rounds once it counts.
        if ($registration->racing_team_id && ($championship->settings->format->team_registration_scope ?? 'per_round') === 'championship') {
            app(ChampionshipTeamEntryService::class)->syncAllExistingRounds($registration, $championship);
        }

        $this->notify($registration, $championship, 'approved', 'Your entry for '.$championship->name.' has been approved — see you on track!');
        AuditLogger::record($request->user(), $championship, 'championship.entry_approved', ['registration_id' => $registration->id]);

        return back()->with('success', $this->entrantName($registration).' approved.');
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
