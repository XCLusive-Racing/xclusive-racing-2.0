<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10-07: when the reporter didn't hide their name, the reported driver
// sees the stewards' verdict and the clips once the report is decided. A hidden report
// still only shows the final penalty, and an open one shows nothing extra.
class ReportVerdictForReportedDriverTest extends TestCase
{
    use RefreshDatabase;

    private function report(User $reported, array $overrides = []): Report
    {
        return Report::create(array_merge([
            'user_id' => User::factory()->create(['name' => 'TheReporter'])->id,
            'reported_user_id' => $reported->id,
            'reported_driver_name' => $reported->displayName(),
            'session_type' => 'R', 'lap_number' => 3, 'incident_corner' => 'T1',
            'description' => 'He dived in and took me out.',
            'video_url' => 'https://youtu.be/main', 'clip_bad_driver_url' => 'https://youtu.be/onboard',
            'clip_heli_url' => 'javascript:alert(1)',
            'status' => 'resolved', 'final_penalty' => 'CAC', 'final_multiplier' => 1.5,
            'xcl_rating_deduction' => 42.5, 'sr_deduction' => 0.75,
            'admin_notes' => 'Late lunge, no chance to avoid.',
            'hide_reporter_name' => false,
        ], $overrides));
    }

    private function againstMe(User $driver): string
    {
        return $this->actingAs($driver)->get(route('reports.index'))->assertOk()->getContent();
    }

    public function test_a_decided_report_shows_the_verdict_and_clips(): void
    {
        $driver = User::factory()->create();
        $this->report($driver);

        $html = $this->againstMe($driver);

        foreach (['Steward verdict', 'Causing a Collision (CAC)', '&times;1.5', 'XCL Rating −43', 'SR −0.75',
            'Late lunge, no chance to avoid.', 'Lap 3', 'https://youtu.be/main', 'https://youtu.be/onboard', 'TheReporter'] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        $this->assertStringNotContainsString('javascript:alert(1)', $html);
    }

    public function test_a_dismissed_report_shows_the_reason(): void
    {
        $driver = User::factory()->create();
        $this->report($driver, ['status' => 'dismissed', 'final_penalty' => 'RI', 'dismissal_reason' => 'Both drivers share the blame.']);

        $this->assertStringContainsString('Both drivers share the blame.', $this->againstMe($driver));
    }

    public function test_a_hidden_report_only_shows_the_final_penalty(): void
    {
        $driver = User::factory()->create();
        $this->report($driver, ['hide_reporter_name' => true]);

        $html = $this->againstMe($driver);

        $this->assertStringContainsString('Final penalty: <strong>CAC</strong>', $html);
        $this->assertStringContainsString('chose to stay anonymous', $html);
        foreach (['Steward verdict', 'https://youtu.be/main', 'Late lunge', 'TheReporter'] as $text) {
            $this->assertStringNotContainsString($text, $html, $text);
        }
    }

    public function test_an_open_report_shows_no_details(): void
    {
        $driver = User::factory()->create();
        $this->report($driver, ['status' => 'investigating', 'final_penalty' => null]);

        $html = $this->againstMe($driver);

        $this->assertStringNotContainsString('Steward verdict', $html);
        $this->assertStringNotContainsString('https://youtu.be/main', $html);
    }
}
