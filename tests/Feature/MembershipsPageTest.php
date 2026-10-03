<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_memberships_page_loads_and_is_linked_under_shop(): void
    {
        $this->get(route('memberships'))->assertOk()->assertSee('MEMBERSHIPS');

        // Coaching is only in the SHOP menu now, no longer under TEAM.
        $this->get(route('home'))->assertOk()
            ->assertSeeInOrder(['>SHOP<', '>MEMBERSHIPS<', '>MERCHANDISE<', '>COACHING<'], false);
        $this->assertSame(1, substr_count($this->get(route('home'))->getContent(), '>COACHING<'));
    }
}
