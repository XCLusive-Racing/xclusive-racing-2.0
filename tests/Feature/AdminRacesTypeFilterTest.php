<?php

namespace Tests\Feature;

use App\Models\EventFormat;
use App\Models\Race;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: the admin Events (standard races) list gets a Type filter row,
// one button per event format in use, next to the Game and Status filters.
class AdminRacesTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_format_in_use_gets_a_type_filter_button_and_rows_carry_their_format(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $format = EventFormat::create([
            'game' => 'acc', 'name' => 'Sprint Race', 'sort_order' => 2,
            'default_event_tag' => 'sprint', 'race1_mins' => 20,
        ]);

        Race::create([
            'title' => $format->name, 'track' => 'Monza', 'game' => $format->game,
            'status' => 'open', 'scheduled_at' => now()->addDay(), 'event_format_id' => $format->id,
        ]);

        $this->actingAs($admin)->get(route('admin.races.index'))
            ->assertOk()
            ->assertSee('data-type-filter="'.e($format->name).'"', false)
            ->assertSee('<tr data-format="'.e($format->name).'">', false);
    }
}
