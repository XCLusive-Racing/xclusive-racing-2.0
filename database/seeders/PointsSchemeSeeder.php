<?php

namespace Database\Seeders;

use App\Models\PointsScheme;
use App\Services\PointsSchemeGenerator;
use Illuminate\Database\Seeder;

// XCL-provided templates (league_id null, is_template true) — copied into a
// league's own scheme, never referenced or edited directly (see
// PointsScheme::copyFor(), PointsSchemePolicy). Every real-world table below
// was checked against a live source before seeding rather than written from
// memory — the exact search/fetch used is noted per template so the numbers
// can be re-verified later. None of these are presented as an authoritative
// rulebook; each carries a scope_note saying so plus which season it reflects.
class PointsSchemeSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Source: web search "Formula 1 points system 2025", cross-checked
            // against planetf1.com/RacingNews365/Crash.net coverage of the 2025
            // season (2026-09-09). Confirms the standard top-ten scale
            // 25-18-15-12-10-8-6-4-2-1 is unchanged, but the +1 fastest-lap
            // bonus point that ran 2019-2024 was removed for 2025 — seeded
            // here with fastest_lap_points = 0 to reflect the current rule
            // rather than the pre-2025 one.
            [
                'name'               => 'Formula 1 Style',
                'type'               => 'manual',
                'description'        => "Formula 1's top-ten points scale.",
                'points_table'       => [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1],
                'fastest_lap_points' => 0,
                'pole_points'        => 0,
                'leading_lap_points' => 0,
                'scope_note'         => "Modelled on the FIA Formula One World Championship points system as of the 2025 season (top ten score 25-18-15-12-10-8-6-4-2-1). "
                    . 'F1 awarded an extra point for the fastest lap (top-ten finishers only) from 2019 through 2024; that bonus was removed for 2025, so fastest lap is set to 0 here — raise it on your own copy if you prefer the pre-2025 rule. '
                    . 'Values may differ from the current official regulations — not an authoritative rulebook.',
            ],

            // Source: web search "FIA WEC points system 2025" (fiawec.com /
            // Wikipedia 2025 FIA World Endurance Championship season summary,
            // 2026-09-09). Confirms the same top-ten scale as F1, plus 1 point
            // for pole, awarded per class; race-length affects the points on
            // offer at specific rounds (notably Le Mans), which the source
            // material doesn't reduce to a single reusable multiplier.
            [
                'name'               => 'WEC Endurance Style',
                'type'               => 'manual',
                'description'        => 'FIA World Endurance Championship style scoring for a standard round.',
                'points_table'       => [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1],
                'fastest_lap_points' => 0,
                'pole_points'        => 1,
                'leading_lap_points' => 0,
                'scope_note'         => 'Modelled on the FIA World Endurance Championship points system for the 2025 season: top ten score 25-18-15-12-10-8-6-4-2-1 per class, plus 1 point for pole position. '
                    . "WEC awards a higher points total at longer, blue-riband rounds — notably the Le Mans 24 Hours — that multiplier isn't a fixed factor and must be applied per round when scoring that specific event, not baked into this scheme. "
                    . 'Values may differ from the current official regulations — not an authoritative rulebook.',
            ],

            // Source: web search "GT World Challenge Europe points system",
            // cross-checked figures from apexinsights.xyz / pitdebrief.com
            // coverage of the Sprint Cup scoring scale (2026-09-09). The
            // half-point steps are real — Sprint Cup rounds run as a pair of
            // races, so a full round's points split across both.
            [
                'name'               => 'GT World Challenge Sprint',
                'type'               => 'manual',
                'description'        => 'SRO GT World Challenge Europe Sprint Cup style scoring, for ACC-style sprint rounds.',
                'points_table'       => [1 => 16.5, 2 => 12, 3 => 9.5, 4 => 7.5, 5 => 6, 6 => 4.5, 7 => 3, 8 => 2, 9 => 1, 10 => 0.5],
                'fastest_lap_points' => 0,
                'pole_points'        => 1,
                'leading_lap_points' => 0,
                'scope_note'         => 'Modelled on the SRO GT World Challenge Europe Sprint Cup points system: top ten score 16.5-12-9.5-7.5-6-4.5-3-2-1-0.5, plus 1 point for pole. '
                    . "Sprint Cup rounds are contested as a pair of races; official scoring only pays the top ten — leagues wanting the full grid to score should use the Full Field Participation template instead. "
                    . 'Values may differ from the current official regulations — not an authoritative rulebook.',
            ],

            // Source: same GT World Challenge Europe research pass as above —
            // Endurance Cup scoring confirmed to match the F1/WEC top-ten
            // scale plus a pole point; the 24 Hours of Spa's interim 6h/12h
            // scoring on a separate scale was noted but is out of scope for a
            // single reusable table.
            [
                'name'               => 'GT World Challenge Endurance',
                'type'               => 'manual',
                'description'        => 'SRO GT World Challenge Europe Endurance Cup style scoring for a standard round.',
                'points_table'       => [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1],
                'fastest_lap_points' => 0,
                'pole_points'        => 1,
                'leading_lap_points' => 0,
                'scope_note'         => 'Modelled on the SRO GT World Challenge Europe Endurance Cup points system: top ten score 25-18-15-12-10-8-6-4-2-1, plus 1 point for pole. '
                    . 'The Total 24 Hours of Spa additionally scores interim classifications at the 6 and 12 hour marks on a separate scale before the final result — that interim scoring is not modelled here and must be applied per round if used. '
                    . 'Values may differ from the current official regulations — not an authoritative rulebook.',
            ],

            // Source: web search "DTM points system 2023 2024", confirmed via
            // sportscar365.com's coverage of the points/penalty system
            // introduced for 2023 and carried into 2024/2025 (2026-09-09):
            // race winner 25, 2nd 20, 3rd 16, 4th 13, then 5th at 11 dropping
            // by 1 per position to 15th at 1. Qualifying separately awards a
            // 3/2/1 bonus scale not fully represented by a single pole value.
            [
                'name'               => 'DTM Style',
                'type'               => 'manual',
                'description'        => 'DTM style scoring for a single race — a DTM weekend runs two of these.',
                'points_table'       => [1 => 25, 2 => 20, 3 => 16, 4 => 13, 5 => 11, 6 => 10, 7 => 9, 8 => 8, 9 => 7, 10 => 6, 11 => 5, 12 => 4, 13 => 3, 14 => 2, 15 => 1],
                'fastest_lap_points' => 0,
                'pole_points'        => 3,
                'leading_lap_points' => 0,
                'scope_note'         => 'Modelled on the DTM (Deutsche Tourenwagen Masters) points system in place since the 2023 season: top fifteen score 25-20-16-13-11-10-9-8-7-6-5-4-3-2-1. '
                    . 'A DTM race weekend runs two races, each scored on this scale, alongside a separate qualifying bonus scale (3/2/1 to the top three) that this scheme approximates with a single Pole Position value. '
                    . 'Values may differ from the current official regulations — not an authoritative rulebook.',
            ],

            // Source: web search "FIA GT World Cup Macau points system" —
            // fia.com/Wikipedia/press coverage of the Macau GT World Cup
            // format (2026-09-09) describes a 12-lap Qualification Race and a
            // 16-lap Main Race crowning an outright winner; no official
            // multi-position points scale was found. Wikipedia's own 2016
            // season summary confirms a prior points system was deliberately
            // abandoned in favour of a single-winner format, "since it doesn't
            // make sense to have points for a one-off event".
            [
                'name'               => 'FIA GT World Cup Style (Single Event)',
                'type'               => 'manual',
                'description'        => 'A single-event, showcase-round scoring table in the spirit of the FIA GT World Cup at Macau.',
                'points_table'       => [1 => 30, 2 => 25, 3 => 21, 4 => 18, 5 => 16, 6 => 14, 7 => 12, 8 => 10],
                'fastest_lap_points' => 0,
                'pole_points'        => 0,
                'leading_lap_points' => 0,
                'scope_note'         => 'The FIA GT World Cup at Macau does not publish an official multi-position points scale — it crowns an outright Main Race winner rather than running a season-long points classification. '
                    . 'This is a stylised points table for a league running a one-off showcase or exhibition round in that spirit, not a reproduction of an official scale.',
            ],

            [
                'name'               => 'Simple Top Three Podium',
                'type'               => 'manual',
                'description'        => 'A minimal scheme scoring only the podium.',
                'points_table'       => [1 => 3, 2 => 2, 3 => 1],
                'fastest_lap_points' => 0,
                'pole_points'        => 0,
                'leading_lap_points' => 0,
                'scope_note'         => 'A simple community scheme, not modelled on a specific real-world series.',
            ],
        ];

        // Console context, no authenticated user — bypass the tenant scope explicitly.
        foreach ($templates as $data) {
            PointsScheme::withoutTenantScope()->updateOrCreate(
                ['name' => $data['name'], 'league_id' => null],
                array_merge($data, ['config' => null, 'is_template' => true])
            );
        }

        // Full Field Participation — the one template the task explicitly asks
        // to be "built from the curved generator" rather than a fixed table.
        // Gentle steepness, 100% of the field (resolved here against a 30-car
        // reference grid purely to size the stored table): every classified
        // finisher scores something, tapering slowly from the win down to a
        // token point at the back — not modelled on a real series.
        $fullFieldConfig = [
            'top' => 30, 'floor' => 1, 'depth_type' => 'percentage', 'depth_value' => 100,
            'reference_field_size' => 30, 'steepness' => 'gentle',
        ];
        $fullFieldDepth = PointsSchemeGenerator::resolveDepth($fullFieldConfig, 30);
        $fullFieldTable = PointsSchemeGenerator::curved(30, 1, $fullFieldDepth, 'gentle');

        PointsScheme::withoutTenantScope()->updateOrCreate(
            ['name' => 'Full Field Participation', 'league_id' => null],
            [
                'type'               => 'curved',
                'description'        => 'Everyone who finishes scores something, tapering gently from the win to the back of the field.',
                'config'             => $fullFieldConfig,
                'points_table'       => $fullFieldTable,
                'fastest_lap_points' => 0,
                'pole_points'        => 0,
                'leading_lap_points' => 0,
                'is_template'        => true,
                'scope_note'         => 'Not modelled on a specific real-world series — built for community leagues that want the whole grid to feel like it matters, not just the top ten. '
                    . 'Generated with the curved generator (gentle steepness, 100% of classified finishers scored, resolved here against a 30-car reference grid).',
            ]
        );
    }
}
