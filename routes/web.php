<?php

use App\Http\Controllers\Admin\BopController as AdminBopController;
use App\Http\Controllers\Admin\ChampionshipController as AdminChampionshipController;
use App\Http\Controllers\ChampionshipController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\EventTagController;
use App\Http\Controllers\Admin\FtpBrowserController;
use App\Http\Controllers\Admin\FtpServerController;
use App\Http\Controllers\Admin\RatingConfigController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\RaceController as AdminRaceController;
use App\Http\Controllers\Admin\RaceResultController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordSetupController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SteamController;
use App\Http\Controllers\ConnectedAccountController;
use App\Http\Controllers\BopController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HotlapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RaceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultsController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('sven', function () {
    $baseTag = 'SatNat911GTS';
    $baseTag = 'DeEchteCas';
        $client = Http::timeout(10)->withOptions(['connect_timeout' => 5]);

        if (app()->environment('local')) {
            $client = $client->withoutVerifying();
        }

        $url = 'https://xbl.io/api/v2/player/summary?gt=' . rawurlencode($baseTag);
    $res = $client->withHeaders([
        'x-authorization' => config('services.openxbl.api_key'),
        'Accept'          => 'application/json',
        'Accept-Language' => 'en-US',
    ])->get($url);
    dd($res, $res->body());
});

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/events/sidebar-data', [EventController::class, 'getSidebarData'])->name('events.sidebar-data');

// Championships - public
Route::get('/championships', [ChampionshipController::class, 'index'])->name('championships.index');
Route::get('/championships/{championship}', [ChampionshipController::class, 'show'])->name('championships.show');
Route::post('/championships/{championship}/register', [ChampionshipController::class, 'register'])->name('championships.register')->middleware('auth');
Route::delete('/championships/{championship}/unregister', [ChampionshipController::class, 'unregister'])->name('championships.unregister')->middleware('auth');

// Events - public
Route::get('/events', [RaceController::class, 'index'])->name('events.index');
Route::get('/events/{race}', [RaceController::class, 'show'])->name('events.show');

// Calendar
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');

// Drivers & Hotlaps - public
Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
Route::get('/drivers/{driver}', [DriverController::class, 'show'])->name('drivers.show');
Route::get('/hotlaps', [HotlapController::class, 'index'])->name('hotlaps.index');

// Results, BOP & Reports - public
Route::get('/results', [ResultsController::class, 'index'])->name('results.index');
Route::get('/bop', [BopController::class, 'index'])->name('bop.index');
Route::middleware('auth')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
});

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Steam OAuth
Route::get('/auth/steam', [SteamController::class, 'redirect'])->name('auth.steam');
Route::get('/auth/steam/callback', [SteamController::class, 'callback'])->name('auth.steam.callback');

// Discord OAuth (auth required — only for linking)
Route::middleware('auth')->group(function () {
    Route::get('/auth/discord', [DiscordController::class, 'redirect'])->name('auth.discord');
    Route::get('/auth/discord/callback', [DiscordController::class, 'callback'])->name('auth.discord.callback');
});

// Password setup (for imported users)
Route::middleware('auth')->group(function () {
    Route::get('/password/setup', [PasswordSetupController::class, 'show'])->name('password.setup');
    Route::post('/password/setup', [PasswordSetupController::class, 'store'])->name('password.setup.store');
});

