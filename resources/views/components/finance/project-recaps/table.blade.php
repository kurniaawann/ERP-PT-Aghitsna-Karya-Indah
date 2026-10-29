{{-- Tabel Rekap Proyek (gaya Filament): toolbar (hapus massal + filter status
     + pencarian), tabel, paginasi.

     Props:
     - recaps    : Paginator ProjectRecap
     - indexUrl  : URL halaman ini (tujuan filter & Reset)
     - filterTab : key tab halaman Rekap (hidden input "tab"), null di halaman sendiri

     Hook JS (project-recaps/index.js): form#deleteForm, #selectAll,
     input[name="selected_recaps[]"], #delete-button. --}}
@props(['recaps', 'indexUrl', 'filterTab' => null])

@php
    $isSuperAdmin = auth()->user()->role === 'superadmin';
    $isFiltered = request()->filled('search') || request()->filled('status');
    $rupiah = fn ($value) => 'Rp ' . number_format($value, 0, ',', '.');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_recaps]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_recaps]:checked').length">

    {{-- Toolbar: aksi massal (kiri, saat ada yang dicentang) + filter & pencarian (kanan) --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
        <div x-cloak x-show="selected > 0" class="flex shrink-0 items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> rekap dipilih
            </span>

            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form GET: status langsung submit saat diganti, pencarian saat Enter --}}
        <form method="GET" action="{{ $filterTab ? route('rekap.index') : $indexUrl }}"
            class="grid w-full grid-cols-1 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            @if ($filterTab)
                <input type="hidden" name="tab" value="{{ $filterTab }}">
            @endif

            <div class="sm:w-40">
                <x-fi.select name="status" id="status-select" aria-label="Filter Status" :value="request('status')"
                    :options="['' => 'Semua Status', 'lunas' => 'Sudah Lunas', 'belum' => 'Belum Lunas']"
                    onchange="this.form.requestSubmit()" data-fi-select />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari nama proyek..." class="sm:w-64" />

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
                        <x-fi.table.th>Nama Proyek</x-fi.table.th>
                        <x-fi.table.th align="end">Total RAB</x-fi.table.th>
                        @if ($isSuperAdmin)
                            <x-fi.table.th align="end">Uang Masuk (DP)</x-fi.table.th>
                        @endif
                        <x-fi.table.th align="end">Terbayar</x-fi.table.th>
                        <x-fi.table.th align="end">Sisa Pembayaran</x-fi.table.th>
                        <x-fi.table.th>Status & Progress</x-fi.table.th>
                        <x-fi.table.th>File Design</x-fi.table.th>
                        <x-fi.table.th align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($recaps as $recap)
                        @php
                            $recapTotal = $recap->getTotalAmount();
                            $recapDp = $recap->getDpAmount();
                            // Terbayar = bukti rekap + pembayaran invoice tertaut
                            // (tanpa PPN) + uang masuk manual Laporan Keuangan.
                            $recapPaid = $recap->getTotalPaidAmount();
                            $recapInvoicePaid = $recap->getInvoicePaidAmount();
                            $recapRemaining = $recap->getRemainingAmount();
                            $recapProgress = $recap->getProgressPercent();
                            $recapFullyPaid = $recap->isFullyPaid();

                            // Lunas → hijau; sudah ada pembayaran → kuning; belum ada pembayaran → merah
                            if ($recapFullyPaid) {
                                $recapStatusLabel = 'Sudah Lunas';
                                $recapStatusColor = 'success';
                                $recapProgressColor = 'bg-emerald-500';
                            } elseif ($recapPaid > 0) {
                                $recapStatusLabel = 'Belum Lunas';
                                $recapStatusColor = 'warning';
                                $recapProgressColor = 'bg-amber-400';
                            } else {
                                $recapStatusLabel = 'Belum Lunas';
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

                            <x-fi.table.td class="min-w-[10rem] max-w-xs whitespace-normal font-medium">
                                <div class="line-clamp-2" title="{{ $recap->project_name }}">{{ $recap->project_name }}</div>
                            </x-fi.table.td>

                            <x-fi.table.td align="end" class="font-medium tabular-nums">{{ $rupiah($recapTotal) }}</x-fi.table.td>

                            @if ($isSuperAdmin)
                                <x-fi.table.td align="end" class="font-medium tabular-nums text-sky-600">{{ $rupiah($recapDp) }}</x-fi.table.td>
                            @endif

                            <x-fi.table.td align="end" class="tabular-nums">
                                <div class="font-medium text-emerald-600">{{ $rupiah($recapPaid) }}</div>
                                @if ($recapInvoicePaid > 0)
                                    <div class="text-xs text-gray-500" title="Pembayaran lewat invoice tertaut (tanpa PPN)">
                                        via invoice {{ $rupiah($recapInvoicePaid) }}
                                    </div>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td align="end" :class="'font-semibold tabular-nums ' . ($recapRemaining > 0 ? 'text-red-600' : 'text-emerald-600')">
                                {{ $rupiah($recapRemaining) }}
                            </x-fi.table.td>

                            {{-- Status + progress pembayaran --}}
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

                            {{-- File Design: dibuka di modal pratinjau (tanpa tab baru) --}}
                            <x-fi.table.td class="max-w-[11rem]">
                                @if ($recap->hasDesignFile())
                                    <a href="{{ asset('storage/' . $recap->design_file) }}"
                                        onclick="if (window.openFilePreview) { event.preventDefault(); window.openFilePreview(this.href, { title: 'File Design', downloadName: @js($recap->design_file_name ?: basename($recap->design_file)) }); }"
                                        class="inline-flex max-w-full items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                                        title="Lihat/unduh {{ $recap->design_file_name }}">
                                        <x-fi.icon name="photo" class="h-4 w-4 shrink-0" />
                                        <span class="truncate">{{ \Illuminate\Support\Str::limit($recap->design_file_name, 30) }}</span>
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $recap->project_name }}" width="w-60" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="eye" onclick="openModal('detailModal-{{ $recap->id }}')">
                                            Lihat Detail
                                        </x-fi.dropdown.item>
                                        <x-fi.dropdown.item icon="pencil-square" onclick="openModal('editModal-{{ $recap->id }}')">
                                            Edit
                                        </x-fi.dropdown.item>
                                        {{-- Sudah lunas: upload bukti dinonaktifkan (bukti tetap terlihat di Lihat Detail) --}}
                                        <x-fi.dropdown.item icon="arrow-up-tray"
                                            onclick="openPaymentProofUpload('recap', '{{ $recap->id }}', true)"
                                            :disabled="$recapFullyPaid" :hint="$recapFullyPaid ? 'Pembayaran sudah lunas' : null">
                                            Upload Bukti Bayar
                                        </x-fi.dropdown.item>
                                        <x-fi.dropdown.item icon="document-currency-dollar" :href="route('project-financial-report.index')">
                                            Laporan Keuangan
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
                            <td colspan="{{ $isSuperAdmin ? 10 : 9 }}">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada rekap proyek yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="building-office-2" heading="Belum ada rekap proyek"
                                        description="Klik “Tambah Proyek” untuk membuat rekap proyek pertama." />
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
