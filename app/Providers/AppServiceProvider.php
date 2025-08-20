<?php

namespace App\Providers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        Request::macro('resident', function() {
            return auth('resident')->user();
        });

        Request::macro('wasteCollector', function() {
            return auth('waste_collector')->user();
        });
    }
}
