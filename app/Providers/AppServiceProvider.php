<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Report\SalesRecap;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentProof;
use App\Models\Inventory\Items;
use App\Observers\SalesRecapObserver;
use App\Observers\InvoiceProyekObserver;
use App\Observers\ItemsObserver;
use App\Observers\PaymentProofObserver;

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

        // Register observer untuk cleanup data terkait saat invoice proyek dihapus
        InvoiceProyek::observe(InvoiceProyekObserver::class);

        // Register observer untuk cascade hapus kwitansi otomatis saat bukti pembayaran dihapus
        PaymentProof::observe(PaymentProofObserver::class);

        // Register observer untuk auto-create opening stock saat item dibuat
        Items::observe(ItemsObserver::class);
    }
}
