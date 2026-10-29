{{-- Tabel Kategori Transaksi (gaya Filament): toolbar (hapus massal + filter
     tipe / modul + pencarian), tabel, paginasi. Jumlah baris terpilih dihitung
     Alpine untuk menampilkan grup aksi massal; status disabled #delete-button
     tetap diatur transaction-categories/index.js.

     Kolom: checkbox, no, nama, kode, tipe, modul, urutan, status, aksi.

     Hook JS (transaction-categories/index.js): form#deleteForm, #selectAll,
     input[name="selected_categories[]"].category-checkbox (data-is-used,
     data-category-name), #delete-button (checkAndDelete()), toggleStatus(id). --}}
@php
    $isFiltered = request()->filled('search') || request()->filled('type') || request()->filled('module');
    $typeOptions = ['INCOME' => 'Pemasukan', 'EXPENSE' => 'Pengeluaran'];
    $moduleOptions = ['expense_recap' => 'Rekap Pengeluaran', 'project_finance' => 'Keuangan Proyek'];
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_categories]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_categories]:checked').length">

    {{-- Toolbar: aksi massal (kiri, saat ada yang dicentang) + filter & pencarian (kanan) --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
        <div x-cloak x-show="selected > 0" class="flex shrink-0 items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> kategori dipilih
            </span>

            {{-- Hapus massal: checkAndDelete() cek dulu kategori yang sedang digunakan --}}
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="checkAndDelete()">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form GET: tipe/modul langsung submit saat diganti, pencarian saat Enter --}}
        <form method="GET" action="{{ route('transaction-category.index') }}" id="filterForm"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            <div class="sm:w-44">
                <x-fi.select name="type" aria-label="Filter Tipe" placeholder="Semua Tipe" :options="$typeOptions"
                    :value="request('type')" onchange="this.form.requestSubmit()" data-fi-select />
            </div>
            <div class="sm:w-48">
                <x-fi.select name="module" aria-label="Filter Modul" placeholder="Semua Modul" :options="$moduleOptions"
                    :value="request('module')" onchange="this.form.requestSubmit()" data-fi-select />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari kategori..." class="col-span-2 sm:w-64" />

            @if ($isFiltered)
                <x-fi.link :href="route('transaction-category.index')" color="gray" icon="x-mark"
                    class="col-span-2 justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    <form id="deleteForm" method="POST" action="{{ route('transaction-category.destroySelected') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">

                {{-- Header Tabel --}}
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-3 py-3.5 sm:ps-6">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th align="center" class="w-1">No</x-fi.table.th>
                        <x-fi.table.th>Nama Kategori</x-fi.table.th>
                        <x-fi.table.th>Kode</x-fi.table.th>
                        <x-fi.table.th>Tipe</x-fi.table.th>
                        <x-fi.table.th>Modul</x-fi.table.th>
                        <x-fi.table.th align="center">Urutan</x-fi.table.th>
                        <x-fi.table.th>Status</x-fi.table.th>
                        <x-fi.table.th align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($categories as $index => $category)
                        @php
                            $isUsed = in_array($category->id, $usedCategoryIds ?? []);
                        @endphp

                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            {{-- Checkbox --}}
                            <td class="w-1 px-3 py-4 sm:ps-6">
                                <input type="checkbox" name="selected_categories[]" value="{{ $category->id }}"
                                    data-is-used="{{ $isUsed ? 'true' : 'false' }}"
                                    data-category-name="{{ $category->name }}"
                                    class="fi-checkbox category-checkbox" title="Pilih {{ $category->name }}">
                            </td>

                            {{-- Nomor urut --}}
                            <x-fi.table.td align="center" class="tabular-nums text-gray-500">
                                {{ $categories->firstItem() + $index }}
                            </x-fi.table.td>

                            {{-- Nama Kategori --}}
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal font-medium">
                                {{ $category->name }}
                            </x-fi.table.td>

                            {{-- Kode --}}
                            <x-fi.table.td>
                                <span class="rounded-md bg-gray-50 px-2 py-1 font-mono text-xs text-gray-950 ring-1 ring-inset ring-gray-950/10">
                                    {{ $category->code }}
                                </span>
                            </x-fi.table.td>

                            {{-- Tipe (Pemasukan/Pengeluaran) --}}
                            <x-fi.table.td>
                                @if ($category->type == 'INCOME')
                                    <x-fi.badge color="success" icon="arrow-down">Pemasukan</x-fi.badge>
                                @else
                                    <x-fi.badge color="danger" icon="arrow-up">Pengeluaran</x-fi.badge>
                                @endif
                            </x-fi.table.td>

                            {{-- Modul (Rekap Pengeluaran / Keuangan Proyek) --}}
                            <x-fi.table.td>
                                @if ($category->module === 'project_finance')
                                    <x-fi.badge color="primary" icon="wallet">Keuangan Proyek</x-fi.badge>
                                @else
                                    <x-fi.badge color="gray" icon="receipt-percent">Rekap Pengeluaran</x-fi.badge>
                                @endif
                            </x-fi.table.td>

                            {{-- Urutan --}}
                            <x-fi.table.td align="center" class="tabular-nums text-gray-500">
                                {{ $category->sort_order }}
                            </x-fi.table.td>

                            {{-- Status: toggle switch → toggleStatus(id) (index.js) --}}
                            <x-fi.table.td>
                                <button type="button" role="switch" aria-checked="{{ $category->is_active ? 'true' : 'false' }}"
                                    onclick="toggleStatus({{ $category->id }})"
                                    title="{{ $category->is_active ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan' }}"
                                    class="inline-flex items-center gap-x-2 rounded-full outline-none focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:ring-offset-2">
                                    <span class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 {{ $category->is_active ? 'bg-emerald-500' : 'bg-gray-200' }}">
                                        <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 {{ $category->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </span>
                                    <span class="text-sm font-medium {{ $category->is_active ? 'text-emerald-700' : 'text-gray-500' }}">
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </button>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $category->name }}" width="w-60" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square" onclick="openModal('editModal-{{ $category->id }}')">
                                            Edit
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    {{-- Aksi destruktif dipisah di paling bawah; kategori yang sedang
                                         digunakan transaksi tidak dapat dihapus --}}
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            :data-delete-row="$isUsed ? null : $category->id"
                                            data-delete-row-label="{{ $category->name }} · {{ $category->code }}"
                                            :disabled="$isUsed" :hint="$isUsed ? 'Sedang digunakan transaksi' : null">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada kategori yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="route('transaction-category.index')">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="tag" heading="Belum ada kategori transaksi"
                                        description="Klik “Tambah Kategori” untuk menambahkan kategori pertama." />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Paginasi --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$categories" />
    </div>
</x-fi.card>
