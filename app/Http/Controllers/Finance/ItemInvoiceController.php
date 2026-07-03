<?php

namespace App\Http\Controllers\Finance;

use App\Exports\Finance\ItemInvoiceIndexExport;
use App\Exports\Finance\ItemInvoiceExport;
use App\Http\Controllers\Controller;
use App\Models\Finance\InvoiceBarang;
use App\Models\Inventory\Items;
use App\Models\Report\SalesRecap;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\InputNormalizer;
use App\Services\StockService;

class ItemInvoiceController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}
    private function baseQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return InvoiceBarang::query()->with('salesRecap')
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = $request->search;
                $builder->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('recipient', 'like', "%{$search}%")
                        ->orWhere('regarding', 'like', "%{$search}%")
                        ->orWhere('project_description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('month'), fn($builder) => $builder->whereMonth('invoice_date', $request->month))
            ->when($request->filled('year'), fn($builder) => $builder->whereYear('invoice_date', $request->year))
            ->orderByDesc('invoice_date');
    }

    public function index(Request $request)
    {
        $invoices = $this->baseQuery($request)->paginate(10)->appends($request->all());
        $summaryInvoices = (clone $this->baseQuery($request))->get();
        $totals = $this->buildTotals($summaryInvoices);
        $items = Items::query()->orderBy('name_item')->get();

        return view('pages.finance.item-invoice', compact('invoices', 'totals', 'items'));
    }

    public function getNextInvoiceNumber()
    {
        return response()->json([
            'invoice_number' => $this->generateInvoiceNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_date' => ['required', 'date'],
            'recipient' => ['required', 'string', 'max:255'],
            'regarding' => ['nullable', 'string', 'max:255'],
            'project_description' => ['required', 'string', 'max:255'],
            'items' => ['required'],
        ]);

        $rawItems = $this->normalizeInvoiceItems($request->input('items'));

        if (empty($rawItems)) {
            return back()->with('error', 'Minimal harus ada 1 item!')->withInput();
        }

        if (empty($request->invoice_number) || str_contains($request->invoice_number, 'Akan digenerate')) {
            $request->merge(['invoice_number' => $this->generateInvoiceNumber()]);
        }

        DB::beginTransaction();
        try {
            $items = $this->processItemsForStore($rawItems);
            $totals = $this->calculateTotals($items);
            $salesRecapId = $this->generateSalesRecapId();

            SalesRecap::create([
                'id_sales_recap' => $salesRecapId,
                'date' => $request->invoice_date,
                'name_proyek' => $request->project_description,
                'items' => $items,
                'total_capital' => $totals['total_capital'],
                'total_selling' => $totals['total_selling'],
                'total_profit' => $totals['total_profit'],
                'status' => 'Belum Lunas',
            ]);

            InvoiceBarang::create([
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'recipient' => $request->recipient,
                'regarding' => $request->regarding,
                'project_description' => $request->project_description,
                'items' => $items,
                'total_capital' => $totals['total_capital'],
                'total_selling' => $totals['total_selling'],
                'total_profit' => $totals['total_profit'],
                'sales_recap_id' => $salesRecapId,
            ]);

            DB::commit();

            return redirect()->route('item-invoice.index')
                ->with('success', 'Invoice item berhasil ditambahkan dan otomatis masuk ke rekap penjualan!');
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Item Invoice store failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan invoice. Silakan coba lagi.')->withInput();
        }
    }

    public function edit(string $invoiceNumber)
    {
        $invoice = InvoiceBarang::where('invoice_number', $invoiceNumber)->firstOrFail();

        return response()->json([
            'invoice' => $invoice,
            'items' => is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items,
        ]);
    }

    public function update(Request $request, string $invoiceNumber)
    {
        $request->validate([
            'invoice_date' => ['required', 'date'],
            'recipient' => ['required', 'string', 'max:255'],
            'regarding' => ['nullable', 'string', 'max:255'],
            'project_description' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
        ]);

        $invoice = InvoiceBarang::where('invoice_number', $invoiceNumber)->firstOrFail();
        $salesRecap = SalesRecap::where('id_sales_recap', $invoice->sales_recap_id)->first();

        if ($salesRecap && $salesRecap->isLunas()) {
            return back()->with('error', 'Data yang sudah lunas tidak dapat diubah!')->withInput();
        }

        $rawItems = $this->normalizeInvoiceItems($request->input('items'));

        if (empty($rawItems)) {
            return back()->with('error', 'Minimal harus ada 1 item!')->withInput();
        }

        DB::beginTransaction();
        try {
            $oldItems = $salesRecap
                ? (is_string($salesRecap->items) ? json_decode($salesRecap->items, true) : $salesRecap->items)
                : (is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items);

            $this->restoreStockFromItems($oldItems ?? []);
            $items = $this->processItemsForStore($rawItems);
            $totals = $this->calculateTotals($items);

            if ($salesRecap) {
                $salesRecap->update([
                    'date' => $request->invoice_date,
                    'name_proyek' => $request->project_description,
                    'items' => $items,
                    'total_capital' => $totals['total_capital'],
                    'total_selling' => $totals['total_selling'],
                    'total_profit' => $totals['total_profit'],
                ]);
            }

            $invoice->update([
                'invoice_date' => $request->invoice_date,
                'recipient' => $request->recipient,
                'regarding' => $request->regarding,
                'project_description' => $request->project_description,
                'items' => $items,
                'total_capital' => $totals['total_capital'],
                'total_selling' => $totals['total_selling'],
                'total_profit' => $totals['total_profit'],
                'sales_recap_id' => $salesRecap?->id_sales_recap ?? $invoice->sales_recap_id,
            ]);

            DB::commit();

            return redirect()->route('item-invoice.index')
                ->with('success', 'Invoice item berhasil diupdate!');
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Item Invoice update failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate invoice. Silakan coba lagi.')->withInput();
        }
    }

    public function destroySelected(Request $request)
    {
        $selectedInvoiceNumbers = $request->input('selected_invoices', []);

        if (empty($selectedInvoiceNumbers)) {
            return redirect()->back()->with('error', 'Tidak ada invoice yang dipilih untuk dihapus.');
        }

        DB::beginTransaction();
        try {
            $invoices = InvoiceBarang::whereIn('invoice_number', $selectedInvoiceNumbers)->get();

            foreach ($invoices as $invoice) {
                $salesRecap = $invoice->salesRecap;

                if ($salesRecap && $salesRecap->isLunas()) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Invoice "' . $invoice->invoice_number . '" sudah Lunas dan tidak dapat dihapus!');
                }

                $items = $salesRecap
                    ? ($salesRecap->items ?? [])
                    : ($invoice->items ?? []);

                $this->restoreStockFromItems($items);

                if ($salesRecap) {
                    $salesRecap->delete();
                }

                $invoice->delete();
            }

            DB::commit();

            return redirect()->route('item-invoice.index')
                ->with('success', count($invoices) . ' invoice berhasil dihapus!');
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Item Invoice destroySelected failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus invoice. Silakan coba lagi.');
        }
    }

    public function printPdf(string $invoiceNumber)
    {
        $invoice = InvoiceBarang::with('salesRecap')->where('invoice_number', $invoiceNumber)->firstOrFail();

        $pdf = Pdf::loadView('exports.finance.item-invoice-pdf', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');

        $safeFileName = str_replace(['/', '\\'], '-', $invoice->invoice_number);

        return $pdf->download('Invoice_Barang_' . $safeFileName . '.pdf');
    }

    public function printExcel(string $invoiceNumber)
    {
        $safeFileName = str_replace(['/', '\\'], '-', $invoiceNumber);

        return Excel::download(new ItemInvoiceExport($invoiceNumber), 'Invoice_Barang_' . $safeFileName . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $invoices = $this->baseQuery($request)->get();

        $pdf = Pdf::loadView('exports.finance.item-invoice-index-pdf', [
            'invoices' => $invoices,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Rekap_Invoice_Barang_' . date('Y-m-d-His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $invoices = $this->baseQuery($request)->get();

        return Excel::download(
            new ItemInvoiceIndexExport($invoices, $request->month, $request->year),
            'Rekap_Invoice_Barang_' . date('Y-m-d-His') . '.xlsx'
        );
    }

    private function generateInvoiceNumber(): string
    {
        $year = date('y');

        $lastInvoice = InvoiceBarang::where('invoice_number', 'like', "%/PT.AKI/{$year}")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            preg_match('/^(\d+)\//', $lastInvoice->invoice_number, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "{$nextNumber}/{$nextNumber}/PT.AKI/{$year}";
    }

    private function normalizeInvoiceItems($items): array
    {
        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        if (!is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized[] = [
                'name_item' => trim((string) ($item['name_item'] ?? '')),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'capital_price' => InputNormalizer::normalizeCurrency($item['capital_price'] ?? 0),
                'selling_price' => InputNormalizer::normalizeCurrency($item['selling_price'] ?? 0),
                'from_stock' => filter_var($item['from_stock'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'id_item' => $item['id_item'] ?? null,
            ];
        }

        return array_values($normalized);
    }

    private function processItemsForStore(array $items): array
    {
        $normalizedItems = [];

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity < 1) {
                throw new \RuntimeException('Qty minimal 1 untuk setiap item.');
            }

            $fromStock = filter_var($item['from_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $idItem = !empty($item['id_item']) ? (string) $item['id_item'] : null;
            $nameItem = trim((string) ($item['name_item'] ?? ''));
            $capitalPrice = (int) ($item['capital_price'] ?? 0);
            $sellingPrice = (int) ($item['selling_price'] ?? 0);

            if ($fromStock) {
                if (empty($idItem)) {
                    throw new \RuntimeException('Barang dari stok harus dipilih dari daftar barang.');
                }

                $stockItem = Items::lockForUpdate()->where('id_item', $idItem)->first();

                if (!$stockItem) {
                    throw new \RuntimeException('Barang dengan ID "' . $idItem . '" tidak ditemukan!');
                }

                if ($stockItem->quantity < $quantity) {
                    throw new \RuntimeException('Stok barang "' . $stockItem->name_item . '" tidak cukup.');
                }

                $stockItem->quantity -= $quantity;
                $stockItem->save();

                $nameItem = $stockItem->name_item;
                $capitalPrice = (int) $stockItem->capital_price;
                $sellingPrice = (int) $stockItem->selling_price;
            } else {
                if ($nameItem === '') {
                    throw new \RuntimeException('Nama barang tidak boleh kosong.');
                }

                if ($capitalPrice >= $sellingPrice) {
                    throw new \RuntimeException('Harga modal harus lebih kecil dari harga jual.');
                }

                $idItem = null;
            }

            $normalizedItems[] = [
                'name_item' => $nameItem,
                'quantity' => $quantity,
                'capital_price' => $capitalPrice,
                'selling_price' => $sellingPrice,
                'from_stock' => $fromStock,
                'id_item' => $idItem,
                'profit' => ($sellingPrice - $capitalPrice) * $quantity,
            ];
        }

        return $normalizedItems;
    }

    private function restoreStockFromItems(array $items): void
    {
        $this->stockService->increaseStockFromItems($items ?? []);
    }

    private function calculateTotals(array $items): array
    {
        $totalCapital = 0;
        $totalSelling = 0;

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $capitalPrice = (int) ($item['capital_price'] ?? 0);
            $sellingPrice = (int) ($item['selling_price'] ?? 0);

            $totalCapital += $capitalPrice * $quantity;
            $totalSelling += $sellingPrice * $quantity;
        }

        return [
            'total_capital' => $totalCapital,
            'total_selling' => $totalSelling,
            'total_profit' => $totalSelling - $totalCapital,
        ];
    }

    private function generateSalesRecapId(): string
    {
        $lastSalesRecap = SalesRecap::orderBy('id_sales_recap', 'desc')->first();

        if ($lastSalesRecap) {
            $lastNumber = intval(substr($lastSalesRecap->id_sales_recap, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $newId = 'SR-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

        while (SalesRecap::where('id_sales_recap', $newId)->exists()) {
            $newNumber++;
            $newId = 'SR-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newId;
    }

    private function buildTotals($invoices): object
    {
        return (object) [
            'total_invoice' => $invoices->sum(fn($invoice) => (int) $invoice->getNetAmount()),
            'invoice_count' => $invoices->count(),
            'paid_count' => $invoices->filter(fn($invoice) => $invoice->salesRecap?->status === 'Lunas')->count(),
            'total_profit' => $invoices->sum(fn($invoice) => (int) ($invoice->total_profit ?? 0)),
        ];
    }
}