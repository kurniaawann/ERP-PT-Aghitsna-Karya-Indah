@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Pengeluaran')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Laporan Pengeluaran</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <!-- Form Pencarian dan Filter -->
            <form method="GET" action="{{ route('expense-report.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                <!-- Filter Kategori -->
                <div class="w-full lg:w-auto">
                    <label for="category-select" class="sr-only">Pilih Kategori</label>
                    <select name="category" id="category-select"
                        class="block w-full lg:w-48 rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div class="w-full lg:w-auto">
                    <label for="month-select" class="sr-only">Pilih Bulan</label>
                    <select name="month" id="month-select"
                        class="block w-full lg:w-40 rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Bulan</option>
                        <option value="1" {{ request('month') == '1' ? 'selected' : '' }}>Januari</option>
                        <option value="2" {{ request('month') == '2' ? 'selected' : '' }}>Februari</option>
                        <option value="3" {{ request('month') == '3' ? 'selected' : '' }}>Maret</option>
                        <option value="4" {{ request('month') == '4' ? 'selected' : '' }}>April</option>
                        <option value="5" {{ request('month') == '5' ? 'selected' : '' }}>Mei</option>
                        <option value="6" {{ request('month') == '6' ? 'selected' : '' }}>Juni</option>
                        <option value="7" {{ request('month') == '7' ? 'selected' : '' }}>Juli</option>
                        <option value="8" {{ request('month') == '8' ? 'selected' : '' }}>Agustus</option>
                        <option value="9" {{ request('month') == '9' ? 'selected' : '' }}>September</option>
                        <option value="10" {{ request('month') == '10' ? 'selected' : '' }}>Oktober</option>
                        <option value="11" {{ request('month') == '11' ? 'selected' : '' }}>November</option>
                        <option value="12" {{ request('month') == '12' ? 'selected' : '' }}>Desember</option>
                    </select>
                </div>

                <!-- Filter Tahun -->
                <div class="w-full lg:w-auto">
                    <label for="year-select" class="sr-only">Pilih Tahun</label>
                    <select name="year" id="year-select"
                        class="block w-full lg:w-32 rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Tahun</option>
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                {{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Search Input -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 20 20" aria-hidden="true">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </span>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari transaksi..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 pl-10 text-sm text-gray-900 
                                   focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light" />
                    </div>

                    <button type="submit"
                        class="w-full sm:w-auto rounded-lg bg-btn-search hover:bg-btn-search-hover px-4 lg:px-6 py-3.5 text-sm font-medium text-white 
                               focus:outline-none focus:ring-4 focus:ring-primary-light whitespace-nowrap transition-colors duration-200">
                        Cari
                    </button>
                </div>
            </form>

            <!-- Aksi di Kanan -->
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <!-- Dropdown Print Laporan -->
                    <div class="relative inline-block text-left w-full sm:w-auto">
                        <button type="button" id="printDropdownButton"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-3 py-2 lg:py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium">
                            <i class="fa-solid fa-print w-4 h-4"></i>
                            <span>Print Laporan</span>
                            <i class="fa-solid fa-chevron-down text-xs ml-auto sm:ml-0"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="printDropdownMenu"
                            class="hidden absolute left-0 sm:right-0 sm:left-auto mt-2 w-full sm:w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1" role="menu">
                                <a href="{{ route('expense-report.export.excel') }}?{{ http_build_query(array_filter(['search' => request('search'), 'category' => request('category'), 'month' => request('month'), 'year' => request('year')])) }}"
                                    class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                    <i class="fa-solid fa-file-excel text-success w-4"></i>
                                    <span>Export Excel</span>
                                </a>
                                <a href="{{ route('expense-report.export.pdf') }}?{{ http_build_query(array_filter(['search' => request('search'), 'category' => request('category'), 'month' => request('month'), 'year' => request('year')])) }}"
                                    class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                    <i class="fa-solid fa-file-pdf text-error w-4"></i>
                                    <span>Export PDF</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Hapus -->
                    <button type="button" onclick="openModal('deleteModal')"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2 lg:py-1.5 rounded-lg transition-colors duration-200 text-sm font-medium">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        <span>Hapus</span>
                    </button>

                    <!-- Tombol Tambah -->
                    <button type="button" onclick="openModal('addModal')"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-lg bg-btn-add hover:bg-btn-add-hover px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-4 focus:ring-success-light transition-colors duration-200">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Pengeluaran</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Form Hapus Terpilih --}}
        <form id="deleteForm" method="POST" action="{{ route('expense-report.destroySelected') }}">
            @csrf
            @method('DELETE')
            <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="inline-block min-w-full align-middle">
                    <div class="border-2 border-gray-300 rounded-xl overflow-hidden shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                                    <th class="p-2 text-center">ID</th>
                                    <th class="p-2 text-left">Kategori</th>
                                    <th class="p-2 text-left">Tanggal</th>
                                    <th class="p-2 text-left">Faktur</th>
                                    <th class="p-2 text-left">Keterangan</th>
                                    <th class="p-2 text-right">Pemasukan</th>
                                    <th class="p-2 text-right">Pengeluaran</th>
                                    <th class="p-2 text-left">Sumber Uang</th>
                                    <th class="p-2 text-center">Sumber Data</th>
                                    <th class="p-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($expenseReports as $expense)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="p-2 text-center">
                                            @if (!$expense->isAutoGenerated())
                                                <input type="checkbox" name="selected_expenses[]"
                                                    value="{{ $expense->id }}" class="expense-checkbox">
                                            @endif
                                        </td>
                                        <td class="p-2 text-center font-medium text-primary">
                                            {{ $expense->id }}
                                        </td>
                                        <td class="p-2">
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $expense->category->type == 'INCOME' ? 'bg-success-light text-success' : 'bg-error-light text-error' }}">
                                                {{ $expense->category->name }}
                                            </span>
                                        </td>
                                        <td class="p-2">
                                            {{ $expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="p-2 text-gray-600">
                                            {{ $expense->invoice_number ?? '-' }}
                                        </td>
                                        <td class="p-2 text-gray-700">
                                            {{ $expense->description ?? '-' }}
                                        </td>
                                        <td
                                            class="p-2 text-right font-medium {{ $expense->income_amount ? 'text-success' : 'text-gray-400' }}">
                                            {{ $expense->income_amount ? 'Rp ' . number_format($expense->income_amount, 0, ',', '.') : '-' }}
                                        </td>
                                        <td
                                            class="p-2 text-right font-medium {{ $expense->expense_amount ? 'text-error' : 'text-gray-400' }}">
                                            {{ $expense->expense_amount ? 'Rp ' . number_format($expense->expense_amount, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="p-2 text-gray-600">
                                            {{ $expense->money_source ?? '-' }}
                                        </td>
                                        <td class="p-2 text-center">
                                            @if ($expense->isAutoGenerated())
                                                <span
                                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-primary-light text-primary gap-1">
                                                    <i class="fa-solid fa-robot"></i>
                                                    Auto
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 gap-1">
                                                    <i class="fa-solid fa-user"></i>
                                                    Manual
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-2 text-center">
                                            @if (!$expense->isAutoGenerated())
                                                <div class="flex flex-col gap-2">
                                                    <button type="button"
                                                        onclick="openModal('editModal-{{ $expense->id }}')"
                                                        class="flex items-center justify-center gap-2 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-1 rounded-lg transition-colors duration-200">
                                                        <i class="fa-solid fa-pen w-4 h-4"></i>
                                                        Edit
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-sm">
                                                    <i class="fa-solid fa-lock"></i>
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center p-4 text-gray-500">Data tidak ditemukan.
                                        </td>
                                    </tr>
                                @endforelse

                                {{-- Grand Total Row --}}
                                @if ($expenseReports->isNotEmpty())
                                    <tr
                                        class="bg-gradient-to-r from-primary/20 to-primary/10 border-t-4 border-primary font-bold text-base">
                                        <td colspan="6" class="p-3 text-right text-gray-800">
                                            TOTAL KESELURUHAN
                                        </td>
                                        <td class="p-3 text-right text-success font-bold text-lg">
                                            Rp {{ number_format($totals->total_income ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="p-3 text-right text-error font-bold text-lg">
                                            Rp {{ number_format($totals->total_expense ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td colspan="3" class="p-3">
                                            <div class="text-right">
                                                <span class="text-gray-700">Saldo: </span>
                                                <span
                                                    class="font-bold text-lg {{ $totals->balance >= 0 ? 'text-success' : 'text-error' }}">
                                                    Rp {{ number_format($totals->balance, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        {{-- Pagination --}}
        <div class="flex mt-4 justify-center">
            <div class="flex items-center gap-3 bg-white border border-gray-300 rounded-lg px-4 py-2 shadow-sm">
                <a href="{{ $expenseReports->appends(request()->query())->previousPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ $expenseReports->onFirstPage() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &lt;
                </a>

                <span class="text-sm font-medium text-gray-700">
                    {{ $expenseReports->currentPage() }}
                    <span class="text-gray-400">/</span>
                    {{ $expenseReports->lastPage() }}
                </span>

                <a href="{{ $expenseReports->appends(request()->query())->nextPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ !$expenseReports->hasMorePages() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &gt;
                </a>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <x-modal id="addModal" title="Tambah Laporan Pengeluaran" action="{{ route('expense-report.store') }}"
        method="POST" buttonText="Simpan">

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Kategori Pengeluaran <span class="text-error">*</span></label>
            <select name="transaction_category_id" class="w-full border rounded p-2" required>
                <option value="">-- Pilih Kategori --</option>
                @foreach ($categories->where('type', 'EXPENSE') as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="transaction_date" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Keterangan <span class="text-error">*</span></label>
            <textarea name="description" class="w-full border rounded p-2" rows="3"
                placeholder="Contoh: Belanja ATK, Sampah Cemara, dll" required></textarea>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Jumlah Pengeluaran <span class="text-error">*</span></label>
            <input type="number" name="expense_amount" class="w-full border rounded p-2" placeholder="Contoh: 50000"
                required min="0">
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">No. Faktur (Opsional)</label>
            <input type="text" name="invoice_number" class="w-full border rounded p-2" placeholder="Contoh: INV-001">
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Sumber Uang (Opsional)</label>
            <input type="text" name="money_source" class="w-full border rounded p-2"
                placeholder="Contoh: Kas Perusahaan">
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Catatan (Opsional)</label>
            <textarea name="notes" class="w-full border rounded p-2" rows="2" placeholder="Catatan tambahan..."></textarea>
        </div>
    </x-modal>

    {{-- Modal Edit untuk setiap expense (hanya yang manual) --}}
    @foreach ($expenseReports as $expense)
        @if (!$expense->isAutoGenerated())
            <x-modal id="editModal-{{ $expense->id }}" title="Edit Laporan Pengeluaran"
                action="{{ route('expense-report.update', $expense->id) }}" method="PUT" buttonText="Update">

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Kategori Pengeluaran <span class="text-error">*</span></label>
                    <select name="transaction_category_id" class="w-full border rounded p-2" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories->where('type', 'EXPENSE') as $cat)
                            <option value="{{ $cat->id }}"
                                {{ $expense->transaction_category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
                    <input type="date" name="transaction_date" class="w-full border rounded p-2"
                        value="{{ $expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('Y-m-d') : '' }}"
                        required>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Keterangan <span class="text-error">*</span></label>
                    <textarea name="description" class="w-full border rounded p-2" rows="3" required>{{ $expense->description }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Jumlah Pengeluaran <span class="text-error">*</span></label>
                    <input type="number" name="expense_amount" class="w-full border rounded p-2"
                        value="{{ $expense->expense_amount }}" required min="0">
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">No. Faktur (Opsional)</label>
                    <input type="text" name="invoice_number" class="w-full border rounded p-2"
                        value="{{ $expense->invoice_number }}">
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Sumber Uang (Opsional)</label>
                    <input type="text" name="money_source" class="w-full border rounded p-2"
                        value="{{ $expense->money_source }}">
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Catatan (Opsional)</label>
                    <textarea name="notes" class="w-full border rounded p-2" rows="2">{{ $expense->notes }}</textarea>
                </div>
            </x-modal>
        @endif
    @endforeach

    {{-- Modal Delete Confirmation --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" action="#" method="POST" buttonText="Hapus"
        buttonClass="bg-btn-delete hover:bg-btn-delete-hover">
        <p class="text-gray-700 mb-4">Apakah Anda yakin ingin menghapus data yang dipilih?</p>
        <p class="text-sm text-gray-500">
            <i class="fa-solid fa-info-circle"></i> Data yang auto-generated dari sales report tidak akan dihapus.
        </p>
    </x-modal>

    @push('scripts')
        <script>
            // Select All Checkbox
            document.getElementById('selectAll').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.expense-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });

            // Print Dropdown Toggle
            const printBtn = document.getElementById('printDropdownButton');
            const printMenu = document.getElementById('printDropdownMenu');

            if (printBtn && printMenu) {
                printBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    printMenu.classList.toggle('hidden');
                });

                document.addEventListener('click', function(e) {
                    if (!printMenu.contains(e.target) && !printBtn.contains(e.target)) {
                        printMenu.classList.add('hidden');
                    }
                });
            }

            // Handle Delete Modal
            function openModal(modalId) {
                if (modalId === 'deleteModal') {
                    const selectedCheckboxes = document.querySelectorAll('.expense-checkbox:checked');
                    if (selectedCheckboxes.length === 0) {
                        alert('Pilih minimal satu data untuk dihapus!');
                        return;
                    }
                    const modal = document.getElementById(modalId);
                    const form = document.getElementById('deleteForm');
                    modal.querySelector('form').action = form.action;

                    // Copy selected checkboxes to modal form
                    const modalForm = modal.querySelector('form');
                    modalForm.innerHTML = '@csrf @method('DELETE')';
                    selectedCheckboxes.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'selected_expenses[]';
                        input.value = cb.value;
                        modalForm.appendChild(input);
                    });
                }

                document.getElementById(modalId).classList.remove('hidden');
            }

            function closeModal(modalId) {
                document.getElementById(modalId).classList.add('hidden');
            }

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-backdrop')) {
                    e.target.querySelector('.modal-container').closest('[id$="Modal"]').classList.add('hidden');
                }
            });
        </script>
    @endpush
@endsection
