<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\RAB;
use App\Models\Administrasi\RABCategory;
use App\Models\Administrasi\RABSubCategory;
use App\Models\Administrasi\RABItem;
use App\Models\Administrasi\RABMiscellaneousCost;
use Illuminate\Support\Facades\DB;

/**
 * Service RAB (Rencana Anggaran Biaya)
 *
 * Menangani seluruh business logic untuk:
 * - Pembuatan RAB baru
 * - Update RAB
 * - Hapus RAB
 * - Perhitungan total biaya
 * - Generate nomor RAB
 */
class RABService
{
    /**
     * Mapping angka arab ke romawi (1-12)
     */
    private static array $romanMap = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /**
     * Mendapatkan daftar RAB dengan eager load dan pagination.
     *
     * @param string|null $search
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedRABs(?string $search = null, int $perPage = 15)
    {
        return RAB::with(['categories', 'miscellaneousCosts'])
            ->when($search, function ($query, $search) {
                return $query->where('rab_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%");
            })
            ->orderBy('sequence_number', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mendapatkan detail RAB lengkap dengan relasi.
     *
     * @param string $rabNumber
     * @return RAB
     */
    public function getRABWithDetails(string $rabNumber): RAB
    {
        return RAB::with(['categories.subcategories.items', 'miscellaneousCosts'])
            ->where('rab_number', $rabNumber)
            ->firstOrFail();
    }

