<?php

namespace App\Console\Commands;

use App\Models\FtpImportedFile;
use App\Models\Race;
use App\Models\RaceSessionFile;
use App\Services\AccResultImportService;
use App\Services\FtpService;
use Illuminate\Console\Command;
use Throwable;

// Puts back the detailed stats (laps, sectors, consistency...) of races imported before the
// raw results file was kept in the database: it used to sit on the server's local disk,
// which every deploy wiped. Re-downloads each race's own results file from the GPORTAL
// server it was imported from (FtpImportedFile) and stores only the stats — results and
// ratings are left untouched. A race whose file GPORTAL no longer has is reported.
//
// Run it on the live server: the FTP passwords are encrypted with that server's APP_KEY.
class RestoreRaceStats extends Command
{
    protected $signature = 'results:restore-stats {--race= : Only this race id} {--dry-run : List what would be restored}';

    protected $description = 'Re-download missing detailed race stats from GPORTAL';

    public function handle(FtpService $ftp, AccResultImportService $importer): int
    {
        $races = Race::whereHas('raceResults')
            ->whereDoesntHave('sessionFiles')
            ->when($this->option('race'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('scheduled_at')
            ->get();

        $restored = 0;
        foreach ($races as $race) {
            // No logged-in user here: FtpServer's TenantScope would hide every server.
            $files = FtpImportedFile::with(['server' => fn ($q) => $q->withoutTenantScope()])->where('race_id', $race->id)->get();
            $label = "#{$race->id} {$race->scheduled_at?->format('Y-m-d H:i')} {$race->title}";

            if ($files->isEmpty()) {
                $this->line("{$label}: no GPORTAL import on record (uploaded by hand?) — skipped");

                continue;
            }
            if ($this->option('dry-run')) {
                $this->line("{$label}: would fetch ".$files->pluck('filename')->implode(', '));

                continue;
            }

            foreach ($files as $file) {
                try {
                    if (! $file->server || ! $ftp->connect($file->server)) {
                        $this->warn("{$label}: could not connect to the server of {$file->filename}");

                        continue;
                    }
                    $content = $ftp->getFileContent(rtrim($file->server->path, '/').'/'.$file->filename);
                    $ftp->disconnect();

                    if ($content === false) {
                        $this->warn("{$label}: {$file->filename} is no longer on the server");

                        continue;
                    }

                    [$decoded, $error] = $importer->decodeContent($content, $file->filename);
                    if ($error) {
                        $this->warn("{$label}: {$file->filename} could not be read ({$error})");

                        continue;
                    }

                    $numbers = $importer->storeStatsOnly($decoded, $race);
                    if ($numbers) {
                        $restored++;
                        $this->info("{$label}: restored race ".implode(', ', $numbers)." from {$file->filename}");
                    }
                } catch (Throwable $e) {
                    $this->error("{$label}: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info("{$races->count()} race(s) without stats checked, {$restored} file(s) restored. "
            .RaceSessionFile::count().' race stats stored in total.');

        return self::SUCCESS;
    }
}
