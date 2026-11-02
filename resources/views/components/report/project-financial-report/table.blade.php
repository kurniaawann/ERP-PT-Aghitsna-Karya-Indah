{{-- Tabel Laporan Keuangan Proyek (gaya Filament): toolbar (hapus massal +
     filter bulan/tahun + pencarian), tabel, paginasi.

     Props:
     - recaps : Paginator ProjectRecap (dengan relasi financialReport.items)

     Hook JS (project-financial-report/index.js): form#deleteForm, #selectAll,
     input[name="selected_recaps[]"], #delete-button. --}}
@props(['recaps'])

@php
    $indexUrl = route('project-financial-report.index');
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_recaps]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_recaps]:checked').length">

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

        {{-- Form GET: bulan/tahun langsung submit saat diganti, pencarian saat Enter --}}
        <form method="GET" action="{{ $indexUrl }}" id="filterForm"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari nama proyek atau lokasi..." class="col-span-2 sm:w-72" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark"
                    class="justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    <form id="deleteForm" method="POST" action="{{ route('recap-proyek.destroySelected') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">

                {{-- Header Tabel --}}
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-2 py-3 sm:ps-4">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th>No</x-fi.table.th>
                        <x-fi.table.th>Tanggal</x-fi.table.th>
                        <x-fi.table.th>Nama Proyek</x-fi.table.th>
                        <x-fi.table.th>Lokasi</x-fi.table.th>
                        <x-fi.table.th align="end">Saldo</x-fi.table.th>
                        <x-fi.table.th>Status & Progress</x-fi.table.th>
                        <x-fi.table.th align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($recaps as $recap)
                        @php
                            $report = $recap->financialReport;
                            $totalIncome = (int) ($report ? $report->items->where('is_informational', false)->sum('income_amount') : 0);
                            $totalExpense = (int) ($report ? $report->items->where('is_informational', false)->sum('expense_amount') : 0);
                            $balance = $totalIncome - $totalExpense;

                            $recapPaid = $recap->getTotalPaidAmount();
                            $recapProgress = $recap->getProgressPercent();

                            // Lunas → hijau; sebagian dibayar → kuning; belum ada pembayaran → merah
                            if ($recap->isFullyPaid()) {
                                $recapStatusLabel = 'Sudah Lunas';
                                $recapStatusColor = 'success';
                                $recapProgressColor = 'bg-emerald-500';
                            } elseif ($recapPaid > 0) {
                                $recapStatusLabel = 'Sebagian';
                                $recapStatusColor = 'warning';
                                $recapProgressColor = 'bg-amber-400';
                            } else {
                                $recapStatusLabel = 'Belum Ada';
                                $recapStatusColor = 'danger';
                                $recapProgressColor = 'bg-red-400';
                            }
                        @endphp

                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-2 py-4 sm:ps-4">
                                <input type="checkbox" name="selected_recaps[]" value="{{ $recap->id }}"
                                    class="fi-checkbox" title="Pilih {{ $recap->project_name }}">
                            </td>

                            <x-fi.table.td>
                                <button type="button" onclick="openModal('detailModal-{{ $recap->id }}')"
                                    class="font-medium text-primary hover:underline" title="Lihat detail">
                                    {{ $recap->id }}
                                </button>
                            </x-fi.table.td>

                            <x-fi.table.td class="text-gray-700">
                                {{ $recap->created_at ? \Carbon\Carbon::parse($recap->created_at)->format('d/m/Y') : '-' }}
                            </x-fi.table.td>

                            <x-fi.table.td class="min-w-[10rem] max-w-xs whitespace-normal font-medium">
                                <div class="line-clamp-2" title="{{ $recap->project_name }}">{{ $recap->project_name }}</div>
                            </x-fi.table.td>

                            <x-fi.table.td class="min-w-[8rem] max-w-[14rem] whitespace-normal text-gray-700">
                                {{ $recap->location ?: '-' }}
                            </x-fi.table.td>

                            <x-fi.table.td align="end" :class="'font-semibold tabular-nums ' . ($balance >= 0 ? 'text-emerald-600' : 'text-red-600')">
                                Rp {{ number_format($balance, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- Status + progress pembayaran rekap --}}
                            <x-fi.table.td>
                                <div class="grid w-24 gap-y-1.5">
                                    <x-fi.badge :color="$recapStatusColor" class="justify-self-start">{{ $recapStatusLabel }}</x-fi.badge>
                                    <div class="flex items-center gap-1.5" title="Progress pembayaran {{ $recapProgress }}%">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-200">
                                            <div class="h-full rounded-full {{ $recapProgressColor }}" style="width: {{ $recapProgress }}%"></div>
                                        </div>
                                        <span class="text-xs font-medium tabular-nums text-gray-500">{{ $recapProgress }}%</span>
                                    </div>
                                </div>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $recap->project_name }}" width="w-56" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="eye" onclick="openModal('detailModal-{{ $recap->id }}')">
                                            Lihat Detail
                                        </x-fi.dropdown.item>
                                        <x-fi.dropdown.item icon="pencil-square" :href="route('project-financial-report.edit', $recap)">
                                            Edit
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    {{-- Export dibuka sebagai pratinjau di dalam halaman (data-preview) --}}
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="document-arrow-down" :href="route('project-financial-report.export.pdf', $recap)"
                                            data-preview data-preview-title="Laporan Keuangan {{ $recap->project_name }} (PDF)">
                                            Export PDF
                                        </x-fi.dropdown.item>
                                        <x-fi.dropdown.item icon="table-cells" :href="route('project-financial-report.export.excel', $recap)"
                                            data-preview data-preview-title="Laporan Keuangan {{ $recap->project_name }} (Excel)">
                                            Export Excel
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    {{-- Aksi destruktif dipisah di paling bawah --}}
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            data-delete-row="{{ $recap->id }}"
                                            data-delete-row-label="{{ $recap->id }} · {{ $recap->project_name }}">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada laporan keuangan proyek yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="document-currency-dollar" heading="Data laporan keuangan proyek tidak ditemukan"
                                        description="Laporan dibuat otomatis saat Rekap Proyek dibuat." />
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
        <x-fi.pagination :paginator="$recaps" />
    </div>
</x-fi.card>
