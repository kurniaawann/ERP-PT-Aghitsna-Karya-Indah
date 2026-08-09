<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceAlumunium;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentProof;
use App\Models\Report\SalesRecap;
use App\Services\Administrasi\KwintansiService;
use App\Services\InputNormalizer;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Service layer untuk operasi bisnis Bukti Pembayaran.
 *
 * Menangani semua logika bisnis terkait Payment Proof termasuk:
 * - Resolusi invoice dari tipe dan nomor
 * - Validasi nominal pembayaran
 * - Perhitungan stage pembayaran berikutnya
 * - Penyimpanan dan penghapusan file gambar
 * - Sinkronisasi status pembayaran pada invoice terkait
 */
class PaymentProofService
{
    public function __construct(
        private InvoiceCalculatorService $calculator,
        private KwintansiService $kwintansiService
    ) {}

    // ─── Invoice Resolution ───────────────────────────────────────────────

    /**
     * Resolusi model invoice berdasarkan tipe dan nomor.
     *
     * Tiga tipe invoice yang didukung:
     * - 'proyek'          → tabel proyek_invoices, kolom invoice_number
     * - 'alumunium'       → tabel alumunium_invoices, kolom invoice_number
     * - 'rekap_penjualan' → tabel sales_recaps, kolom id_sales_recap
     *
     * @param  string $invoiceType   Tipe invoice: proyek|alumunium|rekap_penjualan
     * @param  string $invoiceNumber Nomor atau ID invoice
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveInvoice(string $invoiceType, string $invoiceNumber)
    {
        return match ($invoiceType) {
            'proyek'           => InvoiceProyek::where('invoice_number', $invoiceNumber)->first(),
            'alumunium'        => InvoiceAlumunium::where('invoice_number', $invoiceNumber)->first(),
            'rekap_penjualan'  => SalesRecap::where('id_sales_recap', $invoiceNumber)->first(),
            default            => null,
        };
    }

    // ─── Payment Stage ────────────────────────────────────────────────────

    /**
     * Mendapatkan stage pembayaran berikutnya untuk invoice proyek.
     *
     * Logika: stage dihitung dari nilai tertinggi antara MAX(payment_stage) dan
     * COUNT(proof) yang sudah ada, lalu + 1. Contoh: jika sudah ada proof
     * dengan stage 1 dan 3 → max(3, 2) + 1 = stage 4 (angka tidak pernah
     * bentrok walau stage pernah di-skip).
     *
     * @param  string      $moduleType
     * @param  string      $invoiceType
     * @param  string      $invoiceNumber
     * @return int|null
     */
    public function resolveNextPaymentStage(string $moduleType, string $invoiceType, string $invoiceNumber): ?int
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

    // ─── Payment Validation ───────────────────────────────────────────────

    /**
     * Memvalidasi apakah nominal pembayaran melebihi sisa tagihan.
     *
     * @param  \Illuminate\Database\Eloquent\Model $invoice
     * @param  int                                 $amount
     * @param  int|null                            $excludePaymentProofId  ID yang dikecualikan (untuk update)
     * @return string|null  Pesan error jika validasi gagal, null jika lolos
     */
    public function validatePaymentAmount($invoice, int $amount, ?int $excludePaymentProofId = null): ?string
    {
        $paidAmount = $this->calculator->getPaidAmountForInvoice($invoice, $excludePaymentProofId);
        $grandTotal = (int) ($invoice->total_amount ?? 0);
        $dpAmount = (int) $this->calculator->getDpAmount($invoice);
        $discountAmount = (int) $this->calculator->getDiscountAmount($invoice);
        $remainingAmount = max(0, $grandTotal - $discountAmount - $dpAmount - $paidAmount);

        if ($amount > $remainingAmount) {
            return 'Nominal pembayaran tidak boleh melebihi sisa tagihan: Rp ' . number_format($remainingAmount, 0, ',', '.');
        }

        return null;
    }

