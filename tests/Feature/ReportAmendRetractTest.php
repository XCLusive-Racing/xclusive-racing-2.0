<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAmendRetractTest extends TestCase
{
    use RefreshDatabase;

    private function makeReport(User $reporter, ?User $reported = null, array $overrides = []): Report
    {
        $reported ??= User::factory()->create();

        return Report::create(array_merge([
            'user_id' => $reporter->id,
            'reported_user_id' => $reported->id,
            'reported_driver_name' => $reported->displayName(),
            'session_type' => 'R',
            'description' => 'He braked very late and took me out at turn one.',
            'video_url' => 'https://youtu.be/original',
            'status' => 'pending',
        ], $overrides));
    }

    private function amendPayload(array $overrides = []): array
    {
        return array_merge([
            'session_type' => 'Q',
            'lap_number' => 4,
            'incident_corner' => 'T1',
            'description' => 'Updated: he braked very late and hit my rear at turn one.',
            'video_url' => 'https://youtu.be/updated',
        ], $overrides);
    }

    public function test_reporter_can_amend_a_pending_report(): void
    {
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter);

        $this->actingAs($reporter)->get(route('reports.edit', $report))->assertOk();

        $this->actingAs($reporter)->put(route('reports.update', $report), $this->amendPayload())
            ->assertRedirect(route('reports.index'));

        $report->refresh();
        $this->assertSame('Q', $report->session_type);
        $this->assertSame('https://youtu.be/updated', $report->video_url);
        $this->assertSame('pending', $report->status);
    }

    public function test_amending_does_not_change_the_reported_driver_or_race(): void
    {
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter);
        $other = User::factory()->create();

        $this->actingAs($reporter)->put(route('reports.update', $report), $this->amendPayload([
            'reported_user_id' => $other->id,
            'race_id' => 999,
        ]));

        $report->refresh();
        $this->assertNotSame($other->id, $report->reported_user_id);
        $this->assertNull($report->race_id);
    }

    public function test_reporter_can_retract_a_pending_report(): void
    {
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter);

        $this->actingAs($reporter)->post(route('reports.retract', $report))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHas('success');

        $this->assertSame('retracted', $report->fresh()->status);
    }

    public function test_a_report_under_investigation_cannot_be_amended_or_retracted(): void
    {
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, null, ['status' => 'investigating']);

        $this->actingAs($reporter)->get(route('reports.edit', $report))
            ->assertRedirect(route('reports.index'))->assertSessionHas('error');
        $this->actingAs($reporter)->put(route('reports.update', $report), $this->amendPayload())
            ->assertSessionHas('error');
        $this->actingAs($reporter)->post(route('reports.retract', $report))
            ->assertSessionHas('error');

        $report->refresh();
        $this->assertSame('investigating', $report->status);
        $this->assertSame('https://youtu.be/original', $report->video_url);
    }

    public function test_a_pending_report_with_an_assigned_steward_is_locked(): void
    {
        $reporter = User::factory()->create();
        $steward = User::factory()->create();
        $report = $this->makeReport($reporter, null, ['steward_1_id' => $steward->id]);

        $this->actingAs($reporter)->post(route('reports.retract', $report))->assertSessionHas('error');

        $this->assertSame('pending', $report->fresh()->status);
    }

    public function test_closed_reports_cannot_be_changed(): void
    {
        $reporter = User::factory()->create();

        foreach (['resolved', 'dismissed', 'retracted'] as $status) {
            $report = $this->makeReport($reporter, null, ['status' => $status]);

            $this->actingAs($reporter)->post(route('reports.retract', $report))->assertSessionHas('error');
            $this->assertSame($status, $report->fresh()->status);
        }
    }

    public function test_only_the_reporter_can_amend_or_retract(): void
    {
        $reporter = User::factory()->create();
        $stranger = User::factory()->create();
        $report = $this->makeReport($reporter);

        $this->actingAs($stranger)->get(route('reports.edit', $report))->assertForbidden();
        $this->actingAs($stranger)->put(route('reports.update', $report), $this->amendPayload())->assertForbidden();
        $this->actingAs($stranger)->post(route('reports.retract', $report))->assertForbidden();

        $this->assertSame('pending', $report->fresh()->status);
    }

    public function test_amending_still_validates_the_fields(): void
    {
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter);

        $this->actingAs($reporter)->put(route('reports.update', $report), $this->amendPayload(['description' => 'too short']))
            ->assertSessionHasErrors('description');
    }

    public function test_my_reports_shows_amend_and_retract_only_while_pending(): void
    {
        $reporter = User::factory()->create();
        $pending = $this->makeReport($reporter);
        $locked = $this->makeReport($reporter, null, ['status' => 'investigating']);

        $html = $this->actingAs($reporter)->get(route('reports.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('reports.edit', $pending), $html);
        $this->assertStringContainsString(route('reports.retract', $pending), $html);
        $this->assertStringNotContainsString(route('reports.edit', $locked), $html);
        $this->assertStringContainsString('can no longer be edited or retracted', $html);
    }

    public function test_a_retracted_report_is_hidden_from_the_reported_driver(): void
    {
        $reporter = User::factory()->create();
        $reported = User::factory()->create();
        $this->makeReport($reporter, $reported, ['status' => 'retracted', 'description' => 'RETRACTED-MARKER report text here.']);
        $visible = $this->makeReport($reporter, $reported);

        $this->actingAs($reported)->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('reportsAgainst', fn ($reports) => $reports->count() === 1 && $reports->first()->id === $visible->id);
    }

    public function test_stewards_cannot_start_investigating_a_retracted_report(): void
    {
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, null, ['status' => 'retracted']);

        $owner = User::factory()->create();
        $owner->roles()->attach(Role::where('slug', 'owner')->firstOrFail());

        $this->actingAs($owner)->post(route('admin.reports.start-investigating', $report))
            ->assertSessionHas('error');

        $this->assertSame('retracted', $report->fresh()->status);
    }
}