    /**
     * Mendapatkan data RAB untuk keperluan edit form.
     *
     * @param string $rabNumber
     * @return array
     */
    public function getRABEditData(string $rabNumber): array
    {
        $rab = $this->getRABWithDetails($rabNumber);

        return [
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
    }

    /**
     * Menyimpan RAB baru beserta kategori, sub-kategori, item, dan biaya lain-lain.
     *
     * @param array $validatedData Data sudah tervalidasi
     * @param array $rabData Data kategori/sub-kategori/item dari JSON
     * @param array $miscCostsData Data biaya lain-lain dari JSON
     * @return RAB
     */
    public function storeRAB(array $validatedData, array $rabData, array $miscCostsData): RAB
    {
        $seqNumber = RAB::getNextSequenceNumber();
        $month = (int) date('n');
        $romanMonth = $this->arabicToRoman($month);
        $year = date('Y');
        $rabNumber = "{$seqNumber}/RAB/{$romanMonth}/{$year}";

        $totalAmount = $this->calculateTotalAmount($rabData);
        $miscCostsTotal = $this->calculateMiscCostsTotal($miscCostsData);
        $totalAnggaranBiaya = $totalAmount + $miscCostsTotal;

        $rab = DB::transaction(function () use ($validatedData, $rabNumber, $seqNumber, $totalAmount, $rabData, $miscCostsData, $totalAnggaranBiaya) {
            $rab = RAB::create([
                'rab_number' => $rabNumber,
                'sequence_number' => $seqNumber,
                'date' => $validatedData['date'],
                'recipient' => $validatedData['recipient'],
                'recipient_address' => $validatedData['recipient_address'] ?? 'Ditempat',
                'intro_text' => $validatedData['intro_text'],
                'total_amount' => $totalAnggaranBiaya,
                'incoming_payment' => $validatedData['incoming_payment'] ?? 0,
                'amount_in_words' => ucwords(terbilang($totalAnggaranBiaya)) . ' rupiah',
                'selected_payment_accounts' => $validatedData['selected_payment_accounts'] ?? [],
                'signed_by' => $validatedData['signed_by'] ?? null,
                'division' => $validatedData['division'] ?? null,
            ]);

            $this->createCategories($rab, $rabData);
            $this->createMiscellaneousCosts($rab, $miscCostsData);

            return $rab;
        });

        return $rab;
    }

    /**
     * Memperbarui RAB yang sudah ada.
     *
     * @param string $rabNumber Nomor RAB yang akan diupdate
     * @param array $validatedData Data sudah tervalidasi
     * @param array $rabData Data kategori/sub-kategori/item dari JSON
     * @param array $miscCostsData Data biaya lain-lain dari JSON
     * @return RAB
     */
    public function updateRAB(string $rabNumber, array $validatedData, array $rabData, array $miscCostsData): RAB
    {
        $rab = RAB::where('rab_number', $rabNumber)->firstOrFail();

        $totalAmount = $this->calculateTotalAmount($rabData);
        $miscCostsTotal = $this->calculateMiscCostsTotal($miscCostsData);
        $totalAnggaranBiaya = $totalAmount + $miscCostsTotal;

        DB::transaction(function () use ($rab, $validatedData, $totalAmount, $rabData, $miscCostsData, $totalAnggaranBiaya) {
            $rab->update([
                'date' => $validatedData['date'],
                'recipient' => $validatedData['recipient'],
                'recipient_address' => $validatedData['recipient_address'] ?? 'Ditempat',
                'intro_text' => $validatedData['intro_text'],
                'total_amount' => $totalAnggaranBiaya,
                'incoming_payment' => $validatedData['incoming_payment'] ?? 0,
                'amount_in_words' => ucwords(terbilang($totalAnggaranBiaya)) . ' rupiah',
                'selected_payment_accounts' => $validatedData['selected_payment_accounts'] ?? [],
                'signed_by' => $validatedData['signed_by'] ?? null,
                'division' => $validatedData['division'] ?? null,
            ]);

            $rab->categories()->delete();
            $rab->miscellaneousCosts()->delete();

            $this->createCategories($rab, $rabData);
            $this->createMiscellaneousCosts($rab, $miscCostsData);
        });

        return $rab;
    }

    /**
     * Menghapus RAB berdasarkan nomor RAB.
     * Menghapus header dan cascade ke kategori, sub-kategori, item, dan biaya lain-lain.
     *
     * @param array $rabNumbers Array nomor RAB yang akan dihapus
     * @return int Jumlah RAB yang dihapus
     */
    public function destroyRABs(array $rabNumbers): int
    {
        return DB::transaction(function () use ($rabNumbers) {
            $count = 0;
            foreach ($rabNumbers as $rabNumber) {
                RAB::where('rab_number', $rabNumber)->delete();
                $count++;
            }
            return $count;
        });
    }

    /**
     * Menghitung total biaya dari data kategori/sub-kategori/item.
     *
     * @param array $rabData
     * @return int
     */
    public function calculateTotalAmount(array $rabData): int
    {
        $totalAmount = 0;

        foreach ($rabData as $category) {
            foreach ($category['subcategories'] ?? [] as $subcategory) {
                $subcategoryTotal = $this->calculateSubcategoryTotal($subcategory);
                $totalAmount += $subcategoryTotal;
            }
        }

        return $totalAmount;
    }

    /**
     * Menghitung total sub-kategori dari item-item di dalamnya.
     *
     * @param array $subcategory
     * @return int
     */
    public function calculateSubcategoryTotal(array $subcategory): int
    {
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

        return $subcategoryTotal;
    }

    /**
     * Menghitung total biaya lain-lain.
     *
     * @param array $miscCostsData
     * @return int
     */
    public function calculateMiscCostsTotal(array $miscCostsData): int
    {
        $total = 0;

        foreach ($miscCostsData as $miscCost) {
            $total += (int) ($miscCost['amount'] ?? 0);
        }

        return $total;
    }

    /**
     * Membuat kategori beserta sub-kategori dan item untuk sebuah RAB.
     *
     * @param RAB $rab
     * @param array $rabData
     * @return void
     */
    private function createCategories(RAB $rab, array $rabData): void
    {
        foreach ($rabData as $categoryIndex => $categoryData) {
            $category = RABCategory::create([
                'rab_number' => $rab->rab_number,
                'roman_order' => $categoryIndex + 1,
                'category_name' => $categoryData['category_name'],
                'order' => $categoryIndex,
            ]);

            foreach ($categoryData['subcategories'] ?? [] as $subcategoryIndex => $subcategoryData) {
                $subcategoryTotal = $this->calculateSubcategoryTotal($subcategoryData);

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
    }

    /**
     * Membuat biaya lain-lain untuk sebuah RAB.
     *
     * @param RAB $rab
     * @param array $miscCostsData
     * @return void
     */
    private function createMiscellaneousCosts(RAB $rab, array $miscCostsData): void
    {
        foreach ($miscCostsData as $itemIndex => $miscCost) {
            RABMiscellaneousCost::create([
                'rab_number' => $rab->rab_number,
                'item_order' => $itemIndex + 1,
                'item_name' => $miscCost['item_name'],
                'amount' => (int) ($miscCost['amount'] ?? 0),
                'order' => $itemIndex,
            ]);
        }
    }

    /**
     * Mengonversi angka arab (1-12) ke romawi.
     *
     * @param int $num
     * @return string
     */
    public function arabicToRoman(int $num): string
    {
        return self::$romanMap[$num] ?? '';
    }
}