    /**
     * Resolve amount berdasarkan tipe invoice.
     *
     * Logika:
     * - Invoice 'proyek': amount berasal dari input user (dari form). Divalidasi
     *   tidak melebihi sisa tagihan. Jika amount <= 0 → null (tidak valid).
     * - Invoice lain (alumunium/rekap): amount otomatis = seluruh sisa tagihan
     *   (pembayaran dianggap lunas). Dikembalikan sebagai int.
     *
     * Return union:
     * - int (amount valid) / string (pesan error validasi) / null (amount <= 0).
     *
     * @param  array<string, mixed>                 $validated
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @param  int|null                             $excludePaymentProofId
     * @return int|string|null  int jika valid, string error jika gagal, null jika amount <= 0
     */
    public function resolveAmount(array $validated, $invoice, ?int $excludePaymentProofId = null): int|string|null
    {
        if ($validated['invoice_type'] === 'proyek') {
            $amount = InputNormalizer::normalizeCurrency($validated['amount'] ?? null);

            if ($amount <= 0) {
                return null;
            }

            $errorMessage = $this->validatePaymentAmount($invoice, $amount, $excludePaymentProofId);
            if ($errorMessage) {
                return $errorMessage;
            }

            return $amount;
        }

        return $this->calculator->getRemainingAmount($invoice);
    }

    // ─── CRUD Operations ─────────────────────────────────────────────────

    /**
     * Menyimpan bukti pembayaran baru.
     *
     * Alur logika:
     * 1. Resolusi invoice — jika tidak ditemukan, batal.
     * 2. Tentukan amount (validasi sisa tagihan) — jika gagal, batal.
     * 3. Untuk invoice proyek: hitung payment_stage berikutnya.
     * 4. Simpan file gambar (resize 1200px) lalu insert record + sync status.
     * 5. Jika gagal: file yang baru tersimpan dihapus (cleanup), agar tidak
     *    ada file yatim (file tanpa record di DB).
     *
     * @param  array<string, mixed>         $validated  Data yang sudah validasi
     * @param  \Illuminate\Http\UploadedFile $proofImage
     * @return array{success: bool, message: string}
     */
    public function store(array $validated, UploadedFile $proofImage): array
    {
        $invoice = $this->resolveInvoice($validated['invoice_type'], $validated['invoice_number']);

        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice tidak ditemukan.'];
        }

        $amount = $this->resolveAmount($validated, $invoice);
        if ($amount === null) {
            return ['success' => false, 'message' => 'Nominal pembayaran harus lebih dari 0.'];
        }

        if (is_string($amount)) {
            return ['success' => false, 'message' => $amount];
        }

        $salesRecapId = $validated['invoice_type'] === 'rekap_penjualan' ? $validated['invoice_number'] : null;
        $storedFile = null;
        $paymentStage = null;

        try {
            if ($validated['invoice_type'] === 'proyek') {
                $paymentStage = $this->resolveNextPaymentStage(
                    $validated['module_type'],
                    $validated['invoice_type'],
                    $validated['invoice_number']
                );
            }

            $storedFile = $this->storeFile(
                $proofImage,
                $validated['module_type'],
                $validated['invoice_type'],
                $validated['invoice_number']
            );

            DB::transaction(function () use ($validated, $storedFile, $paymentStage, $amount, $salesRecapId, $invoice) {
                PaymentProof::create([
                    'module_type'    => $validated['module_type'],
                    'invoice_type'   => $validated['invoice_type'],
                    'invoice_number' => $validated['invoice_number'],
                    'sales_recap_id' => $salesRecapId,
                    'payment_stage'  => $paymentStage,
                    'amount'         => $amount,
                    'file_name'      => $storedFile['file_name'],
                    'file_path'      => $storedFile['file_path'],
                    'mime_type'      => $storedFile['mime_type'],
                    'file_size'      => $storedFile['file_size'],
                    'created_by'     => auth()->id(),
                    'payment_date'   => $validated['payment_date'] ?? now()->toDateString(),
                ]);

                if ($validated['invoice_type'] === 'proyek' && $invoice instanceof InvoiceProyek) {
                    $this->kwintansiService->createFromPaymentProof(
                        $invoice,
                        $amount,
                        $invoice->getRemainingAmount(),
                        $validated['payment_date'] ?? now()->toDateString()
                    );
                }

                $this->syncPaymentStatuses($validated['invoice_type'], $validated['invoice_number'], $salesRecapId);
            });
        } catch (\Throwable $throwable) {
            Log::error('Payment Proof store failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            if (isset($storedFile['file_path'])) {
                $this->deleteFile($storedFile['file_path']);
            }

            return ['success' => false, 'message' => 'Gagal menyimpan bukti pembayaran. Silakan coba lagi.'];
        }

