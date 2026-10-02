<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The public Staff page lists config/staff.php: everyone once with all their functions,
// and a tab per function that has people in it.
class StaffPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_page_lists_everyone_with_all_their_functions(): void
    {
        $response = $this->get(route('team.staff'))->assertOk();

        $response->assertSee('Jan Hartog')
            ->assertSee('Admin · Website Developer')   // Olle Kuiper
            ->assertSee('Event Manager · Steward');     // Alex Fear, Dan Houdini

        // Each person's card shows once in All Staff, and once more per function tab.
        $this->assertSame(3, substr_count($response->getContent(), '>Olle Kuiper<'));
    }

    public function test_a_function_without_anyone_gets_no_tab(): void
    {
        $this->get(route('team.staff'))->assertOk()->assertDontSee('data-tab-btn="community_manager"', false);
    }

    public function test_team_page_and_navbar_link_to_the_staff_page(): void
    {
        $this->get(route('team'))->assertOk()->assertSee(route('team.staff'), false);
    }
}
