<?php

namespace Database\Seeders;

use App\Models\FtpServer;
use App\Models\PracticeServer;
use Illuminate\Database\Seeder;

class PracticeServerSeeder extends Seeder
{
    public function run(): void
    {
        $ftpServer = FtpServer::where('server_number', 5)->first();

        if (!$ftpServer) {
            $this->command?->error('No FtpServer with server_number 5 found — skipping practice server seed.');
            return;
        }

        PracticeServer::updateOrCreate(
            ['ftp_server_id' => $ftpServer->id],
            [
                'max_car_slots'            => (int) env('PRACTICE_SERVER_MAX_CAR_SLOTS', 30),
                'max_connections'          => (int) env('PRACTICE_SERVER_MAX_CONNECTIONS', 32),
                'restart_cadence_minutes'  => (int) env('PRACTICE_SERVER_RESTART_CADENCE_MINUTES', 60),
                'restart_offset_minutes'   => (int) env('PRACTICE_SERVER_RESTART_OFFSET_MINUTES', 0),
                'platform'                 => env('PRACTICE_SERVER_PLATFORM', 'console'),
                'join_password'            => env('PRACTICE_SERVER_JOIN_PASSWORD'),
                'is_active'                => true,
            ]
        );
    }
}