// Protected
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/profile/connected-accounts', [ConnectedAccountController::class, 'store'])->name('connected-accounts.store');
    Route::delete('/profile/connected-accounts/{connectedAccount}', [ConnectedAccountController::class, 'destroy'])->name('connected-accounts.destroy');

    // Event registration
    Route::post('/events/{race}/register', [RaceController::class, 'register'])->name('events.register');
    Route::delete('/events/{race}/unregister', [RaceController::class, 'unregister'])->name('events.unregister');
});

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/calendar', [AdminCalendarController::class, 'index'])->name('calendar');
    Route::get('/races', [AdminRaceController::class, 'index'])->name('races.index');
    Route::get('/races/create', [AdminRaceController::class, 'create'])->name('races.create');
    Route::get('/races/bulk-create', [AdminRaceController::class, 'bulkCreate'])->name('races.bulk-create');
    Route::post('/races/bulk-store', [AdminRaceController::class, 'bulkStore'])->name('races.bulk-store');
    Route::post('/races', [AdminRaceController::class, 'store'])->name('races.store');
    Route::get('/races/{race}', [AdminRaceController::class, 'show'])->name('races.show');
    Route::get('/races/{race}/entry-list', [AdminRaceController::class, 'downloadEntryList'])->name('races.entry-list');
    Route::get('/races/{race}/edit', [AdminRaceController::class, 'edit'])->name('races.edit');
    Route::put('/races/{race}', [AdminRaceController::class, 'update'])->name('races.update');
    Route::get('/races/{race}/results', [RaceResultController::class, 'create'])->name('races.results');
    Route::post('/races/{race}/results', [RaceResultController::class, 'store'])->name('races.results.store');
    Route::post('/races/{race}/results/ftp', [RaceResultController::class, 'ftpImport'])->name('races.results.ftp');
    Route::post('/races/{race}/results/dns', [RaceResultController::class, 'addDns'])->name('races.results.dns');
    Route::post('/races/{race}/results/recalculate', [RaceResultController::class, 'recalculate'])->name('races.results.recalculate');
    Route::post('/races/{race}/results/ftp-cancel', [RaceResultController::class, 'ftpCancel'])->name('races.results.ftp-cancel');
    Route::post('/races/{race}/push-config', [AdminRaceController::class, 'pushConfig'])->name('races.push-config');
    Route::post('/races/{race}/save-config', [AdminRaceController::class, 'saveConfig'])->name('races.save-config');
    Route::post('/races/{race}/upload-entrylist', [AdminRaceController::class, 'uploadEntrylist'])->name('races.upload-entrylist');
    Route::delete('/races/{race}/reset-config', [AdminRaceController::class, 'resetConfig'])->name('races.reset-config');

    // Championships
    Route::resource('championships', AdminChampionshipController::class)->except(['destroy']);
    Route::post('championships/{championship}/rounds', [AdminChampionshipController::class, 'addRound'])->name('championships.rounds.store');
    Route::delete('championships/{championship}/rounds/{race}', [AdminChampionshipController::class, 'removeRound'])->name('championships.rounds.destroy');
    Route::post('championships/{championship}/penalties', [AdminChampionshipController::class, 'addPenalty'])->name('championships.penalties.store');
    Route::delete('championships/{championship}/penalties/{penalty}', [AdminChampionshipController::class, 'destroyPenalty'])->name('championships.penalties.destroy');

    // Event Tags
    Route::post('/event-tags', [EventTagController::class, 'store'])->name('event-tags.store');
    Route::delete('/event-tags/{eventTag}', [EventTagController::class, 'destroy'])->name('event-tags.destroy');

    // Media Library
    Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
    Route::get('/media/list', [AdminMediaController::class, 'list'])->name('media.list');
    Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
    Route::post('/media/migrate-storage', [AdminMediaController::class, 'migrateStorage'])->name('media.migrate-storage');

    // BOPs
    Route::get('/bops', [AdminBopController::class, 'index'])->name('bops.index');
    Route::get('/bops/create', [AdminBopController::class, 'create'])->name('bops.create');
    Route::post('/bops', [AdminBopController::class, 'store'])->name('bops.store');
    Route::get('/bops/{bop}/edit', [AdminBopController::class, 'edit'])->name('bops.edit');
    Route::put('/bops/{bop}', [AdminBopController::class, 'update'])->name('bops.update');
    Route::delete('/bops/{bop}', [AdminBopController::class, 'destroy'])->name('bops.destroy');
    Route::post('/bops/import', [AdminBopController::class, 'import'])->name('bops.import');
    Route::post('/bops/push', [AdminBopController::class, 'pushBop'])->name('bops.push');
    Route::post('/bops/toggle-all', [AdminBopController::class, 'toggleAll'])->name('bops.toggle-all');
    Route::post('/bops/toggle-game', [AdminBopController::class, 'toggleGame'])->name('bops.toggle-game');
    Route::post('/bops/{bop}/toggle', [AdminBopController::class, 'toggle'])->name('bops.toggle');

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
    Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.status');

    // FTP Servers
    Route::get('/servers', [FtpServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/create', [FtpServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [FtpServerController::class, 'store'])->name('servers.store');
    Route::get('/servers/{ftpServer}/edit', [FtpServerController::class, 'edit'])->name('servers.edit');
    Route::put('/servers/{ftpServer}', [FtpServerController::class, 'update'])->name('servers.update');
    Route::delete('/servers/{ftpServer}', [FtpServerController::class, 'destroy'])->name('servers.destroy');
    Route::post('/servers/{ftpServer}/test', [FtpServerController::class, 'test'])->name('servers.test');

    // FTP Browser
    Route::get('/servers/{ftpServer}/browse', [FtpBrowserController::class, 'index'])->name('servers.browse');
    Route::get('/servers/{ftpServer}/browse/download', [FtpBrowserController::class, 'download'])->name('servers.browse.download');
    Route::get('/servers/{ftpServer}/browse/view', [FtpBrowserController::class, 'view'])->name('servers.browse.view');
    Route::post('/servers/{ftpServer}/browse/upload', [FtpBrowserController::class, 'upload'])->name('servers.browse.upload');
    Route::post('/servers/{ftpServer}/browse/mkdir', [FtpBrowserController::class, 'mkdir'])->name('servers.browse.mkdir');
    Route::post('/servers/{ftpServer}/browse/delete', [FtpBrowserController::class, 'delete'])->name('servers.browse.delete');
    Route::post('/servers/{ftpServer}/browse/rename', [FtpBrowserController::class, 'rename'])->name('servers.browse.rename');
    Route::post('/servers/{ftpServer}/browse/save', [FtpBrowserController::class, 'save'])->name('servers.browse.save');
});

// Owner + moderator — Users
Route::middleware(['auth', 'role:owner,moderator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});

// Owner only
Route::middleware(['auth', 'role:owner'])->prefix('admin')->name('admin.')->group(function () {
    // Rating Config
    Route::get('/rating-config', [RatingConfigController::class, 'index'])->name('rating-config.index');
    Route::patch('/rating-config/{key}', [RatingConfigController::class, 'update'])->name('rating-config.update');
});