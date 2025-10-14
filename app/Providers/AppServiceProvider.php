<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;   


use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleAnalyticsService;
use Spatie\Permission\Models\Permission;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        if (env('APP_ENV') === 'production') {
            $this->app['request']->server->set('HTTPS', true);
            Log::info(message: 'force change url to HTTPS');

        }else{
            Log::info("did not force change url to HTTPS");
        }

        View::addNamespace('backpack', resource_path('views/vendor/backpack/crud'));

        // if (Schema::hasTable('permissions')) {
        //     $this->ensurePermissionsExist();
        // }

    }
}
