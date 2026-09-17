<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: "underneath partners underneath about in the
// navigation bar i still dont see FAQ as a option in the drop down" -- the
// FAQ link added in an earlier session went into layouts/_navbar.blade.php,
// a leftover file layouts/app.blade.php never actually renders (it renders
// <x-navbar/>, i.e. components/navbar.blade.php) -- so it was never live.
// Regression test against the real rendered navbar, not just the route.
class NavbarLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_about_dropdown_links_to_faq_under_partners(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['PARTNERS', 'FAQ'])
            ->assertSee(route('faq'), false);
    }
}
