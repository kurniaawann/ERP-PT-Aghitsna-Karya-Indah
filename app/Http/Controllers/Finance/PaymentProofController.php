<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePaymentProofRequest;
use App\Http\Requests\Finance\UpdatePaymentProofRequest;
use App\Models\Finance\PaymentProof;
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
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = PaymentProof::query()->latest();

        $moduleOptions = [
            ['value' => 'finance', 'label' => 'Keuangan'],
        ];

        $isAdmin = auth()->user()?->role === 'admin';
        $invoiceTypeOptions = [
            ['value' => 'proyek', 'label' => $isAdmin ? 'Invoice' : 'Invoice Proyek'],
            ['value' => 'alumunium', 'label' => 'Invoice Alumunium'],
            ['value' => 'rekap_penjualan', 'label' => 'Rekap Penjualan'],
        ];

        $salesRecapOptions = SalesRecap::query()
            ->orderByDesc('date')
            ->get();

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
        $alumuniumProofs = (clone $query)->where('invoice_type', 'alumunium')->count();
        $salesRecapProofs = (clone $query)->where('invoice_type', 'rekap_penjualan')->count();

        $proofStageMap = $this->service->buildProofStageMap();

        $invoiceLookup = [
            'finance' => [
                'proyek'           => [],
                'alumunium'        => [],
                'rekap_penjualan'  => [],
            ],
        ];

        $availableInvoices = [
            'finance' => [
                'proyek' => collect(
                    \App\Models\Finance\InvoiceProyek::query()->with('paymentProofs')->orderByDesc('invoice_date')->get()
                )->map(
                    fn ($invoice) => $this->service->buildInvoiceOption($invoice, 'finance', 'proyek', $proofStageMap, $invoiceLookup)
                )->values()->all(),
                'alumunium' => collect(
                    \App\Models\Finance\InvoiceAlumunium::query()->with('paymentProofs')->orderByDesc('invoice_date')->get()
                )->map(
                    fn ($invoice) => $this->service->buildInvoiceOption($invoice, 'finance', 'alumunium', $proofStageMap, $invoiceLookup)
                )->values()->all(),
                'rekap_penjualan' => collect($salesRecapOptions)->map(
                    fn ($salesRecap) => $this->service->buildSalesRecapOption($salesRecap, 'finance', 'rekap_penjualan', $proofStageMap, $invoiceLookup)
                )->values()->all(),
            ],
        ];

        return view('pages.finance.payment-proofs.index', compact(
            'paymentProofs',
            'totalProofs',
            'projectProofs',
            'alumuniumProofs',
            'salesRecapProofs',
            'moduleOptions',
            'invoiceTypeOptions',
            'salesRecapOptions',
            'availableInvoices',
            'invoiceLookup'
        ));
    }

    /**
     * Menyimpan bukti pembayaran baru.
     *
     * @param  \App\Http\Requests\Finance\StorePaymentProofRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePaymentProofRequest $request)
    {
        $result = $this->service->store($request->validated(), $request->file('proof_image'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Memperbarui gambar bukti pembayaran.
     *
     * @param  \App\Http\Requests\Finance\UpdatePaymentProofRequest $request
     * @param  \App\Models\Finance\PaymentProof                     $payment_proof
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePaymentProofRequest $request, PaymentProof $payment_proof)
    {
        $result = $this->service->updateImage($payment_proof, $request->file('proof_image'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Menghapus bukti pembayaran tunggal.
     *
     * @param  \App\Models\Finance\PaymentProof $payment_proof
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
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_items', []);
        $result = $this->service->destroySelected($selectedIds);

        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
