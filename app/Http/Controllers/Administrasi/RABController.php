<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\RAB;
use App\Models\Administrasi\RABCategory;
use App\Models\Administrasi\RABSubCategory;
use App\Models\Administrasi\RABItem;
use App\Models\Administrasi\RABMiscellaneousCost;
use App\Models\Finance\PaymentAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\Administrasi\RABExport;
use Maatwebsite\Excel\Facades\Excel;

class RABController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->input('search');

        $rabs = RAB::with(['categories'])
            ->when($search, function ($query, $search) {
                return $query->where('rab_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%");
            })
            ->orderBy('sequence_number', 'desc')
            ->paginate(15);

        $paymentAccounts = PaymentAccount::active()->get();

        return view('pages.administrasi.rab', compact('rabs', 'paymentAccounts', 'search'));
    }

    // ─── Get Next Number (AJAX) ───────────────────────────────────────────────

    public function getNextRABNumber()
    {
        return response()->json([
            'rab_number' => RAB::generateRABNumber(),
        ]);
    }

    // ─── Show Detail ──────────────────────────────────────────────────────────

    public function show(string $rabNumber)
    {
        $rab = RAB::with(['categories.subcategories.items', 'miscellaneousCosts'])
            ->where('rab_number', $rabNumber)
            ->firstOrFail();

        return view('pages.administrasi.rab-detail', compact('rab'));
    }

    // ─── Show for Edit (AJAX) ────────────────────────────────────────────────

    public function edit(string $rabNumber)
    {
        $rab = RAB::with(['categories.subcategories.items', 'miscellaneousCosts'])
            ->where('rab_number', $rabNumber)
            ->firstOrFail();

        $data = [
            'rab_number' => $rab->rab_number,
            'date' => $rab->date->format('Y-m-d'),
            'recipient' => $rab->recipient,
            'recipient_address' => $rab->recipient_address,
            'intro_text' => $rab->intro_text,
            'total_amount' => $rab->total_amount,
            'amount_in_words' => $rab->amount_in_words,
            'selected_payment_accounts' => is_string($rab->selected_payment_accounts)
                ? json_decode($rab->selected_payment_accounts, true)
                : $rab->selected_payment_accounts,
            'signed_by' => $rab->signed_by,
            'division' => $rab->division,
            'categories' => $rab->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'roman_order' => $category->roman_order,
                    'category_name' => $category->category_name,
                    'subcategories' => $category->subcategories->map(function ($subcategory) {
                        $legacySubtotal = (int) ($subcategory->sub_harga ?? 0);
                        $legacyVolume = $subcategory->volume;
                        $legacyUnit = $subcategory->unit;
                        $legacyUnitPrice = $subcategory->unit_price;

                        return [
                            'id' => $subcategory->id,
                            'number_order' => $subcategory->number_order,
                            'subcategory_name' => $subcategory->subcategory_name,
                            'items' => $subcategory->items->values()->map(function ($item, $index) use ($legacyVolume, $legacyUnit, $legacyUnitPrice, $legacySubtotal) {
                                $hasItemPricing = $item->volume !== null
                                    || $item->unit !== null
                                    || $item->unit_price !== null
                                    || $item->sub_harga !== null;

                                $useLegacyPricing = !$hasItemPricing && $index === 0;

                                return [
                                    'id' => $item->id,
                                    'letter_order' => $item->letter_order,
                                    'item_description' => $item->item_description,
                                    'volume' => $hasItemPricing ? $item->volume : ($useLegacyPricing ? $legacyVolume : null),
                                    'unit' => $hasItemPricing ? $item->unit : ($useLegacyPricing ? $legacyUnit : null),
                                    'unit_price' => $hasItemPricing ? $item->unit_price : ($useLegacyPricing ? $legacyUnitPrice : null),
                                    'sub_harga' => $hasItemPricing ? $item->sub_harga : ($useLegacyPricing ? $legacySubtotal : 0),
                                ];
                            })->toArray(),
                            'sub_harga' => $subcategory->items->sum(function ($item) {
                                return (int) ($item->sub_harga ?? 0);
                            }) ?: $legacySubtotal,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
            'miscellaneous_costs' => $rab->miscellaneousCosts->map(function ($misc) {
                return [
                    'id' => $misc->id,
                    'item_order' => $misc->item_order,
                    'item_name' => $misc->item_name,
                    'amount' => $misc->amount,
                ];
            })->toArray(),
        ];

        return response()->json($data);
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'intro_text' => 'required|string',
            'rab_data' => 'required|string',
        ]);

        if (empty($request->input('selected_payment_accounts', []))) {
            return back()->with('error', 'Minimal 1 rekening pembayaran harus dipilih.')->withInput();
        }

        // Parse RAB data JSON
        $rabData = json_decode($request->input('rab_data'), true);
        if (!$rabData || !is_array($rabData) || count($rabData) === 0) {
            return back()->with('error', 'Minimal 1 kategori pekerjaan harus ditambahkan.')->withInput();
        }

        // Parse miscellaneous costs JSON
        $miscCostsData = [];
        if ($request->input('misc_costs_data')) {
            $miscCostsData = json_decode($request->input('misc_costs_data'), true) ?? [];
        }

        // Auto-generate RAB number
        $seqNumber = RAB::getNextSequenceNumber();
        $month = (int) date('n');
        $romanMonth = $this->arabicToRoman($month);
        $year = date('Y');
        $rabNumber = "{$seqNumber}/RAB/{$romanMonth}/{$year}";

        // Calculate grand total (main categories)
        $totalAmount = 0;
        foreach ($rabData as $category) {
            foreach ($category['subcategories'] ?? [] as $subcategory) {
                $subcategoryTotal = 0;

                foreach ($subcategory['items'] ?? [] as $itemData) {
                    $volume = (float) ($itemData['volume'] ?? 0);
                    $unitPrice = (int) ($itemData['unit_price'] ?? 0);
                    $itemTotal = (int) ($itemData['sub_harga'] ?? round($volume * $unitPrice));
                    $subcategoryTotal += $itemTotal;
                }

                if ($subcategoryTotal === 0) {
                    $subcategoryTotal = (int) ($subcategory['sub_harga'] ?? 0);
                }

                $totalAmount += $subcategoryTotal;
            }
        }

        // Calculate misc costs total
        $miscCostsTotal = 0;
        foreach ($miscCostsData as $miscCost) {
            $miscCostsTotal += (int) ($miscCost['amount'] ?? 0);
        }

        // Total anggaran biaya (tanpa PPN)
        $totalAnggaranBiaya = $totalAmount + $miscCostsTotal;

        DB::transaction(function () use ($request, $rabNumber, $seqNumber, $totalAmount, $rabData, $miscCostsData, $totalAnggaranBiaya) {
            // Create RAB header
            $rab = RAB::create([
                'rab_number' => $rabNumber,
                'sequence_number' => $seqNumber,
                'date' => $request->input('date'),
                'recipient' => $request->input('recipient'),
                'recipient_address' => $request->input('recipient_address', 'Ditempat'),
                'intro_text' => $request->input('intro_text'),
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAnggaranBiaya)) . ' rupiah',
                'selected_payment_accounts' => $request->input('selected_payment_accounts', []),
                'signed_by' => $request->input('signed_by'),
                'division' => $request->input('division'),
            ]);

            // Create categories and their data
            foreach ($rabData as $categoryIndex => $categoryData) {
                $category = RABCategory::create([
                    'rab_number' => $rabNumber,
                    'roman_order' => $categoryIndex + 1,
                    'category_name' => $categoryData['category_name'],
                    'order' => $categoryIndex,
                ]);

                // Create subcategories
                foreach ($categoryData['subcategories'] ?? [] as $subcategoryIndex => $subcategoryData) {
                    $subcategoryTotal = 0;

                    foreach ($subcategoryData['items'] ?? [] as $itemData) {
                        $volume = (float) ($itemData['volume'] ?? 0);
                        $unitPrice = (int) ($itemData['unit_price'] ?? 0);
                        $itemTotal = (int) ($itemData['sub_harga'] ?? round($volume * $unitPrice));
                        $subcategoryTotal += $itemTotal;
                    }

                    if ($subcategoryTotal === 0) {
                        $subcategoryTotal = (int) ($subcategoryData['sub_harga'] ?? 0);
                    }

                    $subcategory = RABSubCategory::create([
                        'rab_category_id' => $category->id,
                        'number_order' => $subcategoryIndex + 1,
                        'subcategory_name' => $subcategoryData['subcategory_name'],
                        'sub_harga' => $subcategoryTotal,
                        'order' => $subcategoryIndex,
                    ]);

                    // Create items
                    foreach ($subcategoryData['items'] ?? [] as $itemIndex => $itemData) {
                        $volume = (float) ($itemData['volume'] ?? 0);
                        $unitPrice = (int) ($itemData['unit_price'] ?? 0);
                        $itemTotal = (int) ($itemData['sub_harga'] ?? round($volume * $unitPrice));

                        RABItem::create([
                            'rab_subcategory_id' => $subcategory->id,
                            'letter_order' => $itemIndex + 1,
                            'item_description' => $itemData['item_description'],
                            'volume' => $volume ?: null,
                            'unit' => $itemData['unit'] ?? null,
                            'unit_price' => $unitPrice ?: null,
                            'sub_harga' => $itemTotal,
                            'order' => $itemIndex,
                        ]);
                    }
                }
            }

            // Create miscellaneous costs
            foreach ($miscCostsData as $itemIndex => $miscCost) {
                RABMiscellaneousCost::create([
                    'rab_number' => $rabNumber,
                    'item_order' => $itemIndex + 1,
                    'item_name' => $miscCost['item_name'],
                    'amount' => (int) ($miscCost['amount'] ?? 0),
                    'order' => $itemIndex,
                ]);
            }
        });

        return redirect()->route('rab.index')
            ->with('success', "RAB {$rabNumber} berhasil ditambahkan!");
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, string $rabNumber)
    {
        $rab = RAB::where('rab_number', $rabNumber)->firstOrFail();

        $request->validate([
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'intro_text' => 'required|string',
            'rab_data' => 'required|string',
        ]);

        if (empty($request->input('selected_payment_accounts', []))) {
            return back()->with('error', 'Minimal 1 rekening pembayaran harus dipilih.')->withInput();
        }

        // Parse RAB data JSON
        $rabData = json_decode($request->input('rab_data'), true);
        if (!$rabData || !is_array($rabData) || count($rabData) === 0) {
            return back()->with('error', 'Minimal 1 kategori pekerjaan harus ditambahkan.')->withInput();
        }

        // Parse miscellaneous costs JSON
        $miscCostsData = [];
        if ($request->input('misc_costs_data')) {
            $miscCostsData = json_decode($request->input('misc_costs_data'), true) ?? [];
        }

        // Calculate grand total
        $totalAmount = 0;
        foreach ($rabData as $category) {
            foreach ($category['subcategories'] ?? [] as $subcategory) {
                $subcategoryTotal = 0;

                foreach ($subcategory['items'] ?? [] as $itemData) {
                    $volume = (float) ($itemData['volume'] ?? 0);
                    $unitPrice = (int) ($itemData['unit_price'] ?? 0);
                    $itemTotal = (int) ($itemData['sub_harga'] ?? round($volume * $unitPrice));
                    $subcategoryTotal += $itemTotal;
                }

                if ($subcategoryTotal === 0) {
                    $subcategoryTotal = (int) ($subcategory['sub_harga'] ?? 0);
                }

                $totalAmount += $subcategoryTotal;
            }
        }

        // Calculate misc costs total
        $miscCostsTotal = 0;
        foreach ($miscCostsData as $miscCost) {
            $miscCostsTotal += (int) ($miscCost['amount'] ?? 0);
        }

        // Total anggaran biaya (tanpa PPN)
        $totalAnggaranBiaya = $totalAmount + $miscCostsTotal;

        DB::transaction(function () use ($request, $rab, $totalAmount, $rabData, $miscCostsData, $totalAnggaranBiaya) {
            // Update RAB header
            $rab->update([
                'date' => $request->input('date'),
                'recipient' => $request->input('recipient'),
                'recipient_address' => $request->input('recipient_address', 'Ditempat'),
                'intro_text' => $request->input('intro_text'),
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAnggaranBiaya)) . ' rupiah',
                'selected_payment_accounts' => $request->input('selected_payment_accounts', []),
                'signed_by' => $request->input('signed_by'),
                'division' => $request->input('division'),
            ]);

            // Delete existing categories and recreate them
            $rab->categories()->delete();

            foreach ($rabData as $categoryIndex => $categoryData) {
                $category = RABCategory::create([
                    'rab_number' => $rab->rab_number,
                    'roman_order' => $categoryIndex + 1,
                    'category_name' => $categoryData['category_name'],
                    'order' => $categoryIndex,
                ]);

                foreach ($categoryData['subcategories'] ?? [] as $subcategoryIndex => $subcategoryData) {
                    $subcategoryTotal = 0;

                    foreach ($subcategoryData['items'] ?? [] as $itemData) {
                        $volume = (float) ($itemData['volume'] ?? 0);
                        $unitPrice = (int) ($itemData['unit_price'] ?? 0);
                        $itemTotal = (int) ($itemData['sub_harga'] ?? round($volume * $unitPrice));
                        $subcategoryTotal += $itemTotal;
                    }

                    if ($subcategoryTotal === 0) {
                        $subcategoryTotal = (int) ($subcategoryData['sub_harga'] ?? 0);
                    }

                    $subcategory = RABSubCategory::create([
                        'rab_category_id' => $category->id,
                        'number_order' => $subcategoryIndex + 1,
                        'subcategory_name' => $subcategoryData['subcategory_name'],
                        'sub_harga' => $subcategoryTotal,
                        'order' => $subcategoryIndex,
                    ]);

                    foreach ($subcategoryData['items'] ?? [] as $itemIndex => $itemData) {
                        $volume = (float) ($itemData['volume'] ?? 0);
                        $unitPrice = (int) ($itemData['unit_price'] ?? 0);
                        $itemTotal = (int) ($itemData['sub_harga'] ?? round($volume * $unitPrice));

                        RABItem::create([
                            'rab_subcategory_id' => $subcategory->id,
                            'letter_order' => $itemIndex + 1,
                            'item_description' => $itemData['item_description'],
                            'volume' => $volume ?: null,
                            'unit' => $itemData['unit'] ?? null,
                            'unit_price' => $unitPrice ?: null,
                            'sub_harga' => $itemTotal,
                            'order' => $itemIndex,
                        ]);
                    }
                }
            }

            // Delete existing miscellaneous costs and recreate them
            $rab->miscellaneousCosts()->delete();

            foreach ($miscCostsData as $itemIndex => $miscCost) {
                RABMiscellaneousCost::create([
                    'rab_number' => $rab->rab_number,
                    'item_order' => $itemIndex + 1,
                    'item_name' => $miscCost['item_name'],
                    'amount' => (int) ($miscCost['amount'] ?? 0),
                    'order' => $itemIndex,
                ]);
            }
        });

        return redirect()->route('rab.index')
            ->with('success', "RAB {$rab->rab_number} berhasil diperbarui!");
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function destroy(Request $request)
    {
        $rabNumbers = $request->input('selected_items', []);

        if (empty($rabNumbers)) {
            return back()->with('error', 'Pilih minimal 1 RAB untuk dihapus.');
        }

        DB::transaction(function () use ($rabNumbers) {
            foreach ($rabNumbers as $rabNumber) {
                RAB::where('rab_number', $rabNumber)->delete();
            }
        });

        return back()->with('success', count($rabNumbers) . ' RAB berhasil dihapus.');
    }

    // ─── Export PDF ───────────────────────────────────────────────────────────

    public function exportPDF(string $rabNumber)
    {
        $rab = RAB::with(['categories.subcategories.items', 'miscellaneousCosts'])
            ->where('rab_number', $rabNumber)
            ->firstOrFail();

        // Buat nama file yang aman (tanpa karakter /)
        $safeFileName = str_replace('/', '-', $rabNumber);

        $pdf = Pdf::loadView('exports.administrasi.rab-pdf', [
            'rab' => $rab,
        ])->setPaper('a4', 'portrait');

        $date = date('Y-m-d');
        return $pdf->download("RAB_{$safeFileName}_{$date}.pdf");
    }

    // ─── Export Excel ─────────────────────────────────────────────────────────

    public function exportExcel(string $rabNumber)
    {
        $rab = RAB::where('rab_number', $rabNumber)->firstOrFail();
        $safeFileName = str_replace('/', '-', $rabNumber);
        $date = date('Y-m-d');

        return Excel::download(new RABExport($rab->rab_number), "RAB_{$safeFileName}_{$date}.xlsx");
    }

    // ─── Helper: Convert Arabic to Roman ───────────────────────────────────────

    private function arabicToRoman($num)
    {
        $romanMap = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
        return $romanMap[$num] ?? '';
    }

    // ─── Helper: Convert number to letter ──────────────────────────────────────

    private function numberToLetter($num)
    {
        return chr(96 + $num);
    }
}
