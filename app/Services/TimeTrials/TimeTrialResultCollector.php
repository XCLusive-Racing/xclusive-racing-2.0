<?php

namespace App\Services\TimeTrials;

use App\Models\TimeTrialCar;
use App\Models\TimeTrialEvent;
use App\Models\TimeTrialEventLap;
use App\Models\TimeTrialLap;
use App\Models\TimeTrialResultFile;
use App\Services\AccCarCatalog;
use App\Services\AccResultImportService;
use App\Services\FtpService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Collects a weekly event's result files (…_FP.json and …_Q.json, one per session) from its
// server and stores each driver's best valid lap per car per file. The server lets every
// member in, so only drivers signed up on the website are kept, and only from sessions that
// ended after they signed up. Each file is read once
// (time_trial_result_files), so it can run as often as the scheduler likes.
class TimeTrialResultCollector
{
    public function __construct(
        private readonly FtpService $ftp,
        private readonly AccResultImportService $decoder,
    ) {}

    /** @return array{files: int, laps: int, error: ?string} */
    public function collect(TimeTrialEvent $event): array
    {
        $server = $event->server;
        $summary = ['files' => 0, 'laps' => 0, 'error' => null];

        if (! $server) {
            return ['error' => 'The event has no server.'] + $summary;
        }

        if (! $this->ftp->connect($server)) {
            return $this->finish($event, ['error' => "Could not connect to {$server->host}:{$server->port}"] + $summary);
        }

        $path = rtrim($server->path ?: '/results', '/');
        $seen = TimeTrialResultFile::where('ftp_server_id', $server->id)->pluck('filename')->flip();

        $files = collect($this->ftp->listFiles($path)['all'])
            ->filter(fn ($name) => preg_match('/_(FP|Q)\.json$/i', $name) && ! isset($seen[$name]))
            ->filter(fn ($name) => $this->belongsToEvent($event, self::fileTime($name)))
            ->sort()
            ->values();

        foreach ($files as $name) {
            $content = $this->ftp->getFileContent("{$path}/{$name}");
            if ($content === false) {
                Log::warning("Time Trial results: download failed for {$name} (event #{$event->id})");

                continue;
            }

            $laps = $this->storeFile($event, $server->id, $name, $content);
            $summary['files']++;
            $summary['laps'] += $laps;
        }

        $this->ftp->disconnect();

        return $this->finish($event, $summary);
    }

    private function finish(TimeTrialEvent $event, array $summary): array
    {
        $event->update(['results_checked_at' => now(), 'results_error' => $summary['error']]);

        return $summary;
    }

    // A session that started inside the event window: its file is written when the session
    // ends, so it may land up to one session length after the event closes.
    private function belongsToEvent(TimeTrialEvent $event, ?Carbon $fileTime): bool
    {
        $interval = self::sessionLoopMinutes();

        return $fileTime !== null
            && $fileTime->gt($event->starts_at)
            && $fileTime->lte($event->ends_at->copy()->addMinutes($interval));
    }

    // Parses one result file and stores its laps. Returns the number of lap rows stored.
    public function storeFile(TimeTrialEvent $event, int $serverId, string $name, string $content): int
    {
        [$json, $error] = $this->decoder->decodeContent($content, $name);
        $rows = $error ? [] : $this->lapsFromResult(json_decode($json, true), $event, $name);

        DB::transaction(function () use ($event, $serverId, $name, $rows) {
            foreach ($rows as $row) {
                TimeTrialEventLap::firstOrCreate([
                    'time_trial_event_id' => $event->id,
                    'result_file' => $name,
                    'platform_identifier' => $row['platform_identifier'],
                    'car_id' => $row['car_id'],
                ], $row);
            }

            TimeTrialResultFile::firstOrCreate(
                ['ftp_server_id' => $serverId, 'filename' => $name],
                ['time_trial_event_id' => $event->id, 'lap_count' => count($rows)]
            );
        });

        if ($error) {
            Log::warning("Time Trial results: {$error}");
        }

        return count($rows);
    }

