<?php

namespace App\Providers;

use App\Models\Administrasi\Nota;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentProof;
use App\Models\Finance\ProjectRecap;
use App\Models\Inventory\Items;
use App\Models\Report\SalesRecap;
use App\Observers\InvoiceProyekObserver;
use App\Observers\ItemsObserver;
use App\Observers\NotaObserver;
use App\Observers\PaymentProofObserver;
use App\Observers\ProjectRecapObserver;
use App\Observers\SalesRecapObserver;
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
        // Nama hari & bulan berbahasa Indonesia untuk isoFormat()/translatedFormat()
        // di dokumen cetak maupun tampilan (mis. "8 Oktober 2026", bukan "October").
        \Carbon\Carbon::setLocale('id');

        // Register observer untuk auto-create expense recap when sales recap LUNAS
        SalesRecap::observe(SalesRecapObserver::class);

        // Register observer untuk cleanup data terkait saat invoice proyek dihapus
        InvoiceProyek::observe(InvoiceProyekObserver::class);

        // Register observer untuk cascade hapus kwitansi otomatis saat bukti pembayaran dihapus
        PaymentProof::observe(PaymentProofObserver::class);

        // Register observer untuk cleanup bukti pembayaran saat rekap proyek dihapus
        ProjectRecap::observe(ProjectRecapObserver::class);

        // Register observer untuk auto-create opening stock saat item dibuat
        Items::observe(ItemsObserver::class);

        // Register observer untuk auto-create/sinkron reimburse draft dari nota Super Admin
        Nota::observe(NotaObserver::class);
    }
}
