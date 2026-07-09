<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceAlumunium;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentProof;
use App\Models\Report\SalesRecap;

class InvoiceCalculatorService
{
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

    public function calculateNetAmount(?float $totalAfterDiscount, ?float $totalAmount): int
    {
        return (int) max(0, $totalAmount ?? 0);
    }

    public function calculateRemainingAmount(int $grandTotal, int $discountAmount, int $dpAmount, int $totalPaidAmount): int
    {
        return (int) max(0, $grandTotal - $discountAmount - $dpAmount - $totalPaidAmount);
    }

    public function isFullyPaid(int $remaining): bool
    {
        return $remaining <= 0;
    }

    public function calculateProgressPercent(int $grandTotal, int $totalPaidAmount): int
    {
        return $grandTotal > 0 ? min(100, (int) round(($totalPaidAmount / $grandTotal) * 100)) : 0;
    }

    public function getDiscountAmount($invoice): float
    {
        return $this->calculateDiscountAmount(
            (float) ($invoice->total_amount ?? 0),
            $invoice->discount_type,
            $invoice->discount_value ? (float) $invoice->discount_value : null
        );
    }

    public function getDpAmount($invoice): float
    {
        return $this->calculateDpAmount(
            (float) ($invoice->total_amount ?? 0),
            $invoice->total_after_discount,
            $invoice->dp_type,
            $invoice->dp_value ? (float) $invoice->dp_value : null
        );
    }

    public function getNetAmount($invoice): int
    {
        return (int) max(0, $invoice->total_amount ?? 0);
    }

    public function getTotalPaidAmount($invoice): int
    {
        $paymentProofs = $invoice->relationLoaded('paymentProofs')
            ? $invoice->paymentProofs
            : $invoice->paymentProofs()->get();

        return (int) max(0, $paymentProofs->sum(fn($paymentProof) => (int) ($paymentProof->amount ?? 0)));
    }

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

    public function isFullyPaidForInvoice($invoice): bool
    {
        return $this->isFullyPaid($this->getRemainingAmount($invoice));
    }

    public function getProgressPercent($invoice): int
    {
        return $this->calculateProgressPercent(
            (int) ($invoice->total_amount ?? 0),
            $this->getTotalPaidAmount($invoice)
        );
    }

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

    public function getInvoiceNetAmount($invoice): int
    {
        if ($invoice instanceof SalesRecap) {
            return (int) max(0, $invoice->total_selling ?? 0);
        }
        return (int) max(0, $invoice->total_amount ?? 0);
    }

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

    public function getRemainingAmountForPayment(?int $grandTotal, ?int $paidAmount): int
    {
        return (int) max(0, ($grandTotal ?? 0) - ($paidAmount ?? 0));
    }

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
