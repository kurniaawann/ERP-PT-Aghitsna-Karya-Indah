<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Controller untuk modul Surat Menyurat.
 *
 * Menyatukan beberapa submodul surat menyurat (Tanda Terima Dokumen,
 * Kwintansi, Nota, Surat Jalan, Surat Perintah Kerja) dalam satu halaman
 * ber-tab. Setiap tab memuat daftar (list) submodul terkait beserta
 * pencariannya. CRUD/export tetap memakai route submodul masing-masing.
 */
class SuratMenyuratController extends Controller
{
    /**
     * Meta data tiap tab: label, ikon, dan controller penyedia data.
     *
     * @var array<string, array{label: string, icon: string, controller: class-string}>
     */
    private const TABS = [
        'document-receipt' => [
            'label'   => 'Tanda Terima Dokumen',
            'icon'    => 'fa-file-signature',
            'controller' => DocumentReceiptController::class,
        ],
        'kwintansi' => [
            'label'   => 'Kwintansi',
            'icon'    => 'fa-receipt',
            'controller' => KwintansiController::class,
        ],
        'nota' => [
            'label'   => 'Nota',
            'icon'    => 'fa-file-invoice',
            'controller' => NotaController::class,
        ],
        'surat-jalan' => [
            'label'   => 'Surat Jalan',
            'icon'    => 'fa-truck',
            'controller' => DeliveryNoteController::class,
        ],
        'spk' => [
            'label'   => 'Surat Perintah Kerja',
            'icon'    => 'fa-file-contract',
            'controller' => SuratPerintahKerjaController::class,
        ],
    ];

    public function __construct(
        private readonly DocumentReceiptController $documentReceiptController,
        private readonly KwintansiController $kwintansiController,
        private readonly NotaController $notaController,
        private readonly DeliveryNoteController $deliveryNoteController,
        private readonly SuratPerintahKerjaController $suratPerintahKerjaController
    ) {}

    /**
     * Menampilkan halaman Surat Menyurat ber-tab sesuai tab yang aktif.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        $tabs = array_map(fn (array $meta) => [
            'label' => $meta['label'],
            'icon'  => $meta['icon'],
        ], self::TABS);

        $tab = $request->get('tab', array_key_first(self::TABS));

        if (!array_key_exists($tab, self::TABS)) {
            return redirect()->route('surat-menyurat.index', ['tab' => array_key_first(self::TABS)]);
        }

        $controller = $this->resolveController(self::TABS[$tab]['controller']);

        $data = $controller->indexData($request);

        return view('pages.administrasi.surat-menyurat', array_merge($data, [
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
            DocumentReceiptController::class => $this->documentReceiptController,
            KwintansiController::class       => $this->kwintansiController,
            NotaController::class            => $this->notaController,
            DeliveryNoteController::class    => $this->deliveryNoteController,
            SuratPerintahKerjaController::class => $this->suratPerintahKerjaController,
            default                           => abort(404),
        };
    }
}
