<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceAlumunium;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentProof;
use App\Models\Report\SalesRecap;

/**
 * Service untuk perhitungan finansial invoice.
 *
 * Menyediakan method-method kalkulasi yang digunakan oleh berbagai model invoice
 * (InvoiceProyek, InvoiceAlumunium, SalesRecap) untuk menghitung:
 * - Discount amount
 * - DP amount
 * - Net amount
 * - Remaining amount
 * - Total paid amount
 * - Progress percentage
 * - Payment status
 */
class InvoiceCalculatorService
{
    /**
     * Menghitung jumlah diskon berdasarkan tipe dan nilai.
     *
     * @param  float  $totalAmount  Total amount sebelum diskon
     * @param  string|null  $discountType  Tipe diskon: 'percentage' atau 'amount'
     * @param  float|null  $discountValue  Nilai diskon
     * @return float  Jumlah diskon
     */
    public function calculateDiscountAmount(float $totalAmount, ?string $discountType, ?float $discountValue): float
    {
        if (!$discountValue || $discountValue <= 0) {
            return 0;
        }
        if ($discountType === 'percentage') {
            return round(($totalAmount * $discountValue) / 100);
        }
        return round($discountValue);
    }

    /**
     * Menghitung jumlah DP berdasarkan tipe dan nilai.
     *
     * Logika pemilihan base amount:
     * - Jika $baseAmount eksplisit diberikan, itu yang dipakai.
     * - Jika tidak, pakai $totalAfterDiscount BUKAN $totalAmount hanya saat
     *   totalAfterDiscount ada dan nilainya berbeda dari totalAmount.
     *   Maksudnya: DP dihitung dari total SETELAH diskon (lebih umum), tapi jika
     *   tidak ada diskon, fallback ke totalAmount.
     * - DP percentage = base × dpValue / 100 (dibulatkan). DP amount = nilai mentah.
     *
     * @param  float  $totalAmount  Total amount asli
     * @param  float|int|null  $totalAfterDiscount  Total setelah diskon
     * @param  string|null  $dpType  Tipe DP: 'percentage' atau 'amount'
     * @param  float|null  $dpValue  Nilai DP
     * @param  float|null  $baseAmount  Base amount untuk perhitungan (opsional)
     * @return float  Jumlah DP
     */
    public function calculateDpAmount(float $totalAmount, float|int|null $totalAfterDiscount, ?string $dpType, ?float $dpValue, ?float $baseAmount = null): float
    {
        if (!$dpValue || $dpValue <= 0) {
            return 0;
        }
        $base = $baseAmount ?? (($totalAfterDiscount !== null && (float) $totalAfterDiscount !== $totalAmount) ? (float) $totalAfterDiscount : $totalAmount);
        if ($dpType === 'percentage') {
            return round(($base * $dpValue) / 100);
        }
        return round($dpValue);
    }

    /**
     * Menghitung net amount (grand total).
     *
     * @param  float|null  $totalAfterDiscount  Total setelah diskon (tidak digunakan, dipertahankan untuk kompatibilitas)
     * @param  float|null  $totalAmount  Total amount
     * @return int  Net amount
     */
    public function calculateNetAmount(?float $totalAfterDiscount, ?float $totalAmount): int
    {
        return (int) max(0, $totalAmount ?? 0);
    }

    /**
     * Menghitung sisa tagihan.
     *
     * Rumus: Grand Total - Diskon - DP - Total yang sudah dibayar.
     * max(0, ...) memastikan hasil tidak pernah negatif (anti overpaid/hutang).
     *
     * @param  int  $grandTotal  Grand total
     * @param  int  $discountAmount  Jumlah diskon
     * @param  int  $dpAmount  Jumlah DP
     * @param  int  $totalPaidAmount  Total yang sudah dibayar
     * @return int  Sisa tagihan (tidak pernah negatif)
     */
    public function calculateRemainingAmount(int $grandTotal, int $discountAmount, int $dpAmount, int $totalPaidAmount): int
    {
        return (int) max(0, $grandTotal - $discountAmount - $dpAmount - $totalPaidAmount);
    }

    /**
     * Mengecek apakah sisa tagihan sudah lunas.
     *
     * @param  int  $remaining  Sisa tagihan
     * @return bool
     */
    public function isFullyPaid(int $remaining): bool
    {
        return $remaining <= 0;
    }

    /**
     * Menghitung progress pembayaran dalam persentase.
     *
     * Rumus: (totalPaid / grandTotal) × 100, dibatasi maksimal 100%.
     * Jika grandTotal 0 (data belum lengkap), hasil 0.
     *
     * @param  int  $grandTotal  Grand total
     * @param  int  $totalPaidAmount  Total yang sudah dibayar
     * @return int  Persentase 0-100
     */
    public function calculateProgressPercent(int $grandTotal, int $totalPaidAmount): int
    {
        return $grandTotal > 0 ? min(100, (int) round(($totalPaidAmount / $grandTotal) * 100)) : 0;
    }

