<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PsnLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The automatic PSN lookup is unreliable, so PlayStation sign-ups enter their own
// numeric account ID next to their Online ID instead of going through the lookup.
class PsnManualRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'platform' => 'ps5',
            'gamertag' => ' PsnDriver ',
            'psn_account_id' => '1234567890123456789',
            'email' => 'psn@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'country' => 'Netherlands',
            'privacy_accepted' => '1',
        ], $overrides);
    }

    public function test_ps5_registration_uses_the_entered_account_id_without_a_lookup(): void
    {
        $this->mock(PsnLookupService::class)->shouldNotReceive('lookup');

        $this->post(route('register'), $this->payload())->assertRedirect(route('profile'));

        $user = User::where('email', 'psn@example.com')->firstOrFail();
        $this->assertSame('P1234567890123456789', $user->platform_id);
        $this->assertSame('PsnDriver', $user->name);
        $this->assertSame('ps5', $user->platform);
        $this->assertAuthenticatedAs($user);
    }

    public function test_ps5_registration_requires_a_numeric_account_id(): void
    {
        $this->post(route('register'), $this->payload(['psn_account_id' => '']))
            ->assertSessionHasErrors('psn_account_id');

        $this->post(route('register'), $this->payload(['psn_account_id' => 'abcdef0123456789']))
            ->assertSessionHasErrors('psn_account_id');

        $this->assertDatabaseMissing('users', ['email' => 'psn@example.com']);
    }

    public function test_ps5_registration_falls_back_to_the_lookup_when_manual_mode_is_off(): void
    {
        config(['services.psn_lookup.manual_account_id' => false]);

        $this->mock(PsnLookupService::class)
            ->shouldReceive('lookup')->once()->with('PsnDriver')
            ->andReturn(['onlineId' => 'PsnDriver', 'accountId' => '999888777666']);

        $this->get(route('register'))->assertDontSee('PSN Account ID');

        $this->post(route('register'), $this->payload(['psn_account_id' => null]))
            ->assertRedirect(route('profile'));

        $this->assertSame('P999888777666', User::where('email', 'psn@example.com')->value('platform_id'));
    }

    public function test_ps5_registration_claims_an_imported_placeholder_by_account_id(): void
    {
        $placeholder = User::factory()->create([
            'platform' => 'ps5',
            'platform_id' => 'P1234567890123456789',
            'email' => 'p1234@import.local',
            'elo_acc' => 1820,
        ]);

        $this->post(route('register'), $this->payload())->assertRedirect(route('profile'));

        $placeholder->refresh();
        $this->assertSame('psn@example.com', $placeholder->email);
        $this->assertSame(1820, (int) $placeholder->elo_acc);
        $this->assertSame(1, User::where('platform_id', 'P1234567890123456789')->count());
    }
}
