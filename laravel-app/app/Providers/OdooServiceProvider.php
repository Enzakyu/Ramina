<?php

namespace App\Providers;

use App\Services\Odoo\OdooService;
use Illuminate\Support\ServiceProvider;

class OdooServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OdooService::class, function ($app) {
            return new OdooService(
                config('odoo.url'),
                config('odoo.db'),
                config('odoo.username'),
                config('odoo.api_key'),
                config('odoo.timeout')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
