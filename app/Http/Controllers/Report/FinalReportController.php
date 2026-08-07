<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\FinalReportService;
use Illuminate\Http\Request;

/**
 * Controller untuk modul Laporan Akhir.
 *
 * Menyatukan Laporan Stok, Laporan Penjualan, dan Laporan Pengeluaran
 * dalam satu halaman ber-tab. Hanya tab yang sesuai role user yang tampil
 * (aturan sama seperti sidebar). Export tetap terpisah per jenis laporan
 * dan memakai endpoint export masing-masing laporan.
 *
 * Business logic didelegasikan ke FinalReportService.
 */
class FinalReportController extends Controller
{
    private const TAB_LABELS = [
        'stock' => 'Laporan Stok',
        'sales' => 'Laporan Penjualan',
        'expense' => 'Laporan Pengeluaran',
    ];

    public function __construct(
        private FinalReportService $service
    ) {}

    /**
     * Menampilkan halaman Laporan Akhir sesuai tab yang aktif.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        $allowedTabs = $this->service->getAllowedTabs(auth()->user());

        if (empty($allowedTabs)) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $tab = $request->get('tab', $allowedTabs[0]);

        // Jika tab tidak diizinkan untuk role user, paksa ke tab pertama yang diizinkan
        if (!in_array($tab, $allowedTabs, true)) {
            return redirect()->route('report.final', ['tab' => $allowedTabs[0]]);
        }

        $data = $this->service->build($tab, $request);

        return view('pages.report.final-report.index', array_merge($data, [
            'allowedTabs' => $allowedTabs,
            'tab' => $tab,
            'tabLabels' => self::TAB_LABELS,
        ]));
    }
}
