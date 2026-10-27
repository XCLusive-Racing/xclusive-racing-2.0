<?php

namespace Tests\Feature;

use App\Models\EventFormat;
use App\Models\Race;
use App\Models\Role;
use App\Models\User;
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

    public function test_a_format_can_require_a_tyre_change(): void
    {
        $format = EventFormat::create([
            'game' => 'acc', 'name' => 'Long Race', 'sort_order' => 9, 'race1_mins' => 90,
            'pitstop_type' => 'fuel_only', 'pitstop_count' => 1, 'mandatory_tyre_change' => true,
        ]);

        $rules = $this->rulesFor(['event_format_id' => $format->id]);

        $this->assertTrue($rules['isMandatoryPitstopTyreChangeRequired']);
        $this->assertFalse($rules['isMandatoryPitstopRefuellingRequired']);
    }

    // 2026-10-27: the format form has no pitstop Type or free stop-time input any more,
    // only the stop count plus three switches (fixed 25s, refuel, tyres).
    public function test_the_format_form_saves_the_pitstop_switches(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'owner')->first());

        $form = [
            'game' => 'acc', 'name' => 'Full Race', 'sort_order' => 6,
            'practice_mins' => 10, 'quali_mins' => 15, 'race1_mins' => 60, 'xcl_r_multiplier' => 2.0,
        ];

        $this->actingAs($admin)->post(route('admin.event-formats.store'), $form + [
            'pitstop_count' => 1, 'fixed_stop_time' => 1, 'mandatory_tyre_change' => 1,
        ])->assertRedirect(route('admin.event-formats.index'));

        $format = EventFormat::where('name', 'Full Race')->firstOrFail();
        $this->assertSame('fuel_only', $format->pitstop_type);
        $this->assertSame(EventFormat::FIXED_STOP_SECS, (int) $format->min_stop_secs);
        $this->assertTrue($format->mandatory_tyre_change);
        $this->assertFalse($format->mandatory_refuelling);

        // No stops: the switches are ignored instead of leaving a stale 25s behind.
        $this->actingAs($admin)->put(route('admin.event-formats.update', $format), $form + [
            'pitstop_count' => 0, 'fixed_stop_time' => 1, 'mandatory_refuelling' => 1,
        ]);

        $format->refresh();
        $this->assertSame('none', $format->pitstop_type);
        $this->assertNull($format->min_stop_secs);
        $this->assertFalse($format->mandatory_refuelling);
    }

    public function test_a_custom_race_or_round_can_require_a_tyre_change(): void
    {
        $rules = $this->rulesFor(['pitstop_count' => 1, 'mandatory_tyre_change' => true]);

        $this->assertTrue($rules['isMandatoryPitstopTyreChangeRequired']);
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