    /**
     * Mendapatkan jumlah diskon dari model invoice.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @return float
     */
    public function getDiscountAmount($invoice): float
    {
        return $this->calculateDiscountAmount(
            (float) ($invoice->total_amount ?? 0),
            $invoice->discount_type,
            $invoice->discount_value ? (float) $invoice->discount_value : null
        );
    }

    /**
     * Mendapatkan jumlah DP dari model invoice.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @return float
     */
    public function getDpAmount($invoice): float
    {
        return $this->calculateDpAmount(
            (float) ($invoice->total_amount ?? 0),
            $invoice->total_after_discount,
            $invoice->dp_type,
            $invoice->dp_value ? (float) $invoice->dp_value : null
        );
    }

    /**
     * Mendapatkan net amount dari model invoice.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @return int
     */
    public function getNetAmount($invoice): int
    {
        return (int) max(0, $invoice->total_amount ?? 0);
    }

    /**
     * Mendapatkan total pembayaran yang sudah masuk dari relasi paymentProofs.
     *
     * Logika:
     * - Jika relasi 'paymentProofs' sudah di-eager-load (relationLoaded), pakai hasilnya
     *   langsung (tidak query ulang). Ini menghemat query pada listing.
     * - Jika belum, lazy-load via paymentProofs()->get().
     * - Total = jumlah semua kolom 'amount'. max(0, ...) mencegah nilai negatif.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @return int  Total nominal pembayaran
     */
    public function getTotalPaidAmount($invoice): int
    {
        $paymentProofs = $invoice->relationLoaded('paymentProofs')
            ? $invoice->paymentProofs
            : $invoice->paymentProofs()->get();

        return (int) max(0, $paymentProofs->sum(fn($paymentProof) => (int) ($paymentProof->amount ?? 0)));
    }

    /**
     * Mendapatkan sisa tagihan dari model invoice.
     *
     * Logika: SalesRecap dihitung beda dari invoice biasa.
     * - SalesRecap: sisa = total_selling - total bayar (tidak ada diskon/DP).
     * - Invoice lain: sisa = total - diskon - DP - total bayar.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @return int  Sisa tagihan
     */
    public function getRemainingAmount($invoice): int
    {
        if ($invoice instanceof SalesRecap) {
            return (int) max(0, ($invoice->total_selling ?? 0) - $this->getTotalPaidAmount($invoice));
        }

        return $this->calculateRemainingAmount(
            (int) ($invoice->total_amount ?? 0),
            (int) $this->getDiscountAmount($invoice),
            (int) $this->getDpAmount($invoice),
            $this->getTotalPaidAmount($invoice)
        );
    }

    /**
     * Mengecek apakah invoice sudah lunas.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @return bool
     */
    public function isFullyPaidForInvoice($invoice): bool
    {
        return $this->isFullyPaid($this->getRemainingAmount($invoice));
    }

    /**
     * Mendapatkan progress pembayaran dari model invoice.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @return int  Persentase 0-100
     */
    public function getProgressPercent($invoice): int
    {
        return $this->calculateProgressPercent(
            (int) ($invoice->total_amount ?? 0),
            $this->getTotalPaidAmount($invoice)
        );
    }

    /**
     * Menghitung diskon dan DP dari request data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  float  $totalAmount  Total amount sebelum diskon
     * @return array{discountAmount: float, totalAfterDiscount: float, dpAmount: float}
     */
    public function calculateFromRequest($request, float $totalAmount): array
    {
        $discountAmount = 0;
        $totalAfterDiscount = $totalAmount;

        if ($request->filled('discount_value') && $request->discount_value > 0) {
            $discountAmount = $this->calculateDiscountAmount(
                $totalAmount,
                $request->discount_type,
                (float) $request->discount_value
            );
            $totalAfterDiscount = $totalAmount - $discountAmount;
        }

        $dpAmount = 0;
        if ($request->filled('dp_value') && $request->dp_value > 0) {
            $dpAmount = $this->calculateDpAmount(
                $totalAmount,
                $totalAfterDiscount,
                $request->dp_type,
                (float) $request->dp_value
            );
        }

        return [
            'discountAmount' => $discountAmount,
            'totalAfterDiscount' => $totalAfterDiscount,
            'dpAmount' => $dpAmount,
        ];
    }

