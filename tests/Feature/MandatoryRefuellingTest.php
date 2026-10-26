<?php

namespace Tests\Feature;

use App\Models\EventFormat;
use App\Models\Race;
use App\Services\AccServerConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Mandatory refuelling is its own option (2026-10-26). It used to follow the fixed
// 25s stop time, so the Daily (a stop but no fixed time) pushed
// isMandatoryPitstopRefuellingRequired=false.
class MandatoryRefuellingTest extends TestCase
{
    use RefreshDatabase;

    private function rulesFor(array $raceAttributes): array
    {
        $race = Race::create(array_merge([
            'title' => 'Daily Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addDay(),
        ], $raceAttributes));

        return app(AccServerConfigService::class)->eventRules($race->fresh());
    }

    public function test_a_format_can_require_refuelling_without_a_fixed_stop_time(): void
    {
        $format = EventFormat::create([
            'game' => 'acc', 'name' => 'Daily Race', 'sort_order' => 3, 'race1_mins' => 30,
            'pitstop_type' => 'fuel_only', 'pitstop_count' => 1, 'min_stop_secs' => null, 'mandatory_refuelling' => true,
        ]);

        $rules = $this->rulesFor(['event_format_id' => $format->id]);

        $this->assertSame(1, $rules['mandatoryPitstopCount']);
        $this->assertTrue($rules['isMandatoryPitstopRefuellingRequired']);
        $this->assertFalse($rules['isRefuellingTimeFixed']);
    }

    public function test_a_fixed_stop_time_no_longer_forces_refuelling(): void
    {
        $rules = $this->rulesFor(['pitstop_count' => 1, 'min_stop_secs' => 25, 'mandatory_refuelling' => false]);

        $this->assertTrue($rules['isRefuellingTimeFixed']);
        $this->assertFalse($rules['isMandatoryPitstopRefuellingRequired']);
    }

    public function test_a_custom_race_with_refuelling_on_requires_it(): void
    {
        $rules = $this->rulesFor(['pitstop_count' => 2, 'min_stop_secs' => null, 'mandatory_refuelling' => true]);

        $this->assertSame(2, $rules['mandatoryPitstopCount']);
        $this->assertTrue($rules['isMandatoryPitstopRefuellingRequired']);
        $this->assertFalse($rules['isRefuellingTimeFixed']);
    }
}
