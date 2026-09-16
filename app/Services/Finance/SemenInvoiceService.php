<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceSemen;
use App\Models\Inventory\Cement;
use App\Models\Inventory\CementDeliveryOrder;
use App\Services\Administrasi\NotaService;
use App\Services\InputNormalizer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service layer untuk operasi bisnis Invoice Semen.
 *
 * Menangani query listing, normalisasi proyek & item, perhitungan total,
 * generasi nomor invoice, serta operasi simpan/ubah/hapus massal.
 */
class SemenInvoiceService
{
    /**
     * Membangun query dasar untuk listing invoice semen.
     *
     * Eager-loads relasi dan menerapkan filter search, month, year.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function baseQuery($request): Builder
    {
        return InvoiceSemen::query()
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = $request->search;
                $builder->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('projects', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('month'), fn($builder) => $builder->whereMonth('invoice_date', $request->month))
            ->when($request->filled('year'), fn($builder) => $builder->whereYear('invoice_date', $request->year))
            ->orderByDesc('invoice_date')
            ->orderByDesc('invoice_number');
    }

    /**
     * Normalisasi input proyek dari request.
     *
     * Format masukan: array proyek, masing-masing berisi:
     * - nama_proyek (string)
     * - pengurus_proyek (string|null)
     * - payment_account_id (int|null)
     * - items (array baris): {no, data_no, tanggal, nama_barang, qty, harga, jumlah}
     *
     * Output dinormalisasi: nomor urut (no) di-reset per proyek mulai 1,
     * dan jumlah dihitung ulang = qty × harga.
     *
     * @param  mixed  $projects  Input proyek (JSON string atau array)
     * @return array  Proyek yang sudah dinormalisasi
     */
    public function normalizeProjects($projects): array
    {
        if (is_string($projects)) {
            $projects = json_decode($projects, true);
        }

        if (!is_array($projects)) {
            return [];
        }

        $normalized = [];

        foreach ($projects as $project) {
            if (!is_array($project)) {
                continue;
            }

            $namaProyek = trim((string) ($project['nama_proyek'] ?? ''));
            if ($namaProyek === '') {
                continue;
            }

            $items = $this->normalizeItems($project['items'] ?? []);

            $normalized[] = [
                'nama_proyek' => $namaProyek,
                'pengurus_proyek' => trim((string) ($project['pengurus_proyek'] ?? '')) ?: null,
                'payment_account_id' => !empty($project['payment_account_id'])
                    ? (int) $project['payment_account_id']
                    : null,
                'items' => $items,
            ];
        }

        return array_values($normalized);
    }

    /**
     * Normalisasi baris item dalam satu proyek.
     *
     * Nomor urut (no) dibuat ulang secara berurutan mulai 1. Kolom `harga`
     * dinormalisasi dari string Rupiah ("Rp 1.000") menjadi integer.
     *
     * @param  mixed  $items  Item dari request
     * @return array  Item yang sudah dinormalisasi dan diurutan ulang
     */
    public function normalizeItems($items): array
    {
        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        $no = 1;

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $qty = (int) ($item['qty'] ?? 0);
            if ($qty < 1) {
                continue;
            }

            $harga = InputNormalizer::normalizeCurrency($item['harga'] ?? 0);

            $normalized[] = [
                'no' => $no++,
                'data_no' => $item['data_no'] ?? null,
                'tanggal' => $item['tanggal'] ?? null,
                'nama_barang' => trim((string) ($item['nama_barang'] ?? '')) ?: 'SEMEN',
                'qty' => $qty,
                'harga' => $harga,
                'jumlah' => $qty * $harga,
            ];
        }

