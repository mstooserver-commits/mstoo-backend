<?php

namespace App\Providers;

use App\Support\UrlScheme;
use App\Traits\ActivationClass;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
ini_set('memory_limit', '512M');

class AppServiceProvider extends ServiceProvider
{
    use ActivationClass;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     */
    public function boot(Request $request)
    {
        try {
            if (UrlScheme::isHttpRuntime()) {
                URL::forceScheme(UrlScheme::forRequest($request));
            }

            Config::set('default_pagination', 25);
            Paginator::useBootstrap();
        } catch (\Exception $ex) {
            info($ex);
        }
    }
}
