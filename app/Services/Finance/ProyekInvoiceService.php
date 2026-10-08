<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\ProjectRecap;
use App\Services\InputNormalizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service layer untuk operasi bisnis Invoice Proyek.
 *
 * Menangani semua logika bisnis terkait Invoice Proyek termasuk:
 * - Query builder untuk listing
 * - Normalisasi item
 * - Perhitungan total
 * - Generasi nomor invoice
 */
class ProyekInvoiceService
{
    public function __construct(
        private InvoiceCalculatorService $calculator
    ) {}

    /**
     * Membangun query dasar untuk listing invoice proyek.
     *
     * Eager-loads relasi paymentProofs dan menerapkan filter search, month, year.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function baseQuery($request): Builder
    {
        return InvoiceProyek::query()->with(['paymentProofs', 'projectRecap.invoices'])
            ->where('created_by', auth()->id())
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = $request->search;
                $builder->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('recipient', 'like', "%{$search}%")
                        ->orWhere('project_description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('month'), fn($builder) => $builder->whereMonth('invoice_date', $request->month))
            ->when($request->filled('year'), fn($builder) => $builder->whereYear('invoice_date', $request->year))
            ->when($request->filled('status'), function ($builder) use ($request) {
                $this->applyStatusFilter($builder, $request->status);
            })
            ->orderByDesc('invoice_date');
    }

    /**
     * Menerapkan filter status pembayaran invoice proyek.
     *
     * Status "lunas" mengikuti isFullyPaid() dari kalkulator: sisa tagihan
     * = (grand_total - discount - dp - total_payment) <= 0, dengan
     * grand_total = total_amount + ppn. Karena sisa dipastikan non-negatif
     * (max(0, ...)), lunas berarti (grand_total - discount - dp - paid) <= 0.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $status  'lunas' atau 'belum'
     * @return void
     */
    private function applyStatusFilter($builder, string $status): void
    {
        $base = '(CASE WHEN total_after_discount IS NOT NULL AND total_after_discount <> total_amount
            THEN total_after_discount ELSE total_amount END)';
        $ppn = '(CASE WHEN ppn IS NOT NULL AND ppn > 0 THEN ROUND(' . $base . ' * ppn / 100) ELSE 0 END)';
        $discount = '(CASE WHEN discount_value IS NOT NULL AND discount_value > 0 THEN
            (CASE WHEN discount_type = \'percentage\' THEN ROUND(total_amount * discount_value / 100)
                  ELSE ROUND(discount_value) END) ELSE 0 END)';
        $dp = '(CASE WHEN dp_value IS NOT NULL AND dp_value > 0 THEN
            (CASE WHEN dp_type = \'percentage\' THEN ROUND(' . $base . ' * dp_value / 100)
                  ELSE ROUND(dp_value) END) ELSE 0 END)';
        $paid = '(SELECT COALESCE(SUM(pp.amount), 0) FROM payment_proofs pp
            WHERE pp.invoice_type = \'proyek\'
              AND pp.invoice_number = proyek_invoices.invoice_number)';
        $remaining = '((total_amount + ' . $ppn . ') - ' . $discount . ' - ' . $dp . ' - ' . $paid . ')';

        $builder->whereRaw($remaining . ($status === 'lunas' ? ' <= 0' : ' > 0'));
    }

    /**
     * Normalisasi item invoice dari input request.
     *
     * Mendukung dua format:
     * - Superadmin: keterangan, volume, satuan, harga
     * - Admin: deskripsi, harga, persentase
     *
     * Persentase item admin bersifat OPSIONAL: bila kosong disimpan sebagai
     * null (bukan 0) dan jumlah item = harga. Volume & satuan item superadmin
     * juga OPSIONAL: bila kosong disimpan sebagai null (bukan 0 / '') dan
     * item dianggap borongan (jumlah = harga). Setiap item juga diberi field
     * turunan "jumlah" (hasil InvoiceProyek::itemAmount) agar template
     * PDF/Excel dapat memakai nilai yang sama dengan perhitungan total.
     *
     * @param  mixed  $items  Item dari request (JSON string atau array)
     * @return array  Item yang sudah dinormalisasi
     */
    public function normalizeInvoiceItems($items): array
    {
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_map(function ($item) {
            $item = is_array($item) ? $item : [];
            $item['harga'] = InputNormalizer::normalizeCurrency($item['harga'] ?? 0);

            if (InvoiceProyek::isAdminItem($item)) {
                $item['persentase'] = self::normalizeOptionalPercentage($item['persentase'] ?? null);
            } else {
                // Volume kosong → null (borongan: Jumlah = Harga), bukan 0
                $item['volume'] = self::normalizeOptionalDecimal($item['volume'] ?? null);
                $satuan = trim((string) ($item['satuan'] ?? ''));
                $item['satuan'] = $satuan === '' ? null : $satuan;
            }

            $item['jumlah'] = (int) round(InvoiceProyek::itemAmount($item));

            return $item;
        }, $items));
    }

    /**
     * Normalisasi persentase item admin yang bersifat opsional.
     *
     * Nilai kosong (null / string kosong / spasi) dikembalikan sebagai null
     * agar dibedakan dari persentase 0. Koma desimal ("12,5") didukung.
     *
     * @param  mixed  $value
     * @return float|null
     */
    public static function normalizeOptionalPercentage($value): ?float
    {
        return self::normalizeOptionalDecimal($value);
    }

    /**
     * Normalisasi angka desimal opsional (persentase admin / volume superadmin).
     *
     * Nilai kosong (null / string kosong / spasi) dikembalikan sebagai null
     * agar dibedakan dari angka 0. Koma desimal ("12,5") didukung.
     *
     * @param  mixed  $value
     * @return float|null
     */
    public static function normalizeOptionalDecimal($value): ?float
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        return InputNormalizer::normalizeDecimal($value);
    }

    /**
     * Menghitung total_amount dari array items.
     *
     * Format superadmin: volume x harga; volume kosong → harga (borongan)
     * Format admin: harga x (persentase / 100); persentase kosong → harga
     *
     * @param  array  $items  Item yang sudah dinormalisasi
     * @return int  Total amount
     */
    public function calculateItemsTotal(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            $total += InvoiceProyek::itemAmount(is_array($item) ? $item : []);
        }

        return (int) round($total);
    }

    /**
     * Menghitung diskon dan DP menggunakan InvoiceCalculatorService.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $totalAmount
     * @return array{discountAmount: float, totalAfterDiscount: float, dpAmount: float}
     */
    public function calculateFromRequest($request, int $totalAmount): array
    {
        return $this->calculator->calculateFromRequest($request, $totalAmount);
    }

    /**
     * Menghasilkan nomor invoice berikutnya berdasarkan role user.
     *
     * Format admin: {nomor}/AKI/{bulan romawi}/{yyyy}
     *   - Nomor urut diketik user di form (lihat composeAdminInvoiceNumber);
     *     method ini hanya memberi saran nomor berikutnya (tahun berjalan).
     *   - Contoh: 060/AKI/VI/2026
     *
     * Format superadmin: {A}/{B}/PT.AKI/{yy}
     *   - Kedua angka diincrement secara terpisah
     *
     * @param  bool|null  $isAdmin  true untuk admin, null = cek auth()
     * @return string  Nomor invoice berikutnya
     */
    public function generateInvoiceNumber(?bool $isAdmin = null): string
    {
        if ($isAdmin === null) {
            $isAdmin = auth()->check() && auth()->user()->isAdmin();
        }

        if ($isAdmin) {
            return $this->generateAdminInvoiceNumber();
        }

        return $this->generateSuperadminInvoiceNumber();
    }

    /**
     * Menyusun nomor invoice admin dari nomor urut yang diketik user.
     *
     * Format: {nomor}/AKI/{bulan romawi}/{yyyy}, bulan & tahun diambil dari
     * tanggal invoice. Nomor urut dipakai apa adanya (leading zero tetap).
     * Contoh: ("060", "2026-06-15") → "060/AKI/VI/2026".
     *
     * @param  string  $sequence  Nomor urut (angka saja)
     * @param  mixed  $invoiceDate  Tanggal invoice (string/Carbon)
     * @return string
     */
    public function composeAdminInvoiceNumber(string $sequence, $invoiceDate): string
    {
        $date = Carbon::parse($invoiceDate);

        return trim($sequence) . '/AKI/' . self::toRoman((int) $date->format('n')) . '/' . $date->format('Y');
    }

    /**
     * Daftar nomor invoice admin format baru yang sudah dipakai.
     *
     * Dipakai form untuk validasi live (nomor tidak boleh dobel) dan saran
     * nomor berikutnya. Unik secara global karena invoice_number adalah
     * primary key tabel proyek_invoices.
     *
     * @return array<int, string>
     */
    public function getTakenAdminInvoiceNumbers(): array
    {
        return InvoiceProyek::query()
            ->where('invoice_number', 'like', '%/AKI/%')
            ->pluck('invoice_number')
            ->filter(fn ($number) => InvoiceProyek::parseAdminInvoiceNumber($number) !== null)
            ->values()
            ->all();
    }

    /**
     * Saran nomor invoice admin berikutnya (format baru) untuk tahun berjalan.
     *
     * Nomor urut = nomor terbesar pada tahun berjalan + 1 (panjang digit
     * minimal 3, contoh 061). Bila belum ada, mulai dari 060.
     *
     * @return string
     */
    private function generateAdminInvoiceNumber(): string
    {
        $year = date('Y');
        $maxSequence = null;
        $width = 3;

        foreach ($this->getTakenAdminInvoiceNumbers() as $number) {
            $parts = InvoiceProyek::parseAdminInvoiceNumber($number);

            if ($parts && $parts['year'] === $year && ($maxSequence === null || (int) $parts['sequence'] > $maxSequence)) {
                $maxSequence = (int) $parts['sequence'];
                $width = max(3, strlen($parts['sequence']));
            }
        }

        $next = $maxSequence === null ? 60 : $maxSequence + 1;

        return $this->composeAdminInvoiceNumber(str_pad((string) $next, $width, '0', STR_PAD_LEFT), now());
    }

    /**
     * Format nomor invoice untuk superadmin: {A}/{B}/PT.AKI/{yy}.
     *
     * @return string
     */
    private function generateSuperadminInvoiceNumber(): string
    {
        $year = date('y');

        $lastInvoice = InvoiceProyek::where('invoice_number', 'like', "%/PT.AKI/{$year}")
            ->orderByRaw('LENGTH(invoice_number) DESC')
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice && preg_match('/^(\d+)\/(\d+)\//', $lastInvoice->invoice_number, $matches)) {
            $nextA = (int) $matches[1] + 1;
            $nextB = (int) $matches[2] + 1;
        } else {
            $nextA = 1;
            $nextB = 6;
        }

        return "{$nextA}/{$nextB}/PT.AKI/{$year}";
    }

    /**
     * Konversi angka 1-3999 ke numerals Romawi.
     *
     * @param  int  $number
     * @return string
     */
    public static function toRoman(int $number): string
    {
        $map = [
            1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
            100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I',
        ];
        $result = '';
        foreach ($map as $value => $symbol) {
            while ($number >= $value) {
                $result .= $symbol;
                $number -= $value;
            }
        }
        return $result;
    }

    /**
     * Menentukan nomor invoice untuk invoice baru.
     *
     * - Admin: disusun dari nomor urut yang diketik user + bulan/tahun
     *   tanggal invoice (composeAdminInvoiceNumber). Input invoice_number
     *   mentah dari request diabaikan.
     * - Superadmin: tidak berubah — pakai invoice_number dari request bila
     *   ada, selain itu digenerate otomatis ({A}/{B}/PT.AKI/{yy}).
     *
     * @param  array  $data  Data invoice yang sudah divalidasi
     * @return string
     */
    public function resolveNewInvoiceNumber(array $data): string
    {
        $isAdmin = auth()->check() && auth()->user()->isAdmin();

        if ($isAdmin) {
            return $this->composeAdminInvoiceNumber((string) $data['invoice_number_seq'], $data['invoice_date']);
        }

        if (empty($data['invoice_number']) || str_contains($data['invoice_number'], 'Akan digenerate')) {
            return $this->generateInvoiceNumber(false);
        }

        return $data['invoice_number'];
    }

    /**
     * Menentukan nomor invoice setelah diedit.
     *
     * Nomor hanya bisa berubah untuk invoice admin berformat baru
     * ({nomor}/AKI/{bulan romawi}/{yyyy}): disusun ulang dari nomor urut
     * (invoice_number_seq) dan bulan/tahun tanggal invoice. Nomor format
     * lama dan nomor superadmin selalu dipertahankan.
     *
     * @param  \App\Models\Finance\InvoiceProyek  $invoice
     * @param  array  $data  Data invoice yang sudah divalidasi
     * @return string
     */
    public function resolveUpdatedInvoiceNumber(InvoiceProyek $invoice, array $data): string
    {
        $isAdmin = auth()->check() && auth()->user()->isAdmin();
        $currentParts = InvoiceProyek::parseAdminInvoiceNumber($invoice->invoice_number);

        if (! $isAdmin || $currentParts === null || empty($data['invoice_date'])) {
            return $invoice->invoice_number;
        }

        $sequence = isset($data['invoice_number_seq']) && $data['invoice_number_seq'] !== ''
            ? (string) $data['invoice_number_seq']
            : $currentParts['sequence'];

        return $this->composeAdminInvoiceNumber($sequence, $data['invoice_date']);
    }

    /**
     * Menyimpan invoice proyek baru ke database.
     *
     * @param  array  $data  Data invoice yang sudah divalidasi (invoice_number sudah final)
     * @param  array  $items  Item yang sudah dinormalisasi
     * @return \App\Models\Finance\InvoiceProyek
     */
    public function createInvoice(array $data, array $items): InvoiceProyek
    {
        $totalAmount = $this->calculateItemsTotal($items);
        $calculations = $this->calculateFromRequest(
            new Request($data),
            $totalAmount
        );

        unset($data['invoice_number_seq']);

        $data['items'] = $items;
        $data['total_amount'] = $totalAmount;
        $data['total_after_discount'] = $calculations['totalAfterDiscount'] > 0
            && $calculations['totalAfterDiscount'] != $totalAmount
            ? $calculations['totalAfterDiscount']
            : null;
        $data['dp_amount'] = $calculations['dpAmount'] > 0
            ? $calculations['dpAmount']
            : null;
        $data['created_by'] = auth()->id();
        $data['proyek'] = (auth()->check() && auth()->user()->role === 'superadmin') ? ($data['proyek'] ?? null) : null;
        $data['project_recap_id'] = $data['project_recap_id'] ?? null;

        return InvoiceProyek::create($data);
    }

    /**
     * Mengupdate invoice proyek yang sudah ada.
     *
     * Bila nomor invoice berubah (invoice admin format baru: nomor urut atau
     * bulan/tahun tanggal invoice berubah), primary key invoice diganti dan
     * seluruh data yang mereferensikan nomor invoice ikut disinkronkan dalam
     * satu transaksi (lihat renameInvoiceReferences).
     *
     * @param  \App\Models\Finance\InvoiceProyek  $invoice
     * @param  array  $data  Data invoice yang sudah divalidasi
     * @param  array  $items  Item yang sudah dinormalisasi
     * @return string  Nomor invoice setelah update
     */
    public function updateInvoice(InvoiceProyek $invoice, array $data, array $items): string
    {
        $totalAmount = $this->calculateItemsTotal($items);
        $calculations = $this->calculateFromRequest(
            new Request($data),
            $totalAmount
        );

        $oldNumber = $invoice->invoice_number;
        $oldRecapId = $invoice->project_recap_id;
        $newNumber = $this->resolveUpdatedInvoiceNumber($invoice, $data);

        DB::transaction(function () use ($data, $items, $totalAmount, $calculations, $oldNumber, $newNumber) {
            InvoiceProyek::where('invoice_number', $oldNumber)->update([
                'invoice_number' => $newNumber,
                'invoice_date' => $data['invoice_date'],
                'recipient' => $data['recipient'],
                'regarding' => $data['regarding'] ?? null,
                'project_description' => $data['project_description'] ?? null,
                'proyek' => (auth()->check() && auth()->user()->role === 'superadmin') ? ($data['proyek'] ?? null) : null,
                'location' => $data['location'] ?? null,
                'project_recap_id' => $data['project_recap_id'] ?? null,
                'items' => json_encode($items),
                'total_amount' => $totalAmount,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
                'total_after_discount' => $calculations['totalAfterDiscount'] > 0
                    && $calculations['totalAfterDiscount'] != $totalAmount
                    ? $calculations['totalAfterDiscount']
                    : null,
                'dp_type' => $data['dp_type'] ?? null,
                'dp_value' => $data['dp_value'] ?? null,
                'dp_amount' => $calculations['dpAmount'] > 0
                    ? $calculations['dpAmount']
                    : null,
                'ppn' => $data['ppn'] ?? null,
                'selected_payment_accounts' => json_encode($data['selected_payment_accounts'] ?? []),
                'signed_by_id' => $data['signed_by_id'] ?? null,
                'division_id' => $data['division_id'] ?? null,
            ]);

            if ($newNumber !== $oldNumber) {
                $this->renameInvoiceReferences($oldNumber, $newNumber);
            }
        });

        // Tautan Rekap Proyek berubah → pindahkan baris "Uang Masuk" Laporan
        // Keuangan Proyek dari pembayaran invoice ini ke rekap yang baru
        // (atau hapus bila tautan dilepas) tanpa menunggu halaman rekap dibuka.
        if (($data['project_recap_id'] ?? null) != $oldRecapId) {
            $updated = InvoiceProyek::find($newNumber);
            if ($updated) {
                app(\App\Services\Report\ProjectFinancialReportService::class)->syncInvoicePayments($updated);
            }
        }

        return $newNumber;
    }

    /**
     * Menyinkronkan data yang mereferensikan nomor invoice proyek saat
     * nomor invoice diganti.
     *
     * Tabel yang menyimpan proyek_invoices.invoice_number (tanpa FK):
     * - payment_proofs.invoice_number (invoice_type = 'proyek')
     * - kwintansi.invoice_number (invoice_type = 'proyek', atau null untuk
     *   kwitansi lama sebelum kolom invoice_type ada)
     *
     * Tautan Rekap Proyek (project_recap_id) & penawaran (quotation_number)
     * tersimpan di baris invoice itu sendiri sehingga tidak perlu disentuh.
     * Update memakai query builder (tanpa event model) agar observer bukti
     * pembayaran tidak memicu sinkronisasi ulang yang tidak perlu.
     *
     * @param  string  $oldNumber
     * @param  string  $newNumber
     * @return void
     */
    private function renameInvoiceReferences(string $oldNumber, string $newNumber): void
    {
        DB::table('payment_proofs')
            ->where('invoice_type', 'proyek')
            ->where('invoice_number', $oldNumber)
            ->update(['invoice_number' => $newNumber]);

        DB::table('kwintansi')
            ->where('invoice_number', $oldNumber)
            ->where(function ($query) {
                $query->where('invoice_type', 'proyek')->orWhereNull('invoice_type');
            })
            ->update(['invoice_number' => $newNumber]);
    }

    /**
     * Daftar Rekap Proyek milik user login untuk pilihan tautan invoice.
     *
     * Setiap opsi memuat data ringkasan yang dipakai panel "Rekap Proyek"
     * pada form invoice:
     * - total  : nilai proyek (Total RAB rekap)
     * - billed : total nilai tagihan seluruh invoice yang sudah ditautkan
     *            (InvoiceProyek::getBilledAmount, setelah diskon, sebelum PPN)
     * - project_name / location / recipient (dari RAB sumber) untuk prefill
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function getProjectRecapOptions(): Collection
    {
        $recaps = ProjectRecap::query()
            ->where('created_by', auth()->id())
            ->with('rab:rab_number,recipient')
            ->orderByDesc('created_at')
            ->get(['id', 'rab_number', 'project_name', 'location', 'total_rab', 'created_at']);

        if ($recaps->isEmpty()) {
            return collect();
        }

        $billedByRecap = InvoiceProyek::query()
            ->whereIn('project_recap_id', $recaps->pluck('id'))
            ->get(['invoice_number', 'project_recap_id', 'total_amount', 'discount_type', 'discount_value'])
            ->groupBy('project_recap_id')
            ->map(fn ($invoices) => (int) $invoices->sum(fn ($invoice) => $invoice->getBilledAmount()));

        return $recaps->map(fn (ProjectRecap $recap) => [
            'id' => $recap->id,
            'project_name' => $recap->project_name,
            'location' => $recap->location,
            'recipient' => $recap->rab?->recipient,
            'total' => $recap->getTotalAmount(),
            'billed' => (int) ($billedByRecap[$recap->id] ?? 0),
        ])->values();
    }

    /**
     * Menghapus beberapa invoice proyek sekaligus (bulk delete).
     *
     * PENTING (kenapa foreach, bukan mass delete):
     * - InvoiceProyek punya InvoiceProyekObserver. Event 'deleted' di observer
     *   membersihkan file bukti pembayaran terkait.
     * - foreach + $invoice->delete() memicu observer tersebut; mass delete tidak.
     *
     * @param  array  $ids  Daftar invoice_number yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function destroySelected(array $ids): int
    {
        $invoices = InvoiceProyek::whereIn('invoice_number', $ids)->get();

        foreach ($invoices as $invoice) {
            $invoice->delete();
        }

        return $invoices->count();
    }
}