        return $normalized;
    }

    /**
     * Menghitung total nilai seluruh proyek (semua item).
     *
     * @param  array  $projects  Proyek yang sudah dinormalisasi
     * @return int  Total seluruh nominal
     */
    public function calculateTotal(array $projects): int
    {
        $total = 0;

        foreach ($projects as $project) {
            foreach ($project['items'] ?? [] as $item) {
                $total += (int) ($item['jumlah'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Menghasilkan nomor invoice semen berikutnya.
     *
     * Format mengikuti pola invoice lain di sistem: {A}/{B}/PT.AKI/{yy}.
     * Basis counter diambil dari tabel semen_invoices sendiri agar nomor
     * bersifat unik per modul.
     *
     * @return string  Nomor invoice berikutnya
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('y');

        $lastInvoice = InvoiceSemen::where('invoice_number', 'like', "%/PT.AKI/{$year}")
            ->orderByRaw('LENGTH(invoice_number) DESC')
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice && preg_match('/^(\d+)\/(\d+)\//', $lastInvoice->invoice_number, $matches)) {
            $nextA = (int) $matches[1] + 1;
            $nextB = (int) $matches[2] + 1;
        } else {
            $nextA = 1;
            $nextB = 1;
        }

        return "{$nextA}/{$nextB}/PT.AKI/{$year}";
    }

    /**
     * Menyimpan invoice semen baru.
     *
     * @param  array  $data      Data invoice (sudah divalidasi request)
     * @param  array  $projects  Proyek yang sudah dinormalisasi
     * @return \App\Models\Finance\InvoiceSemen
     */
    public function createInvoice(array $data, array $projects): InvoiceSemen
    {
        return InvoiceSemen::create([
            'invoice_number' => $data['invoice_number'],
            'do_no' => $data['do_no'] ?? null,
            'invoice_date' => $data['invoice_date'],
            'projects' => $projects,
            'total_amount' => $this->calculateTotal($projects),
            'note' => $data['note'] ?? null,
            'signed_by_id' => $data['signed_by_id'] ?? null,
        ]);
    }

    /**
     * Membuat Invoice Semen langsung dari DO Semen (alur superadmin).
     *
     * Data semen terpilih dikelompokkan per nama proyek menjadi satu proyek
     * dalam invoice (berisi satu atau lebih baris SEMEN). Untuk tiap proyek
     * dibuatkan satu nota proyek otomatis, lalu baris Data Semen ditandai
     * `invoice_number` agar tidak diinvois dua kali.
     *
     * @param  CementDeliveryOrder  $deliveryOrder  DO Semen sumber.
     * @param  array                $data           {invoice_date, note, signed_by_id, payment_account_id}
     * @param  array<int, Cement>   $cements        Data semen yang dipilih.
     * @return \App\Models\Finance\InvoiceSemen
     */
    public function createFromDo(CementDeliveryOrder $deliveryOrder, array $data, array $cements): InvoiceSemen
    {
        $invoice = $this->createInvoice([
            'invoice_number' => $this->generateInvoiceNumber(),
            'do_no' => $deliveryOrder->no,
            'invoice_date' => $data['invoice_date'],
            'note' => $data['note'] ?? null,
            'signed_by_id' => $data['signed_by_id'] ?? null,
        ], $this->buildProjectsFromCements($data, $cements));

        // Buat nota proyek otomatis per proyek dalam invoice.
        $notaService = app(NotaService::class);
        foreach ($invoice->getProjectsCollectionAttribute() as $project) {
            $notaService->createProyekNotaForInvoice(
                $invoice->do_no,
                $invoice->invoice_number,
                $project,
                $data
            );
        }

        // Tandai Data Semen sudah masuk invoice (cegah double).
        $cementNo = collect($cements)->pluck('no')->all();
        if (!empty($cementNo)) {
            Cement::whereIn('no', $cementNo)->update(['invoice_number' => $invoice->invoice_number]);
        }

        return $invoice;
    }

    /**
     * Menyusun daftar proyek dari Data Semen terpilih.
     *
     * Data semen dikelompokkan berdasarkan nama_proyek. Tiap kelompok menjadi
     * satu proyek dengan beberapa baris item SEMEN.
     *
     * @param  array               $data     Data invoice (untuk rekening).
     * @param  array<int, Cement>  $cements  Data semen terpilih.
     * @return array<int, array<string, mixed>>
     */
    private function buildProjectsFromCements(array $data, array $cements): array
    {
        $projects = [];

        foreach (collect($cements)->groupBy('nama_proyek') as $namaProyek => $group) {
            $items = [];
            $no = 1;

            foreach ($group as $cement) {
                $qty = (int) $cement->jumlah;
                $harga = (int) $cement->harga;

                if ($qty < 1 || $harga < 0) {
                    continue;
                }

                $items[] = [
                    'no' => $no++,
                    'data_no' => $cement->no,
                    'tanggal' => $cement->tanggal?->format('Y-m-d'),
                    'nama_barang' => 'SEMEN',
                    'qty' => $qty,
                    'harga' => $harga,
                    'jumlah' => $qty * $harga,
                ];
            }

            if (empty($items)) {
                continue;
            }

            $pengurus = trim((string) ($group->first()->name ?? ''));
            $paymentAccountId = !empty($data['payment_account_id'])
                ? (int) $data['payment_account_id']
                : null;

            $projects[] = [
                'nama_proyek' => (string) $namaProyek,
                'pengurus_proyek' => $pengurus !== '' ? $pengurus : null,
                'payment_account_id' => $paymentAccountId,
                'items' => $items,
            ];
        }

        return array_values($projects);
    }

    /**
     * Memperbarui invoice semen.
     *
     * @param  \App\Models\Finance\InvoiceSemen  $invoice
     * @param  array  $data      Data invoice (sudah divalidasi request)
     * @param  array  $projects  Proyek yang sudah dinormalisasi
     * @return bool
     */
    public function updateInvoice(InvoiceSemen $invoice, array $data, array $projects): bool
    {
        return $invoice->update([
            'invoice_date' => $data['invoice_date'],
            'projects' => $projects,
            'total_amount' => $this->calculateTotal($projects),
            'note' => $data['note'] ?? null,
            'signed_by_id' => $data['signed_by_id'] ?? null,
        ]);
    }

    /**
     * Menghapus beberapa invoice sekaligus.
     *
     * @param  array<int, string>  $invoiceNumbers
     * @return int  Jumlah data yang berhasil dihapus
     */
    public function destroySelected(array $invoiceNumbers): int
    {
        if (empty($invoiceNumbers)) {
            return 0;
        }

        return InvoiceSemen::whereIn('invoice_number', $invoiceNumbers)->delete();
    }
}