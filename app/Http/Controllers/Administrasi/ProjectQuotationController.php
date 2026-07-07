<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\ProjectQuotation;
use App\Models\Administrasi\ProjectQuotationItem;
use App\Models\Finance\PaymentAccount;
use App\Exports\Administrasi\ProjectQuotationExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProjectQuotationController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->input('search');

        $quotations = ProjectQuotation::with(['items'])
            ->when($search, function ($query, $search) {
                return $query->where('quotation_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            })
            ->orderBy('sequence_number', 'desc')
            ->paginate(15);

        $paymentAccounts = PaymentAccount::active()->get();

        return view('pages.administrasi.project-quotation', compact('quotations', 'paymentAccounts', 'search'));
    }

    // ─── Get Next Number (AJAX) ───────────────────────────────────────────────

    public function getNextQuotationNumber()
    {
        return response()->json([
            'quotation_number' => ProjectQuotation::generateQuotationNumber(),
        ]);
    }

    // ─── Show (Get for Edit) ──────────────────────────────────────────────────

    public function show(string $quotationNumber)
    {
        $quotation = ProjectQuotation::with(['items'])
            ->where('quotation_number', $quotationNumber)
            ->firstOrFail();

        return response()->json([
            'quotation_number' => $quotation->quotation_number,
            'date' => $quotation->date,
            'subject' => $quotation->subject,
            'recipient' => $quotation->recipient,
            'recipient_address' => $quotation->recipient_address,
            'total_amount' => $quotation->total_amount,
            'amount_in_words' => $quotation->amount_in_words,
            'selected_payment_accounts' => is_string($quotation->selected_payment_accounts)
                ? json_decode($quotation->selected_payment_accounts, true)
                : $quotation->selected_payment_accounts,
            'signed_by' => $quotation->signed_by,
            'division' => $quotation->division,
            'items' => $quotation->items->map(function ($item) {
                return [
                    'order_number' => $item->order_number,
                    'description' => $item->description,
                    'volume' => $item->volume,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ];
            })->toArray(),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'items_json' => 'required|string',
        ]);

        if (empty($request->input('selected_payment_accounts', []))) {
            return back()->with('error', 'Minimal 1 rekening pembayaran harus dipilih.')->withInput();
        }

        // Parse items JSON
        $items = json_decode($request->input('items_json'), true);
        if (!$items || !is_array($items) || count($items) === 0) {
            return back()->with('error', 'Minimal 1 item harus ditambahkan.')->withInput();
        }

        // Auto-generate quotation number
        $seqNumber = ProjectQuotation::getNextSequenceNumber();
        $year = date('y');
        $quotationNumber = "{$seqNumber}/{$seqNumber}/PT.AKI/{$year}";

        // Calculate grand total
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += (int) ($item['total_price'] ?? 0);
        }

        DB::transaction(function () use ($request, $quotationNumber, $seqNumber, $totalAmount, $items) {
            // Create header
            $quotation = ProjectQuotation::create([
                'quotation_number' => $quotationNumber,
                'sequence_number' => $seqNumber,
                'date' => $request->input('date'),
                'subject' => $request->input('subject', 'Penawaran Harga'),
                'recipient' => $request->input('recipient'),
                'recipient_address' => $request->input('recipient_address', 'Ditempat'),
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
                'selected_payment_accounts' => $request->input('selected_payment_accounts', []),
                'signed_by' => $request->input('signed_by'),
                'division' => $request->input('division'),
            ]);

            // Create items
            foreach ($items as $index => $itemData) {
                ProjectQuotationItem::create([
                    'quotation_number' => $quotationNumber,
                    'order_number' => $index + 1,
                    'description' => $itemData['description'],
                    'volume' => $itemData['volume'] ?? null,
                    'unit' => $itemData['unit'] ?? null,
                    'unit_price' => (int) ($itemData['unit_price'] ?? 0),
                    'total_price' => (int) ($itemData['total_price'] ?? 0),
                ]);
            }
        });

        return redirect()->route('project-quotation.index')
            ->with('success', "Penawaran {$quotationNumber} berhasil ditambahkan!");
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, string $quotationNumber)
    {
        $quotation = ProjectQuotation::findOrFail($quotationNumber);

        $request->validate([
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'items_json' => 'required|string',
        ]);

        if (empty($request->input('selected_payment_accounts', []))) {
            return back()->with('error', 'Minimal 1 rekening pembayaran harus dipilih.')->withInput();
        }

        $items = json_decode($request->input('items_json'), true);
        if (!$items || !is_array($items) || count($items) === 0) {
            return back()->with('error', 'Minimal 1 item harus ditambahkan.')->withInput();
        }

        // Calculate grand total
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += (int) ($item['total_price'] ?? 0);
        }

        DB::transaction(function () use ($request, $quotation, $totalAmount, $items) {
            // Delete old items
            $quotation->items()->delete();

            // Update header
            $quotation->update([
                'date' => $request->input('date'),
                'subject' => $request->input('subject', 'Penawaran Harga'),
                'recipient' => $request->input('recipient'),
                'recipient_address' => $request->input('recipient_address', 'Ditempat'),
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
                'selected_payment_accounts' => $request->input('selected_payment_accounts', []),
                'signed_by' => $request->input('signed_by'),
                'division' => $request->input('division'),
            ]);

            // Re-create items
            foreach ($items as $index => $itemData) {
                ProjectQuotationItem::create([
                    'quotation_number' => $quotation->quotation_number,
                    'order_number' => $index + 1,
                    'description' => $itemData['description'],
                    'volume' => $itemData['volume'] ?? null,
                    'unit' => $itemData['unit'] ?? null,
                    'unit_price' => (int) ($itemData['unit_price'] ?? 0),
                    'total_price' => (int) ($itemData['total_price'] ?? 0),
                ]);
            }
        });

        return redirect()->route('project-quotation.index')
            ->with('success', 'Penawaran berhasil diperbarui!');
    }

    // ─── Destroy Selected ────────────────────────────────────────────────────

    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        ProjectQuotation::whereIn('quotation_number', $ids)->get()->each->delete();

        $message = count($ids) . ' data terpilih berhasil dihapus.';
        return redirect()->route('project-quotation.index')
            ->with('success', $message);
    }

    // ─── Print PDF (Single from GET route) ───────────────────────────────────

    public function printPdfSingle(string $quotationNumber)
    {
        try {
            $quotation = ProjectQuotation::with(['items'])
                ->where('quotation_number', $quotationNumber)
                ->firstOrFail();

            $selectedAccountIds = is_string($quotation->selected_payment_accounts)
                ? json_decode($quotation->selected_payment_accounts, true)
                : ($quotation->selected_payment_accounts ?? []);

            if (!empty($selectedAccountIds)) {
                $paymentAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedAccountIds)
                    ->orderBy('id')
                    ->get();
            } else {
                $paymentAccounts = \App\Models\Finance\PaymentAccount::where('is_active', true)->get();
            }

            $items = $quotation->items()->orderBy('order_number')->get();

            $pdf = Pdf::loadView('exports.administrasi.project-quotation-pdf', compact('quotation', 'items', 'paymentAccounts'))
                ->setPaper('a4', 'portrait');

            $safeNumber = str_replace(['/', '\\'], '-', $quotation->quotation_number);
            $date = date('Y-m-d');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, "Penawaran_Proyek_{$safeNumber}_{$date}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal generate PDF: ' . $e->getMessage());
        }
    }

    // ─── Print Excel (Single from GET route) ─────────────────────────────────

    public function printExcelSingle(string $quotationNumber)
    {
        $safeFileName = str_replace(['/', '\\'], '-', $quotationNumber);
        $date = date('Y-m-d');
        return Excel::download(
            new ProjectQuotationExport($quotationNumber),
            "Penawaran_Proyek_{$safeFileName}_{$date}.xlsx"
        );
    }

    // ─── Print PDF (Single or Multiple) ──────────────────────────────────────

    public function printPdf(Request $request)
    {
        $quotationNumbers = $request->input('quotation_numbers', []);

        if (empty($quotationNumbers)) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $quotations = ProjectQuotation::with(['items'])
            ->whereIn('quotation_number', $quotationNumbers)
            ->orderBy('sequence_number')
            ->get();

        if ($quotations->isEmpty()) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Data tidak ditemukan!');
        }

        // Get payment accounts for each quotation
        foreach ($quotations as $quotation) {
            $selectedAccountIds = is_string($quotation->selected_payment_accounts)
                ? json_decode($quotation->selected_payment_accounts, true)
                : ($quotation->selected_payment_accounts ?? []);

            if (!empty($selectedAccountIds)) {
                $quotation->paymentAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedAccountIds)
                    ->orderBy('id')
                    ->get();
            } else {
                $quotation->paymentAccounts = \App\Models\Finance\PaymentAccount::where('is_active', true)->get();
            }

            $quotation->items = $quotation->items()->orderBy('order_number')->get();
        }

        // Single PDF view or bulk view
        if (count($quotationNumbers) === 1) {
            $quotation = $quotations->first();
            $items = $quotation->items;
            $paymentAccounts = $quotation->paymentAccounts;

            $pdf = Pdf::loadView('exports.administrasi.project-quotation-pdf', compact('quotation', 'items', 'paymentAccounts'))
                ->setPaper('a4', 'portrait');

            $safeNumber = str_replace(['/', '\\'], '-', $quotation->quotation_number);
            $date = date('Y-m-d');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, "Penawaran_Proyek_{$safeNumber}_{$date}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);
        } else {
            // Multiple quotations - create separate pages
            $pdf = Pdf::loadView('exports.administrasi.project-quotation-pdf-bulk', compact('quotations'))
                ->setPaper('a4', 'portrait');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'Penawaran_Proyek_' . date('Y-m-d') . '.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        }
    }

    // ─── Print Excel (Single or Multiple) ────────────────────────────────────

    public function printExcel(Request $request)
    {
        $quotationNumbers = $request->input('quotation_numbers', []);

        if (empty($quotationNumbers)) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        if (count($quotationNumbers) === 1) {
            $safeFileName = str_replace(['/', '\\'], '-', $quotationNumbers[0]);
            $date = date('Y-m-d');
            return Excel::download(
                new ProjectQuotationExport($quotationNumbers[0]),
                "Penawaran_Proyek_{$safeFileName}_{$date}.xlsx"
            );
        } else {
            // For multiple, create a multi-sheet Excel file
            return Excel::download(
                new \App\Exports\Administrasi\ProjectQuotationMultiExport($quotationNumbers),
                'Penawaran_Proyek_' . date('Y-m-d') . '.xlsx'
            );
        }
    }

    // ─── Export selected PDF ─────────────────────────────────────────────────

    public function exportPdfSelected(Request $request)
    {
        // This is now handled by printPdf() method
        return $this->printPdf($request);
    }
}
