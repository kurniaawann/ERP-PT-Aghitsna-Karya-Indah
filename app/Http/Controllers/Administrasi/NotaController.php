<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreNotaRequest;
use App\Http\Requests\Administrasi\UpdateNotaRequest;
use App\Models\Administrasi\Nota;
use App\Models\Sdm\Executive;
use App\Models\Sdm\Division;
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
        return view('pages.administrasi.nota', $this->indexData($request));
    }

    /**
     * Menyiapkan data untuk halaman daftar nota.
     *
     * Dipakai oleh index() dan oleh SuratMenyuratController (tab Nota).
     *
     * @param  Request  $request  Request HTTP (search/filter parameter)
     * @return array
     */
    public function indexData(Request $request)
    {
        $search = $request->input('search');
        $month = $request->integer('month') ?: null;
        $year = $request->integer('year') ?: null;
        $tipe = $request->input('tipe');
        $notas = $this->notaService->getPaginated($search, $month, $year, $tipe);

        // Data petinggi (Executive) & divisi (Division) untuk dropdown
        // penanda tangan pada form nota proyek (add & edit).
        $executives = Executive::where('created_by', auth()->id())
            ->orderBy('name')
            ->get();

        $divisions = Division::where('created_by', auth()->id())
            ->orderBy('name')
            ->get();

        // Nota yang tanda tangannya belum lengkap pada filter aktif → "Export
        // Semua (PDF)" meminta dilengkapi dulu (modal x-nota-sign-modal).
        $unsignedCount = $this->notaService->countUnsigned($search, $month, $year, $tipe);

        return compact('notas', 'search', 'tipe', 'executives', 'divisions', 'unsignedCount');
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
     * Nota sesuai cakupan permintaan tanda tangan: `ids[]` (nota tertentu)
     * atau `scope=all` + filter halaman (search/month/year/tipe). Hanya nota
     * milik user login.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Nota>
     */
    private function notasForSigning(Request $request)
    {
        if ($request->input('scope') === 'all') {
            return $this->notaService->getAllForExport(
                $request->input('search'),
                $request->integer('month') ?: null,
                $request->integer('year') ?: null,
                $request->input('tipe')
            );
        }

        return $this->notaService->getByIds(array_values(array_filter((array) $request->input('ids', []), 'is_string')));
    }

    /**
     * Rincian nota yang tanda tangannya belum lengkap (Penerima / Hormat Kami)
     * untuk modal "Lengkapi Tanda Tangan Nota" (resources/js/shared/nota-sign.js).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsigned(Request $request)
    {
        return response()->json([
            'data' => $this->notaService->unsignedDetails($this->notasForSigning($request)),
        ]);
    }

    /**
     * Melengkapi tanda tangan nota sebelum download.
     *
     * Revisi klien (Super Admin & Admin): nota punya DUA tanda tangan seperti
     * di PDF — Penerima/Tanda Terima dan Hormat Kami (petinggi). Keduanya
     * opsional saat nota dibuat, tetapi WAJIB lengkap sebelum nota di-download.
     * Dipanggil modal dari tombol PDF, Export Dipilih, atau Export Semua —
     * lalu pratinjau/download dibuka.
     *
     * Input per nota: signatures[id_nota][penerima|petinggi_id|divisi] — hanya
     * bagian yang masih kosong yang disimpan.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sign(Request $request)
    {
        $validated = $request->validate([
            'signatures' => 'required|array',
            'signatures.*.penerima' => 'nullable|string|max:255',
            'signatures.*.petinggi_id' => 'nullable|integer',
            'signatures.*.divisi' => 'nullable|string|max:100',
            'ids' => 'nullable|array',
            'ids.*' => 'string',
            'scope' => 'nullable|in:all',
        ], [
            'signatures.required' => 'Tanda tangan nota wajib diisi.',
            'signatures.*.penerima.max' => 'Nama penerima maksimal 255 karakter.',
        ]);

        // Petinggi yang bukan milik user login diabaikan resolvePenandatangan
        // (dianggap kosong → ditolak sebagai belum lengkap).
        $result = $this->notaService->completeSignatures(
            $this->notasForSigning($request),
            $validated['signatures']
        );

        if (! empty($result['incomplete'])) {
            return response()->json([
                'message' => 'Tanda tangan belum lengkap untuk nota ' . implode(', ', array_unique($result['incomplete']))
                    . '. Isi nama Penerima dan pilih Penanda Tangan (Hormat Kami).',
            ], 422);
        }

        return response()->json([
            'signed' => $result['signed'],
            'message' => "{$result['signed']} nota berhasil ditandatangani.",
        ]);
    }

    /**
     * Tolak download bila masih ada nota (milik user login) yang tanda
     * tangannya belum lengkap — tombol di UI selalu meminta dilengkapi dulu.
     *
     * @param  \Illuminate\Support\Collection<int, Nota>  $notas
     */
    private function ensureSigned($notas): void
    {
        $unsigned = $notas
            ->filter(fn (Nota $nota) => (string) $nota->created_by === (string) auth()->id() && ! $nota->isSigned())
            ->pluck('id_nota');

        abort_if(
            $unsigned->isNotEmpty(),
            422,
            'Nota ' . $unsigned->take(5)->implode(', ') . ($unsigned->count() > 5 ? ', dst.' : '')
                . ' belum lengkap tanda tangannya (Penerima & Hormat Kami). Lengkapi terlebih dahulu sebelum download.'
        );
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
        $month = $request->integer('month') ?: null;
        $year = $request->integer('year') ?: null;
        $tipe = $request->input('tipe');
        $notas = $this->notaService->getAllForExport($search, $month, $year, $tipe);
        $this->ensureSigned($notas);

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
        $this->ensureSigned($notas);

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

    /**
     * Export satu nota ke PDF (tombol PDF pada kolom Aksi).
     *
     * @param  string  $id  Primary key id_nota
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse File PDF
     */
    public function exportPdfSingle(string $id)
    {
        $nota = Nota::findOrFail($id);

        $notas = collect([$nota]);
        $this->ensureSigned($notas);

        $pdf = Pdf::loadView('exports.administrasi.nota-pdf', compact('notas'));

        $safeId = str_replace(['/', '\\'], '-', $nota->id_nota);

        return $pdf->download('Nota_'.$safeId.'_'.date('Y-m-d').'.pdf');
    }
}
