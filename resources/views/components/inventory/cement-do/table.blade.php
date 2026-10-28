{{-- Table DO Semen (gaya Filament, master-detail: baris DO + baris Data Semen).
     Toolbar: aksi massal (muncul saat ada DO dicentang) + filter bulan/tahun +
     pencarian; paginasi di bawah. Baris DO menampilkan agregat (total volume,
     subtotal) pada kolom yang sama dengan baris Data Semen di bawahnya.
     Jumlah DO terpilih dihitung Alpine untuk menampilkan grup aksi massal;
     status disabled #delete-button tetap diatur cement-do/index.js. --}}
@php
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_items]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_items]:checked').length">

    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between sm:px-6">
        {{-- Aksi massal: hanya tampil saat ada DO yang dicentang, agar tidak
             tertukar dengan hapus satu DO lewat menu ⋮ --}}
        <div x-cloak x-show="selected > 0" class="flex items-center gap-3">
            <span class="text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> DO dipilih
            </span>

            {{-- Tombol Hapus Massal (status disabled tetap diatur cement-do/index.js) --}}
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form Filter & Pencarian: submit GET ke route('cement-do.index').
             Bulan/tahun langsung submit saat diganti; pencarian saat Enter. --}}
        <form method="GET" action="{{ route('cement-do.index') }}" id="filterForm"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:items-center lg:ms-auto lg:w-auto">
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari no. DO / nama proyek..."
                class="col-span-2 sm:w-72" />

            @if ($isFiltered)
                <x-fi.link :href="route('cement-do.index')" color="gray" icon="x-mark"
                    class="col-span-2 justify-self-start" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    <form id="deleteForm" method="POST" action="{{ route('cement-does.destroySelected') }}">
        @csrf
        @method('DELETE')

        <div class="overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">

                {{-- Header Tabel --}}
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-2 py-3 sm:ps-4">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th dense>No</x-fi.table.th>
                        <x-fi.table.th dense>Tanggal</x-fi.table.th>
                        <x-fi.table.th dense>Proyek</x-fi.table.th>
                        <x-fi.table.th dense align="end">Volume</x-fi.table.th>
                        <x-fi.table.th dense align="end">Harga/zak</x-fi.table.th>
                        <x-fi.table.th dense align="end">Total</x-fi.table.th>
                        <x-fi.table.th dense>Tgl Lunas</x-fi.table.th>
                        <x-fi.table.th dense align="end">Harga Modal</x-fi.table.th>
                        <x-fi.table.th dense align="end">Profit</x-fi.table.th>
                        <x-fi.table.th dense align="center" class="z-10 bg-gray-50 md:sticky md:right-0 md:shadow-[inset_1px_0_0_0_theme(colors.gray.200)]">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse($cementDeliveryOrders as $cementDeliveryOrder)
                        @php
                            $doDates = array_filter([
                                $cementDeliveryOrder->tanggal_datang ? 'Datang: ' . $cementDeliveryOrder->tanggal_datang->format('d M Y') : null,
                                $cementDeliveryOrder->tanggal_bayar ? 'Bayar: ' . $cementDeliveryOrder->tanggal_bayar->format('d M Y') : null,
                            ]);

                            // Status invoice dihitung dari Data Semen (invoice_number terisi =
                            // sudah masuk invoice), sama seperti modal "Buat Invoice".
                            $cementCount = $cementDeliveryOrder->cements->count();
                            $invoicedCount = $cementDeliveryOrder->cements->filter(fn ($c) => !empty($c->invoice_number))->count();
                            $pendingCount = $cementCount - $invoicedCount;
                        @endphp

                        {{-- Baris Header DO --}}
                        <tr class="bg-gray-50 transition duration-75 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-2 py-3 align-top sm:ps-4">
                                <input type="checkbox" name="selected_items[]"
                                    value="{{ $cementDeliveryOrder->no }}"
                                    class="fi-checkbox" title="Pilih {{ $cementDeliveryOrder->no }}">
                            </td>

                            {{-- No DO + status invoice --}}
                            <x-fi.table.td dense class="align-top">
                                <x-fi.badge color="primary">{{ $cementDeliveryOrder->no }}</x-fi.badge>
                                @if ($cementCount > 0 && $pendingCount === 0)
                                    <div class="mt-1.5 flex items-center gap-1 text-sm text-emerald-600">
                                        <x-fi.icon name="check-circle" class="h-4 w-4" />
                                        Sudah diinvois
                                    </div>
                                @elseif ($invoicedCount > 0)
                                    <div class="mt-1.5 flex items-center gap-1 text-sm text-amber-600"
                                        title="{{ $invoicedCount }} dari {{ $cementCount }} data semen sudah diinvois">
                                        <x-fi.icon name="exclamation-circle" class="h-4 w-4" />
                                        Sebagian diinvois
                                    </div>
                                @elseif ($cementCount > 0)
                                    <div class="mt-1.5 text-sm text-gray-500">Belum diinvois</div>
                                @endif
                            </x-fi.table.td>

                            {{-- Tanggal DO (+ tanggal datang/bayar) --}}
                            <x-fi.table.td dense class="align-top">
                                <div class="font-medium">{{ $cementDeliveryOrder->tanggal?->format('d M Y') }}</div>
                                @foreach ($doDates as $doDate)
                                    <div class="text-sm text-gray-500">{{ $doDate }}</div>
                                @endforeach
                            </x-fi.table.td>

                            {{-- Ringkasan isi DO --}}
                            <x-fi.table.td dense class="align-top text-gray-500">
                                {{ $cementCount }} data semen
                            </x-fi.table.td>

                            {{-- Total volume DO --}}
                            <x-fi.table.td dense align="end" class="align-top font-semibold tabular-nums">
                                {{ number_format($cementDeliveryOrder->total_volume, 0, ',', '.') }}
                                <span class="font-normal text-gray-500">zak</span>
                            </x-fi.table.td>

                            <x-fi.table.td dense />

                            {{-- Subtotal DO --}}
                            <x-fi.table.td dense align="end" class="align-top font-semibold tabular-nums">
                                {{ 'Rp ' . number_format($cementDeliveryOrder->subtotal, 0, ',', '.') }}
                            </x-fi.table.td>

                            <x-fi.table.td dense />

                            {{-- Harga Modal --}}
                            <x-fi.table.td dense align="end" class="align-top tabular-nums text-gray-700">
                                {{ 'Rp ' . number_format($cementDeliveryOrder->harga_modal, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- Profit (minus / harga modal > penjualan tidak ditampilkan) --}}
                            <x-fi.table.td dense align="end" class="align-top font-semibold tabular-nums text-emerald-600">
                                @if ($cementDeliveryOrder->profit >= 0)
                                    {{ 'Rp ' . number_format($cementDeliveryOrder->profit, 0, ',', '.') }}
                                @endif
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ berisi menu aksi DO (sticky di kanan agar tetap
                                 terlihat saat tabel di-scroll). Urutan: aksi DO, aksi invoice,
                                 lalu aksi destruktif terpisah di paling bawah. --}}
                            <x-fi.table.td dense align="center" class="bg-inherit align-top md:sticky md:right-0 md:shadow-[inset_1px_0_0_0_theme(colors.gray.200)]">
                                <x-fi.dropdown label="Aksi {{ $cementDeliveryOrder->no }}" width="w-60" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square"
                                            :href="route('cement-do.edit', $cementDeliveryOrder->no)">
                                            Edit DO
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    <x-fi.dropdown.list label="Invoice">
                                        {{-- Buat Invoice dari DO; nonaktif bila tidak ada data semen yang bisa diinvois --}}
                                        <x-fi.dropdown.item icon="document-plus"
                                            onclick="openGenerateInvoiceModal('{{ $cementDeliveryOrder->no }}')"
                                            :disabled="$pendingCount === 0"
                                            :hint="$pendingCount === 0 ? ($cementCount === 0 ? 'Belum ada data semen' : 'Semua sudah diinvois') : null">
                                            Buat Invoice
                                        </x-fi.dropdown.item>

                                        {{-- Cetak invoice yang pernah dibuat dari DO ini --}}
                                        @foreach ($cementDeliveryOrder->invoices as $invoice)
                                            <x-fi.dropdown.item icon="printer"
                                                :href="route('semen-invoice.print.pdf', $invoice->invoice_number)"
                                                title="Cetak Invoice {{ $invoice->invoice_number }}">
                                                Cetak {{ $invoice->invoice_number }}
                                            </x-fi.dropdown.item>
                                        @endforeach
                                    </x-fi.dropdown.list>

                                    <x-fi.dropdown.list>
                                        {{-- Hapus DO ini saja (modal x-fi.delete-row-modal) --}}
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            data-delete-row="{{ $cementDeliveryOrder->no }}"
                                            data-delete-row-label="DO {{ $cementDeliveryOrder->no }}">
                                            Hapus DO
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>

                        {{-- Baris Detail Data Semen --}}
                        @forelse ($cementDeliveryOrder->cements as $cement)
                            <tr class="bg-white transition duration-75 hover:bg-gray-50">
                                <td class="w-1 px-2 py-3 sm:ps-4"></td>

                                {{-- No Data Semen (+ tanda sudah masuk invoice) --}}
                                <x-fi.table.td dense class="text-gray-500">
                                    <span class="inline-flex items-center gap-1.5 ps-1">
                                        <x-fi.icon name="arrow-turn-down-right" class="h-4 w-4 text-gray-300" />
                                        {{ $cement->no }}
                                        @if (!empty($cement->invoice_number))
                                            <span class="text-emerald-500" title="Sudah masuk invoice {{ $cement->invoice_number }}">
                                                <x-fi.icon name="check-circle" class="h-4 w-4" />
                                                <span class="sr-only">Sudah masuk invoice {{ $cement->invoice_number }}</span>
                                            </span>
                                        @endif
                                    </span>
                                </x-fi.table.td>

                                <x-fi.table.td dense>{{ $cement->tanggal?->format('d M Y') ?: '' }}</x-fi.table.td>

                                <x-fi.table.td dense class="min-w-[10rem] whitespace-normal">
                                    <div>{{ $cement->nama_proyek }}</div>
                                    @if ($cement->name)
                                        <div class="text-sm text-gray-500">{{ $cement->name }}</div>
                                    @endif
                                </x-fi.table.td>

                                <x-fi.table.td dense align="end" class="tabular-nums">
                                    {{ number_format($cement->jumlah, 0, ',', '.') }}
                                    <span class="text-gray-500">{{ $cement->satuan ?: 'zak' }}</span>
                                </x-fi.table.td>

                                <x-fi.table.td dense align="end" class="tabular-nums text-gray-700">
                                    {{ 'Rp ' . number_format($cement->harga, 0, ',', '.') }}
                                </x-fi.table.td>

                                <x-fi.table.td dense align="end" class="font-medium tabular-nums">
                                    {{ 'Rp ' . number_format($cement->total, 0, ',', '.') }}
                                </x-fi.table.td>

                                <x-fi.table.td dense>
                                    {{ $cement->tanggal_lunas?->format('d M Y') ?: '' }}
                                </x-fi.table.td>

                                {{-- Harga modal & profit hanya dihitung per DO, bukan per baris --}}
                                <x-fi.table.td dense />
                                <x-fi.table.td dense />
                                <x-fi.table.td dense class="bg-inherit md:sticky md:right-0 md:shadow-[inset_1px_0_0_0_theme(colors.gray.200)]" />
                            </tr>
                        @empty
                            <tr class="bg-white">
                                <td class="w-1 px-2 py-3 sm:ps-4"></td>
                                <td colspan="9" class="px-2 py-3 text-sm italic text-gray-400">
                                    Tidak ada data semen dalam DO ini.
                                </td>
                                <td class="bg-inherit md:sticky md:right-0 md:shadow-[inset_1px_0_0_0_theme(colors.gray.200)]"></td>
                            </tr>
                        @endforelse
                    @empty
                        {{-- Pesan jika data kosong --}}
                        <tr>
                            <td colspan="11">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada DO Semen yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="route('cement-do.index')">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="truck" heading="Belum ada DO Semen"
                                        description="Klik “Tambah DO” untuk menambahkan delivery order pertama." />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Paginasi (selalu tampil, termasuk saat hanya 1 halaman) --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$cementDeliveryOrders" />
    </div>
</x-fi.card>
