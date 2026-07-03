<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\InvoiceAlumunium;
use App\Models\Finance\PaymentAccount;
use App\Exports\Finance\AlumuniumInvoiceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\InputNormalizer;
use App\Traits\HasBulkActions;
use Illuminate\Support\Facades\Log;


class AlumuniumInvoiceController extends Controller
{
    use HasBulkActions;

    public function getNextInvoiceNumber()
    {
        // Ambil tahun 2 digit (contoh: 25 untuk tahun 2025)
        $year = date('y');

        // Cari invoice terakhir untuk tahun ini
        $lastInvoice = InvoiceAlumunium::where('invoice_number', 'like', "%/ALU/{$year}")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract nomor dari format: 1/1/ALU/25 (ambil digit sebelum slash pertama)
            preg_match('/^(\d+)\//', $lastInvoice->invoice_number, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $nextNumber = $lastNumber + 1;
        } else {
            // Invoice pertama di tahun ini, mulai dari 1
            $nextNumber = 1;
        }

        // Format: {n}/{n}/ALU/{yy}
        $invoiceNumber = "{$nextNumber}/{$nextNumber}/ALU/{$year}";

        return response()->json(['invoice_number' => $invoiceNumber]);
    }


    public function index(Request $request)
    {
        // Query builder untuk InvoiceAlumunium
        $query = InvoiceAlumunium::with('paymentProofs');

        // Fitur pencarian: cari di nomor invoice, penerima, atau deskripsi proyek
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('project_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('month')) {
            $query->whereMonth('invoice_date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('invoice_date', $request->year);
        }

        // Urutkan berdasarkan tanggal invoice terbaru, lalu pagination
        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(15);

        // Get active payment accounts
        $paymentAccounts = \App\Models\Finance\PaymentAccount::active()->get();

        return view('pages.finance.alumunium-invoice', compact('invoices', 'paymentAccounts'));
    }


    public function store(Request $request)
    {
        $request->merge([
            'discount_value' => InputNormalizer::normalizeDecimal($request->discount_value),
            'dp_value' => InputNormalizer::normalizeDecimal($request->dp_value),
        ]);

        // Validasi awal: pastikan items ada dan tidak kosong
        if (!$request->has('items') || empty($request->items)) {
            return back()->with('error', 'Data items tidak ditemukan atau kosong')->withInput();
        }

        // Validasi payment accounts
        if (!$request->has('selected_payment_accounts') || empty($request->selected_payment_accounts)) {
            return back()->with('error', 'Minimal 1 rekening pembayaran harus dipilih')->withInput();
        }

        // Validasi discount percentage
        if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
            return back()->with('error', 'Persentase diskon tidak boleh lebih dari 100%')->withInput();
        }

        // Validasi DP percentage
        if ($request->dp_type === 'percentage' && $request->dp_value > 100) {
            return back()->with('error', 'Persentase DP tidak boleh lebih dari 100%')->withInput();
        }

        // Auto-generate invoice number jika kosong atau berisi placeholder
        if (empty($request->invoice_number) || strpos($request->invoice_number, 'Akan digenerate') !== false) {
            // Ambil tahun 2 digit (contoh: 25 untuk tahun 2025)
            $year = date('y');

            // Cari invoice terakhir untuk tahun ini
            $lastInvoice = InvoiceAlumunium::where('invoice_number', 'like', "%/ALU/{$year}")
                ->orderBy('invoice_number', 'desc')
                ->first();

            if ($lastInvoice) {
                // Extract nomor dari invoice terakhir menggunakan regex
                // Format: {n}/{n}/ALU/{yy}, ambil {n} pertama
                preg_match('/^(\d+)\//', $lastInvoice->invoice_number, $matches);
                $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                // Invoice pertama tahun ini, mulai dari 1
                $nextNumber = 1;
            }

            // Set invoice_number ke request dengan format {n}/{n}/ALU/{yy}
            $request->merge(['invoice_number' => "{$nextNumber}/{$nextNumber}/ALU/{$year}"]);
        }

        // Parse items JSON dari request dan normalisasi harga sebelum dihitung/simpan
        $items = $this->normalizeInvoiceItems(json_decode($request->items, true));
        $totalAmount = 0;

        // Loop setiap item untuk hitung total: volume × harga
        foreach ($items as $item) {
            $jumlah = ($item['volume'] ?? 0) * ($item['harga'] ?? 0);
            $totalAmount += $jumlah;
        }

        // Hitung discount dan DP menggunakan method helper
        $calculations = $this->calculateInvoiceTotals($request, $totalAmount);

        // Ambil semua data dari request (validasi sudah dilakukan di HTML)
        $data = $request->all();
        // Override items dengan array (bukan JSON string)
        $data['items'] = $items;
        // Set total_amount yang sudah dihitung
        $data['total_amount'] = $totalAmount;
        $data['total_after_discount'] = $calculations['totalAfterDiscount'] > 0 && $calculations['totalAfterDiscount'] != $totalAmount ? $calculations['totalAfterDiscount'] : null;
        $data['dp_amount'] = $calculations['dpAmount'] > 0 ? $calculations['dpAmount'] : null;

        // Simpan invoice ke database
        InvoiceAlumunium::create($data);

        return redirect()->route('alumunium-invoice.index')
            ->with('success', 'Invoice berhasil ditambahkan!');
    }


