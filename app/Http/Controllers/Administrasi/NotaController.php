<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreNotaRequest;
use App\Http\Requests\Administrasi\UpdateNotaRequest;
use App\Models\Administrasi\Nota;
use App\Services\Administrasi\NotaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Controller untuk modul Nota Administrasi.
 *
 * Kelas ini bertanggung jawab atas:
 * - Mengelola request HTTP (input)
 * - Mengelola response HTTP (output/view/redirect)
 * - Business logic diserahkan ke NotaService
 * - Validasi input diserahkan ke Form Request
 */
class NotaController extends Controller
{
    /**
     * Constructor - inject NotaService.
     *
     * @param  NotaService  $notaService  Service layer untuk modul Nota
     */
    public function __construct(
        private readonly NotaService $notaService
    ) {}

    /**
     * Menampilkan halaman daftar nota dengan pencarian dan paginasi.
     *
     * @param  Request  $request  Request HTTP dengan parameter search (opsional)
     * @return \Illuminate\View\View Halaman daftar nota
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $notas = $this->notaService->getPaginated($search);

        return view('pages.administrasi.nota', compact('notas', 'search'));
    }

    /**
     * Menyimpan data nota baru.
     *
     * @param  StoreNotaRequest  $request  Request dengan data yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman daftar nota
     */
    public function store(StoreNotaRequest $request)
    {
        $this->notaService->create($request->validated());

        return redirect()->route('nota.administrasi.index')
            ->with('success', 'Nota berhasil ditambahkan!');
    }

    /**
     * Memperbarui data nota yang sudah ada.
     *
     * @param  UpdateNotaRequest  $request  Request dengan data yang sudah divalidasi
     * @param  int|string  $id  ID nota yang akan diperbarui
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman daftar nota
     */
    public function update(UpdateNotaRequest $request, $id)
    {
        $nota = Nota::findOrFail($id);
        $this->notaService->update($nota, $request->validated());

        return redirect()->route('nota.administrasi.index')
            ->with('success', 'Nota berhasil diperbarui!');
    }

    /**
     * Menghapus beberapa nota sekaligus (bulk delete).
     *
     * @param  Request  $request  Request dengan array ids[]
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman daftar nota
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('nota.administrasi.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $deletedCount = $this->notaService->destroySelected($ids);

        return redirect()->route('nota.administrasi.index')
            ->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
    }

    /**
     * Export seluruh data nota ke PDF.
     *
     * @param  Request  $request  Request dengan parameter search (opsional)
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse File PDF
     */
    public function exportPdfAll(Request $request)
    {
        $search = $request->input('search');
        $notas = $this->notaService->getAllForExport($search);

        $pdf = Pdf::loadView('exports.administrasi.nota-pdf', compact('notas'));

        return $pdf->download('Nota_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export nota yang dipilih ke PDF.
     *
     * @param  Request  $request  Request dengan array ids[]
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse File PDF
     */
    public function exportPdfSelected(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('nota.administrasi.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $notas = $this->notaService->getByIds($ids);

        $pdf = Pdf::loadView('exports.administrasi.nota-pdf', compact('notas'));

        // Generate filename yang aman (tanpa karakter "/" atau "\")
        if (count($ids) == 1) {
            $safeId = str_replace(['/', '\\'], '-', $ids[0]);
            $filename = "Nota_{$safeId}_" . date('Y-m-d') . '.pdf';
        } else {
            $filename = 'Nota_' . date('Y-m-d') . '.pdf';
        }

        return $pdf->download($filename);
    }
}
