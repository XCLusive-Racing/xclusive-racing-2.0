<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10-29: change a setting on every existing round at once.
// Edit Round's "Apply to later rounds" copies the ticked groups to the
// championship's later rounds that haven't run yet (earlier ones stay as they are).
class RoundApplyToOthersTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private Championship $championship;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $this->manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $this->manager->id, 'role' => 'manager']);
        $this->manager->syncLeagueRoleFlags();
        $this->manager = $this->manager->fresh();
    }

    private function round(int $number, array $overrides = []): Race
    {
        return Race::create(array_merge([
            'championship_id' => $this->championship->id, 'round_number' => $number, 'title' => 'Round '.$number,
            'track' => 'Track '.$number, 'game' => 'acc', 'status' => 'open', 'is_championship' => true,
            'scheduled_at' => now()->addWeeks($number)->startOfHour(), 'race_duration' => 30, 'weather' => 'dry',
            'points_multiplier' => 1.0,
        ], $overrides));
    }

    private function update(Race $race, array $input)
    {
        return $this->actingAs($this->manager)->put(
            route('admin.leagues.championships.rounds.update', [$this->league, $this->championship, $race]),
            array_merge([
                'track' => $race->track,
                'scheduled_at' => $race->scheduledAtUk()->format('Y-m-d\TH:i'),
            ], $input)
        );
    }

    public function test_ticked_groups_are_copied_to_the_other_open_rounds_only(): void
    {
        $round1 = $this->round(1);
        $round2 = $this->round(2, ['points_multiplier' => 2.0]);
        $finished = $this->round(3, ['status' => 'finished']);

        $this->update($round1, ['race_duration' => 60, 'weather' => 'wet', 'apply_to' => ['sessions']])
            ->assertSessionHasNoErrors();

        $round2->refresh();
        $this->assertSame(60, (int) $round2->race_duration);
        $this->assertSame('dry', $round2->weather, 'Weather was not ticked.');
        $this->assertSame('Track 2', $round2->track);
        $this->assertSame(2.0, $round2->points_multiplier, 'Points stay per round.');
        $this->assertSame(30, (int) $finished->fresh()->race_duration, 'A round that ran is left alone.');
        $this->assertSame('wet', $round1->fresh()->weather);
    }

    public function test_nothing_ticked_only_saves_the_edited_round(): void
    {
        $round1 = $this->round(1);
        $round2 = $this->round(2);

        $this->update($round1, ['race_duration' => 60])->assertSessionHasNoErrors();

        $this->assertSame(30, (int) $round2->fresh()->race_duration);
    }

    public function test_applying_a_server_sets_each_rounds_slot_and_rejects_a_taken_one(): void
    {
        $server = FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21, 'username' => 'u', 'password' => 'p',
            'path' => '/results', 'server_type' => 'scheduled', 'league_id' => $this->league->id, 'active' => true,
        ]);
        $round1 = $this->round(1);
        $round2 = $this->round(2);

        $this->update($round1, ['ftp_server_id' => $server->id, 'apply_to' => ['server']])->assertSessionHasNoErrors();

        $round2->refresh();
        $this->assertSame($server->id, $round2->ftp_server_id);
        $this->assertTrue($round2->slot_time->equalTo($round2->scheduled_at));
        $this->assertSame('pending', $round2->config_push_status);

        // Another championship's race already holds round 3's slot: nothing is saved.
        $round3 = $this->round(3);
        Race::create([
            'title' => 'Other', 'track' => 'Spa', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => $round3->scheduled_at, 'ftp_server_id' => $server->id, 'slot_time' => $round3->scheduled_at,
        ]);
        $round1->refresh();

        $this->update($round1, ['ftp_server_id' => $server->id, 'race_duration' => 90, 'apply_to' => ['server', 'sessions']])
            ->assertSessionHasErrors('apply_to');

        $this->assertNull($round3->fresh()->ftp_server_id);
        $this->assertSame(30, (int) $round1->fresh()->race_duration);
    }

    public function test_only_later_rounds_get_the_copy(): void
    {
        $round1 = $this->round(1);
        $round2 = $this->round(2);
        $round3 = $this->round(3);

        $this->update($round2, ['race_duration' => 60, 'apply_to' => ['sessions']])->assertSessionHasNoErrors();

        $this->assertSame(30, (int) $round1->fresh()->race_duration);
        $this->assertSame(60, (int) $round3->fresh()->race_duration);
    }

    public function test_edit_page_offers_apply_to_later_rounds_only_when_there_are_any(): void
    {
        $round1 = $this->round(1);
        $round2 = $this->round(2);

        $this->actingAs($this->manager)
            ->get(route('admin.leagues.championships.rounds.edit', [$this->league, $this->championship, $round1]))
            ->assertOk()
            ->assertSee('Apply to later rounds');

        $this->actingAs($this->manager)
            ->get(route('admin.leagues.championships.rounds.edit', [$this->league, $this->championship, $round2]))
            ->assertOk()
            ->assertDontSee('Apply to later rounds');
    }
}
