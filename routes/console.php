<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gportal:push-configs')->everyMinute()->onOneServer();
Schedule::command('gportal:import-results')->everyFifteenMinutes()->onOneServer();
Schedule::command('xcl:discord:sync-ranks')->daily()->onOneServer();
