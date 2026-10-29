{{-- Tabel Rekap Pengeluaran (admin: "Kas Kantor"), gaya Filament: toolbar
     (hapus massal + filter kategori / bulan / tahun + pencarian), tabel,
     baris total keseluruhan, paginasi.

     Variabel:
     - $expenseRecaps : Paginator ExpenseRecap
     - $categories    : kategori untuk filter
     - $totals        : total_income / total_expense / balance
     - $indexUrl      : URL halaman ini (tujuan filter & Reset)
     - $filterTab     : key tab halaman Rekap (hidden input "tab"), null di halaman sendiri

     Data auto-generated (dari sales report) tidak bisa dicentang/diedit/dihapus.

     Hook JS (expense-recaps/index.js): form#deleteForm, #selectAll,
     input[name="selected_expenses[]"], #delete-button, #category-select,
     #month-select, #year-select. --}}
@php
    $isAdmin = auth()->user()->isAdmin();
    // Kolom Faktur tidak ditampilkan untuk role admin (Kas Kantor)
    $showFaktur = ! $isAdmin;
    $isFiltered = request()->filled('search') || request()->filled('category') || request()->filled('month') || request()->filled('year');
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
    $columnCount = $showFaktur ? 11 : 10;
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_expenses]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_expenses]:checked').length">

    {{-- Toolbar: aksi massal (kiri, saat ada yang dicentang) + filter & pencarian (kanan) --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
        <div x-cloak x-show="selected > 0" class="flex shrink-0 items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> data dipilih
            </span>

            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form GET: kategori/bulan/tahun langsung submit saat diganti (index.js), pencarian saat Enter --}}
        <form method="GET" action="{{ $filterTab ? route('rekap.index') : $indexUrl }}"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            @if ($filterTab)
                <input type="hidden" name="tab" value="{{ $filterTab }}">
            @endif

            <div class="col-span-2 sm:w-52">
                <x-fi.select name="category" id="category-select" aria-label="Filter Kategori" placeholder="Semua Kategori"
                    prefix-icon="tag" data-fi-select>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-fi.select>
            </div>
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari transaksi..." class="col-span-2 sm:w-60" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark"
                    class="col-span-2 justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    <form id="deleteForm" method="POST" action="{{ route('recap-expense.destroySelected') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">

                {{-- Header Tabel --}}
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-2 py-3 sm:ps-4">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua data manual">
                        </th>
                        <x-fi.table.th dense>ID</x-fi.table.th>
                        <x-fi.table.th dense>Kategori</x-fi.table.th>
                        <x-fi.table.th dense>Tanggal</x-fi.table.th>
                        @if ($showFaktur)
                            <x-fi.table.th dense>Faktur</x-fi.table.th>
                        @endif
                        <x-fi.table.th dense>Keterangan</x-fi.table.th>
                        <x-fi.table.th dense align="end">Pemasukan</x-fi.table.th>
                        <x-fi.table.th dense align="end">Pengeluaran</x-fi.table.th>
                        {{-- Role admin: "Sumber Uang" tampil sebagai "Keterangan" (hanya teks UI) --}}
                        <x-fi.table.th dense>{{ $isAdmin ? 'Keterangan' : 'Sumber Uang' }}</x-fi.table.th>
                        <x-fi.table.th dense>Sumber Data</x-fi.table.th>
                        <x-fi.table.th dense align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($expenseRecaps as $expense)
                        @php
                            $isAuto = $expense->isAutoGenerated();
                        @endphp

                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            {{-- Checkbox: hanya untuk data manual --}}
                            <td class="w-1 px-2 py-3 sm:ps-4">
                                @unless ($isAuto)
                                    <input type="checkbox" name="selected_expenses[]" value="{{ $expense->id }}"
                                        class="fi-checkbox" title="Pilih {{ $expense->id }}">
                                @endunless
                            </td>

                            <x-fi.table.td dense class="font-medium text-primary">{{ $expense->id }}</x-fi.table.td>

                            <x-fi.table.td dense class="max-w-[11rem] whitespace-normal">
                                <x-fi.badge :color="$expense->category->type == 'INCOME' ? 'success' : 'danger'" class="!whitespace-normal text-start">
                                    {{ $expense->category->name }}
                                </x-fi.badge>
                            </x-fi.table.td>

                            <x-fi.table.td dense>
                                {{ $expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('d M Y') : '-' }}
                            </x-fi.table.td>

                            {{-- Nomor Faktur (super admin saja) --}}
                            @if ($showFaktur)
                                <x-fi.table.td dense class="text-gray-700">{{ $expense->invoice_number ?? '-' }}</x-fi.table.td>
                            @endif

                            <x-fi.table.td dense class="min-w-[12rem] max-w-xs whitespace-normal">
                                <div class="line-clamp-2" title="{{ $expense->description }}">{{ $expense->description ?? '-' }}</div>
                            </x-fi.table.td>

                            <x-fi.table.td dense align="end" :class="'tabular-nums ' . ($expense->income_amount ? 'font-medium text-emerald-600' : 'text-gray-400')">
                                {{ $expense->income_amount ? $rupiah($expense->income_amount) : '-' }}
                            </x-fi.table.td>

                            <x-fi.table.td dense align="end" :class="'tabular-nums ' . ($expense->expense_amount ? 'font-medium text-red-600' : 'text-gray-400')">
                                {{ $expense->expense_amount ? $rupiah($expense->expense_amount) : '-' }}
                            </x-fi.table.td>

                            <x-fi.table.td dense class="min-w-[8rem] max-w-[12rem] whitespace-normal text-gray-700">
                                {{ $expense->money_source ?? '-' }}
                            </x-fi.table.td>

                            {{-- Sumber Data: Auto / Manual --}}
                            <x-fi.table.td dense>
                                @if ($isAuto)
                                    <x-fi.badge color="primary" icon="cpu-chip">Auto</x-fi.badge>
                                @else
                                    <x-fi.badge color="gray" icon="user">Manual</x-fi.badge>
                                @endif
                            </x-fi.table.td>

                            {{-- Aksi: ⋮ (hanya data manual); data otomatis terkunci --}}
                            <x-fi.table.td dense align="center">
                                @if ($isAuto)
                                    <span class="inline-flex h-9 w-9 items-center justify-center text-gray-400"
                                        title="Data otomatis dari sales report — tidak dapat diubah">
                                        <x-fi.icon name="lock-closed" class="h-5 w-5" />
                                        <span class="sr-only">Data otomatis, tidak dapat diubah</span>
                                    </span>
                                @else
                                    <x-fi.dropdown label="Aksi {{ $expense->id }}" class="-my-1.5">
                                        <x-fi.dropdown.list>
                                            <x-fi.dropdown.item icon="pencil-square"
                                                onclick="openModal('editModal-{{ str_replace('/', '-', $expense->id) }}')">
                                                Edit
                                            </x-fi.dropdown.item>
                                        </x-fi.dropdown.list>

                                        {{-- Aksi destruktif dipisah di paling bawah --}}
                                        <x-fi.dropdown.list>
                                            <x-fi.dropdown.item icon="trash" color="danger"
                                                data-delete-row="{{ $expense->id }}"
                                                data-delete-row-label="{{ $expense->id }}{{ $expense->description ? ' · ' . \Illuminate\Support\Str::limit($expense->description, 40) : '' }}">
                                                Hapus
                                            </x-fi.dropdown.item>
                                        </x-fi.dropdown.list>
                                    </x-fi.dropdown>
                                @endif
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada transaksi yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="banknotes" heading="Belum ada transaksi"
                                        description="Klik tombol “Tambah” untuk mencatat transaksi pertama." />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Baris Total Keseluruhan (semua data sesuai filter) --}}
                @if ($expenseRecaps->isNotEmpty())
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td colspan="{{ $showFaktur ? 6 : 5 }}" class="px-3 py-3 text-end text-sm font-semibold text-gray-950 sm:ps-4">
                                Total Keseluruhan
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-semibold tabular-nums text-emerald-600">
                                {{ $rupiah($totals->total_income ?? 0) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-semibold tabular-nums text-red-600">
                                {{ $rupiah($totals->total_expense ?? 0) }}
                            </td>
                            <td colspan="3" class="whitespace-nowrap px-3 py-3 text-end text-sm sm:pe-6">
                                <span class="text-gray-500">Saldo:</span>
                                <span @class(['font-semibold tabular-nums', 'text-emerald-600' => $totals->balance >= 0, 'text-red-600' => $totals->balance < 0])>
                                    {{ $rupiah($totals->balance) }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </form>

    {{-- Paginasi --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$expenseRecaps" />
    </div>
</x-fi.card>
