<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // When the canonical APP_URL is https, force every generated URL
        // (including asset() CSS/JS links) to https regardless of proxy or
        // X-Forwarded-Proto quirks. This prevents browsers from blocking the
        // panel's stylesheets/scripts as mixed content when the app sits
        // behind a TLS-terminating proxy (e.g. Hostinger). No effect on
        // local development, where APP_URL is http://localhost.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
