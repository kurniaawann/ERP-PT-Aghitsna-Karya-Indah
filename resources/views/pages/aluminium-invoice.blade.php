@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Invoice Aluminium')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Invoice Aluminium</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <!-- Form Pencarian -->
            <form method="GET" action="{{ route('aluminium-invoice.index') }}"
                class="w-full md:w-auto md:max-w-md md:flex-1">
                <label for="search-input" class="sr-only">Cari Invoice</label>

                <div class="flex items-center gap-2">
                    <!-- Input dengan ikon -->
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 20 20" aria-hidden="true">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </span>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari no invoice atau kepada..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 pl-10 text-sm text-gray-900 
                       focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200" />
                    </div>

                    <!-- Tombol Cari -->
                    <button type="submit"
                        class="rounded-lg bg-btn-search hover:bg-btn-search-hover px-4 md:px-6 py-3.5 text-sm font-medium text-white 
                   focus:outline-none focus:ring-4 focus:ring-primary-light whitespace-nowrap transition-colors duration-200">
                        Cari
                    </button>
                </div>
            </form>


            <!-- Aksi di Kanan -->
            <!-- Aksi di Kanan -->
            <div class="flex items-center gap-3 mt-2 sm:mt-0 flex-col sm:flex-row w-full sm:w-auto">
                <div class="flex gap-2 w-full sm:w-auto">
                    <!-- Tombol Hapus -->
                    <button type="button" onclick="openModal('deleteModal')"
                        class="flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-1.5 rounded-lg transition-colors duration-200 flex-1 sm:flex-initial">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        Hapus
                    </button>

                    <!-- Tombol Tambah -->
                    <button type="button" onclick="openModal('addModal')"
                        class="flex items-center justify-center gap-2 rounded-lg bg-btn-add hover:bg-btn-add-hover px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-4 focus:ring-success-light flex-1 sm:flex-initial transition-colors duration-200">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Invoice
                    </button>
                </div>
            </div>
        </div>


        {{-- Form Hapus Terpilih --}}
        <form id="deleteForm" method="POST" action="{{ route('aluminium-invoice.destroySelected') }}">
            @csrf
            @method('DELETE')
            <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="inline-block min-w-full align-middle">
                    <!-- Border luar table -->
                    <div class="border-2 border-gray-300 rounded-xl overflow-hidden shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                                    <th class="p-2 text-left">No Invoice</th>
                                    <th class="p-2 text-left">Tanggal</th>
                                    <th class="p-2 text-left">Kepada</th>
                                    <th class="p-2 text-left">Proyek</th>
                                    <th class="p-2 text-center">Total</th>
                                    <th class="p-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="p-2 text-center">
                                            <input type="checkbox" name="selected_invoices[]"
                                                value="{{ $invoice->invoice_number }}"
                                                class="w-4 h-4 accent-blue-600 cursor-pointer">
                                        </td>

                                        <td class="p-2 font-medium text-blue-600">{{ $invoice->invoice_number }}</td>
                                        <td class="p-2 text-sm">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                                        <td class="p-2">{{ $invoice->recipient }}</td>
                                        <td class="p-2 text-sm text-gray-600">
                                            {{ substr($invoice->project_description ?? '-', 0, 30) }}</td>

                                        {{-- Total Amount --}}
                                        <td class="p-2 text-right font-medium">
                                            {{ 'Rp ' . number_format($invoice->total_amount, 0, ',', '.') }}
                                        </td>

                                        {{-- Aksi --}}
                                        <td class="p-2 text-center">
                                            <div class="flex justify-center gap-1 flex-wrap">
                                                {{-- Tombol Lihat Detail --}}
                                                <button type="button"
                                                    onclick="openModal('detailModal-{{ $invoice->invoice_number }}')"
                                                    class="flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                    title="Lihat Detail">
                                                    <i class="fa-solid fa-eye w-3 h-3"></i>
                                                    Lihat
                                                </button>

                                                {{-- Tombol Edit --}}
                                                <button type="button"
                                                    onclick="openModal('editModal-{{ $invoice->invoice_number }}')"
                                                    class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                    title="Edit Invoice">
                                                    <i class="fa-solid fa-pen w-3 h-3"></i>
                                                    Edit
                                                </button>

                                                {{-- Tombol Print PDF --}}
                                                <a href="{{ route('aluminium-invoice.print.pdf', $invoice->invoice_number) }}"
                                                    class="flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                    title="Print PDF" target="_blank">
                                                    <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                                    PDF
                                                </a>

                                                {{-- Tombol Print Excel --}}
                                                <a href="{{ route('aluminium-invoice.print.excel', $invoice->invoice_number) }}"
                                                    class="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                    title="Print Excel">
                                                    <i class="fa-solid fa-file-excel w-3 h-3"></i>
                                                    Excel
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center p-4 text-gray-500">Data invoice tidak
                                            ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- End border luar table -->
                </div>
            </div>

        </form>
        <div class="flex mt-4 justify-center">
            <div class="flex items-center gap-3 bg-white border border-gray-300 rounded-lg px-4 py-2 shadow-sm">

                {{-- Tombol Sebelumnya --}}
                <a href="{{ $invoices->appends(request()->query())->previousPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ $invoices->onFirstPage() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &lt;
                </a>

                {{-- Info Halaman --}}
                <span class="text-sm font-medium text-gray-700">
                    {{ $invoices->currentPage() }}
                    <span class="text-gray-400">/</span>
                    {{ $invoices->lastPage() }}
                </span>

                {{-- Tombol Berikutnya --}}
                <a href="{{ $invoices->appends(request()->query())->nextPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ !$invoices->hasMorePages() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &gt;
                </a>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Invoice --}}
    <x-modal id="addModal" title="Tambah Invoice Aluminium" action="{{ route('aluminium-invoice.store') }}" method="POST"
        buttonText="Simpan">

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Tanggal Invoice <span class="text-red-500">*</span></label>
            <input type="date" name="invoice_date" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Kepada (Nama Penerima) <span class="text-red-500">*</span></label>
            <input type="text" name="recipient" class="w-full border rounded p-2" placeholder="Nama penerima invoice"
                required>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Hal / Regarding <span class="text-red-500">*</span></label>
            <input type="text" name="regarding" class="w-full border rounded p-2"
                placeholder="Contoh: Penagihan Pembayaran" required>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Deskripsi Proyek <span class="text-red-500">*</span></label>
            <textarea name="project_description" class="w-full border rounded p-2" rows="2"
                placeholder="Contoh: Proyek Karbela 3 / Pak Sis" required></textarea>
        </div>

        <div id="items-container" class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Item-Item Invoice <span
                    class="text-red-500">*</span></label>
            <div id="items-list">
                <div class="item-row mb-3 p-3 border rounded bg-gray-50">
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <input type="text" class="item-keterangan border rounded p-2" placeholder="Keterangan *"
                            required>
                        <input type="number" step="0.01" class="item-volume border rounded p-2"
                            placeholder="Volume *" required>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="text" class="item-satuan border rounded p-2"
                            placeholder="Satuan (m3, unit, dll) *" required>
                        <input type="number" step="0.01" class="item-harga border rounded p-2" placeholder="Harga *"
                            required>
                        <button type="button"
                            class="remove-item bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <button type="button" id="add-item" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
        </div>

        <input type="hidden" name="items" id="items-json" value="[]">
    </x-modal>

    {{-- Modal Edit untuk setiap invoice --}}
    @foreach ($invoices as $invoice)
        <x-modal id="editModal-{{ $invoice->invoice_number }}" title="Edit Invoice"
            action="{{ route('aluminium-invoice.update', $invoice->invoice_number) }}" method="PUT"
            buttonText="Update">

            <div class="mb-3">
                <label class="block text-gray-700 mb-1">No Invoice</label>
                <input type="text" value="{{ $invoice->invoice_number }}"
                    class="w-full border rounded p-2 bg-gray-100 cursor-not-allowed" readonly>
                <p class="text-xs text-gray-500 mt-1">No Invoice tidak dapat diubah</p>
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 mb-1">Tanggal Invoice <span class="text-red-500">*</span></label>
                <input type="date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}"
                    class="w-full border rounded p-2" required>
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 mb-1">Kepada <span class="text-red-500">*</span></label>
                <input type="text" name="recipient" value="{{ $invoice->recipient }}"
                    class="w-full border rounded p-2" required>
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 mb-1">Hal / Regarding <span class="text-red-500">*</span></label>
                <input type="text" name="regarding" value="{{ $invoice->regarding }}"
                    class="w-full border rounded p-2" required>
            </div>

            <div class="mb-3">
                <label class="block text-gray-700 mb-1">Deskripsi Proyek <span class="text-red-500">*</span></label>
                <textarea name="project_description" class="w-full border rounded p-2" rows="2" required>{{ $invoice->project_description }}</textarea>
            </div>

            <div id="items-container-edit-{{ $invoice->invoice_number }}" class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Item-Item Invoice <span
                        class="text-red-500">*</span></label>
                <div id="items-list-edit-{{ $invoice->invoice_number }}">
                    @php
                        $existingItems = is_string($invoice->items)
                            ? json_decode($invoice->items, true)
                            : $invoice->items;
                    @endphp
                    @foreach ($existingItems as $index => $item)
                        <div class="item-row-edit mb-3 p-3 border rounded bg-gray-50">
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <input type="text" name="items[{{ $index }}][keterangan]"
                                    value="{{ $item['keterangan'] ?? '' }}"
                                    class="item-keterangan-edit border rounded p-2" placeholder="Keterangan" required>
                                <input type="number" step="0.01" name="items[{{ $index }}][volume]"
                                    value="{{ $item['volume'] ?? 0 }}" class="item-volume-edit border rounded p-2"
                                    placeholder="Volume" required>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <input type="text" name="items[{{ $index }}][satuan]"
                                    value="{{ $item['satuan'] ?? '' }}" class="item-satuan-edit border rounded p-2"
                                    placeholder="Satuan" required>
                                <input type="number" step="0.01" name="items[{{ $index }}][harga]"
                                    value="{{ $item['harga'] ?? 0 }}" class="item-harga-edit border rounded p-2"
                                    placeholder="Harga" required>
                                <button type="button"
                                    class="remove-item-edit bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="add-item-edit bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                    data-invoice-id="{{ $invoice->invoice_number }}">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>

        </x-modal>

        {{-- Modal Detail Invoice --}}
        <x-modal id="detailModal-{{ $invoice->invoice_number }}" title="Detail Invoice - {{ $invoice->invoice_number }}"
            :readonly="true">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600">No Invoice</label>
                    <p class="font-semibold">{{ $invoice->invoice_number }}</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Tanggal</label>
                    <p class="font-semibold">{{ $invoice->invoice_date->format('d-m-Y') }}</p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm text-gray-600">Kepada</label>
                <p class="font-semibold">{{ $invoice->recipient }}</p>
            </div>

            <div class="mt-4">
                <label class="block text-sm text-gray-600">Hal / Regarding</label>
                <p>{{ $invoice->regarding ?? '-' }}</p>
            </div>

            <div class="mt-4">
                <label class="block text-sm text-gray-600">Proyek</label>
                <p>{{ $invoice->project_description ?? '-' }}</p>
            </div>

            <div class="mt-4">
                <label class="block text-sm text-gray-600 font-semibold mb-2">Item-Item:</label>
                @php
                    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                @endphp
                <table class="w-full text-sm border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">Keterangan</th>
                            <th class="border p-2 text-center">Volume</th>
                            <th class="border p-2 text-center">Satuan</th>
                            <th class="border p-2 text-right">Harga</th>
                            <th class="border p-2 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php
                                $jumlah = ($item['volume'] ?? 0) * ($item['harga'] ?? 0);
                            @endphp
                            <tr>
                                <td class="border p-2">{{ $item['keterangan'] ?? '-' }}</td>
                                <td class="border p-2 text-center">{{ $item['volume'] ?? 0 }}</td>
                                <td class="border p-2 text-center">{{ $item['satuan'] ?? '-' }}</td>
                                <td class="border p-2 text-right">
                                    Rp{{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                                <td class="border p-2 text-right">Rp{{ number_format($jumlah, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-right border-t pt-4">
                <p class="text-sm text-gray-600">Total</p>
                <p class="text-xl font-bold text-blue-600">Rp{{ number_format($invoice->total_amount, 0, ',', '.') }}
                </p>
            </div>
        </x-modal>
    @endforeach

    {{-- Modal Konfirmasi Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Pagination --}}



    <script>
        function submitDeleteForm() {
            const form = document.getElementById('deleteForm');
            const checkboxes = form.querySelectorAll('input[name="selected_invoices[]"]:checked');
            form.submit();
        }

        // Select All Checkbox functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    invoiceCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Uncheck "Select All" if any individual checkbox is unchecked
            invoiceCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (!this.checked) {
                        selectAllCheckbox.checked = false;
                    } else {
                        // Check if all checkboxes are checked
                        const allChecked = Array.from(invoiceCheckboxes).every(cb => cb.checked);
                        selectAllCheckbox.checked = allChecked;
                    }
                });
            });

            // Handle add item button
            if (document.getElementById('add-item')) {
                document.getElementById('add-item').addEventListener('click', function(e) {
                    e.preventDefault();
                    const itemsContainer = document.getElementById('items-list');
                    const newItem = document.createElement('div');
                    newItem.className = 'item-row mb-3 p-3 border rounded bg-gray-50';
                    newItem.innerHTML = `
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="text" class="item-keterangan border rounded p-2" placeholder="Keterangan *" required>
                            <input type="number" step="0.01" class="item-volume border rounded p-2" placeholder="Volume *" required>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="text" class="item-satuan border rounded p-2" placeholder="Satuan *" required>
                            <input type="number" step="0.01" class="item-harga border rounded p-2" placeholder="Harga *" required>
                            <button type="button" class="remove-item bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                    itemsContainer.appendChild(newItem);
                    attachRemoveListener();
                });
            }

            function attachRemoveListener() {
                document.querySelectorAll('.remove-item').forEach(btn => {
                    btn.removeEventListener('click', removeItemClickHandler);
                    btn.addEventListener('click', removeItemClickHandler);
                });
            }

            function removeItemClickHandler(e) {
                e.preventDefault();
                this.closest('.item-row').remove();
            }

            attachRemoveListener();

            // Handle add item button for EDIT modals
            document.querySelectorAll('.add-item-edit').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const invoiceId = this.getAttribute('data-invoice-id');
                    const itemsContainer = document.getElementById('items-list-edit-' + invoiceId);
                    const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
                    const newIndex = currentItems.length;

                    const newItem = document.createElement('div');
                    newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-gray-50';
                    newItem.innerHTML = `
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="text" name="items[${newIndex}][keterangan]" 
                                class="item-keterangan-edit border rounded p-2" placeholder="Keterangan *" required>
                            <input type="number" step="0.01" name="items[${newIndex}][volume]"
                                class="item-volume-edit border rounded p-2" placeholder="Volume *" required>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="text" name="items[${newIndex}][satuan]"
                                class="item-satuan-edit border rounded p-2" placeholder="Satuan *" required>
                            <input type="number" step="0.01" name="items[${newIndex}][harga]"
                                class="item-harga-edit border rounded p-2" placeholder="Harga *" required>
                            <button type="button" class="remove-item-edit bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                    itemsContainer.appendChild(newItem);
                    attachRemoveListenerEdit();
                });
            });

            function attachRemoveListenerEdit() {
                document.querySelectorAll('.remove-item-edit').forEach(btn => {
                    btn.removeEventListener('click', removeItemEditClickHandler);
                    btn.addEventListener('click', removeItemEditClickHandler);
                });
            }

            function removeItemEditClickHandler(e) {
                e.preventDefault();
                const itemsContainer = this.closest('[id^="items-list-edit-"]');
                const remainingItems = itemsContainer.querySelectorAll('.item-row-edit');

                if (remainingItems.length <= 1) {
                    showToast('Minimal harus ada 1 item dalam invoice', 'warning');
                    return;
                }

                this.closest('.item-row-edit').remove();

                // Re-index items
                itemsContainer.querySelectorAll('.item-row-edit').forEach((row, index) => {
                    row.querySelectorAll('input[name^="items"]').forEach(input => {
                        const fieldName = input.name.match(/\[(\w+)\]$/)[1];
                        input.name = `items[${index}][${fieldName}]`;
                    });
                });
            }

            attachRemoveListenerEdit();

            // Handle form submit untuk modal ADD
            // Attach event saat DOM ready
            attachAddFormListener();

            function attachAddFormListener() {
                const addModalElement = document.getElementById('addModal');
                if (!addModalElement) {
                    console.error('addModal not found');
                    return;
                }

                const addForm = addModalElement.querySelector('form');
                if (!addForm) {
                    console.error('Form in addModal not found');
                    return;
                }

                console.log('Attaching submit listener to add form');

                addForm.addEventListener('submit', function(e) {
                    console.log('=== FORM SUBMIT TRIGGERED ===');

                    const items = [];
                    const itemRows = this.querySelectorAll('.item-row');

                    console.log('Item rows found:', itemRows.length);

                    itemRows.forEach((row, index) => {
                        const keterangan = row.querySelector('.item-keterangan')?.value || '';
                        const volumeInput = row.querySelector('.item-volume');
                        const satuanInput = row.querySelector('.item-satuan');
                        const hargaInput = row.querySelector('.item-harga');

                        const volume = volumeInput ? parseFloat(volumeInput.value) : 0;
                        const satuan = satuanInput ? satuanInput.value : '';
                        const harga = hargaInput ? parseFloat(hargaInput.value) : 0;

                        console.log(`Row ${index}:`, {
                            keterangan,
                            volume,
                            satuan,
                            harga
                        });

                        if (keterangan && !isNaN(volume) && volume > 0 && satuan && !isNaN(harga) &&
                            harga > 0) {
                            items.push({
                                keterangan,
                                volume,
                                satuan,
                                harga
                            });
                        }
                    });

                    if (items.length === 0) {
                        e.preventDefault();
                        alert('Minimal harus ada 1 item dalam invoice dengan data lengkap');
                        return false;
                    }

                    const itemsJsonField = this.querySelector('#items-json');
                    if (!itemsJsonField) {
                        console.error('items-json field not found!');
                        e.preventDefault();
                        alert('Error: Field items tidak ditemukan');
                        return false;
                    }

                    const jsonString = JSON.stringify(items);
                    itemsJsonField.value = jsonString;
                    console.log('Items JSON value set:', itemsJsonField.value);
                    console.log('Field name:', itemsJsonField.name);

                    // Let form submit naturally
                    return true;
                });
            }

            // Validation for EDIT forms
            document.querySelectorAll('form[action*="aluminium-invoice"]').forEach(form => {
                if (form.querySelector('[name="_method"][value="PUT"]')) {
                    form.addEventListener('submit', function(e) {
                        const editItems = this.querySelectorAll('.item-row-edit');
                        if (editItems.length === 0) {
                            e.preventDefault();
                            alert('Minimal harus ada 1 item dalam invoice');
                            return;
                        }
                    });
                }
            });
        });
    </script>
@endsection
