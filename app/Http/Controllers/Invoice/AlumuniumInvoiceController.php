<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice\InvoiceAlumunium;
use App\Models\Invoice\PaymentAccount;
use App\Exports\Invoice\AlumuniumInvoiceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;


class AlumuniumInvoiceController extends Controller
{

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
        $query = InvoiceAlumunium::query();

        // Fitur pencarian: cari di nomor invoice, penerima, atau deskripsi proyek
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('invoice_number', 'like', "%{$search}%")
                ->orWhere('recipient', 'like', "%{$search}%")
                ->orWhere('project_description', 'like', "%{$search}%");
        }

        // Urutkan berdasarkan tanggal invoice terbaru, lalu pagination
        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(10);

        // Get active payment accounts
        $paymentAccounts = \App\Models\Invoice\PaymentAccount::active()->get();

        return view('pages.alumunium-invoice', compact('invoices', 'paymentAccounts'));
    }


    public function store(Request $request)
    {
        // Validasi awal: pastikan items ada dan tidak kosong
        if (!$request->has('items') || empty($request->items)) {
            return back()->with('error', 'Data items tidak ditemukan atau kosong')->withInput();
        }

        // Validasi payment accounts
        if (!$request->has('selected_payment_accounts') || empty($request->selected_payment_accounts)) {
            return back()->with('error', 'Minimal 1 rekening pembayaran harus dipilih')->withInput();
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

        // Parse items JSON dari request dan hitung total amount
        $items = json_decode($request->items, true);
        $totalAmount = 0;

        // Loop setiap item untuk hitung total: volume × harga
        foreach ($items as $item) {
            $jumlah = ($item['volume'] ?? 0) * ($item['harga'] ?? 0);
            $totalAmount += $jumlah;
        }

        // Hitung discount
        $discountAmount = 0;
        $totalAfterDiscount = $totalAmount;

        if ($request->filled('discount_value') && $request->discount_value > 0) {
            if ($request->discount_type === 'percentage') {
                $discountAmount = ($totalAmount * $request->discount_value) / 100;
            } else {
                $discountAmount = $request->discount_value;
            }
            $totalAfterDiscount = $totalAmount - $discountAmount;
        }

        // Hitung DP
        $dpAmount = 0;
        if ($request->filled('dp_value') && $request->dp_value > 0) {
            // Validasi percentage DP tidak lebih dari 100%
            if ($request->dp_type === 'percentage' && $request->dp_value > 100) {
                return back()->with('error', 'Nilai DP persentase tidak boleh lebih dari 100%')->withInput();
            }
            
            $baseAmount = $totalAfterDiscount != $totalAmount ? $totalAfterDiscount : $totalAmount;
            if ($request->dp_type === 'percentage') {
                $dpAmount = ($baseAmount * $request->dp_value) / 100;
            } else {
                $dpAmount = $request->dp_value;
            }
        }

        // Ambil semua data dari request (validasi sudah dilakukan di HTML)
        $data = $request->all();
        // Override items dengan array (bukan JSON string)
        $data['items'] = $items;
        // Set total_amount yang sudah dihitung
        $data['total_amount'] = $totalAmount;
        $data['total_after_discount'] = $totalAfterDiscount > 0 && $totalAfterDiscount != $totalAmount ? $totalAfterDiscount : null;
        $data['dp_amount'] = $dpAmount > 0 ? $dpAmount : null;

        // Simpan invoice ke database
        InvoiceAlumunium::create($data);

        return redirect()->route('alumunium-invoice.index')
            ->with('success', 'Invoice berhasil ditambahkan!');
    }


    public function update(Request $request, InvoiceAlumunium $aluminium_invoice)
    {
        try {
            // Ambil items dari request (validasi sudah dilakukan di HTML)
            $items = $request->items;
            $totalAmount = 0;

            // Hitung ulang total_amount dari items baru: volume × harga
            foreach ($items as $item) {
                $jumlah = $item['volume'] * $item['harga'];
                $totalAmount += $jumlah;
            }

            // Hitung discount
            $discountAmount = 0;
            $totalAfterDiscount = $totalAmount;

            if ($request->filled('discount_value') && $request->discount_value > 0) {
                if ($request->discount_type === 'percentage') {
                    $discountAmount = ($totalAmount * $request->discount_value) / 100;
                } else {
                    $discountAmount = $request->discount_value;
                }
                $totalAfterDiscount = $totalAmount - $discountAmount;
            }

            // Hitung DP
            $dpAmount = 0;
            if ($request->filled('dp_value') && $request->dp_value > 0) {
                // Validasi percentage DP tidak lebih dari 100%
                if ($request->dp_type === 'percentage' && $request->dp_value > 100) {
                    return back()->with('error', 'Nilai DP persentase tidak boleh lebih dari 100%')->withInput();
                }
                
                $baseAmount = $totalAfterDiscount > 0 ? $totalAfterDiscount : $totalAmount;
                if ($request->dp_type === 'percentage') {
                    $dpAmount = ($baseAmount * $request->dp_value) / 100;
                } else {
                    $dpAmount = $request->dp_value;
                }
            }

            // Update data invoice (invoice_number tidak diupdate karena sebagai primary key)
            $aluminium_invoice->update([
                'invoice_date' => $request->invoice_date,
                'recipient' => $request->recipient,
                'regarding' => $request->regarding ?? null,
                'project_description' => $request->project_description,
                'items' => $items, // Laravel akan auto-encode ke JSON karena cast di Model
                'total_amount' => $totalAmount,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'total_after_discount' => $totalAfterDiscount > 0 && $totalAfterDiscount != $totalAmount ? $totalAfterDiscount : null,
                'dp_type' => $request->dp_type,
                'dp_value' => $request->dp_value,
                'dp_amount' => $dpAmount > 0 ? $dpAmount : null,
                'selected_payment_accounts' => $request->selected_payment_accounts,
            ]);

            return redirect()->route('alumunium-invoice.index')
                ->with('success', 'Invoice berhasil diupdate!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }



    public function edit(InvoiceAlumunium $aluminium_invoice)
    {
        // Return data invoice dengan items yang sudah di-decode
        return response()->json([
            'invoice' => $aluminium_invoice,
            'items' => json_decode($aluminium_invoice->items) // Decode JSON ke array
        ]);

    }


    public function destroySelected(Request $request)
    {
        // Ambil array invoice_number dari checkbox
        $selectedInvoiceNumbers = $request->input('selected_invoices', []);

        // Validasi: pastikan ada data yang dipilih
        if (empty($selectedInvoiceNumbers)) {
            return redirect()->back()->with('error', 'Tidak ada invoice yang dipilih untuk dihapus.');
        }

        // Hapus invoice berdasarkan invoice_number
        InvoiceAlumunium::whereIn('invoice_number', $selectedInvoiceNumbers)->delete();

        // Redirect dengan info jumlah yang dihapus
        return redirect()->route('alumunium-invoice.index')
            ->with('success', count($selectedInvoiceNumbers) . ' invoice berhasil dihapus!');
    }


    public function printPdf($invoiceNumber)
    {
        // Cari invoice by invoice_number (throw 404 jika tidak ada)
        $invoice = InvoiceAlumunium::where('invoice_number', $invoiceNumber)->firstOrFail();

        // Generate PDF dari view
        $pdf = Pdf::loadView('exports.alumunium-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        // Replace karakter tidak aman di filename (/ dan \)
        $safeFileName = str_replace(['/', '\\'], '-', $invoice->invoice_number);

        // Download PDF dengan nama invoice
        return $pdf->download('Invoice-' . $safeFileName . '.pdf');
    }


    public function printExcel($invoiceNumber)
    {
        // Replace karakter tidak aman di filename (/ dan \)
        $safeFileName = str_replace(['/', '\\'], '-', $invoiceNumber);

        // Download Excel dengan parameter invoiceNumber dan nama file aman
        return Excel::download(new AlumuniumInvoiceExport($invoiceNumber), 'Invoice-' . $safeFileName . '.xlsx');
    }
}

