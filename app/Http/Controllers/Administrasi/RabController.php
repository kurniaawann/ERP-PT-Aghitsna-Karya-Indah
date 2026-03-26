<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\RAB;
use App\Models\Administrasi\RABCategory;
use App\Models\Administrasi\RABSubCategory;
use App\Models\Administrasi\RABItem;
use App\Models\Finance\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->paginate(10);

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
        $rab = RAB::with(['categories.subcategories.items'])
            ->where('rab_number', $rabNumber)
            ->firstOrFail();

        return view('pages.administrasi.rab-detail', compact('rab'));
    }

    // ─── Show for Edit (AJAX) ────────────────────────────────────────────────

    public function edit(string $rabNumber)
    {
        $rab = RAB::with(['categories.subcategories.items'])
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
                        return [
                            'id' => $subcategory->id,
                            'number_order' => $subcategory->number_order,
                            'subcategory_name' => $subcategory->subcategory_name,
                            'volume' => $subcategory->volume,
                            'unit' => $subcategory->unit,
                            'unit_price' => $subcategory->unit_price,
                            'sub_harga' => $subcategory->sub_harga,
                            'items' => $subcategory->items->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'letter_order' => $item->letter_order,
                                    'item_description' => $item->item_description,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
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

        // Auto-generate RAB number
        $seqNumber = RAB::getNextSequenceNumber();
        $year = date('y');
        $rabNumber = "{$seqNumber}/RAB/PT.AKI/{$year}";

        // Calculate grand total
        $totalAmount = 0;
        foreach ($rabData as $category) {
            foreach ($category['subcategories'] ?? [] as $subcategory) {
                $totalAmount += (int) ($subcategory['sub_harga'] ?? 0);
            }
        }

        DB::transaction(function () use ($request, $rabNumber, $seqNumber, $totalAmount, $rabData) {
            // Create RAB header
            $rab = RAB::create([
                'rab_number' => $rabNumber,
                'sequence_number' => $seqNumber,
                'date' => $request->input('date'),
                'recipient' => $request->input('recipient'),
                'recipient_address' => $request->input('recipient_address', 'Ditempat'),
                'intro_text' => $request->input('intro_text'),
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
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
                    $subcategory = RABSubCategory::create([
                        'rab_category_id' => $category->id,
                        'number_order' => $subcategoryIndex + 1,
                        'subcategory_name' => $subcategoryData['subcategory_name'],
                        'volume' => (int) ($subcategoryData['volume'] ?? 0),
                        'unit' => $subcategoryData['unit'] ?? '',
                        'unit_price' => (int) ($subcategoryData['unit_price'] ?? 0),
                        'sub_harga' => (int) ($subcategoryData['sub_harga'] ?? 0),
                        'order' => $subcategoryIndex,
                    ]);

                    // Create items
                    foreach ($subcategoryData['items'] ?? [] as $itemIndex => $itemData) {
                        RABItem::create([
                            'rab_subcategory_id' => $subcategory->id,
                            'letter_order' => $itemIndex + 1,
                            'item_description' => $itemData['item_description'],
                            'order' => $itemIndex,
                        ]);
                    }
                }
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

        // Calculate grand total
        $totalAmount = 0;
        foreach ($rabData as $category) {
            foreach ($category['subcategories'] ?? [] as $subcategory) {
                $totalAmount += (int) ($subcategory['sub_harga'] ?? 0);
            }
        }

        DB::transaction(function () use ($request, $rab, $totalAmount, $rabData) {
            // Update RAB header
            $rab->update([
                'date' => $request->input('date'),
                'recipient' => $request->input('recipient'),
                'recipient_address' => $request->input('recipient_address', 'Ditempat'),
                'intro_text' => $request->input('intro_text'),
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
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
                    $subcategory = RABSubCategory::create([
                        'rab_category_id' => $category->id,
                        'number_order' => $subcategoryIndex + 1,
                        'subcategory_name' => $subcategoryData['subcategory_name'],
                        'volume' => (int) ($subcategoryData['volume'] ?? 0),
                        'unit' => $subcategoryData['unit'] ?? '',
                        'unit_price' => (int) ($subcategoryData['unit_price'] ?? 0),
                        'sub_harga' => (int) ($subcategoryData['sub_harga'] ?? 0),
                        'order' => $subcategoryIndex,
                    ]);

                    foreach ($subcategoryData['items'] ?? [] as $itemIndex => $itemData) {
                        RABItem::create([
                            'rab_subcategory_id' => $subcategory->id,
                            'letter_order' => $itemIndex + 1,
                            'item_description' => $itemData['item_description'],
                            'order' => $itemIndex,
                        ]);
                    }
                }
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
}
