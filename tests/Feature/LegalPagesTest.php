<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Terms, Refund Policy, Privacy and Contact: the pages a payment provider (Mollie) checks
// before approving the site, linked from the footer and the Memberships page.
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_legal_pages_load_with_the_company_details(): void
    {
        config(['xcl.legal' => array_merge(config('xcl.legal'), [
            'address' => 'Racestraat 1, 1234 AB Zandvoort, Netherlands', 'coc' => '12345678', 'vat' => 'NL001234567B01',
        ])]);

        $this->get(route('terms'))->assertOk()->assertSee('Terms of Service')->assertSee('€2,99 per month')->assertSee('Racestraat 1');
        $this->get(route('refund-policy'))->assertOk()->assertSee('14-day right of withdrawal')->assertSee('Model withdrawal form');
        $this->get(route('privacy'))->assertOk()->assertSee('Mollie B.V.')->assertSee('12345678');
        $this->get(route('contact'))->assertOk()->assertSee('info@xclusiveracing.com')->assertSee('NL001234567B01');
    }

    public function test_unset_company_details_are_left_out(): void
    {
        config(['xcl.legal.address' => null, 'xcl.legal.coc' => null, 'xcl.legal.vat' => null]);

        $this->get(route('contact'))->assertOk()->assertDontSee('Chamber of Commerce')->assertDontSee('VAT number');
    }

    public function test_the_footer_and_memberships_page_link_to_them(): void
    {
        $page = $this->get(route('memberships'))->assertOk();

        foreach (['terms', 'refund-policy', 'privacy', 'contact'] as $route) {
            $page->assertSee('href="'.route($route).'"', false);
        }
    }
}
