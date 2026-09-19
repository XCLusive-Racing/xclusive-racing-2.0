<?php

use App\Models\Result;
use Illuminate\Database\Migrations\Migration;

// One-off backfill of the results that used to be hardcoded in
// ProDriverController::allDrivers() — reproduced verbatim so the public pro
// driver pages render identically once that controller switches to reading
// from the database (see Result::legacyProArrayForSubject()).
return new class extends Migration
{
    public function up(): void
    {
        if (Result::where('category', 'pro')->exists()) {
            return; // already migrated
        }

        $this->insertResult('dirk-schouten', 2025, 'Porsche Mobil 1 Supercup', [
            ['track' => 'Imola', 'class' => 'Rookie', 'positions' => ['P4']],
            ['track' => 'Monaco', 'class' => 'Rookie', 'positions' => ['P1']],
            ['track' => 'Barcelona', 'class' => 'Rookie', 'positions' => ['P3']],
            ['track' => 'Red Bull Ring', 'class' => 'Rookie', 'positions' => ['P3']],
            ['track' => 'Hungaroring', 'class' => 'Rookie', 'positions' => ['P2']],
            ['track' => 'Spa-Francorchamps', 'class' => 'Rookie', 'positions' => ['P3']],
            ['track' => 'Zandvoort', 'class' => 'Rookie', 'positions' => ['P3']],
            ['track' => 'Monza', 'class' => 'Rookie', 'positions' => ['P3']],
        ], 'P3 Rookie · P15 Overall');

        $this->insertResult('dirk-schouten', 2025, 'Porsche Carrera Cup Italia', [
            ['track' => 'Misano', 'positions' => ['P15', 'P9']],
            ['track' => 'Vallelunga', 'positions' => ['P6', 'P6']],
            ['track' => 'Mugello', 'positions' => ['P11', 'P15']],
            ['track' => 'Imola', 'positions' => ['P8', 'P25']],
            ['track' => 'Misano', 'positions' => ['P9', 'P13']],
            ['track' => 'Monza', 'positions' => ['P3', 'P3']],
        ], 'P7 Overall Championship');

        $this->insertResult('dirk-schouten', 2024, 'Porsche Carrera Cup Benelux', [
            ['track' => 'Spa-Francorchamps', 'positions' => ['P10', 'P1']],
            ['track' => 'Zandvoort', 'positions' => ['P1', 'P2']],
            ['track' => 'Imola', 'positions' => ['P18', 'P5']],
            ['track' => 'TT Assen', 'positions' => ['P2', 'P1']],
            ['track' => 'Red Bull Ring', 'positions' => ['P4', 'P2']],
            ['track' => 'Circuit Zolder', 'positions' => ['P5', 'P3']],
        ], 'Champion');

        $this->insertResult('dirk-schouten', 2024, 'Belcar', [
            ['track' => 'Zolder', 'positions' => ['P1']],
        ], '');

        $this->insertResult('dirk-schouten', 2023, 'Porsche Carrera Cup Benelux', [
            ['track' => 'Spa-Francorchamps', 'positions' => ['P8', 'P10']],
            ['track' => 'Hockenheim', 'positions' => ['P5', 'P7']],
            ['track' => 'Zandvoort', 'positions' => ['P4', 'P6']],
            ['track' => 'TT Assen', 'positions' => ['P8', 'P19']],
            ['track' => 'Zolder', 'positions' => ['P2', 'P5']],
            ['track' => 'Red Bull Ring', 'positions' => ['P6', 'P10']],
        ], 'P2 Rookie · P4 Overall');

        $this->insertResult('dirk-schouten', 2022, 'GT Cup Open', [
            ['track' => 'Paul Ricard', 'positions' => ['P2', 'P3']],
            ['track' => 'Spa', 'positions' => ['P2', 'P3']],
            ['track' => 'Hungaroring', 'positions' => ['P2', 'P2']],
            ['track' => 'Monza', 'positions' => ['P2']],
            ['track' => 'Barcelona', 'positions' => ['P3']],
        ], 'Vice-Champion · 4/5 Pole Positions');

        $this->insertResult('dirk-schouten', 2021, 'GT Cup Open', [
            ['track' => 'Spa', 'positions' => ['P2', 'P3']],
            ['track' => 'Monza', 'positions' => ['P1', 'P3']],
            ['track' => 'Barcelona', 'positions' => ['P2', 'P3']],
        ], 'P3 Overall');

        $this->insertResult('dirk-schouten', 2021, 'Supercarchallenge', [
            ['track' => 'Spa', 'positions' => ['P1', 'P1']],
        ], '');

        $this->insertResult('dirk-schouten', 2021, 'Belcar', [
            ['track' => 'Hockenheim', 'positions' => ['P3']],
        ], '');
    }

    public function down(): void
    {
        Result::where('category', 'pro')->get()->each->delete();
    }

    private function insertResult(string $subject, int $year, string $championship, array $races, string $standing): void
    {
        $result = Result::create([
            'subject' => $subject,
            'category' => 'pro',
            'year' => $year,
            'title' => $championship,
            'standing' => $standing !== '' ? $standing : null,
        ]);

        foreach ($races as $raceIndex => $race) {
            $resultRace = $result->races()->create([
                'track' => $race['track'],
                'car_class' => $race['class'] ?? null,
                'sort_order' => $raceIndex,
            ]);

            foreach ($race['positions'] as $posIndex => $position) {
                $resultRace->positions()->create([
                    'position' => $position,
                    'sort_order' => $posIndex,
                ]);
            }
        }
    }
};
