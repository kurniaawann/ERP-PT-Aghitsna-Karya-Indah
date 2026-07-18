<?php

namespace App\Services\Administrasi;

use App\Models\Administrasi\DeliveryNote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service layer untuk modul Surat Jalan (Delivery Note).
 *
 * Kelas ini bertanggung jawab atas seluruh business logic modul surat jalan,
 * termasuk pencarian, pembuatan data, pembaruan data, dan ekspor PDF.
 * Controller hanya menerima request dan mengembalikan response.
 *
 * @package App\Services\Administrasi
 */
class DeliveryNoteService
{
    /**
     * Karakter wildcard LIKE yang perlu di-escape untuk pencarian.
     */
    private const LIKE_WILDCARDS = ['%', '_'];

    /**
     * Jumlah data per halaman untuk paginasi.
     */
    private const PER_PAGE = 15;

    /**
     * Default unit barang jika tidak diisi.
     */
    private const DEFAULT_UNIT = 'pcs';

    /**
     * Mengambil data surat jalan dengan filter pencarian dan paginasi.
     *
     * @param  string|null  $search  Keyword pencarian (id_delivery_note, document_number, receiver_name, shipper_name)
     * @return LengthAwarePaginator Hasil pencarian dengan paginasi
     */
    public function getPaginated(?string $search): LengthAwarePaginator
    {
        return DeliveryNote::query()
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->latest('created_at')
            ->paginate(self::PER_PAGE);
    }

