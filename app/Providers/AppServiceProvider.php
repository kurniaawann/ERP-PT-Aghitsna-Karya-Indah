<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\SalesReport;
use App\Observers\SalesReportObserver;

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
        // Register observer untuk auto-create expense report when sales report LUNAS
        SalesReport::observe(SalesReportObserver::class);
    }
}