    public static function sessionLoopMinutes(): int
    {
        return (int) config('time_trials.practice_minutes', 2) + (int) config('time_trials.qualifying_minutes', 30) + 15;
    }

    /** Each signed-up driver's best valid lap per car in the file's practice and qualifying sessions. */
    public function lapsFromResult(?array $data, TimeTrialEvent $event, string $name): array
    {
        $sessions = isset($data['sessions']) ? $data['sessions'] : (isset($data[0]) ? $data : [$data]);
        $users = $event->drivers()->with('connectedAccounts')->get()
            ->keyBy(fn ($user) => $user->playerIdFor('acc'));
        $recordedAt = self::fileTime($name);

        $best = [];
        foreach ($sessions as $session) {
            if (! in_array($session['sessionType'] ?? null, ['FP', 'P', 'Q'], true)
                || ($session['trackName'] ?? null) !== $event->track) {
                continue;
            }

            $cars = collect($session['sessionResult']['leaderBoardLines'] ?? [])
                ->mapWithKeys(fn ($line) => [$line['car']['carId'] ?? -1 => $line['car']]);

            foreach ($session['laps'] ?? [] as $lap) {
                $car = $cars[$lap['carId'] ?? -1] ?? null;
                $driver = $car['drivers'][$lap['driverIndex'] ?? 0] ?? null;
                $playerId = $driver['playerId'] ?? null;
                $lapMs = (int) ($lap['laptime'] ?? 0);
                $carModel = $car['carModel'] ?? null;

                if (! ($lap['isValidForBest'] ?? false) || ! $playerId || $carModel === null
                    || $lapMs <= 0 || $lapMs >= 2147483647 || ! $users->has($playerId)
                    || ($recordedAt && $users[$playerId]->pivot->created_at?->gt($recordedAt))) {
                    continue;
                }

                $key = $playerId.'|'.$carModel;
                if (isset($best[$key]) && $best[$key]['lap_time_ms'] <= $lapMs) {
                    continue;
                }

                $splits = array_values($lap['splits'] ?? []);
                $user = $users[$playerId];
                $best[$key] = [
                    'user_id' => $user->id,
                    'platform_identifier' => $playerId,
                    'platform' => TimeTrialLap::platformFromIdentifier($playerId) ?? 'pc',
                    'driver_name' => $user->name,
                    'car_id' => $this->carId((int) $carModel),
                    'car_class' => AccCarCatalog::carClass((int) $carModel, 'acc') ?? ($event->car_class ?? 'GT3'),
                    'lap_time_ms' => $lapMs,
                    'sector1_ms' => $splits[0] ?? null,
                    'sector2_ms' => $splits[1] ?? null,
                    'sector3_ms' => $splits[2] ?? null,
                    'recorded_at' => $recordedAt,
                ];
            }
        }

        return array_values($best);
    }

    // Car IDs follow the console numbering; a car the lookup doesn't know yet is added from
    // the ACC catalogue so the leaderboards can name it.
    private function carId(int $carModel): int
    {
        if (! TimeTrialCar::whereKey($carModel)->exists()) {
            $name = AccCarCatalog::name($carModel, 'acc');
            preg_match('/^(.*) \((\d{4})\)$/', $name, $m);
            TimeTrialCar::create([
                'id' => $carModel,
                'name' => $m[1] ?? $name,
                'year' => isset($m[2]) ? (int) $m[2] : null,
                'acc_car_model' => AccCarCatalog::carClass($carModel, 'acc') ? $carModel : null,
            ]);
        }

        return $carModel;
    }

    // gPortal names result files {YYMMDD}_{HHmmss}_FP.json in the server's local time
    // (Europe/Berlin), same convention ImportGportalResults relies on.
    public static function fileTime(string $name): ?Carbon
    {
        if (! preg_match('/^(\d{6})_(\d{4})/', $name, $m)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('ymd Hi', $m[1].' '.$m[2], 'Europe/Berlin')->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
