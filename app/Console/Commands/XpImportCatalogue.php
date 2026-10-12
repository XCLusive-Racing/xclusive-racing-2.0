<?php

namespace App\Console\Commands;

use App\Xp\CatalogueImporter;
use Illuminate\Console\Command;

// Re-reads the achievement catalogue from database/data/xp/achievements.csv: new codes are
// added, existing ones updated, nothing is removed and an archived one stays archived.
// Awards already made keep the XP they were made with.
class XpImportCatalogue extends Command
{
    protected $signature = 'xp:import-catalogue {--curve : Also re-read the rank curve (rank-curve.csv)}';

    protected $description = 'Import the XP achievement catalogue from its CSV';

    public function handle(CatalogueImporter $importer): int
    {
        if ($this->option('curve')) {
            $this->info($importer->importRankCurve(database_path('data/xp/rank-curve.csv')).' rank levels imported.');
        }

        $report = $importer->importAchievements(database_path('data/xp/achievements.csv'));
        $this->info($report['imported'].' achievements imported.');

        if ($report['unknown_metrics']) {
            $this->warn('Metrics not in the registry (their achievements can never be awarded): '.implode(', ', $report['unknown_metrics']));
        }
        if ($report['not_countable']) {
            $this->line('Metrics not countable yet: '.implode(', ', $report['not_countable']));
        }

        return self::SUCCESS;
    }
}
