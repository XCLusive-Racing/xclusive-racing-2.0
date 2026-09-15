<?php

namespace Tests\Unit;

use App\Services\XclRating;
use PHPUnit\Framework\TestCase;

class XclRatingTest extends TestCase
{
    private function entry(int $id, float $rating, int $finishPos, ?string $fieldKey = null): array
    {
        return [
            'driver_id'  => $id,
            'name'       => "Driver {$id}",
            'rating'     => $rating,
            'finish_pos' => $finishPos,
            'status'     => 'FIN',
            'field_key'  => $fieldKey,
        ];
    }

    public function test_min_drivers_gate_counts_unique_cars_not_result_rows(): void
    {
        $calc = new XclRating();
        $calc->MIN_DRIVERS = 8;

        // 7 cars, one of them (car "1") has two co-drivers sharing a field_key —
        // 8 raw result rows, but only 7 real field entries.
        $entries = [
            $this->entry(1, 1500, 1, 'car-1'),
            $this->entry(2, 1500, 1, 'car-1'), // co-driver, same car/finish
            $this->entry(3, 1500, 2),
            $this->entry(4, 1500, 3),
            $this->entry(5, 1500, 4),
            $this->entry(6, 1500, 5),
            $this->entry(7, 1500, 6),
            $this->entry(8, 1500, 7),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $calc->processRace(['name' => 'Test', 'race_date' => '2026-01-01'], $entries);
    }

    public function test_co_drivers_do_not_inflate_sof_for_the_whole_race(): void
    {
        $calc = new XclRating();
        $calc->MIN_DRIVERS = 4;

        // 4 cars: car "1" has two co-drivers at 1500 each (should count once for SoF),
        // the other 3 cars are solo at 2000 each.
        $entries = [
            $this->entry(1, 1500, 1, 'car-1'),
            $this->entry(2, 1500, 1, 'car-1'),
            $this->entry(3, 2000, 2),
            $this->entry(4, 2000, 3),
            $this->entry(5, 2000, 4),
        ];

        $results = $calc->processRace(['name' => 'Test', 'race_date' => '2026-01-01'], $entries);

        // 4 real cars: (1500 + 2000 + 2000 + 2000) / 4 = 1875 — not 5-way-averaged (1900).
        $this->assertEqualsWithDelta(1875.0, $results[0]['sof'], 0.5);
    }

    public function test_co_drivers_on_the_same_car_still_get_individually_different_elo_changes(): void
    {
        $calc = new XclRating();
        $calc->MIN_DRIVERS = 4;

        // Same car, same finishing position — but a much higher-rated co-driver
        // alongside a much lower-rated one. 4 real cars total (car-1 plus 3 solo).
        $entries = [
            $this->entry(1, 2400, 1, 'car-1'), // high-rated
            $this->entry(2, 1200, 1, 'car-1'), // low-rated
            $this->entry(3, 1800, 2),
            $this->entry(4, 1800, 3),
            $this->entry(5, 1800, 4),
        ];

        $results = collect($calc->processRace(['name' => 'Test', 'race_date' => '2026-01-01'], $entries))
            ->keyBy('driver_id');

        $highChange = $results[1]['elo_change'];
        $lowChange  = $results[2]['elo_change'];

        $this->assertNotEqualsWithDelta($highChange, $lowChange, 0.01);
        $this->assertLessThan($lowChange, $highChange, 'The lower-rated co-driver should gain more than the higher-rated one for the identical result.');
    }

    public function test_entries_without_a_field_key_behave_exactly_as_before(): void
    {
        $calc = new XclRating();
        $calc->MIN_DRIVERS = 4;

        $entries = [
            ['driver_id' => 1, 'name' => 'A', 'rating' => 1500, 'finish_pos' => 1, 'status' => 'FIN'],
            ['driver_id' => 2, 'name' => 'B', 'rating' => 1500, 'finish_pos' => 2, 'status' => 'FIN'],
            ['driver_id' => 3, 'name' => 'C', 'rating' => 1500, 'finish_pos' => 3, 'status' => 'FIN'],
            ['driver_id' => 4, 'name' => 'D', 'rating' => 1500, 'finish_pos' => 4, 'status' => 'FIN'],
        ];

        $results = $calc->processRace(['name' => 'Test', 'race_date' => '2026-01-01'], $entries);

        $this->assertCount(4, $results);
        $this->assertEqualsWithDelta(1500.0, $results[0]['sof'], 0.01);
    }
}
