<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePaymentProofRequest;
use App\Http\Requests\Finance\UpdatePaymentProofRequest;
use App\Models\Finance\InvoiceAlumunium;
use App\Models\Finance\InvoiceBarang;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentProof;
use App\Models\Finance\ProjectRecap;
use App\Models\Report\SalesRecap;
use App\Services\Finance\InvoiceCalculatorService;
use App\Services\Finance\PaymentProofService;
use Illuminate\Http\Request;

/**
 * Controller untuk modul Bukti Pembayaran (Payment Proof).
 *
 * Menangani HTTP request untuk operasi CRUD bukti pembayaran.
 * Seluruh business logic didelegasikan ke PaymentProofService.
 */
class PaymentProofController extends Controller
{
    public function __construct(
        private PaymentProofService $service,
        private InvoiceCalculatorService $calculator
    ) {}

    /**
     * Menampilkan halaman index bukti pembayaran dengan filter & search.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = PaymentProof::query()->latest();

        $moduleOptions = [
            ['value' => 'finance', 'label' => 'Keuangan'],
        ];

        $isAdmin = auth()->user()?->role === 'admin';
        $isSuperAdmin = auth()->user()?->role === 'superadmin';
        $invoiceTypeOptions = $isAdmin
            ? [
                ['value' => 'proyek', 'label' => 'Invoice'],
                ['value' => 'recap', 'label' => 'Rekap Proyek'],
            ]
            : [
                ['value' => 'proyek', 'label' => 'Invoice Proyek'],
                ['value' => 'recap', 'label' => 'Rekap Proyek'],
                ['value' => 'alumunium', 'label' => 'Invoice Alumunium'],
                ['value' => 'barang', 'label' => 'Invoice Barang'],
            ];

        // Rekap Penjualan hanya tersedia untuk Super Admin.
        if ($isSuperAdmin) {
            $invoiceTypeOptions[] = ['value' => 'rekap_penjualan', 'label' => 'Rekap Penjualan'];
        }

        if (in_array(auth()->user()?->role, ['admin', 'superadmin'], true)) {
            $query->where('created_by', auth()->id());
        }

        if ($request->filled('module_type')) {
            $query->where('module_type', $request->module_type);
        }

        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($where) use ($search) {
                $where->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhere('invoice_type', 'like', "%{$search}%")
                    ->orWhere('payment_stage', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        $paymentProofs = $query->paginate(10)->appends($request->query());

        $totalProofs = (clone $query)->count();
        $projectProofs = (clone $query)->where('invoice_type', 'proyek')->count();
        $recapProofs = (clone $query)->where('invoice_type', 'recap')->count();
        $alumuniumProofs = (clone $query)->where('invoice_type', 'alumunium')->count();
        $barangProofs = (clone $query)->where('invoice_type', 'barang')->count();
        $rekapPenjualanProofs = (clone $query)->where('invoice_type', 'rekap_penjualan')->count();

        $proofStageMap = $this->service->buildProofStageMap();

        $invoiceLookup = [
            'finance' => [
                'proyek' => [],
                'recap' => [],
                'alumunium' => [],
                'barang' => [],
            ],
        ];

        $availableInvoices = [
            'finance' => [
                'proyek' => collect(
                    InvoiceProyek::query()->with('paymentProofs')->where('created_by', auth()->id())->orderByDesc('invoice_date')->get()
                )->map(
                    fn ($invoice) => $this->service->buildInvoiceOption($invoice, 'finance', 'proyek', $proofStageMap, $invoiceLookup)
                )->values()->all(),
                'recap' => collect(
                    ProjectRecap::query()->with('paymentProofs')->where('created_by', auth()->id())->orderByDesc('created_at')->get()
                )->map(
                    fn ($recap) => $this->service->buildInvoiceOption($recap, 'finance', 'recap', $proofStageMap, $invoiceLookup)
                )->values()->all(),
            ],
        ];

        if (auth()->user()?->role !== 'admin') {
            $availableInvoices['finance']['alumunium'] = collect(
                InvoiceAlumunium::query()->with('paymentProofs')->orderByDesc('invoice_date')->get()
            )->map(
                fn ($invoice) => $this->service->buildInvoiceOption($invoice, 'finance', 'alumunium', $proofStageMap, $invoiceLookup)
            )->values()->all();

            $availableInvoices['finance']['barang'] = collect(
                InvoiceBarang::query()->with('paymentProofs')->orderByDesc('invoice_date')->get()
            )->map(
                fn ($invoice) => $this->service->buildInvoiceOption($invoice, 'finance', 'barang', $proofStageMap, $invoiceLookup)
            )->values()->all();
        }

        // Rekap Penjualan hanya tersedia untuk Super Admin.
        if (auth()->user()?->role === 'superadmin') {
            $availableInvoices['finance']['rekap_penjualan'] = collect(
                SalesRecap::query()->with('paymentProofs')->orderByDesc('date')->get()
            )->map(
                fn ($recap) => $this->service->buildInvoiceOption($recap, 'finance', 'rekap_penjualan', $proofStageMap, $invoiceLookup)
            )->values()->all();
        }

        return view('pages.finance.payment-proofs', compact(
            'paymentProofs',
            'totalProofs',
            'projectProofs',
            'recapProofs',
            'alumuniumProofs',
            'barangProofs',
            'rekapPenjualanProofs',
            'moduleOptions',
            'invoiceTypeOptions',
            'availableInvoices',
            'invoiceLookup'
        ));
    }

    /**
     * Menyimpan bukti pembayaran baru.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePaymentProofRequest $request)
    {
        $result = $this->service->store($request->validated(), $request->file('proof_image'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Memperbarui gambar dan/atau tanggal pembayaran bukti pembayaran.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePaymentProofRequest $request, PaymentProof $payment_proof)
    {
        $result = $this->service->updateImage(
            $payment_proof,
            $request->file('proof_image'),
            $request->validated()['payment_date'] ?? null
        );

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Menghapus bukti pembayaran tunggal.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PaymentProof $payment_proof)
    {
        $result = $this->service->destroy($payment_proof);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Menghapus bukti pembayaran secara massal.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_items', []);
        $result = $this->service->destroySelected($selectedIds);

        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
