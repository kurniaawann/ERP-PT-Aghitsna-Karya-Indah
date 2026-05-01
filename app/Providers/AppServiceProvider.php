<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Report\SalesRecap;
use App\Models\Sdm\Payroll;
use App\Models\Finance\InvoiceProyek;
use App\Observers\SalesRecapObserver;
use App\Observers\PayrollObserver;
use App\Observers\InvoiceProyekObserver;

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

        // Register observer untuk auto-sync SalaryReminder ketika Payroll status berubah
        Payroll::observe(PayrollObserver::class);

        // Register observer untuk auto-generate InvoiceProyekReminder ketika invoice dibuat
        InvoiceProyek::observe(InvoiceProyekObserver::class);
    }
}
