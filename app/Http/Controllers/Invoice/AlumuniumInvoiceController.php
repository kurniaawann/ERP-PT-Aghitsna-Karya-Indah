<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice\InvoiceAlumunium;
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

        return view('pages.alumunium-invoice', compact('invoices'));
    }


    public function store(Request $request)
    {
        // Validasi awal: pastikan items ada dan tidak kosong
        if (!$request->has('items') || empty($request->items)) {
            return back()->with('error', 'Data items tidak ditemukan atau kosong')->withInput();
        }

        // Auto-generate invoice number jika kosong atau berisi placeholder
        if (empty($request->invoice_number) || strpos($request->invoice_number, 'Akan digenerate') !== false) {
            $year = date('y');

            // Cari invoice terakhir tahun ini
            $lastInvoice = InvoiceAlumunium::where('invoice_number', 'like', "%/ALU/{$year}")
                ->orderBy('invoice_number', 'desc')
                ->first();

            if ($lastInvoice) {
                // Extract nomor dari invoice terakhir
                preg_match('/^(\d+)\//', $lastInvoice->invoice_number, $matches);
                $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                // Invoice pertama tahun ini
                $nextNumber = 1;
            }

            // Inject invoice_number ke request
            $request->merge(['invoice_number' => "{$nextNumber}/{$nextNumber}/ALU/{$year}"]);
        }

        // Validasi form dengan custom error messages
        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:alumunium_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'recipient' => 'required|string',
            'regarding' => 'nullable|string',
            'project_description' => 'nullable|string',
            'items' => 'required|json',
        ], [
            'invoice_number.unique' => 'No Invoice sudah digunakan, gunakan nomor yang berbeda',
            'invoice_number.required' => 'No Invoice wajib diisi',
            'invoice_date.required' => 'Tanggal Invoice wajib diisi',
            'recipient.required' => 'Nama penerima wajib diisi',
            'items.required' => 'Minimal harus ada 1 item dalam invoice',
            'items.json' => 'Format data items tidak valid',
        ]);

        // Parse items JSON dan hitung total amount
        $items = json_decode($request->items, true);
        $totalAmount = 0;

        foreach ($items as $item) {
            // Hitung jumlah per item: volume × harga
            $jumlah = ($item['volume'] ?? 0) * ($item['harga'] ?? 0);
            $totalAmount += $jumlah;
        }

        // Tambahkan items (array) dan total_amount ke validated data
        $validated['items'] = $items;
        $validated['total_amount'] = $totalAmount;

        // Simpan invoice ke database
        InvoiceAlumunium::create($validated);

        return redirect()->route('alumunium-invoice.index')
            ->with('success', 'Invoice berhasil ditambahkan!');
    }


    public function update(Request $request, InvoiceAlumunium $aluminium_invoice)
    {
        try {
            // Validasi form dengan custom error messages
            $validated = $request->validate([
                'invoice_date' => 'required|date',
                'recipient' => 'required|string',
                'regarding' => 'nullable|string',
                'project_description' => 'nullable|string',
                'items' => 'required|array|min:1', // Items harus array dengan minimal 1 item
                'items.*.keterangan' => 'required|string',
                'items.*.volume' => 'required|numeric|min:0',
                'items.*.satuan' => 'required|string',
                'items.*.harga' => 'required|numeric|min:0',
            ], [
                'invoice_date.required' => 'Tanggal Invoice wajib diisi',
                'recipient.required' => 'Nama penerima wajib diisi',
                'items.required' => 'Minimal harus ada 1 item dalam invoice',
                'items.min' => 'Minimal harus ada 1 item dalam invoice',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Redirect back dengan error message pertama
            return back()->with('error', $e->validator->errors()->first())->withInput();
        }

        // Ambil items dari request
        $items = $request->items;
        $totalAmount = 0;

        // Hitung ulang total_amount dari items baru
        foreach ($items as $item) {
            $jumlah = $item['volume'] * $item['harga'];
            $totalAmount += $jumlah;
        }

        // Update data (invoice_number tidak diupdate karena sebagai primary key)
        $aluminium_invoice->update([
            'invoice_date' => $validated['invoice_date'],
            'recipient' => $validated['recipient'],
            'regarding' => $validated['regarding'] ?? null,
            'project_description' => $validated['project_description'],
            'items' => $items, // Laravel akan auto-encode ke JSON karena cast di Model
            'total_amount' => $totalAmount,
        ]);

        return redirect()->route('alumunium-invoice.index')
            ->with('success', 'Invoice berhasil diupdate!');
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

