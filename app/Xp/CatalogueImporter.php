<?php

namespace App\Xp;

use App\Models\Achievement;
use App\Models\RankLevel;
use RuntimeException;

// Reads the catalogue and the rank curve from their CSV exports (database/data/xp, from
// docs/xp-achievements/xcl-xp-achievements.xlsx). Safe to run again: achievements are
// matched on their code and an archived one stays archived.
class CatalogueImporter
{
    // Metrics the sheet scoped "all" that can only be earned at an ACC circuit
    // (PLAN.md decision 17): they belong to the title, not to the title agnostic pool.
    private const ACC_ONLY_METRICS = ['nords_best_lap', 'nords_valid_streak'];

    // Metrics where a lower value is better (a lap time), so the threshold is "at most".
    private const LOWER_IS_BETTER = ['nords_best_lap'];

    public function __construct(private MetricRegistry $metrics) {}

    /** @return array{imported: int, unknown_metrics: string[], not_countable: string[]} */
    public function importAchievements(string $path): array
    {
        $unknown = $notCountable = [];
        $imported = 0;

        foreach ($this->rows($path) as $index => $row) {
            $metric = $this->metrics->get($row['metric']);
            if (! $metric) {
                $unknown[$row['metric']] = true;
            } elseif (! $metric->countable()) {
                $notCountable[$row['metric']] = true;
            }

            Achievement::updateOrCreate(['code' => $row['code']], [
                'name' => $row['name'],
                'description' => $row['description'],
                'category' => $row['category'],
                'family' => $row['family'] ?: null,
                'metric_key' => $row['metric'],
                'game_scope' => in_array($row['metric'], self::ACC_ONLY_METRICS, true) ? 'acc' : $row['game'],
                'threshold' => $this->threshold($row['threshold']),
                'comparison' => in_array($row['metric'], self::LOWER_IS_BETTER, true) ? 'lte' : 'gte',
                'xp' => (int) $row['xp'],
                'sort_order' => $index + 1,
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'unknown_metrics' => array_keys($unknown), 'not_countable' => array_keys($notCountable)];
    }

    public function importRankCurve(string $path): int
    {
        $count = 0;
        foreach ($this->rows($path) as $row) {
            RankLevel::updateOrCreate(['rank' => (int) $row['rank']], [
                'xp_to_next' => is_numeric($row['xp_to_next']) ? (int) $row['xp_to_next'] : null,
                'cumulative_xp' => (int) $row['cumulative_xp'],
            ]);
            $count++;
        }

        return $count;
    }

    // "25", "4.5", or a lap time "7:30" (stored as seconds).
    private function threshold(string $value): float
    {
        if (preg_match('/^(\d+):(\d{2})(\.\d+)?$/', trim($value), $m)) {
            return (int) $m[1] * 60 + (float) ($m[2].($m[3] ?? ''));
        }

        return (float) $value;
    }

    private function rows(string $path): array
    {
        $handle = @fopen($path, 'r');
        if (! $handle) {
            throw new RuntimeException("Cannot read {$path}");
        }

        $head = fgetcsv($handle, null, ',', '"', '');
        $rows = [];
        while (($line = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($line === [null] || count($line) < count($head)) {
                $line = array_pad($line === [null] ? [] : $line, count($head), '');
            }
            if (trim(implode('', $line)) !== '') {
                $rows[] = array_combine($head, array_slice($line, 0, count($head)));
            }
        }
        fclose($handle);

        return $rows;
    }
}
