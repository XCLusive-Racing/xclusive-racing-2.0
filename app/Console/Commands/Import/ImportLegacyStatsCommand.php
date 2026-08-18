<?php

namespace App\Console\Commands\Import;

use App\Models\User;
use Illuminate\Console\Command;

class ImportLegacyStatsCommand extends Command
{
    protected $signature = 'xcl:import:legacystats {file : Path to DRIVERSTATS CSV} {--force : Overwrite users that already have legacy stats set}';
    protected $description = 'Bulk-set legacy_races/legacy_wins/legacy_podiums on users by matching CSV DriverName to users.name';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $content   = str_replace(["\r\n", "\r"], "\n", file_get_contents($path));
        $lines     = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));
        $delimiter = $this->detectDelimiter($lines[0] ?? '');
        $headers   = array_map('trim', str_getcsv(array_shift($lines), $delimiter));
        $lower     = array_map('strtolower', $headers);

        $nameIdx    = array_search('drivername', $lower, true);
        $winsIdx    = array_search('wins', $lower, true);
        $racesIdx   = array_search('totalraces', $lower, true);
        $podiumsIdx = array_search('podiums', $lower, true);

        if ($nameIdx === false || $winsIdx === false || $racesIdx === false || $podiumsIdx === false) {
            $this->error('CSV is missing one of the required columns: DriverName, Wins, TotalRaces, Podiums.');
            return 1;
        }

        // Group rows by gamertag first so a name that appears more than once in the
        // CSV (two different DriverIDs sharing a gamertag) is treated as ambiguous
        // rather than silently picking one.
        $rowsByName = [];
        foreach ($lines as $line) {
            $raw  = array_pad(array_map('trim', str_getcsv($line, $delimiter)), count($headers), null);
            $name = trim($raw[$nameIdx] ?? '');
            if ($name === '') continue;

            $rowsByName[mb_strtolower($name)][] = [
                'name'    => $name,
                'wins'    => (int) ($raw[$winsIdx] ?? 0),
                'races'   => (int) ($raw[$racesIdx] ?? 0),
                'podiums' => (int) ($raw[$podiumsIdx] ?? 0),
            ];
        }

        $force = (bool) $this->option('force');

        $usersByName = User::query()->pluck('id', 'name')->mapWithKeys(
            fn ($id, $name) => [mb_strtolower($name) => $id]
        );

        $updated  = 0;
        $noUser   = 0;
        $ambiguousCsv  = [];
        $ambiguousUser = [];
        $skippedSet    = [];

        foreach ($rowsByName as $key => $rows) {
            if (count($rows) > 1) {
                $ambiguousCsv[] = $rows[0]['name'] . ' (' . count($rows) . ' CSV rows)';
                continue;
            }

            $userId = $usersByName[$key] ?? null;
            if (!$userId) {
                $noUser++;
                continue;
            }

            $user = User::find($userId);
            if (!$force && ($user->legacy_races || $user->legacy_wins || $user->legacy_podiums)) {
                $skippedSet[] = $user->name;
                continue;
            }

            $row = $rows[0];
            $user->update([
                'legacy_races'   => $row['races'],
                'legacy_wins'    => $row['wins'],
                'legacy_podiums' => $row['podiums'],
            ]);
            $updated++;
        }

        $this->info("Updated {$updated} users.");
        $this->info("No matching user for {$noUser} CSV gamertags (fine — not everyone from the old site has an account here).");

        if ($skippedSet) {
            $this->warn(count($skippedSet) . ' users already had legacy stats set, skipped (use --force to overwrite): ' . implode(', ', $skippedSet));
        }

        if ($ambiguousCsv) {
            $this->warn(count($ambiguousCsv) . ' gamertags appear more than once in the CSV, skipped — review manually: ' . implode(', ', $ambiguousCsv));
        }

        return 0;
    }

    private function detectDelimiter(string $line): string
    {
        $counts = ["\t" => substr_count($line, "\t"), ';' => substr_count($line, ';'), ',' => substr_count($line, ',')];
        arsort($counts);
        return array_key_first($counts);
    }
}
