<?php

namespace App\Models\Finance;

use App\Models\Report\ProjectFinancialReport;
use App\Models\User;
use App\Services\Finance\RecapProyekService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model untuk Rekap Proyek (standalone).
 *
 * Merepresentasikan rekap proyek yang diinput manual oleh user.
 * Berbeda dari sebelumnya (yang mengambil data dari invoice proyek),
 * modul ini merupakan sub-modul mandiri dengan data:
 * - No (id auto-generate format RP-00001)
 * - Nama Proyek
 * - Total RAB
 * - File design (unggahan)
 *
 * Table: project_recaps
 * Primary Key: id (string, format: RP-00001)
 */
class ProjectRecap extends Model
{
    use HasFactory;

    protected $table = 'project_recaps';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'rab_number',
        'project_name',
        'location',
        'total_rab',
        'design_file',
        'design_file_name',
        'created_by',
    ];

    protected $casts = [
        'total_rab' => 'integer',
    ];

    /**
     * Boot method untuk auto-generate ID saat creating.
     *
     * ID di-generate oleh RecapProyekService::generateId() yang sudah
     * menggunakan lockForUpdate() untuk mencegah race condition.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = app(RecapProyekService::class)->generateId();
            }
        });
    }

    /**
     * Relasi ke user yang membuat rekap proyek ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * RAB sumber yang menautkan rekap proyek ini.
     */
    public function rab(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Administrasi\RAB::class, 'rab_number', 'rab_number');
    }

    /**
     * Bukti pembayaran yang menautkan ke rekap proyek ini.
     *
     * Bukti disimpan di tabel payment_proofs dengan invoice_type 'recap'
     * dan invoice_number berisi ID rekap (format RP-00001), sehingga
     * mekanisme upload bukti konsisten dengan invoice proyek.
     */
    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class, 'invoice_number', 'id')
            ->where('invoice_type', 'recap')
            ->orderByDesc('created_at');
    }

    /**
     * Invoice Proyek yang ditautkan ke rekap proyek ini.
     *
     * Tautan bersifat opsional dan dipilih user saat membuat/mengedit
     * invoice proyek (kolom proyek_invoices.project_recap_id). Pembayaran
     * pada invoice tertaut ikut dihitung sebagai Terbayar rekap
     * (getInvoicePaidAmount) dan sebagai uang masuk Laporan Keuangan Proyek.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceProyek::class, 'project_recap_id', 'id')
            ->orderBy('invoice_date')
            ->orderBy('created_at');
    }

    /**
     * Laporan Keuangan Proyek yang menautkan ke rekap proyek ini (relasi 1:1).
     *
     * Laporan dibuat otomatis saat dibuka pertama kali dari tombol
     * "Laporan Keuangan" di tabel Rekap Proyek.
     */
    public function financialReport(): HasOne
    {
        return $this->hasOne(ProjectFinancialReport::class, 'project_recap_id', 'id');
    }

    /**
     * Apakah rekap proyek memiliki file design.
     */
    public function hasDesignFile(): bool
    {
        return ! empty($this->design_file);
    }

    // ─── Perhitungan Finansial ─────────────────────────────────────────────

    /**
     * Total nilai rekap (Total RAB).
     */
    public function getTotalAmount(): int
    {
        return (int) ($this->total_rab ?? 0);
    }

    /**
     * Total nilai yang sudah ditagih lewat invoice proyek yang ditautkan.
     *
     * Nilai tiap invoice = total item setelah diskon, sebelum PPN
     * (InvoiceProyek::getBilledAmount) agar sebanding dengan Total RAB.
     *
     * @param  string|null  $excludeInvoiceNumber  Invoice yang tidak ikut dihitung (mis. invoice yang sedang diedit)
     * @return int
     */
    public function getInvoicedAmount(?string $excludeInvoiceNumber = null): int
    {
        $invoices = $this->relationLoaded('invoices')
            ? $this->invoices
            : $this->invoices()->get();

        return (int) $invoices
            ->reject(fn ($invoice) => $excludeInvoiceNumber !== null && $invoice->invoice_number === $excludeInvoiceNumber)
            ->sum(fn ($invoice) => $invoice->getBilledAmount());
    }

    /**
     * Sisa nilai proyek yang belum ditagih: Total RAB - total invoice tertaut.
     *
     * Bisa bernilai negatif bila total invoice melebihi nilai proyek
     * (ditampilkan sebagai peringatan "melebihi nilai proyek").
     *
     * @return int
     */
    public function getUninvoicedAmount(): int
    {
        return $this->getTotalAmount() - $this->getInvoicedAmount();
    }

    /**
     * Uang masuk (DP) yang diambil dari RAB sumber yang ditautkan.
     */
    public function getDpAmount(): int
    {
        return (int) ($this->rab?->incoming_payment ?? 0);
    }

    /**
     * Rekap proyek tidak memiliki diskon.
     */
    public function getDiscountAmount(): int
    {
        return 0;
    }

    /**
     * Rekap proyek tidak dikenakan PPN.
     */
    public function getPpnAmount(): int
    {
        return 0;
    }

    /**
     * Item "uang masuk" pada Laporan Keuangan Proyek yang dihitung sebagai
     * pembayaran rekap.
     *
     * Pembayaran rekap proyek tidak hanya berasal dari bukti pembayaran
     * (payment_proofs), tapi juga dari baris "Bon" ber-kategori INCOME pada
     * Laporan Keuangan Proyek. Identifikasi dilakukan lewat nilai
     * income_amount > 0 (bukan lewat kode kategori) sehingga kategori uang
     * masuk dengan kode apa pun tetap terhitung, tidak hanya UANG_MASUK.
     *
     * Item yang berasal dari bukti pembayaran (payment_proof_id terisi) —
     * baik bukti rekap maupun bukti invoice tertaut — dikecualikan karena
     * sudah dihitung lewat getDirectPaidAmount() / getInvoicePaidAmount()
     * dari payment_proofs — menghindari hitung ganda.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Report\ProjectFinancialReportItem>
     */
    public function getIncomePayments()
    {
        $report = $this->financialReport;

        if (! $report) {
            return collect();
        }

        $items = $report->relationLoaded('items')
            ? $report->items
            : $report->items()->get();

        return $items
            ->where('income_amount', '>', 0)
            ->whereNull('payment_proof_id')
            ->where('is_informational', false)
            ->values();
    }

    /**
     * Total bukti pembayaran yang diupload langsung ke rekap ini
     * (payment_proofs dengan invoice_type 'recap').
     *
     * @param  int|null  $excludePaymentProofId  Bukti yang tidak ikut dihitung (mis. bukti yang sedang diedit)
     * @return int
     */
    public function getDirectPaidAmount(?int $excludePaymentProofId = null): int
    {
        $paymentProofs = $this->relationLoaded('paymentProofs')
            ? $this->paymentProofs
            : $this->paymentProofs()->get();

        return (int) max(0, $paymentProofs
            ->reject(fn ($proof) => $excludePaymentProofId !== null && (int) $proof->id === $excludePaymentProofId)
            ->sum(fn ($proof) => (int) ($proof->amount ?? 0)));
    }

    /**
     * Bagian pembayaran invoice yang dihitung ke nilai proyek (tanpa PPN).
     *
     * Total RAB rekap & nilai tagihan invoice (getBilledAmount) sama-sama
     * SEBELUM PPN, sedangkan bukti pembayaran invoice dibayar termasuk PPN.
     * Agar sebanding dengan Total RAB, setiap pembayaran invoice dihitung
     * proporsional: nominal × nilai tagihan / (nilai tagihan + PPN).
     * Contoh: tagihan 200.000 + PPN 22.000, dibayar 222.000 → 200.000.
     *
     * Rumus yang sama dipakai untuk baris "uang masuk" Laporan Keuangan
     * Proyek (ProjectFinancialReportService) sehingga Terbayar rekap dan
     * pemasukan laporan selalu sama.
     *
     * @param  \App\Models\Finance\InvoiceProyek  $invoice  Invoice tempat bukti pembayaran diupload
     * @param  int  $amount  Nominal bukti pembayaran (termasuk PPN)
     * @return int
     */
    public static function invoicePaymentProjectPortion(InvoiceProyek $invoice, int $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $ppnAmount = (int) $invoice->getPpnAmount();
        $billedAmount = (int) $invoice->getBilledAmount();

        if ($ppnAmount <= 0 || $billedAmount <= 0) {
            return $amount;
        }

        return (int) round($amount * $billedAmount / ($billedAmount + $ppnAmount));
    }

    /**
     * Daftar pembayaran (bukti pembayaran) pada invoice proyek yang
     * ditautkan ke rekap ini.
     *
     * Setiap baris berisi bukti, invoice-nya, nominal yang dibayar (termasuk
     * PPN), bagian yang dihitung ke rekap (tanpa PPN) dan porsi PPN-nya.
     * DP invoice tidak ikut dihitung: uang masuk/DP rekap diambil dari RAB
     * (getDpAmount) sehingga DP tidak terhitung dua kali.
     *
     * @return \Illuminate\Support\Collection<int, object{proof: \App\Models\Finance\PaymentProof, invoice: \App\Models\Finance\InvoiceProyek, amount: int, project_amount: int, ppn_amount: int}>
     */
    public function getInvoicePayments()
    {
        $this->loadMissing('invoices.paymentProofs');

        return $this->invoices
            ->flatMap(fn (InvoiceProyek $invoice) => $invoice->paymentProofs->map(function ($proof) use ($invoice) {
                $amount = (int) ($proof->amount ?? 0);
                $projectAmount = static::invoicePaymentProjectPortion($invoice, $amount);

                return (object) [
                    'proof' => $proof,
                    'invoice' => $invoice,
                    'amount' => $amount,
                    'project_amount' => $projectAmount,
                    'ppn_amount' => max(0, $amount - $projectAmount),
                ];
            }))
            ->values();
    }

    /**
     * Total pembayaran lewat invoice tertaut yang dihitung ke rekap
     * (tanpa PPN, lihat invoicePaymentProjectPortion()).
     *
     * @return int
     */
    public function getInvoicePaidAmount(): int
    {
        return (int) $this->getInvoicePayments()->sum('project_amount');
    }

    /**
     * Total pembayaran yang sudah masuk.
     *
     * Terdiri dari tiga sumber (masing-masing bukti/baris hanya dihitung
     * sekali):
     * - Bukti pembayaran yang diupload langsung ke rekap (invoice_type 'recap').
     * - Bukti pembayaran pada Invoice Proyek yang ditautkan ke rekap
     *   (proyek_invoices.project_recap_id), dihitung tanpa PPN.
     * - Baris "uang masuk" (kategori INCOME) pada Laporan Keuangan Proyek
     *   yang diinput manual (tidak berasal dari bukti pembayaran).
     *
     * @return int Total nominal yang sudah dibayar
     */
    public function getTotalPaidAmount(): int
    {
        $incomeTotal = (int) $this->getIncomePayments()->sum('income_amount');

        return $this->getDirectPaidAmount() + $this->getInvoicePaidAmount() + $incomeTotal;
    }

    /**
     * Sisa pembayaran: Total RAB - DP - total terbayar (bukti rekap +
     * pembayaran invoice tertaut tanpa PPN + uang masuk manual laporan).
     */
    public function getRemainingAmount(): int
    {
        return (int) max(0, $this->getTotalAmount() - $this->getDpAmount() - $this->getTotalPaidAmount());
    }

    /**
     * Apakah rekap proyek sudah lunas.
     */
    public function isFullyPaid(): bool
    {
        return $this->getRemainingAmount() <= 0;
    }

    /**
     * Progress pembayaran dalam persentase: (DP + terbayar) / Total RAB.
     *
     * @return int Persentase 0-100
     */
    public function getProgressPercent(): int
    {
        $total = $this->getTotalAmount();

        if ($total <= 0) {
            return 0;
        }

        return min(100, (int) round((($this->getDpAmount() + $this->getTotalPaidAmount()) / $total) * 100));
    }
}
