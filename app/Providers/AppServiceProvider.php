<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Report\SalesRecap;
use App\Observers\SalesRecapObserver;

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
        // Register observer untuk auto-create expense recap when sales recap LUNAS
        SalesRecap::observe(SalesRecapObserver::class);
    }
}
