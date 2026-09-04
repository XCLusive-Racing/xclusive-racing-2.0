<?php

namespace App\Providers;

use App\Models\TeamApplication;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            \URL::forceScheme('https');
        }

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('steam', \SocialiteProviders\Steam\Provider::class);
            $event->extendSocialite('discord', \SocialiteProviders\Discord\Provider::class);
        });

        View::composer('layouts.admin', function ($view) {
            $view->with('newApplicationsCount', auth()->check()
                ? TeamApplication::whereNull('viewed_at')->count()
                : 0);
        });
    }
}