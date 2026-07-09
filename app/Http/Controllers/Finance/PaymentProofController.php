<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\InvoiceAlumunium;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentProof;
use App\Models\Report\SalesRecap;
use App\Services\Finance\InvoiceCalculatorService;
use App\Services\Finance\PaymentProofService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\InputNormalizer;
use Illuminate\Support\Facades\Log;

class PaymentProofController extends Controller
{
    public function __construct(
        protected InvoiceCalculatorService $calculator
    ) {}

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

        $proofStageMap = PaymentProof::query()
            ->select('module_type', 'invoice_type', 'invoice_number', DB::raw('MAX(COALESCE(payment_stage, 0)) as max_stage'), DB::raw('COUNT(*) as proof_count'))
            ->groupBy('module_type', 'invoice_type', 'invoice_number')
            ->get()
            ->keyBy(fn($row) => $row->module_type . '|' . $row->invoice_type . '|' . $row->invoice_number);

        $invoiceLookup = [
            'finance' => [
                'proyek' => [],
                'alumunium' => [],
                'rekap_penjualan' => [],
            ],
        ];

        $availableInvoices = [
            'finance' => [
                'proyek' => $this->buildInvoiceOptions(
                    InvoiceProyek::query()->with('paymentProofs')->orderByDesc('invoice_date')->get(),
                    'finance',
                    'proyek',
                    $proofStageMap,
                    $invoiceLookup
                ),
                'alumunium' => $this->buildInvoiceOptions(
                    InvoiceAlumunium::query()->with('paymentProofs')->orderByDesc('invoice_date')->get(),
                    'finance',
                    'alumunium',
                    $proofStageMap,
                    $invoiceLookup
                ),
                'rekap_penjualan' => $this->buildSalesRecapOptions(
                    $salesRecapOptions,
                    'finance',
                    'rekap_penjualan',
                    $proofStageMap,
                    $invoiceLookup
                ),
            ],
        ];

