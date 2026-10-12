<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\XpPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The XP reveal design preview (/profile?xp_preview=1): example data, staff only.
class XpPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_see_the_preview_and_drivers_never_do(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $this->actingAs($admin)->get(route('profile', ['xp_preview' => 1]))->assertOk()
            ->assertSee('data-xp-reveal', false)->assertSee('Preview · example data');
        $this->actingAs($admin)->get(route('profile'))->assertOk()->assertDontSee('data-xp-reveal', false);

        $this->actingAs(User::factory()->create())->get(route('profile', ['xp_preview' => 1]))->assertOk()
            ->assertDontSee('data-xp-reveal', false);
    }

    public function test_the_example_adds_up_and_lands_two_ranks_higher(): void
    {
        $sample = XpPreview::sample();

        $this->assertSame(3250, array_sum(array_column($sample['lines'], 'xp')));
        $this->assertSame(4650, $sample['gained']);
        $this->assertSame(25, XpPreview::position($sample['curve'], $sample['before'])[0]);
        $this->assertSame([27, 625, 2625], XpPreview::position($sample['curve'], $sample['after']));
    }
}
