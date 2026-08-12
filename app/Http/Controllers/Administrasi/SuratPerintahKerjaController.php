<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreSuratPerintahKerjaRequest;
use App\Http\Requests\Administrasi\UpdateSuratPerintahKerjaRequest;
use App\Models\Administrasi\SuratPerintahKerja;
use App\Services\Administrasi\SuratPerintahKerjaService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller untuk modul Surat Perintah Kerja (SPK).
 *
 * Menangani HTTP request/response untuk CRUD surat perintah kerja,
 * pencarian, penghapusan massal, dan export PDF & Word.
 * Business logic dialihkan ke SuratPerintahKerjaService.
 *
 * @package App\Http\Controllers\Administrasi
 */
class SuratPerintahKerjaController extends Controller
{
    /**
     * @var SuratPerintahKerjaService Service layer untuk surat perintah kerja
     */
    protected SuratPerintahKerjaService $service;

    /**
     * Konstruktor - inject SuratPerintahKerjaService.
     *
     * @param  SuratPerintahKerjaService  $service  Service surat perintah kerja
     */
    public function __construct(SuratPerintahKerjaService $service)
    {
        $this->service = $service;
    }

    /**
     * Menampilkan daftar surat perintah kerja dengan fitur pencarian dan paginasi.
     *
     * @param  \Illuminate\Http\Request  $request  Request HTTP
     * @return \Illuminate\View\View    Halaman daftar surat perintah kerja
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $suratPerintahKerjas = $this->service->getPaginated($search);

        return view('pages.administrasi.surat-perintah-kerja', compact('suratPerintahKerjas', 'search'));
    }

    /**
     * Mengambil nomor SPK berikutnya (untuk preview di form tambah).
     *
     * @return \Illuminate\Http\JsonResponse  JSON berisi nextNomor
     */
    public function getNextNomor()
    {
        return response()->json(['nextNomor' => SuratPerintahKerja::generateNomor()]);
    }

    /**
     * Menyimpan data surat perintah kerja baru ke database.
     *
     * @param  StoreSuratPerintahKerjaRequest  $request  Request dengan data yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse  Redirect ke halaman index dengan pesan sukses
     */
    public function store(StoreSuratPerintahKerjaRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('surat-perintah-kerja.administrasi.index')
            ->with('success', 'Surat Perintah Kerja berhasil ditambahkan!');
    }

    /**
     * Memperbarui data surat perintah kerja yang sudah ada.
     *
     * @param  UpdateSuratPerintahKerjaRequest  $request  Request dengan data yang sudah divalidasi
     * @param  string  $nomor  Nomor SPK yang akan diperbarui
     * @return \Illuminate\Http\RedirectResponse  Redirect ke halaman index dengan pesan sukses
     */
    public function update(UpdateSuratPerintahKerjaRequest $request, $nomor)
    {
        $spk = SuratPerintahKerja::findOrFail($nomor);
        $this->service->update($spk, $request->validated());

        return redirect()->route('surat-perintah-kerja.administrasi.index')
            ->with('success', 'Surat Perintah Kerja berhasil diperbarui!');
    }

    /**
     * Menghapus beberapa surat perintah kerja sekaligus (bulk delete).
     *
     * @param  \Illuminate\Http\Request  $request  Request HTTP dengan array nomor SPK
     * @return \Illuminate\Http\RedirectResponse  Redirect ke halaman index dengan pesan sukses/error
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('surat-perintah-kerja.administrasi.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $deletedCount = $this->service->destroySelected($ids);

        return redirect()->route('surat-perintah-kerja.administrasi.index')
            ->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
    }

    /**
     * Export satu surat perintah kerja ke PDF.
     *
     * @param  string  $nomor  Nomor SPK
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse  File PDF yang di-download
     */
    public function exportPdf(string $nomor)
    {
        $spk = SuratPerintahKerja::where('nomor', $nomor)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $suratPerintahKerjas = collect([$spk]);
        $pdf = Pdf::loadView('exports.administrasi.surat-perintah-kerja-pdf', compact('suratPerintahKerjas'));

        $safeNomor = str_replace(['/', '\\'], '-', $spk->nomor);

        return $pdf->download('Surat_Perintah_Kerja_' . $safeNomor . '.pdf');
    }

    /**
     * Export satu surat perintah kerja ke Word (.doc).
     *
     * @param  string  $nomor  Nomor SPK
     * @return \Symfony\Component\HttpFoundation\Response  File Word yang di-download
     */
    public function exportWord(string $nomor)
    {
        $spk = SuratPerintahKerja::where('nomor', $nomor)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $suratPerintahKerjas = collect([$spk]);
        $safeNomor = str_replace(['/', '\\'], '-', $spk->nomor);
        $filename = 'Surat_Perintah_Kerja_' . $safeNomor . '.doc';

        $html = view('exports.administrasi.surat-perintah-kerja-word', compact('suratPerintahKerjas'))->render();

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-word')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
