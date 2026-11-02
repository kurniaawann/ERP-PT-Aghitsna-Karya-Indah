{{--
    Slip Gaji Table Component (gaya Filament)

    Toolbar (aksi massal kiri; filter bulan/tahun + pencarian kanan) + tabel
    + paginasi dalam satu kartu. Satu baris = satu slip (satu karyawan + satu
    periode). Semua status (draft maupun paid) bisa dipilih untuk aksi massal;
    PDF per slip selalu tersedia (menu ⋮ → Cetak PDF).

    Hook JS (resources/js/pages/sdm/salary-slip/index.js): #selectAll,
    input[name="ids[]"].slip-checkbox (data-status), #delete-button,
    #bulk-pay-button, #printSelectedItem / #selectedCountText (di
    x-fi.export-dropdown header), form#deleteForm, form#bulkPayForm.
    Parameter: $slips (paginator SalarySlip)
--}}
@php
    $indexUrl = route('payroll.index', ['tab' => 'salary-slip']);
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year');
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=ids]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=ids]:checked').length">

    {{-- ─── Toolbar: aksi massal (kiri) + filter & pencarian (kanan) ─────────── --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
        <div x-cloak x-show="selected > 0" class="flex flex-wrap items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> data dipilih
            </span>
            <x-fi.button id="bulk-pay-button" color="success" size="sm" icon="banknotes" disabled
                onclick="openModal('bulkPayModal')">
                Bayar Terpilih
            </x-fi.button>
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form GET ke tab Slip Gaji (filter & paginasi tetap di tab ini) --}}
        <form method="GET" action="{{ route('payroll.index') }}"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            {{-- Form GET membuang query string pada action → tab dikirim lewat input --}}
            <input type="hidden" name="tab" value="salary-slip">
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari karyawan..." class="col-span-2 sm:w-64" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark"
                    class="justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    {{-- ─── Tabel ────────────────────────────────────────────────────────────── --}}
    <div class="relative overflow-x-auto">
        <table class="w-full table-auto divide-y divide-gray-200 text-start">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-1 px-2 py-3 sm:ps-4">
                        <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                    </th>
                    <x-fi.table.th dense>Nama Karyawan</x-fi.table.th>
                    <x-fi.table.th dense align="center">Periode</x-fi.table.th>
                    <x-fi.table.th dense align="end">Gaji Pokok</x-fi.table.th>
                    <x-fi.table.th dense align="center">Rekap Absensi</x-fi.table.th>
                    <x-fi.table.th dense align="end">Total Potongan</x-fi.table.th>
                    <x-fi.table.th dense align="end">THP</x-fi.table.th>
                    <x-fi.table.th dense align="center">Status</x-fi.table.th>
                    <x-fi.table.th dense align="center">Aksi</x-fi.table.th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                @forelse ($slips as $slip)
                    @php $isDraft = $slip->status === 'draft'; @endphp
                    <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                        <td class="w-1 px-2 py-4 sm:ps-4">
                            <input type="checkbox" name="ids[]" value="{{ $slip->id }}" data-status="{{ $slip->status }}"
                                class="slip-checkbox fi-checkbox" title="Pilih slip {{ $slip->employee->name ?? $slip->employee_code }}">
                        </td>

                        <x-fi.table.td dense>
                            <div class="font-medium">{{ $slip->employee->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $slip->employee_code }}</div>
                        </x-fi.table.td>

                        <x-fi.table.td dense align="center" class="text-gray-700">
                            {{ $slip->formatted_period }}
                            @if ($slip->payment_date)
                                <div class="text-xs text-gray-500">Dibayar {{ $slip->payment_date->format('d M Y') }}</div>
                            @endif
                        </x-fi.table.td>

                        <x-fi.table.td dense align="end" class="tabular-nums">{{ $rupiah($slip->base_salary) }}</x-fi.table.td>

                        {{-- Rekap Absensi: H / I / S / C / A / L --}}
                        <x-fi.table.td dense align="center">
                            <div class="inline-flex items-center gap-2 text-xs">
                                @foreach (['H' => $slip->present_days, 'I' => $slip->permission_days, 'S' => $slip->sick_days, 'C' => $slip->leave_days, 'A' => $slip->absent_days, 'L' => $slip->libur_days] as $letter => $count)
                                    <span class="inline-flex items-center gap-1" title="{{ $letter }}">
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-gray-100 font-semibold text-gray-700">{{ $letter }}</span>
                                        {{ $count }}
                                    </span>
                                @endforeach
                            </div>
                        </x-fi.table.td>

                        <x-fi.table.td dense align="end" class="tabular-nums text-red-600">
                            @if ($slip->total_deduction > 0)
                                {{ $rupiah($slip->total_deduction) }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </x-fi.table.td>

                        <x-fi.table.td dense align="end" class="font-semibold tabular-nums">{{ $rupiah($slip->net_salary) }}</x-fi.table.td>

                        <x-fi.table.td dense align="center">
                            <x-fi.badge :color="$isDraft ? 'warning' : 'success'">{{ $isDraft ? 'Draft' : 'Paid' }}</x-fi.badge>
                        </x-fi.table.td>

                        {{-- Aksi: tombol ⋮ --}}
                        <x-fi.table.td dense align="center">
                            <x-fi.dropdown label="Aksi slip {{ $slip->employee->name ?? $slip->employee_code }}" class="-my-1.5">
                                <x-fi.dropdown.list>
                                    <x-fi.dropdown.item icon="eye" onclick="openModal('detailModal-{{ $slip->id }}')">
                                        Lihat Detail
                                    </x-fi.dropdown.item>
                                    <x-fi.dropdown.item icon="pencil-square"
                                        :onclick="$isDraft ? 'openModal(\'editModal-' . $slip->id . '\')' : null"
                                        :disabled="! $isDraft" :hint="$isDraft ? null : 'Sudah dibayar'">
                                        Edit Rekap Absensi
                                    </x-fi.dropdown.item>
                                    <x-fi.dropdown.item icon="document-text" :href="route('salary-slips.print.pdf', $slip->id)" data-preview
                                        data-preview-title="Slip Gaji {{ $slip->employee->name ?? $slip->employee_code }} - {{ $slip->formatted_period }}">
                                        Cetak PDF
                                    </x-fi.dropdown.item>
                                </x-fi.dropdown.list>
                                <x-fi.dropdown.list>
                                    <x-fi.dropdown.item icon="trash" color="danger" :data-delete-row="$slip->id"
                                        :data-delete-row-label="($slip->employee->name ?? $slip->employee_code) . ' · ' . $slip->formatted_period">
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
                                    description="Tidak ada slip gaji yang cocok dengan filter/pencarian.">
                                    <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                </x-fi.empty-state>
                            @else
                                <x-fi.empty-state icon="document-text" heading="Data tidak ditemukan" />
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ─── Paginasi ─────────────────────────────────────────────────────────── --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$slips" />
    </div>
</x-fi.card>

{{-- Form untuk Bulk Delete (ids[] disalin JS dari checkbox terpilih) --}}
<form id="deleteForm" method="POST" action="{{ route('salary-slips.destroy') }}" class="hidden">
    @csrf
    @method('DELETE')
</form>

{{-- Form untuk Bulk Pay (ids[] + payment_date disalin JS) --}}
<form id="bulkPayForm" method="POST" action="{{ route('salary-slips.bulk-pay') }}" class="hidden">
    @csrf
    @method('PATCH')
</form>
