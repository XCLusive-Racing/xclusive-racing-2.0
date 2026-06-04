<?php

use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\FtpServerController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\RaceController as AdminRaceController;
use App\Http\Controllers\Admin\RaceResultController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordSetupController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SteamController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HotlapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/events/sidebar-data', [EventController::class, 'getSidebarData'])->name('events.sidebar-data');

// Races - public
Route::get('/race', [RaceController::class, 'index'])->name('race');
Route::get('/race/{race}', [RaceController::class, 'show'])->name('race.show');

// Calendar
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');

// Drivers & Hotlaps - public
Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
Route::get('/drivers/{driver}', [DriverController::class, 'show'])->name('drivers.show');
Route::get('/hotlaps', [HotlapController::class, 'index'])->name('hotlaps.index');

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

    // Race registration
    Route::post('/race/{race}/register', [RaceController::class, 'register'])->name('race.register');
    Route::delete('/race/{race}/register', [RaceController::class, 'unregister'])->name('race.unregister');
});

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/calendar', [AdminCalendarController::class, 'index'])->name('calendar');
    Route::get('/races', [AdminRaceController::class, 'index'])->name('races.index');
    Route::get('/races/create', [AdminRaceController::class, 'create'])->name('races.create');
    Route::post('/races', [AdminRaceController::class, 'store'])->name('races.store');
    Route::get('/races/{race}/edit', [AdminRaceController::class, 'edit'])->name('races.edit');
    Route::put('/races/{race}', [AdminRaceController::class, 'update'])->name('races.update');
    Route::delete('/races/{race}', [AdminRaceController::class, 'destroy'])->name('races.destroy');
    Route::get('/races/{race}/results', [RaceResultController::class, 'create'])->name('races.results');
    Route::post('/races/{race}/results', [RaceResultController::class, 'store'])->name('races.results.store');
    Route::post('/races/{race}/results/ftp', [RaceResultController::class, 'ftpImport'])->name('races.results.ftp');

    // Media Library
    Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
    Route::get('/media/list', [AdminMediaController::class, 'list'])->name('media.list');
    Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');

    // FTP Servers
    Route::get('/servers', [FtpServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/create', [FtpServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [FtpServerController::class, 'store'])->name('servers.store');
    Route::get('/servers/{ftpServer}/edit', [FtpServerController::class, 'edit'])->name('servers.edit');
    Route::put('/servers/{ftpServer}', [FtpServerController::class, 'update'])->name('servers.update');
    Route::delete('/servers/{ftpServer}', [FtpServerController::class, 'destroy'])->name('servers.destroy');
    Route::post('/servers/{ftpServer}/test', [FtpServerController::class, 'test'])->name('servers.test');
});

// Users — owner, admin, moderator
Route::middleware(['auth', 'role:owner,admin,moderator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});