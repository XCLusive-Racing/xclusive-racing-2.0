<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\PracticeServer;
use App\Models\PracticeServerSession;
use App\Models\Race;
use Illuminate\Support\Collection;

// One row per server for the admin Server Status page: what was last pushed to it,
// what's next, when results last came in, what practice is on it — and whether
// anything needs a look. Everything here is read from what the scheduled commands
// (gportal:push-configs, gportal:import-results, practice pushes) already record;
// nothing talks to the servers themselves.
class ServerStatusService
{
    // gportal:import-results only looks for a race's results 30 minutes after it
    // started; a race still without results an hour after that is overdue — if
    // anyone signed up for it (an empty race never produces results).
    public const RESULTS_OVERDUE_MINUTES = 90;

    // Look this far back for overdue results and failed pushes — older ones are
    // history, not something to act on now.
    public const LOOKBACK_HOURS = 48;

    public const HEALTH_OK = 'ok';

    public const HEALTH_WARNING = 'warning';

    public const HEALTH_INACTIVE = 'inactive';

    /** @return Collection<int, array> */
    public function rows(): Collection
    {
        $servers = FtpServer::withoutTenantScope()->with('league')->orderBy('league_id')->orderBy('name')->get();
        $since = now()->subHours(self::LOOKBACK_HOURS);

        $races = Race::whereIn('ftp_server_id', $servers->pluck('id'))
            ->where(fn ($q) => $q->where('scheduled_at', '>=', $since)->orWhereNotNull('config_pushed_at'))
            ->withCount(['raceResults', 'registrations'])
            ->get()
            ->groupBy('ftp_server_id');

        $lastImports = FtpImportedFile::with('race')
            ->whereIn('id', FtpImportedFile::selectRaw('max(id)')->groupBy('ftp_server_id'))
            ->get()
            ->keyBy('ftp_server_id');

        $practiceSessions = PracticeServer::with(['sessions' => fn ($q) => $q->latest('window_start')->limit(1)])
            ->get()
            ->mapWithKeys(fn (PracticeServer $practice) => [$practice->ftp_server_id => $practice->sessions->first()]);

        $championshipPractice = $this->championshipPractice();

        return $servers->map(fn (FtpServer $server) => $this->row(
            $server,
            $races->get($server->id, collect()),
            $lastImports->get($server->id),
            $practiceSessions->get($server->id),
            $championshipPractice->get($server->id),
        ));
    }

    private function row(FtpServer $server, Collection $races, ?FtpImportedFile $lastImport, ?PracticeServerSession $practiceSession, ?Championship $championshipPractice): array
    {
        $since = now()->subHours(self::LOOKBACK_HOURS);

        $lastPush = $races->whereNotNull('config_pushed_at')->sortByDesc('config_pushed_at')->first();
        $nextRace = $races->filter(fn (Race $race) => $race->scheduled_at->isFuture() && $race->status === 'open')
            ->sortBy('scheduled_at')->first();
        $failedPushes = $races->filter(fn (Race $race) => $race->config_push_status === 'failed' && $race->scheduled_at->gte($since))
            ->sortBy('scheduled_at')->values();
        $overdueResults = $races->filter(fn (Race $race) => $race->status !== 'cancelled'
            && $race->race_results_count === 0
            && $race->registrations_count > 0
            && $race->scheduled_at->between($since, now()->subMinutes(self::RESULTS_OVERDUE_MINUTES)))
            ->sortBy('scheduled_at')->values();

        $practiceError = ($practiceSession?->status === PracticeServerSession::STATUS_FAILED ? $practiceSession->last_error : null)
            ?? $championshipPractice?->practice_push_error;

        $problems = array_filter([
            $failedPushes->isNotEmpty() ? $failedPushes->count().' failed config '.str('push')->plural($failedPushes->count()) : null,
            $overdueResults->isNotEmpty() ? $overdueResults->count().' '.str('race')->plural($overdueResults->count()).' without results' : null,
            $practiceError ? 'Practice push failed' : null,
        ]);

        return [
            'server' => $server,
            'health' => ! $server->active ? self::HEALTH_INACTIVE : ($problems ? self::HEALTH_WARNING : self::HEALTH_OK),
            'problems' => array_values($problems),
            'last_push' => $lastPush,
            'next_race' => $nextRace,
            'failed_pushes' => $failedPushes,
            'overdue_results' => $overdueResults,
            'last_import' => $lastImport,
            'practice_session' => $practiceSession,
            'championship_practice' => $championshipPractice,
            'practice_error' => $practiceError,
        ];
    }

    // The championship whose 24h practice was last pushed to each server
    // (ChampionshipPracticeService) — its round's server, else its default one.
    private function championshipPractice(): Collection
    {
        return Championship::withoutTenantScope()
            ->whereNotNull('practice_pushed_at')
            ->with('practiceRace')
            ->orderBy('practice_pushed_at')
            ->get()
            ->keyBy(fn (Championship $championship) => $championship->practiceRace?->ftp_server_id ?? $championship->ftp_server_id)
            ->forget('');
    }
}
