{{-- Modal Edit Invoice Alumunium --}}
<x-modal id="editModal-{{ $invoice->invoice_number }}" title="Edit Invoice"
    action="{{ route('alumunium-invoice.update', $invoice->invoice_number) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">No Invoice</label>
        <input type="text" value="{{ $invoice->invoice_number }}"
            class="w-full border rounded p-2 bg-gray-100 cursor-not-allowed" readonly>
        <p class="text-xs text-gray-500 mt-1">No Invoice tidak dapat diubah</p>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal Invoice <span class="text-error">*</span></label>
        <input type="date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}"
            class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Kepada <span class="text-error">*</span></label>
        <input type="text" name="recipient" value="{{ $invoice->recipient }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Hal / Regarding <span class="text-error">*</span></label>
        <input type="text" name="regarding" value="{{ $invoice->regarding }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('Hal/Regarding tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Deskripsi Proyek <span class="text-error">*</span></label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2" required
            oninvalid="this.setCustomValidity('Deskripsi proyek tidak boleh kosong')" oninput="this.setCustomValidity('')">{{ $invoice->project_description }}</textarea>
    </div>

    <div id="items-container-edit-{{ $invoice->invoice_number }}" class="mb-4">
        <label class="block text-gray-700 font-semibold mb-2">Item-Item Invoice <span
                class="text-error">*</span></label>
        <div id="items-list-edit-{{ $invoice->invoice_number }}">
            @php
                $existingItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
            @endphp
            @foreach ($existingItems as $index => $item)
                <div class="item-row-edit mb-3 p-3 border rounded bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                        <input type="text" name="items[{{ $index }}][keterangan]"
                            value="{{ $item['keterangan'] ?? '' }}"
                            class="item-keterangan-edit border rounded p-2 w-full" placeholder="Keterangan" required
                            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][volume]"
                            value="{{ $item['volume'] ?? 0 }}" class="item-volume-edit border rounded p-2 w-full"
                            placeholder="Volume" required oninput="calculateEditRowTotal(this)"
                            oninvalid="this.setCustomValidity('Volume tidak boleh kosong')"
                            oninput="calculateEditRowTotal(this); this.setCustomValidity('')">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <input type="text" name="items[{{ $index }}][satuan]"
                            value="{{ $item['satuan'] ?? '' }}" class="item-satuan-edit border rounded p-2 w-full"
                            placeholder="Satuan" required
                            oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][harga]"
                            value="{{ $item['harga'] ?? 0 }}" class="item-harga-edit border rounded p-2 w-full"
                            placeholder="Harga" required oninput="calculateEditRowTotal(this)"
                            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')"
                            oninput="calculateEditRowTotal(this); this.setCustomValidity('')">
                        <div class="flex items-center">
                            <span class="item-total-edit text-sm font-semibold text-primary">
                                Rp {{ number_format(($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}
                            </span>
                        </div>
                        <button type="button"
                            class="remove-item-edit bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="add-item-edit bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover"
            data-invoice-id="{{ $invoice->invoice_number }}">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    <!-- Live Total Preview for Edit -->
    <div class="mb-4 p-4 bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg border-2 border-primary/20">
        <div class="flex justify-between items-center">
            <span class="text-gray-700 font-semibold">Total Invoice:</span>
            <span id="invoice-total-preview-edit-{{ $invoice->invoice_number }}"
                class="text-2xl font-bold text-primary">
                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
            </span>
        </div>
    </div>

</x-modal>
