<?php

namespace Tests\Feature;

use App\Models\FtpServer;
use App\Models\PracticeServer;
use App\Models\Race;
use App\Services\PracticeServer\PracticeWindowCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: "we need the practise server to push 4 min earlier.
// It is not pushing in time for the reset sadly." -- upload_at used to be
// go-live minus 1 minute, but practice:push-due (routes/console.php) only
// polls every 5 minutes, so a push could dispatch up to ~5 minutes after
// upload_at -- i.e. up to 4 minutes *after* the server had already reset.
// Widened the lead to 5 minutes so that same worst-case polling delay lands
// at/before go-live instead of after it.
class PracticeWindowCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_at_is_five_minutes_before_go_live(): void
    {
        $ftpServer = FtpServer::create([
            'name' => 'Practice Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled',
        ]);

        $server = PracticeServer::create([
            'ftp_server_id' => $ftpServer->id,
            'max_car_slots' => 30, 'max_connections' => 30,
            'restart_cadence_minutes' => 120, 'restart_offset_minutes' => 0,
            'platform' => 'pc', 'is_active' => true,
        ]);

        // January (GMT, UTC+0) to keep UTC and Europe/London aligned for this
        // assertion. Restarts at :00 every 2 hours -- an event at 20:05 leaves the
        // 18:00 restart as the latest one at least 60 minutes before it.
        $race = Race::create([
            'title' => 'Test', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => '2026-01-08 20:05:00',
        ]);

        $window = (new PracticeWindowCalculator())->calculate($race, $server);

        $this->assertSame('18:00', $window->windowStart->timezone('Europe/London')->format('H:i'));
        $this->assertSame('17:55', $window->uploadAt->timezone('Europe/London')->format('H:i'));
    }
}
