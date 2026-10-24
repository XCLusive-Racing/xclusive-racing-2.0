<?php

namespace Tests\Feature;

use App\Models\EsportsDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A driver can race for more than one esports team (`game` + `extra_games`).
class EsportsDriverTeamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_garcia_williams_and_peters_are_on_the_acc_team_and_keep_their_own_team(): void
    {
        $byGame = EsportsDriver::groupedByGame();

        foreach (['jose-garcia' => 'lmu', 'kyle-williams' => 'lmu', 'menno-peters' => 'iracing'] as $slug => $ownGame) {
            $this->assertContains($slug, $byGame['acc']->pluck('slug'));
            $this->assertContains($slug, $byGame[$ownGame]->pluck('slug'));
            $this->assertSame([$ownGame, 'acc'], EsportsDriver::where('slug', $slug)->firstOrFail()->games());
        }
    }

    public function test_wilson_gige_has_left_the_lmu_team(): void
    {
        $this->assertNotContains('Wilson Gigé', EsportsDriver::groupedByGame()['lmu']->pluck('name'));
        $this->assertDatabaseMissing('esports_drivers', ['name' => 'Wilson Gigé']);
    }

    public function test_a_driver_from_another_game_is_slotted_into_the_roster_by_surname(): void
    {
        EsportsDriver::query()->delete();
        EsportsDriver::create(['name' => 'Ann Adams', 'slug' => 'ann-adams', 'game' => 'acc', 'sort_order' => 1]);
        EsportsDriver::create(['name' => 'Zed Zane', 'slug' => 'zed-zane', 'game' => 'acc', 'sort_order' => 2]);
        EsportsDriver::create(['name' => 'Max Moss', 'slug' => 'max-moss', 'game' => 'lmu', 'extra_games' => ['acc'], 'sort_order' => 1]);

        $byGame = EsportsDriver::groupedByGame();

        $this->assertSame(['ann-adams', 'max-moss', 'zed-zane'], $byGame['acc']->pluck('slug')->all());
        $this->assertSame(['max-moss'], $byGame['lmu']->pluck('slug')->all());
    }

    public function test_the_esports_page_and_profile_show_a_driver_on_both_teams(): void
    {
        $this->get(route('teams.esports.index'))->assertOk();

        $this->get(route('teams.esports.show', EsportsDriver::where('slug', 'menno-peters')->firstOrFail()))
            ->assertOk()
            ->assertSee('iRacing · ACC');
    }
}