    /**
     * Membangun ringkasan total untuk invoice proyek.
     *
     * @param  \Illuminate\Support\Collection  $invoices  Koleksi invoice proyek
     * @return object  Objek berisi: invoice_count, total_invoice, total_paid, total_remaining, paid_count, unpaid_count
     */
    public function buildProyekTotals($invoices): object
    {
        return (object) [
            'invoice_count' => $invoices->count(),
            'total_invoice' => $invoices->sum(fn($i) => (int) ($i->total_amount ?? 0)),
            'total_paid' => $invoices->sum(fn($i) => $this->getTotalPaidAmount($i)),
            'total_remaining' => $invoices->sum(fn($i) => $this->getRemainingAmount($i)),
            'paid_count' => $invoices->filter(fn($i) => $this->isFullyPaidForInvoice($i))->count(),
            'unpaid_count' => $invoices->filter(fn($i) => !$this->isFullyPaidForInvoice($i))->count(),
        ];
    }

    /**
     * Membangun ringkasan total untuk invoice alumunium.
     *
     * @param  \Illuminate\Support\Collection  $invoices  Koleksi invoice alumunium
     * @return object  Objek berisi: total_invoice, invoice_count, paid_count, paid_amount, remaining_amount
     */
    public function buildAlumuniumTotals($invoices): object
    {
        return (object) [
            'total_invoice' => $invoices->sum(fn($i) => (int) ($i->total_amount ?? 0)),
            'invoice_count' => $invoices->count(),
            'paid_count' => $invoices->filter(fn($i) => $this->isFullyPaidForInvoice($i))->count(),
            'paid_amount' => $invoices->sum(fn($i) => $this->getTotalPaidAmount($i)),
            'remaining_amount' => $invoices->sum(fn($i) => $this->getRemainingAmount($i)),
        ];
    }

    /**
     * Mendapatkan net amount dari invoice (atau total_selling untuk SalesRecap).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @return int
     */
    public function getInvoiceNetAmount($invoice): int
    {
        if ($invoice instanceof SalesRecap) {
            return (int) max(0, $invoice->total_selling ?? 0);
        }
        return (int) max(0, $invoice->total_amount ?? 0);
    }

    /**
     * Mendapatkan total pembayaran untuk invoice, dengan opsi exclude payment proof tertentu.
     *
     * Logika:
     * - Jika tidak perlu exclude dan bukan SalesRecap, langsung pakai getTotalPaidAmount()
     *   (menggunakan relasi eager-load — lebih cepat).
     * - SalesRecap & invoice lain dihitung dengan query SUM langsung ke tabel payment_proofs,
     *   karena butuh filter invoice_type + invoice_number (dan opsi exclude id).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @param  int|null  $excludePaymentProofId  ID PaymentProof yang dikecualikan
     * @return int  Total nominal pembayaran
     */
    public function getPaidAmountForInvoice($invoice, ?int $excludePaymentProofId = null): int
    {
        if ($excludePaymentProofId === null && !($invoice instanceof SalesRecap)) {
            return $this->getTotalPaidAmount($invoice);
        }

        if ($invoice instanceof SalesRecap) {
            $query = PaymentProof::query()
                ->where('invoice_type', 'rekap_penjualan')
                ->where('invoice_number', $invoice->id_sales_recap);
            if ($excludePaymentProofId) {
                $query->where('id', '!=', $excludePaymentProofId);
            }
            return (int) $query->sum('amount');
        }

        $query = PaymentProof::query()
            ->where('invoice_type', $invoice instanceof InvoiceAlumunium ? 'alumunium' : 'proyek')
            ->where('invoice_number', $invoice->invoice_number);
        if ($excludePaymentProofId) {
            $query->where('id', '!=', $excludePaymentProofId);
        }
        return (int) $query->sum('amount');
    }

    /**
     * Menghitung sisa pembayaran untuk keperluan pembayaran.
     *
     * @param  int|null  $grandTotal  Grand total
     * @param  int|null  $paidAmount  Total yang sudah dibayar
     * @return int  Sisa pembayaran
     */
    public function getRemainingAmountForPayment(?int $grandTotal, ?int $paidAmount): int
    {
        return (int) max(0, ($grandTotal ?? 0) - ($paidAmount ?? 0));
    }

    /**
     * Membangun data opsi invoice untuk keperluan pemilihan invoice.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $invoice
     * @param  string  $moduleType  Tipe modul
     * @param  string  $invoiceType  Tipe invoice
     * @return array  Data: paid_amount, net_amount, remaining_amount, is_fully_paid
     */
    public function buildInvoiceOptionData($invoice, string $moduleType, string $invoiceType): array
    {
        $paidAmount = $this->getPaidAmountForInvoice($invoice);

        if ($invoice instanceof SalesRecap) {
            $grandTotal = $this->getInvoiceNetAmount($invoice);
            $remainingAmount = $this->getRemainingAmountForPayment($grandTotal, $paidAmount);
        } else {
            $grandTotal = (int) ($invoice->total_amount ?? 0);
            $remainingAmount = $this->getRemainingAmount($invoice);
        }

        return [
            'paid_amount' => $paidAmount,
            'net_amount' => $grandTotal,
            'remaining_amount' => $remainingAmount,
            'is_fully_paid' => $remainingAmount <= 0,
        ];
    }
}
