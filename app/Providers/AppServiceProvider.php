<?php

namespace App\Providers;

use App\Models\TeamApplication;
use Illuminate\Pagination\Paginator;
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

        // The site is Bootstrap 5 throughout — without this, pagination falls back to
        // Laravel's default Tailwind markup, which has no matching CSS here and renders
        // as oversized unstyled links (e.g. the news page paginator).
        Paginator::useBootstrapFive();

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