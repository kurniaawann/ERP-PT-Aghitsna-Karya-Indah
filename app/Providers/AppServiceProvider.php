<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Report\SalesRecap;
use App\Models\Sdm\Payroll;
use App\Models\Finance\InvoiceProyek;
use App\Models\Inventory\Items;
use App\Observers\SalesRecapObserver;
use App\Observers\PayrollObserver;
use App\Observers\InvoiceProyekObserver;
use App\Observers\ItemsObserver;

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

        // Register observer untuk auto-create opening stock saat item dibuat
        Items::observe(ItemsObserver::class);
    }
}
