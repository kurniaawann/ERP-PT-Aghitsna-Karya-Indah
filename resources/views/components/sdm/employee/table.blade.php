{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN TABEL KARYAWAN (gaya Filament)
     Toolbar (aksi massal kiri, filter + pencarian kanan) + tabel + paginasi
     dalam satu kartu.

     Hook JS (resources/js/pages/sdm/employee/index.js) yang dipertahankan:
     #selectAll, input[name="ids[]"], #delete-button, #bulk-project-button
     (openBulkProjectModal), form#deleteForm, #filter_project_name,
     #filter_employment_type.
     Parameter: $employees (paginator Employee)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php
    $indexUrl = route('employee.index');
    $isFiltered = request()->filled('search') || request()->filled('project_name') || request()->filled('employment_type');
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

            <x-fi.button id="bulk-project-button" color="gray" size="sm" icon="folder-open" disabled
                onclick="openBulkProjectModal()">
                Ubah Proyek
            </x-fi.button>

            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form GET: proyek & jenis langsung submit saat diganti, pencarian saat Enter --}}
        <form method="GET" action="{{ $indexUrl }}"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">

            {{-- Filter Proyek: searchable (10 item per load) dari Rekap Proyek --}}
            <x-fi.project-dropdown class="col-span-2 sm:w-56" :value="request('project_name')"
                dropdown-id="filter_project_name" all-option="Semua Proyek" placeholder="Semua Proyek" :auto-submit="true" />

            {{-- Filter Jenis Karyawan --}}
            <div class="col-span-2 sm:w-48">
                <x-fi.select name="employment_type" id="filter_employment_type" aria-label="Jenis Karyawan"
                    :value="request('employment_type')"
                    :options="['' => 'Semua Jenis', 'harian' => 'TUKANG PROYEK', 'bulanan' => 'KARYAWAN KANTOR']"
                    onchange="this.form.requestSubmit()" data-fi-select />
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

    {{-- ─── Form Hapus Massal + Tabel ────────────────────────────────────────── --}}
    <form id="deleteForm" method="POST" action="{{ route('employee.destroy') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-2 py-3 sm:ps-4">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th dense>Kode</x-fi.table.th>
                        <x-fi.table.th dense>Nama</x-fi.table.th>
                        <x-fi.table.th dense>Jabatan</x-fi.table.th>
                        <x-fi.table.th dense align="center">Jenis Karyawan</x-fi.table.th>
                        <x-fi.table.th dense align="end">Upah Per Hari</x-fi.table.th>
                        <x-fi.table.th dense>Divisi</x-fi.table.th>
                        <x-fi.table.th dense>Proyek</x-fi.table.th>
                        <x-fi.table.th dense>No. Telp</x-fi.table.th>
                        <x-fi.table.th dense>Alamat</x-fi.table.th>
                        <x-fi.table.th dense align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($employees as $employee)
                        @php $isBulanan = $employee->employment_type === 'bulanan'; @endphp
                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-2 py-4 sm:ps-4">
                                <input type="checkbox" name="ids[]" value="{{ $employee->employee_code }}"
                                    class="fi-checkbox" title="Pilih {{ $employee->employee_code }}">
                            </td>
                            <x-fi.table.td dense class="font-medium text-primary">{{ $employee->employee_code }}</x-fi.table.td>
                            <x-fi.table.td dense class="min-w-[8rem] max-w-[12rem] whitespace-normal font-medium">
                                {{ $employee->name }}
                            </x-fi.table.td>
                            <x-fi.table.td dense class="min-w-[6rem] max-w-[10rem] whitespace-normal text-gray-700">
                                {{ $employee->position ?? '-' }}
                            </x-fi.table.td>
                            <x-fi.table.td dense align="center">
                                <x-fi.badge :color="$isBulanan ? 'primary' : 'warning'">
                                    {{ $isBulanan ? 'KARYAWAN KANTOR' : 'TUKANG PROYEK' }}
                                </x-fi.badge>
                            </x-fi.table.td>
                            <x-fi.table.td dense align="end" class="tabular-nums">
                                @if ($isBulanan)
                                    <span class="text-gray-400">-</span>
                                @else
                                    {{ 'Rp ' . number_format($employee->daily_wage ?? 0, 0, ',', '.') }}
                                @endif
                            </x-fi.table.td>
                            <x-fi.table.td dense>
                                @if ($employee->division)
                                    <x-fi.badge>{{ $employee->division }}</x-fi.badge>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </x-fi.table.td>
                            <x-fi.table.td dense class="min-w-[8rem] max-w-[12rem] whitespace-normal text-gray-700">
                                {{ $employee->project_name ?? '-' }}
                            </x-fi.table.td>
                            <x-fi.table.td dense class="text-gray-700">{{ $employee->phone ?? '-' }}</x-fi.table.td>
                            <x-fi.table.td dense class="min-w-[8rem] max-w-[12rem] whitespace-normal text-gray-700">
                                <div class="line-clamp-2" title="{{ $employee->address }}">{{ $employee->address ?? '-' }}</div>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td dense align="center">
                                <x-fi.dropdown label="Aksi {{ $employee->employee_code }}" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square"
                                            onclick="openModal('editModal-{{ $employee->employee_code }}')">
                                            Edit
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            :data-delete-row="$employee->employee_code"
                                            :data-delete-row-label="$employee->employee_code . ' · ' . $employee->name">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada karyawan yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="identification" heading="Data tidak ditemukan" />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- ─── Paginasi ─────────────────────────────────────────────────────────── --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$employees" />
    </div>
</x-fi.card>
