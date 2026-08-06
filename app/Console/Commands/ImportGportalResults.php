<?php

namespace App\Console\Commands;

use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\Race;
use App\Services\AccResultImportService;
use App\Services\FtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportGportalResults extends Command
{
    protected $signature   = 'gportal:import-results';
    protected $description = 'Auto-close overdue races and auto-import their results from gPortal FTP once available';

    // How long after scheduled_at before we start looking for a results file —
    // covers even the shortest sprint format, and we just retry every run until
    // a matching file shows up (harmless no-op otherwise).
    private const RESULT_WAIT_MINUTES = 30;

    // How far past a race's slot_time a result file's own timestamp may be and
    // still count as "this race's file" — generous enough to cover endurance races.
    private const MATCH_WINDOW_HOURS = 6;

    public function handle(AccResultImportService $importer, FtpService $ftp): void
    {
        $closed = Race::where('status', 'open')
            ->where('scheduled_at', '<', now())
            ->update(['status' => 'closed']);

        if ($closed > 0) {
            Log::info("gportal:import-results: auto-closed {$closed} overdue race(s)");
        }

        $races = Race::whereNotNull('ftp_server_id')
            ->where('status', '!=', 'finished')
            ->where('scheduled_at', '<', now()->subMinutes(self::RESULT_WAIT_MINUTES))
            ->with('ftpServer')
            ->get()
            ->groupBy('ftp_server_id');

        if ($races->isEmpty()) {
            return;
        }

        foreach ($races as $serverId => $serverRaces) {
            $server = $serverRaces->first()->ftpServer;
            if (!$server || !$server->active) {
                continue;
            }

            if (!$ftp->connect($server)) {
                Log::error("gportal:import-results: could not connect to {$server->host}");
                continue;
            }

            $files = $ftp->listFiles($server->path)['json'] ?? [];
            $ftp->disconnect();

            foreach ($files as $file) {
                $filename = $file['name'];
                $fileTime = $this->parseFileTimestamp($filename);
                if (!$fileTime) {
                    continue;
                }

                // Closest race whose slot started at/before this file's timestamp, within the match window
                $race = $serverRaces
                    ->filter(function ($r) use ($fileTime) {
                        $slot = $r->slot_time ?? $r->scheduled_at;
                        return $slot && $slot->lte($fileTime) && $slot->diffInHours($fileTime) <= self::MATCH_WINDOW_HOURS;
                    })
                    ->sortByDesc(fn($r) => ($r->slot_time ?? $r->scheduled_at)->timestamp)
                    ->first();

                if (!$race) {
                    continue;
                }

                if (FtpImportedFile::where('race_id', $race->id)->where('filename', $filename)->exists()) {
                    continue;
                }

                $this->importFile($ftp, $importer, $server, $race, $filename);
            }
        }
    }

    private function importFile(FtpService $ftp, AccResultImportService $importer, FtpServer $server, Race $race, string $filename): void
    {
        try {
            if (!$ftp->connect($server)) {
                Log::error("gportal:import-results: reconnect failed for race #{$race->id}");
                return;
            }

            $content = $ftp->getFileContent(rtrim($server->path, '/') . '/' . $filename);
            $ftp->disconnect();

            if ($content === false) {
                Log::error("gportal:import-results: download failed", ['race_id' => $race->id, 'file' => $filename]);
                return;
            }

            [$decoded, $error] = $importer->decodeContent($content, $filename);
            if ($error) {
                Log::error("gportal:import-results: decode failed", ['race_id' => $race->id, 'file' => $filename, 'error' => $error]);
                return;
            }

            [$counts] = $importer->processSessions($decoded, $race, $filename);

            if ($counts['race'] > 0 || $counts['quali'] > 0) {
                FtpImportedFile::updateOrCreate(
                    ['race_id' => $race->id, 'filename' => $filename],
                    ['ftp_server_id' => $server->id]
                );
                Log::info("gportal:import-results: imported", [
                    'race_id' => $race->id, 'file' => $filename,
                    'race_rows' => $counts['race'], 'quali_rows' => $counts['quali'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("gportal:import-results: exception", [
                'race_id' => $race->id, 'file' => $filename, 'error' => $e->getMessage(),
            ]);
        }
    }

    // Filenames follow gPortal's {YYMMDD}_{HHmm}_{R|Q}.json convention (see FtpService::parseFilename).
    private function parseFileTimestamp(string $filename): ?\Illuminate\Support\Carbon
    {
        $parts = explode('_', pathinfo($filename, PATHINFO_FILENAME));
        if (count($parts) < 2 || strlen($parts[0]) !== 6 || !is_numeric($parts[0])) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::createFromFormat('ymd Hi', $parts[0] . ' ' . str_pad($parts[1], 4, '0'), 'UTC');
        } catch (\Throwable) {
            return null;
        }
    }
}