        return view('pages.finance.payment-proofs', compact(
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

    public function store(Request $request, PaymentProofService $paymentProofService)
    {
        $validated = $request->validate([
            'module_type' => ['required', Rule::in(['finance'])],
            'invoice_type' => ['required', Rule::in(['proyek', 'alumunium', 'rekap_penjualan'])],
            'invoice_number' => ['required', 'string'],
            'amount' => ['nullable'],
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $invoice = $this->resolveInvoice($validated['invoice_type'], $validated['invoice_number']);

        if (!$invoice) {
            return back()->with('error', 'Invoice tidak ditemukan.');
        }

        if ($validated['invoice_type'] === 'proyek') {
            $amount = InputNormalizer::normalizeCurrency($request->input('amount'));

            if ($amount <= 0) {
                return back()->with('error', 'Nominal pembayaran harus lebih dari 0.')->withInput();
            }

            if ($errorResponse = $this->validatePaymentAmount($invoice, $amount)) {
                return $errorResponse;
            }
        } else {
            $amount = $this->calculator->getRemainingAmount($invoice);
        }

        $storedFile = null;
        $paymentStage = null;
        $salesRecapId = $validated['invoice_type'] === 'rekap_penjualan' ? $validated['invoice_number'] : null;

        try {
            if ($validated['invoice_type'] === 'proyek') {
                $paymentStage = $this->resolveNextPaymentStage(
                    $validated['module_type'],
                    $validated['invoice_type'],
                    $validated['invoice_number']
                );
            }

            $storedFile = $paymentProofService->store(
                $request->file('proof_image'),
                $validated['module_type'],
                $validated['invoice_type'],
                $validated['invoice_number']
            );

            DB::transaction(function () use ($validated, $storedFile, $paymentStage, $amount, $invoice, $salesRecapId) {
                PaymentProof::create([
                    'module_type' => $validated['module_type'],
                    'invoice_type' => $validated['invoice_type'],
                    'invoice_number' => $validated['invoice_number'],
                    'sales_recap_id' => $salesRecapId,
                    'payment_stage' => $paymentStage,
                    'amount' => $amount,
                    'file_name' => $storedFile['file_name'],
                    'file_path' => $storedFile['file_path'],
                    'mime_type' => $storedFile['mime_type'],
                    'file_size' => $storedFile['file_size'],
                ]);

                $this->syncPaymentStatuses($validated['invoice_type'], $validated['invoice_number'], $salesRecapId);
            });
        } catch (\Throwable $throwable) {
            Log::error('Payment Proof store failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            if (isset($storedFile['file_path'])) {
                $paymentProofService->delete($storedFile['file_path']);
            }

            return back()->with('error', 'Gagal menyimpan bukti pembayaran. Silakan coba lagi.');
        }

        return back()->with('success', 'Bukti pembayaran berhasil diupload.');
    }

    public function update(Request $request, PaymentProof $payment_proof, PaymentProofService $paymentProofService)
    {
        $validated = $request->validate([
            'module_type' => ['required', Rule::in(['finance'])],
            'invoice_type' => ['required', Rule::in(['proyek', 'alumunium', 'rekap_penjualan'])],
            'invoice_number' => ['required', 'string'],
            'amount' => ['nullable'],
            'proof_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $invoice = $this->resolveInvoice($validated['invoice_type'], $validated['invoice_number']);

        if (!$invoice) {
            return back()->with('error', 'Invoice tidak ditemukan.');
        }

        if ($validated['invoice_type'] === 'proyek') {
            $amount = InputNormalizer::normalizeCurrency($request->input('amount'));

            if ($amount <= 0) {
                return back()->with('error', 'Nominal pembayaran harus lebih dari 0.')->withInput();
            }

            if ($errorResponse = $this->validatePaymentAmount($invoice, $amount, $payment_proof->id)) {
                return $errorResponse;
            }
        } else {
            $amount = $this->calculator->getRemainingAmount($invoice);
        }

        $storedFile = null;
        $oldFilePath = $payment_proof->file_path;
        $originalInvoiceType = $payment_proof->invoice_type;
        $originalInvoiceNumber = $payment_proof->invoice_number;
        $originalSalesRecapId = $payment_proof->sales_recap_id;
        $salesRecapId = $validated['invoice_type'] === 'rekap_penjualan' ? $validated['invoice_number'] : null;

        try {
            $invoiceChanged = $payment_proof->module_type !== $validated['module_type']
                || $payment_proof->invoice_type !== $validated['invoice_type']
                || $payment_proof->invoice_number !== $validated['invoice_number'];

            $nextStage = $payment_proof->invoice_type === 'proyek'
                ? ($invoiceChanged
                    ? $this->resolveNextPaymentStage($validated['module_type'], $validated['invoice_type'], $validated['invoice_number'])
                    : $payment_proof->payment_stage)
                : null;

            $data = [
                'module_type' => $validated['module_type'],
                'invoice_type' => $validated['invoice_type'],
                'invoice_number' => $validated['invoice_number'],
                'sales_recap_id' => $salesRecapId,
                'payment_stage' => $nextStage,
                'amount' => $amount,
            ];

            if ($request->hasFile('proof_image')) {
                $storedFile = $paymentProofService->store(
                    $request->file('proof_image'),
                    $validated['module_type'],
                    $validated['invoice_type'],
                    $validated['invoice_number']
                );

                $data = array_merge($data, [
                    'file_name' => $storedFile['file_name'],
                    'file_path' => $storedFile['file_path'],
                    'mime_type' => $storedFile['mime_type'],
                    'file_size' => $storedFile['file_size'],
                ]);
            }

            DB::transaction(function () use ($payment_proof, $data, $validated, $originalInvoiceType, $originalInvoiceNumber, $salesRecapId, $originalSalesRecapId) {
                $payment_proof->update($data);

                $this->syncPaymentStatuses($originalInvoiceType, $originalInvoiceNumber, $originalSalesRecapId);
                $this->syncPaymentStatuses($validated['invoice_type'], $validated['invoice_number'], $salesRecapId, $originalSalesRecapId);
            });

            if (isset($storedFile['file_path']) && $oldFilePath && $oldFilePath !== $storedFile['file_path']) {
                $paymentProofService->delete($oldFilePath);
            }
        } catch (\Throwable $throwable) {
            Log::error('Payment Proof update failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            if (isset($storedFile['file_path'])) {
                $paymentProofService->delete($storedFile['file_path']);
            }

            return back()->with('error', 'Gagal mengupdate bukti pembayaran. Silakan coba lagi.');
        }

        return back()->with('success', 'Bukti pembayaran berhasil diupdate.');
    }

    public function destroySelected(Request $request, PaymentProofService $paymentProofService)
    {
        $selectedIds = $request->input('selected_items', []);

        if (empty($selectedIds)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $paymentProofs = PaymentProof::whereIn('id', $selectedIds)->get();

        $affectedInvoices = [];
        foreach ($paymentProofs as $proof) {
            $key = $proof->invoice_type . '|' . $proof->invoice_number . '|' . ($proof->sales_recap_id ?? '');
            $affectedInvoices[$key] = [
                'invoice_type' => $proof->invoice_type,
                'invoice_number' => $proof->invoice_number,
                'sales_recap_id' => $proof->sales_recap_id,
            ];
        }

        DB::transaction(function () use ($paymentProofs, $affectedInvoices) {
            foreach ($paymentProofs as $proof) {
                $proof->delete();
            }

            foreach ($affectedInvoices as $info) {
                $this->syncPaymentStatuses($info['invoice_type'], $info['invoice_number'], $info['sales_recap_id']);
            }
        });

        foreach ($paymentProofs as $proof) {
            $paymentProofService->delete($proof->file_path);
        }

        $message = count($selectedIds) . ' data terpilih berhasil dihapus.';

        return redirect()->back()->with('success', $message);
    }

    public function destroy(PaymentProof $payment_proof, PaymentProofService $paymentProofService)
    {
        $invoiceType = $payment_proof->invoice_type;
        $invoiceNumber = $payment_proof->invoice_number;
        $salesRecapId = $payment_proof->sales_recap_id;

        DB::transaction(function () use ($payment_proof, $invoiceType, $invoiceNumber, $salesRecapId) {
            $payment_proof->delete();
            $this->syncPaymentStatuses($invoiceType, $invoiceNumber, $salesRecapId);
        });

        $paymentProofService->delete($payment_proof->file_path);

        return back()->with('success', 'Bukti pembayaran berhasil dihapus.');
    }

    private function resolveInvoice(string $invoiceType, string $invoiceNumber)
    {
        return match ($invoiceType) {
            'proyek' => InvoiceProyek::where('invoice_number', $invoiceNumber)->first(),
            'alumunium' => InvoiceAlumunium::where('invoice_number', $invoiceNumber)->first(),
            'rekap_penjualan' => SalesRecap::where('id_sales_recap', $invoiceNumber)->first(),
            default => null,
        };
    }

    private function resolveNextPaymentStage(string $moduleType, string $invoiceType, string $invoiceNumber): ?int
    {
        if ($invoiceType !== 'proyek') {
            return null;
        }

        $stage = PaymentProof::query()
            ->where('module_type', $moduleType)
            ->where('invoice_type', $invoiceType)
            ->where('invoice_number', $invoiceNumber)
            ->max('payment_stage');

        $count = PaymentProof::query()
            ->where('module_type', $moduleType)
            ->where('invoice_type', $invoiceType)
            ->where('invoice_number', $invoiceNumber)
            ->count();

        return max((int) $stage, (int) $count) + 1;
    }

    private function validatePaymentAmount($invoice, int $amount, ?int $excludePaymentProofId = null)
    {
        $paidAmount = $this->calculator->getPaidAmountForInvoice($invoice, $excludePaymentProofId);
        $grandTotal = (int) ($invoice->total_amount ?? 0);
        $dpAmount = (int) $this->calculator->getDpAmount($invoice);
        $discountAmount = (int) $this->calculator->getDiscountAmount($invoice);
        $remainingAmount = max(0, $grandTotal - $discountAmount - $dpAmount - $paidAmount);

        if ($amount > $remainingAmount) {
            return back()
                ->with('error', 'Nominal pembayaran tidak boleh melebihi sisa tagihan: Rp ' . number_format($remainingAmount, 0, ',', '.'))
                ->withInput();
        }

        return null;
    }

    private function syncPaymentStatuses(string $invoiceType, string $invoiceNumber, ?string $salesRecapId = null, ?string $oldSalesRecapId = null): void
    {
        $invoice = $this->resolveInvoice($invoiceType, $invoiceNumber);

        if (!$invoice) {
            return;
        }

        if ($invoiceType === 'proyek') {
            $this->syncSalesRecapStatus($invoice, $salesRecapId);

            if ($oldSalesRecapId && $oldSalesRecapId !== $salesRecapId) {
                $this->syncSalesRecapStatus($invoice, $oldSalesRecapId);
            }
        } elseif ($invoiceType === 'rekap_penjualan') {
            $this->syncSalesRecapProofStatus($invoice);
        }
    }

    private function syncSalesRecapProofStatus(SalesRecap $salesRecap): void
    {
        $totalPaid = (int) PaymentProof::query()
            ->where('invoice_type', 'rekap_penjualan')
            ->where('invoice_number', $salesRecap->id_sales_recap)
            ->sum('amount');

        $salesRecap->update([
            'status' => $totalPaid >= (int) ($salesRecap->total_selling ?? 0) ? 'Lunas' : 'Belum Lunas',
        ]);
    }

    private function syncSalesRecapStatus(InvoiceProyek $invoice, ?string $salesRecapId = null): void
    {
        $salesRecap = null;

        if ($salesRecapId) {
            $salesRecap = SalesRecap::where('id_sales_recap', $salesRecapId)->first();
        }

        if (!$salesRecap) {
            $projectName = trim((string) $invoice->project_description);

            if ($projectName === '') {
                return;
            }

            $normalizedProjectName = mb_strtolower($projectName);

            $salesRecap = SalesRecap::query()
                ->whereRaw('LOWER(name_proyek) = ?', [$normalizedProjectName])
                ->orWhereRaw('LOWER(name_proyek) LIKE ?', ['%' . $normalizedProjectName . '%'])
                ->first();
        }

        if (!$salesRecap) {
            return;
        }

        $salesRecap->update([
            'status' => $invoice->isFullyPaid() ? 'Lunas' : 'Belum Lunas',
        ]);
    }

    private function buildInvoiceOptions($invoices, string $moduleType, string $invoiceType, $proofStageMap, array &$invoiceLookup): array
    {
        $options = [];

        foreach ($invoices as $invoice) {
            $invoiceKey = $invoice instanceof SalesRecap ? $invoice->id_sales_recap : $invoice->invoice_number;
            $mapKey = $moduleType . '|' . $invoiceType . '|' . $invoiceKey;
            $proofMeta = $proofStageMap->get($mapKey);
            $nextStage = $invoiceType === 'proyek'
                ? max((int) ($proofMeta->max_stage ?? 0), (int) ($proofMeta->proof_count ?? 0)) + 1
                : null;
            $calcData = $this->calculator->buildInvoiceOptionData($invoice, $moduleType, $invoiceType);

            $label = $invoice instanceof SalesRecap
                ? $invoice->id_sales_recap . ' - ' . $invoice->name_proyek
                : $invoice->invoice_number . ' - ' . $invoice->recipient;

            if (!$invoice instanceof SalesRecap && !empty($invoice->project_description)) {
                $label .= ' - ' . $invoice->project_description;
            }

            $options[] = [
                'value' => $invoiceKey,
                'label' => $label,
                'next_stage' => $nextStage,
                'paid_amount' => $calcData['paid_amount'],
                'net_amount' => $calcData['net_amount'],
                'remaining_amount' => $calcData['remaining_amount'],
                'is_fully_paid' => $calcData['is_fully_paid'],
            ];

            $invoiceLookup[$moduleType][$invoiceType][$invoiceKey] = [
                'label' => $label,
                'next_stage' => $nextStage,
                'paid_amount' => $calcData['paid_amount'],
                'net_amount' => $calcData['net_amount'],
                'remaining_amount' => $calcData['remaining_amount'],
                'is_fully_paid' => $calcData['is_fully_paid'],
            ];
        }

        return $options;
    }

    private function buildSalesRecapOptions($salesRecaps, string $moduleType, string $invoiceType, $proofStageMap, array &$invoiceLookup): array
    {
        $options = [];

        foreach ($salesRecaps as $salesRecap) {
            $mapKey = $moduleType . '|' . $invoiceType . '|' . $salesRecap->id_sales_recap;
            $proofMeta = $proofStageMap->get($mapKey);
            $calcData = $this->calculator->buildInvoiceOptionData($salesRecap, $moduleType, $invoiceType);

            $label = $salesRecap->id_sales_recap . ' - ' . $salesRecap->name_proyek;

            $options[] = [
                'value' => $salesRecap->id_sales_recap,
                'label' => $label,
                'next_stage' => null,
                'paid_amount' => $calcData['paid_amount'],
                'net_amount' => $calcData['net_amount'],
                'remaining_amount' => $calcData['remaining_amount'],
                'is_fully_paid' => $calcData['is_fully_paid'],
            ];

            $invoiceLookup[$moduleType][$invoiceType][$salesRecap->id_sales_recap] = [
                'label' => $label,
                'next_stage' => null,
                'paid_amount' => $calcData['paid_amount'],
                'net_amount' => $calcData['net_amount'],
                'remaining_amount' => $calcData['remaining_amount'],
                'is_fully_paid' => $calcData['is_fully_paid'],
            ];
        }

        return $options;
    }
}