    /**
     * Mengambil seluruh data surat jalan untuk ekspor PDF.
     *
     * @param  string|null  $search  Keyword pencarian (opsional)
     * @return Collection Koleksi seluruh data surat jalan
     */
    public function getAllForExport(?string $search): Collection
    {
        return DeliveryNote::query()
            ->when($search, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->latest('created_at')
            ->get();
    }

    /**
     * Mengambil data surat jalan berdasarkan array ID untuk ekspor PDF.
     *
     * @param  array<int, string>  $ids  Array ID surat jalan yang dipilih
     * @return Collection Koleksi data surat jalan yang dipilih
     */
    public function getByIds(array $ids): Collection
    {
        return DeliveryNote::whereIn('id_delivery_note', $ids)
            ->latest('created_at')
            ->get();
    }

    /**
     * Membuat data surat jalan baru.
     *
     * Proses:
     * 1. Generate ID surat jalan otomatis (DN-YYYYMMDD-XXXX)
     * 2. Proses array items (no, item_name, quantity, unit, notes)
     * 3. Hitung total quantity
     * 4. Simpan ke database
     *
     * @param  array<string, mixed>  $validated  Data yang sudah divalidasi dari StoreDeliveryNoteRequest
     * @return DeliveryNote Model surat jalan yang baru dibuat
     */
    public function create(array $validated): DeliveryNote
    {
        $deliveryNoteId = DeliveryNote::generateDeliveryNoteId();

        $items = $this->processItems($validated);
        $totalQuantity = $this->calculateTotalQuantity($items);

        return DeliveryNote::create([
            'id_delivery_note' => $deliveryNoteId,
            'document_number' => $validated['document_number'],
            'delivery_date' => $validated['delivery_date'],
            'shipper_name' => $validated['shipper_name'],
            'shipper_address' => $validated['shipper_address'],
            'receiver_name' => $validated['receiver_name'],
            'receiver_address' => $validated['receiver_address'],
            'description' => $validated['description'] ?? null,
            'items' => $items,
            'driver_name' => $validated['driver_name'] ?? null,
            'vehicle_number' => $validated['vehicle_number'] ?? null,
            'total_quantity' => $totalQuantity,
            'notes' => $validated['notes'] ?? null,
        ]);
    }

    /**
     * Memperbarui data surat jalan yang sudah ada.
     *
     * Proses sama dengan create, tetapi memperbarui data existing.
     *
     * @param  DeliveryNote  $deliveryNote  Model surat jalan yang akan diperbarui
     * @param  array<string, mixed>  $validated  Data yang sudah divalidasi dari UpdateDeliveryNoteRequest
     * @return DeliveryNote Model surat jalan yang sudah diperbarui
     */
    public function update(DeliveryNote $deliveryNote, array $validated): DeliveryNote
    {
        $items = $this->processItems($validated);
        $totalQuantity = $this->calculateTotalQuantity($items);

        $deliveryNote->update([
            'document_number' => $validated['document_number'],
            'delivery_date' => $validated['delivery_date'],
            'shipper_name' => $validated['shipper_name'],
            'shipper_address' => $validated['shipper_address'],
            'receiver_name' => $validated['receiver_name'],
            'receiver_address' => $validated['receiver_address'],
            'description' => $validated['description'] ?? null,
            'items' => $items,
            'driver_name' => $validated['driver_name'] ?? null,
            'vehicle_number' => $validated['vehicle_number'] ?? null,
            'total_quantity' => $totalQuantity,
            'notes' => $validated['notes'] ?? null,
        ]);

        return $deliveryNote;
    }

    /**
     * Memproses array items dari form input.
     *
     * Mengambil data item_no[], item_name[], quantity[], unit[], item_notes[]
     * dan mengubahnya menjadi array of items dengan struktur:
     * - no (int): nomor urut item
     * - item_name (string): nama barang
     * - quantity (int): jumlah barang
     * - unit (string): satuan barang
     * - notes (string): catatan item
     *
     * @param  array<string, mixed>  $validated  Data dari form request
     * @return array<int, array<string, mixed>> Array of items
     */
    private function processItems(array $validated): array
    {
        $items = [];

        $itemNos = $validated['item_no'] ?? [];
        $itemNames = $validated['item_name'] ?? [];
        $quantities = $validated['quantity'] ?? [];
        $units = $validated['unit'] ?? [];
        $itemNotes = $validated['item_notes'] ?? [];

        foreach ($itemNos as $index => $no) {
            if (!empty($no) && !empty($itemNames[$index]) && !empty($quantities[$index])) {
                $items[] = [
                    'no' => (int) $no,
                    'item_name' => $itemNames[$index],
                    'quantity' => (int) $quantities[$index],
                    'unit' => $units[$index] ?? self::DEFAULT_UNIT,
                    'notes' => $itemNotes[$index] ?? '',
                ];
            }
        }

        return $items;
    }

    /**
     * Menghitung total quantity seluruh items.
     *
     * @param  array<int, array<string, mixed>>  $items  Array of items
     * @return int Total quantity seluruh items
     */
    private function calculateTotalQuantity(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['quantity'] ?? 0;
        }
        return $total;
    }

    /**
     * Menerapkan filter pencarian pada query builder.
     *
     * Pencarian dilakukan pada kolom: id_delivery_note, document_number, receiver_name, shipper_name.
     * Karakter wildcard LIKE (% dan _) di-escape untuk mencegah hasil yang tidak diinginkan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder
     * @param  string  $search  Keyword pencarian
     * @return \Illuminate\Database\Eloquent\Builder Query builder yang sudah difilter
     */
    private function applySearchFilter($query, string $search)
    {
        $escapedSearch = $this->escapeLikeWildcards($search);

        return $query->where('id_delivery_note', 'like', "%{$escapedSearch}%")
            ->orWhere('document_number', 'like', "%{$escapedSearch}%")
            ->orWhere('receiver_name', 'like', "%{$escapedSearch}%")
            ->orWhere('shipper_name', 'like', "%{$escapedSearch}%");
    }

    /**
     * Meng-escape karakter wildcard LIKE untuk mencegah hasil pencarian yang tidak diinginkan.
     *
     * @param  string  $value  Nilai yang akan di-escape
     * @return string Nilai yang sudah di-escape
     */
    private function escapeLikeWildcards(string $value): string
    {
        foreach (self::LIKE_WILDCARDS as $wildcard) {
            $value = str_replace($wildcard, '\\'.$wildcard, $value);
        }

        return $value;
    }
}