    public function update(Request $request, string $invoiceNumber)
    {
        try {
            $aluminium_invoice = InvoiceAlumunium::where('invoice_number', $invoiceNumber)->firstOrFail();

            $request->merge([
                'discount_value' => InputNormalizer::normalizeDecimal($request->discount_value),
                'dp_value' => InputNormalizer::normalizeDecimal($request->dp_value),
            ]);

            // Validasi discount percentage
            if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
                return back()->with('error', 'Persentase diskon tidak boleh lebih dari 100%')->withInput();
            }

            // Validasi DP percentage
            if ($request->dp_type === 'percentage' && $request->dp_value > 100) {
                return back()->with('error', 'Persentase DP tidak boleh lebih dari 100%')->withInput();
            }

            // Ambil items langsung dari form edit seperti invoice proyek
            $items = $this->normalizeInvoiceItems($request->items);
            $totalAmount = 0;

            // Hitung ulang total_amount dari items baru: volume × harga
            foreach ($items as $item) {
                $jumlah = ($item['volume'] ?? 0) * ($item['harga'] ?? 0);
                $totalAmount += $jumlah;
            }

            // Hitung discount dan DP menggunakan method helper
            $calculations = $this->calculateInvoiceTotals($request, $totalAmount);

            // Update data invoice menggunakan model yang sudah ditemukan secara eksplisit.
            $aluminium_invoice->update([
                'invoice_date' => $request->invoice_date,
                'recipient' => $request->recipient,
                'regarding' => $request->regarding ?? null,
                'project_description' => $request->project_description,
                'items' => $items,
                'total_amount' => $totalAmount,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'total_after_discount' => $calculations['totalAfterDiscount'] > 0 && $calculations['totalAfterDiscount'] != $totalAmount ? $calculations['totalAfterDiscount'] : null,
                'dp_type' => $request->dp_type,
                'dp_value' => $request->dp_value,
                'dp_amount' => $calculations['dpAmount'] > 0 ? $calculations['dpAmount'] : null,
                'selected_payment_accounts' => $request->selected_payment_accounts,
            ]);

            return redirect()->route('alumunium-invoice.index')
                ->with('success', 'Invoice berhasil diupdate!');
        } catch (\Exception $e) {
            Log::error('Alumunium Invoice update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate invoice. Silakan coba lagi.')->withInput();
        }
    }


    public function edit(string $invoiceNumber)
    {
        $aluminium_invoice = InvoiceAlumunium::where('invoice_number', $invoiceNumber)->firstOrFail();

        // Return data invoice dengan items yang sudah di-decode
        return response()->json([
            'invoice' => $aluminium_invoice,
            'items' => json_decode($aluminium_invoice->items) // Decode JSON ke array
        ]);
    }


    public function destroySelected(Request $request)
    {
        return $this->destroySelectedBy($request, InvoiceAlumunium::class, 'selected_invoices', 'invoice_number', 'alumunium-invoice.index');
    }


    public function printPdf($invoiceNumber)
    {
        // Cari invoice by invoice_number (throw 404 jika tidak ada)
        $invoice = InvoiceAlumunium::where('invoice_number', $invoiceNumber)->firstOrFail();

        // Generate PDF dari view
        $pdf = Pdf::loadView('exports.finance.alumunium-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        // Replace karakter tidak aman di filename (/ dan \)
        $safeFileName = str_replace(['/', '\\'], '-', $invoice->invoice_number);

        // Download PDF dengan nama invoice
        return $pdf->download('Invoice_Aluminium_' . $safeFileName . '.pdf');
    }


    public function printExcel($invoiceNumber)
    {
        // Replace karakter tidak aman di filename (/ dan \)
        $safeFileName = str_replace(['/', '\\'], '-', $invoiceNumber);

        // Download Excel dengan parameter invoiceNumber dan nama file aman
        return Excel::download(new AlumuniumInvoiceExport($invoiceNumber), 'Invoice_Aluminium_' . $safeFileName . '.xlsx');
    }

    /**
     * Calculate invoice totals including discount and DP
     */
    private function calculateInvoiceTotals(Request $request, float $totalAmount): array
    {
        // Hitung discount
        $discountAmount = 0;
        $totalAfterDiscount = $totalAmount;

        if ($request->filled('discount_value') && $request->discount_value > 0) {
            if ($request->discount_type === 'percentage') {
                $discountAmount = round(($totalAmount * $request->discount_value) / 100);
            } else {
                $discountAmount = round($request->discount_value);
            }
            $totalAfterDiscount = $totalAmount - $discountAmount;
        }

        // Hitung DP
        $dpAmount = 0;
        if ($request->filled('dp_value') && $request->dp_value > 0) {
            $baseAmount = $totalAfterDiscount != $totalAmount ? $totalAfterDiscount : $totalAmount;
            if ($request->dp_type === 'percentage') {
                $dpAmount = round(($baseAmount * $request->dp_value) / 100);
            } else {
                $dpAmount = round($request->dp_value);
            }
        }

        return [
            'discountAmount' => $discountAmount,
            'totalAfterDiscount' => $totalAfterDiscount,
            'dpAmount' => $dpAmount,
        ];
    }



    /**
     * Normalisasi data items invoice agar harga format ribuan tetap tersimpan sebagai angka utuh.
     */
    private function normalizeInvoiceItems($items): array
    {
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        if (!is_array($items)) {
            return [];
        }

        return array_map(function ($item) {
            $item['volume'] = InputNormalizer::normalizeDecimal($item['volume'] ?? 0);
            $item['harga'] = InputNormalizer::normalizeCurrency($item['harga'] ?? 0);

            return $item;
        }, $items);
    }

}

