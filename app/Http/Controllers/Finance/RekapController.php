<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Controller untuk modul Rekap.
 *
 * Menyatukan beberapa halaman rekap (Penjualan, Alumunium, Proyek,
 * Pengeluaran) dalam satu halaman ber-tab. Halaman ini khusus untuk
 * role super admin; role admin tetap menggunakan submenu & halaman
 * rekap masing-masing.
 *
 * Setiap tab menyiapkan datanya lewat indexData() pada controller
 * submodul; CRUD/export tetap memakai route submodul masing-masing.
 */
class RekapController extends Controller
{
    /**
     * Meta data tiap tab: label, ikon, dan controller penyedia data.
     *
     * @var array<string, array{label: string, icon: string, controller: class-string}>
     */
    private const TABS = [
        'sales' => [
            'label'   => 'Rekap Penjualan',
            'icon'    => 'fa-chart-bar',
            'controller' => RecapSalesController::class,
        ],
        'aluminium' => [
            'label'   => 'Rekap Alumunium',
            'icon'    => 'fa-file-invoice-dollar',
            'controller' => RecapAlumuniumController::class,
        ],
        'proyek' => [
            'label'   => 'Rekap Proyek',
            'icon'    => 'fa-file-invoice',
            'controller' => RecapProyekController::class,
        ],
        'pengeluaran' => [
            'label'   => 'Rekap Pengeluaran',
            'icon'    => 'fa-money-bill-wave',
            'controller' => RecapExpenseController::class,
        ],
    ];

    public function __construct(
        private readonly RecapSalesController $recapSalesController,
        private readonly RecapAlumuniumController $recapAlumuniumController,
        private readonly RecapProyekController $recapProyekController,
        private readonly RecapExpenseController $recapExpenseController
    ) {}

    /**
     * Menampilkan halaman Rekap ber-tab untuk role super admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        if ((auth()->user()?->role ?? null) !== 'superadmin') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $tabs = array_map(fn (array $meta) => [
            'label' => $meta['label'],
            'icon'  => $meta['icon'],
        ], self::TABS);

        $tab = $request->get('tab', array_key_first(self::TABS));

        if (!array_key_exists($tab, self::TABS)) {
            return redirect()->route('rekap.index', ['tab' => array_key_first(self::TABS)]);
        }

        $controller = $this->resolveController(self::TABS[$tab]['controller']);

        $data = $controller->indexData($request);

        return view('pages.finance.rekap', array_merge($data, [
            'tab'  => $tab,
            'tabs' => $tabs,
        ]));
    }

    /**
     * Memilih controller submodul sesuai tab yang aktif.
     *
     * @param  string  $controller
     * @return \App\Http\Controllers\Controller
     */
    private function resolveController(string $controller)
    {
        return match ($controller) {
            RecapSalesController::class      => $this->recapSalesController,
            RecapAlumuniumController::class  => $this->recapAlumuniumController,
            RecapProyekController::class     => $this->recapProyekController,
            RecapExpenseController::class    => $this->recapExpenseController,
            default                           => abort(404),
        };
    }
}