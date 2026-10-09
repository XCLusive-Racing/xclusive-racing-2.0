<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Message;
use App\Models\Race;
use App\Models\User;
use App\Services\AccCarCatalog;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A championship entry that counts gets one inbox message with its entry and every
// upcoming round's server name and password (App\Services\ChampionshipEntryMessage).
class ChampionshipEntryMessageTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeChampionship(array $settings = []): Championship
    {
        $championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'Sunday Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), $settings);
        $championship->save();

        return $championship;
    }

    private function makeRound(Championship $championship, int $number, ?FtpServer $server, array $attributes = []): Race
    {
        return Race::create($attributes + [
            'championship_id' => $championship->id, 'round_number' => $number,
            'title' => 'Round '.$number, 'track' => 'Kyalami', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeeks($number),
            'ftp_server_id' => $server?->id,
        ]);
    }

    private function server(): FtpServer
    {
        return FtpServer::withoutTenantScope()->create([
            'name' => 'NLRL Server 2', 'ingame_name' => 'NLRL Sunday Server', 'ingame_password' => 'sunday42',
            'host' => '1.2.3.4', 'port' => 21, 'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $this->league->id,
        ]);
    }

    private function soloEntry(): array
    {
        return ['car_model' => array_key_first(AccCarCatalog::namesWithClass('acc')), 'car_number' => 77];
    }

    public function test_registering_sends_every_upcoming_round_with_its_server_and_password(): void
    {
        $championship = $this->makeChampionship();
        $server = $this->server();
        $this->makeRound($championship, 1, $server);
        $this->makeRound($championship, 2, null);
        $this->makeRound($championship, 3, $server, ['status' => 'finished', 'scheduled_at' => now()->subWeek()]);
        $driver = User::factory()->create();

        $this->actingAs($driver)->post(route('championships.register', $championship), $this->soloEntry())
            ->assertSessionHas('success');

        $message = Message::where('user_id', $driver->id)->sole();
        $this->assertSame('championship_entry', $message->type);
        $this->assertSame('Registered: Sunday Cup', $message->title);
        $this->assertStringContainsString('#77 '.$this->soloEntry()['car_model'], $message->body);
        $this->assertStringContainsString('Round 1 · Kyalami', $message->body);
        $this->assertStringContainsString("Server: NLRL Sunday Server\nPassword: sunday42", $message->body);
        // A round without a server yet, and no finished rounds.
        $this->assertStringContainsString("Server: To be announced\nPassword: To be announced", $message->body);
        $this->assertStringNotContainsString('Round 3', $message->body);
    }

    public function test_a_manual_approval_entry_gets_the_message_only_once_approved(): void
    {
        $championship = $this->makeChampionship(['requirements' => ['manual_approval_required' => true]]);
        $this->makeRound($championship, 1, $this->server());
        $driver = User::factory()->create();

        $this->actingAs($driver)->post(route('championships.register', $championship), $this->soloEntry());
        $this->assertSame(0, Message::where('user_id', $driver->id)->count());

        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        $this->actingAs($manager->refresh())->post(route('admin.leagues.championships.entries.approve', [
            $this->league, $championship, $championship->registrations()->firstOrFail(),
        ]))->assertSessionHas('success');

        $message = Message::where('user_id', $driver->id)->sole();
        $this->assertSame('Championship entry approved: Sunday Cup', $message->title);
        $this->assertStringContainsString('Password: sunday42', $message->body);
    }

    public function test_a_spectator_gets_the_spectator_password(): void
    {
        $championship = $this->makeChampionship(['format' => ['spectator_slots' => 2]]);
        $this->makeRound($championship, 1, $this->server());
        $spectator = User::factory()->create();

        $this->actingAs($spectator)->post(route('championships.register', $championship), ['is_spectator' => 1])
            ->assertSessionHas('success');

        $body = Message::where('user_id', $spectator->id)->sole()->body;
        $this->assertStringContainsString('Spectator password:', $body);
        $this->assertStringNotContainsString('sunday42', $body);
    }
}
