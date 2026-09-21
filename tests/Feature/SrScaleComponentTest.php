<?php

namespace Tests\Feature;

use App\Enums\SafetyRatingGrade;
use App\Models\Race;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SrScaleComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_all_seven_grades_with_both_label_forms_from_the_enum(): void
    {
        $html = Blade::render('<x-sr-scale heading="Test heading" />');

        foreach (SafetyRatingGrade::scale() as $grade) {
            $this->assertStringContainsString($grade->rangeLabel(), $html);
            $this->assertStringContainsString('>'.e($grade->shortLabel()).'<', $html);
            $this->assertStringContainsString($grade->color(), $html);
        }
        $this->assertSame(7, substr_count($html, 'data-grade='));
        $this->assertStringContainsString('Test heading', $html);
    }

    public function test_reports_page_shows_the_sr_scale(): void
    {
        $this->actingAs(User::factory()->create())->get(route('reports.index'))->assertOk()->assertSee('xcl-sr-scale', false);
    }

    public function test_an_sr_5_race_requirement_badge_is_a_red_b(): void
    {
        $race = new Race(['sr_requirement' => 5.0]);

        $this->assertSame(['B', '#cc0000'], $race->srTier());
    }

    public function test_badge_has_an_accessible_label_with_the_full_range_and_one_base_colour(): void
    {
        $html = Blade::render('<x-sr-badge :grade="\App\Enums\SafetyRatingGrade::B" size="lg" />');

        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('aria-label="Safety rating B, 5.00 to 5.99"', $html);
        $this->assertStringContainsString('--sr-color:#cc0000', $html);
        $this->assertStringContainsString('xcl-sr-badge--lg', $html);
    }

    public function test_badge_accepts_a_letter_and_defaults_to_the_small_size(): void
    {
        $html = Blade::render('<x-sr-badge grade="Y" />');

        $this->assertStringContainsString('aria-label="Safety rating Y, 8.00 to 8.99"', $html);
        $this->assertStringContainsString('xcl-sr-badge--sm', $html);
    }

    public function test_badge_renders_nothing_for_an_unknown_grade(): void
    {
        $this->assertSame('', trim(Blade::render('<x-sr-badge grade="Q" />')));
    }

    public function test_event_page_requirement_badge_uses_the_component(): void
    {
        $race = Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(), 'sr_requirement' => 5,
        ]);

        $this->get(route('events.show', $race))
            ->assertOk()
            ->assertSee('aria-label="Safety rating B, 5.00 to 5.99"', false);
    }
}
