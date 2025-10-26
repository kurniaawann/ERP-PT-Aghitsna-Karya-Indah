<?php

namespace App\Http\Controllers;

use App\Models\InvoiceAlumunium;
use App\Exports\AlumuniumInvoiceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class AlumuniumInvoiceController extends Controller
{
    /**
     * Generate next invoice number
     * Format: n/n/ALU/{tahun}
     */
    public function getNextInvoiceNumber()
    {
        $year = date('y'); // Get 2-digit year (e.g., 25 for 2025)

        // Get last invoice number for current year
        $lastInvoice = InvoiceAlumunium::where('invoice_number', 'like', "%/ALU/{$year}")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract number from format: 1/1/ALU/25
            preg_match('/^(\d+)\//', $lastInvoice->invoice_number, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $nextNumber = $lastNumber + 1;
        } else {
            // First invoice of the year
            $nextNumber = 1;
        }

        $invoiceNumber = "{$nextNumber}/{$nextNumber}/ALU/{$year}";

        return response()->json(['invoice_number' => $invoiceNumber]);
    }

    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request)
    {
        $query = InvoiceAlumunium::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('invoice_number', 'like', "%{$search}%")
                ->orWhere('recipient', 'like', "%{$search}%")
                ->orWhere('project_description', 'like', "%{$search}%");
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(10);

        return view('pages.alumunium-invoice', compact('invoices'));
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(Request $request)
    {
        // Check if items is being sent
        if (!$request->has('items') || empty($request->items)) {
            return back()->with('error', 'Data items tidak ditemukan atau kosong')->withInput();
        }

        // Auto-generate invoice number if not provided or placeholder
        if (empty($request->invoice_number) || strpos($request->invoice_number, 'Akan digenerate') !== false) {
            $year = date('y');
            $lastInvoice = InvoiceAlumunium::where('invoice_number', 'like', "%/ALU/{$year}")
                ->orderBy('invoice_number', 'desc')
                ->first();

            if ($lastInvoice) {
                preg_match('/^(\d+)\//', $lastInvoice->invoice_number, $matches);
                $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $request->merge(['invoice_number' => "{$nextNumber}/{$nextNumber}/ALU/{$year}"]);
        }


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


        // Parse items JSON dan hitung total
        $items = json_decode($request->items, true);
        $totalAmount = 0;

        foreach ($items as $item) {
            $jumlah = ($item['volume'] ?? 0) * ($item['harga'] ?? 0);
            $totalAmount += $jumlah;
        }

        $validated['items'] = $items;
        $validated['total_amount'] = $totalAmount;

        InvoiceAlumunium::create($validated);

        return redirect()->route('alumunium-invoice.index')
            ->with('success', 'Invoice berhasil ditambahkan!');
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(Request $request, InvoiceAlumunium $aluminium_invoice)
    {
        try {
            $validated = $request->validate([
                'invoice_date' => 'required|date',
                'recipient' => 'required|string',
                'regarding' => 'nullable|string',
                'project_description' => 'nullable|string',
                'items' => 'required|array|min:1',
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
            return back()->with('error', $e->validator->errors()->first())->withInput();
        }

        $items = $request->items;
        $totalAmount = 0;

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


    /**
     * Get invoice data for editing
     */
    public function edit(InvoiceAlumunium $aluminium_invoice)
    {
        return response()->json([
            'invoice' => $aluminium_invoice,
            'items' => json_decode($aluminium_invoice->items)
        ]);

    }


    /**
     * Delete multiple selected invoices.
     */
    public function destroySelected(Request $request)
    {
        $selectedInvoiceNumbers = $request->input('selected_invoices', []);

        if (empty($selectedInvoiceNumbers)) {
            return redirect()->back()->with('error', 'Tidak ada invoice yang dipilih untuk dihapus.');
        }

        InvoiceAlumunium::whereIn('invoice_number', $selectedInvoiceNumbers)->delete();

        return redirect()->route('alumunium-invoice.index')
            ->with('success', count($selectedInvoiceNumbers) . ' invoice berhasil dihapus!');
    }

    /**
     * Export invoice to PDF
     */
    public function printPdf($invoiceNumber)
    {
        $invoice = InvoiceAlumunium::where('invoice_number', $invoiceNumber)->firstOrFail();

        $pdf = Pdf::loadView('exports.alumunium-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        // Replace / and \ with - for safe filename
        $safeFileName = str_replace(['/', '\\'], '-', $invoice->invoice_number);

        return $pdf->download('Invoice-' . $safeFileName . '.pdf');
    }

    /**
     * Export invoice to Excel
     */
    public function printExcel($invoiceNumber)
    {
        // Replace / and \ with - for safe filename
        $safeFileName = str_replace(['/', '\\'], '-', $invoiceNumber);

        return Excel::download(new AlumuniumInvoiceExport($invoiceNumber), 'Invoice-' . $safeFileName . '.xlsx');
    }
}