        return ['success' => true, 'message' => 'Bukti pembayaran berhasil diupload.'];
    }

    /**
     * Memperbarui bukti pembayaran.
     *
     * @param  \App\Models\Finance\PaymentProof  $paymentProof
     * @param  array<string, mixed>               $validated
     * @param  \Illuminate\Http\UploadedFile|null $proofImage
     * @return array{success: bool, message: string}
     */
    public function update(PaymentProof $paymentProof, array $validated, ?UploadedFile $proofImage = null): array
    {
        $invoice = $this->resolveInvoice($validated['invoice_type'], $validated['invoice_number']);

        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice tidak ditemukan.'];
        }

        $amount = $this->resolveAmount($validated, $invoice, $paymentProof->id);
        if ($amount === null) {
            return ['success' => false, 'message' => 'Nominal pembayaran harus lebih dari 0.'];
        }

        if (is_string($amount)) {
            return ['success' => false, 'message' => $amount];
        }

        $oldFilePath = $paymentProof->file_path;
        $originalInvoiceType = $paymentProof->invoice_type;
        $originalInvoiceNumber = $paymentProof->invoice_number;
        $originalSalesRecapId = $paymentProof->sales_recap_id;
        $salesRecapId = $validated['invoice_type'] === 'rekap_penjualan' ? $validated['invoice_number'] : null;
        $storedFile = null;

        try {
            // invoiceChanged = apakah bukti bayar dipindah ke invoice/modul lain.
            // Jika ya, stage dihitung ulang; jika tidak, stage dipertahankan.
            $invoiceChanged = $paymentProof->module_type !== $validated['module_type']
                || $paymentProof->invoice_type !== $validated['invoice_type']
                || $paymentProof->invoice_number !== $validated['invoice_number'];

            $nextStage = $paymentProof->invoice_type === 'proyek'
                ? ($invoiceChanged
                    ? $this->resolveNextPaymentStage($validated['module_type'], $validated['invoice_type'], $validated['invoice_number'])
                    : $paymentProof->payment_stage)
                : null;

            $data = [
                'module_type'    => $validated['module_type'],
                'invoice_type'   => $validated['invoice_type'],
                'invoice_number' => $validated['invoice_number'],
                'sales_recap_id' => $salesRecapId,
                'payment_stage'  => $nextStage,
                'amount'         => $amount,
            ];

            if ($proofImage) {
                $storedFile = $this->storeFile(
                    $proofImage,
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

            DB::transaction(function () use ($paymentProof, $data, $validated, $originalInvoiceType, $originalInvoiceNumber, $salesRecapId, $originalSalesRecapId) {
                $paymentProof->update($data);

                $this->syncPaymentStatuses($originalInvoiceType, $originalInvoiceNumber, $originalSalesRecapId);
                $this->syncPaymentStatuses($validated['invoice_type'], $validated['invoice_number'], $salesRecapId, $originalSalesRecapId);
            });

            if (isset($storedFile['file_path']) && $oldFilePath && $oldFilePath !== $storedFile['file_path']) {
                $this->deleteFile($oldFilePath);
            }
        } catch (\Throwable $throwable) {
            Log::error('Payment Proof update failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            if (isset($storedFile['file_path'])) {
                $this->deleteFile($storedFile['file_path']);
            }

            return ['success' => false, 'message' => 'Gagal mengupdate bukti pembayaran. Silakan coba lagi.'];
        }

        return ['success' => true, 'message' => 'Bukti pembayaran berhasil diupdate.'];
    }

    /**
     * Memperbarui gambar dan/atau tanggal pembayaran bukti pembayaran.
     *
     * Tanggal pembayaran (payment_date) boleh diubah manual tanpa harus
     * mengganti gambar; begitu juga sebaliknya. Param $proofImage dan
     * $paymentDate bersifat opsional.
     *
     * @param  \App\Models\Finance\PaymentProof  $paymentProof
     * @param  \Illuminate\Http\UploadedFile|null $proofImage
     * @param  string|null                        $paymentDate  Format Y-m-d
     * @return array{success: bool, message: string}
     */
    public function updateImage(PaymentProof $paymentProof, ?UploadedFile $proofImage = null, ?string $paymentDate = null): array
    {
        $oldFilePath = $paymentProof->file_path;

        try {
            $storedFile = null;

            if ($proofImage) {
                $storedFile = $this->storeFile(
                    $proofImage,
                    $paymentProof->module_type,
                    $paymentProof->invoice_type,
                    $paymentProof->invoice_number
                );
            }

            $data = [];
            if ($storedFile) {
                $data = array_merge($data, [
                    'file_name' => $storedFile['file_name'],
                    'file_path' => $storedFile['file_path'],
                    'mime_type' => $storedFile['mime_type'],
                    'file_size' => $storedFile['file_size'],
                ]);
            }

            if ($paymentDate !== null) {
                $data['payment_date'] = $paymentDate;
            }

            if ($data) {
                $paymentProof->update($data);
            }

            if ($storedFile && $oldFilePath && $oldFilePath !== $storedFile['file_path']) {
                $this->deleteFile($oldFilePath);
            }
        } catch (\Throwable $throwable) {
            Log::error('Payment Proof image update failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            return ['success' => false, 'message' => 'Gagal mengupdate bukti pembayaran. Silakan coba lagi.'];
        }

        return ['success' => true, 'message' => 'Bukti pembayaran berhasil diupdate.'];
    }

    /**
     * Menghapus bukti pembayaran tunggal.
     *
     * @param  \App\Models\Finance\PaymentProof $paymentProof
     * @return array{success: bool, message: string}
     */
    public function destroy(PaymentProof $paymentProof): array
    {
        $invoiceType = $paymentProof->invoice_type;
        $invoiceNumber = $paymentProof->invoice_number;
        $salesRecapId = $paymentProof->sales_recap_id;

        DB::transaction(function () use ($paymentProof, $invoiceType, $invoiceNumber, $salesRecapId) {
            $paymentProof->delete();
            $this->syncPaymentStatuses($invoiceType, $invoiceNumber, $salesRecapId);
        });

        $this->deleteFile($paymentProof->file_path);

        return ['success' => true, 'message' => 'Bukti pembayaran berhasil dihapus.'];
    }

    /**
     * Menghapus bukti pembayaran secara massal.
     *
     * @param  array<int, int> $selectedIds
     * @return array{success: bool, message: string}
     */
    public function destroySelected(array $selectedIds): array
    {
        if (empty($selectedIds)) {
            return ['success' => false, 'message' => 'Tidak ada data yang dipilih untuk dihapus.'];
        }

        $paymentProofs = PaymentProof::whereIn('id', $selectedIds)->get();

        $affectedInvoices = [];
        foreach ($paymentProofs as $proof) {
            $key = $proof->invoice_type . '|' . $proof->invoice_number . '|' . ($proof->sales_recap_id ?? '');
            $affectedInvoices[$key] = [
                'invoice_type'   => $proof->invoice_type,
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
            $this->deleteFile($proof->file_path);
        }

        $message = count($selectedIds) . ' data terpilih berhasil dihapus.';

        return ['success' => true, 'message' => $message];
    }

    /**
     * Menghapus file bukti pembayaran berdasarkan path relatif.
     *
     * Method publik untuk backward compatibility dengan Observer dan Model
     * yang memanggil PaymentProofService::delete() langsung.
     *
     * @param  string|null $relativePath  Path relatif file (dari public/)
     * @return void
     */
    public function delete(?string $relativePath): void
    {
        $this->deleteFile($relativePath);
    }

    // ─── Invoice Option Building ──────────────────────────────────────────

    /**
     * Membangun data opsi invoice untuk dropdown/modal.
     *
     * @param  \Illuminate\Database\Eloquent\Model $invoice
     * @param  string                               $moduleType
     * @param  string                               $invoiceType
     * @param  \Illuminate\Support\Collection       $proofStageMap
     * @param  array                                &$invoiceLookup
     * @return array
     */
    public function buildInvoiceOption($invoice, string $moduleType, string $invoiceType, $proofStageMap, array &$invoiceLookup): array
    {
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

        $option = [
            'value'            => $invoiceKey,
            'label'            => $label,
            'next_stage'       => $nextStage,
            'paid_amount'      => $calcData['paid_amount'],
            'net_amount'       => $calcData['net_amount'],
            'remaining_amount' => $calcData['remaining_amount'],
            'is_fully_paid'    => $calcData['is_fully_paid'],
        ];

        $invoiceLookup[$moduleType][$invoiceType][$invoiceKey] = [
            'label'            => $label,
            'next_stage'       => $nextStage,
            'paid_amount'      => $calcData['paid_amount'],
            'net_amount'       => $calcData['net_amount'],
            'remaining_amount' => $calcData['remaining_amount'],
            'is_fully_paid'    => $calcData['is_fully_paid'],
        ];

        return $option;
    }

    /**
     * Membangun data opsi sales recap untuk dropdown/modal.
     *
     * @param  \App\Models\Report\SalesRecap $salesRecap
     * @param  string                         $moduleType
     * @param  string                         $invoiceType
     * @param  \Illuminate\Support\Collection $proofStageMap
     * @param  array                          &$invoiceLookup
     * @return array
     */
    public function buildSalesRecapOption(SalesRecap $salesRecap, string $moduleType, string $invoiceType, $proofStageMap, array &$invoiceLookup): array
    {
        $mapKey = $moduleType . '|' . $invoiceType . '|' . $salesRecap->id_sales_recap;
        $proofMeta = $proofStageMap->get($mapKey);
        $calcData = $this->calculator->buildInvoiceOptionData($salesRecap, $moduleType, $invoiceType);

        $label = $salesRecap->id_sales_recap . ' - ' . $salesRecap->name_proyek;

        $option = [
            'value'            => $salesRecap->id_sales_recap,
            'label'            => $label,
            'next_stage'       => null,
            'paid_amount'      => $calcData['paid_amount'],
            'net_amount'       => $calcData['net_amount'],
            'remaining_amount' => $calcData['remaining_amount'],
            'is_fully_paid'    => $calcData['is_fully_paid'],
        ];

        $invoiceLookup[$moduleType][$invoiceType][$salesRecap->id_sales_recap] = [
            'label'            => $label,
            'next_stage'       => null,
            'paid_amount'      => $calcData['paid_amount'],
            'net_amount'       => $calcData['net_amount'],
            'remaining_amount' => $calcData['remaining_amount'],
            'is_fully_paid'    => $calcData['is_fully_paid'],
        ];

        return $option;
    }

    /**
     * Membangun peta stage pembayaran dari semua payment proof.
     *
     * Logika query:
     * - GROUP BY module_type, invoice_type, invoice_number → 1 baris per kombinasi.
     * - MAX(COALESCE(payment_stage, 0)) = stage tertinggi per invoice (0 jika null).
     * - COUNT(*) = jumlah proof per invoice.
     * - keyBy(kombinasi 'module|type|number') → map lookup O(1) di buildInvoiceOption().
     *   Ini menghindari query per invoice (N+1).
     *
     * @return \Illuminate\Support\Collection
     */
    public function buildProofStageMap()
    {
        return PaymentProof::query()
            ->select('module_type', 'invoice_type', 'invoice_number', DB::raw('MAX(COALESCE(payment_stage, 0)) as max_stage'), DB::raw('COUNT(*) as proof_count'))
            ->groupBy('module_type', 'invoice_type', 'invoice_number')
            ->get()
            ->keyBy(fn ($row) => $row->module_type . '|' . $row->invoice_type . '|' . $row->invoice_number);
    }

    // ─── Payment Status Sync ─────────────────────────────────────────────

    /**
     * Sinkronisasi status pembayaran pada invoice terkait.
     *
     * Logika:
     * - Invoice proyek: sinkronkan status SalesRecap terkait (via syncSalesRecapStatus).
     *   Jika salesRecapId berubah (update), status sales recap LAMA juga ikut
     *   disinkronkan ulang agar tidak ada yang tersisa "Lunas" padahal proof sudah pindah.
     * - Invoice rekap_penjualan: sinkronkan status sales recap langsung.
     *
     * @param  string      $invoiceType
     * @param  string      $invoiceNumber
     * @param  string|null $salesRecapId
     * @param  string|null $oldSalesRecapId
     * @return void
     */
    public function syncPaymentStatuses(string $invoiceType, string $invoiceNumber, ?string $salesRecapId = null, ?string $oldSalesRecapId = null): void
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

    /**
     * Sinkronisasi status sales recap dari invoice proyek.
     *
     * Logika pencarian sales recap:
     * 1. Jika salesRecapId diberikan, cari langsung by id.
     * 2. Jika tidak (invoice proyek lama tanpa sales_recap_id), cocokkan nama proyek:
     *    - Exact match (case-insensitive via LOWER()).
     *    - Jika tidak ketemu, partial match LIKE '%nama%' (fallback).
     * 3. Update status sales recap: 'Lunas' jika invoice lunas, 'Belum Lunas' sebaliknya.
     *
     * @param  \App\Models\Finance\InvoiceProyek $invoice
     * @param  string|null                       $salesRecapId
     * @return void
     */
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

    /**
     * Sinkronisasi status sales recap dari payment proof rekap_penjualan.
     *
     * @param  \App\Models\Report\SalesRecap $salesRecap
     * @return void
     */
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

    // ─── File Storage (inline) ───────────────────────────────────────────

    /**
     * Menyimpan file gambar bukti pembayaran.
     *
     * Logika:
     * - Jika ekstensi GD tidak tersedia (imagecreatetruecolor tidak ada): simpan file
     *   asli apa adanya (tanpa resize).
     * - Jika ada: resize gambar maksimal 1200×1200 (proporsional, tidak pernah diperbesar),
     *   konversi ke JPEG kualitas 80, lalu simpan. File disimpan dengan nama UUID unik.
     * - Semua file disimpan via Storage::disk('public') — jadi path yang disimpan ke DB
     *   adalah path RELATIF (bukan absolut) agar portabel antar server.
     *
     * @param  \Illuminate\Http\UploadedFile $file
     * @param  string                         $moduleType
     * @param  string                         $invoiceType
     * @param  string                         $invoiceNumber
     * @return array{file_name: string, file_path: string, mime_type: string, file_size: int|null}
     *
     * @throws \RuntimeException
     */
    private function storeFile(UploadedFile $file, string $moduleType, string $invoiceType, string $invoiceNumber): array
    {
        $relativeDirectory = $this->buildRelativeDirectory($moduleType, $invoiceType, $invoiceNumber);

        if (!function_exists('imagecreatetruecolor')) {
            $fileName = Str::uuid()->toString() . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
            $relativePath = $relativeDirectory . '/' . $fileName;

            $file->storeAs($relativeDirectory, $fileName, ['disk' => 'public']);

            return [
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $relativePath,
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'file_size' => Storage::disk('public')->size($relativePath),
            ];
        }

        $imageInfo = @getimagesize($file->getPathname());

        if ($imageInfo === false) {
            throw new RuntimeException('File yang diunggah bukan gambar yang valid.');
        }

        [$sourceWidth, $sourceHeight] = $imageInfo;
        $sourceImage = $this->createImageResource($file->getPathname(), $file->getMimeType());

        $maxWidth = 1200;
        $maxHeight = 1200;
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = (int) round($sourceWidth * $ratio);
        $targetHeight = (int) round($sourceHeight * $ratio);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $fileName = Str::uuid()->toString() . '.jpg';
        $relativePath = $relativeDirectory . '/' . $fileName;

        $tempPath = tempnam(sys_get_temp_dir(), 'proof_');

        if (!imagejpeg($canvas, $tempPath, 80)) {
            imagedestroy($sourceImage);
            imagedestroy($canvas);
            @unlink($tempPath);
            throw new RuntimeException('Gagal menyimpan file bukti pembayaran.');
        }

        imagedestroy($sourceImage);
        imagedestroy($canvas);

        Storage::disk('public')->put($relativePath, file_get_contents($tempPath));
        @unlink($tempPath);

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
            'mime_type' => 'image/jpeg',
            'file_size' => Storage::disk('public')->size($relativePath),
        ];
    }

    /**
     * Menghapus file gambar bukti pembayaran.
     *
     * @param  string|null $relativePath
     * @return void
     */
    private function deleteFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }

    /**
     * Membuat image resource dari file path.
     *
     * @param  string      $path
     * @param  string|null $mimeType
     * @return GdImage
     *
     * @throws \RuntimeException
     */
    private function createImageResource(string $path, ?string $mimeType): GdImage
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png'               => imagecreatefrompng($path),
            'image/gif'               => imagecreatefromgif($path),
            'image/webp'              => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : throw new RuntimeException('Format WEBP tidak didukung di server ini.'),
            default                   => throw new RuntimeException('Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.'),
        };
    }

    /**
     * Membangun path direktori relatif untuk penyimpanan file.
     *
     * Struktur: images/proof_payment/{module}/{invoice_type}/{invoice_number}/
     * Setiap segment dibersihkan dari karakter tidak valid (sanitizeSegment) agar
     * aman dijadikan folder.
     *
     * @param  string $moduleType
     * @param  string $invoiceType
     * @param  string $invoiceNumber
     * @return string
     */
    private function buildRelativeDirectory(string $moduleType, string $invoiceType, string $invoiceNumber): string
    {
        return 'images/proof_payment/'
            . $this->sanitizeSegment($moduleType) . '/'
            . $this->sanitizeSegment($invoiceType) . '/'
            . $this->sanitizeSegment($invoiceNumber);
    }

    /**
     * Membersihkan segment path dari karakter tidak valid.
     *
     * @param  string $value
     * @return string
     */
    private function sanitizeSegment(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '_', $value) ?? $value;

        return trim($value, '_') ?: 'default';
    }
}