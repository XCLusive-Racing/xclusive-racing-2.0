<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gportal:push-configs')->everyMinute()->onOneServer();
Schedule::command('gportal:import-results')->everyMinute()->onOneServer(); // TEMP: testing, revert to everyFifteenMinutes() after
Schedule::command('xcl:discord:sync-ranks')->everyFiveMinutes()->onOneServer();
Schedule::command('races:announce-daily')->dailyAt('12:00')->timezone('Europe/London')->onOneServer();
Schedule::command('reports:announce-daily')->dailyAt('12:00')->timezone('Europe/London')->onOneServer();
Schedule::command('practice:push-due')->everyFiveMinutes()->onOneServer();
// 24h championship practice servers: next round's track, every midnight (UK).
Schedule::command('championships:push-practice')->dailyAt('00:00')->timezone('Europe/London')->onOneServer();
// Inbox reminder for championship entrants not yet signed up for a round starting
// within 48h — once per driver per round.
Schedule::command('championships:remind-round-signups')->hourly()->onOneServer();
// Weekly Time Trials: config + entry list before every server restart, results collected
// from the hourly practice sessions, events finalized once they end.
Schedule::command('time-trials:push-due')->everyFiveMinutes()->onOneServer();
Schedule::command('time-trials:collect-results')->everyTenMinutes()->onOneServer();
