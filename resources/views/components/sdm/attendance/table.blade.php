{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN TABEL ABSENSI (gaya Filament)
     Toolbar (hapus massal kiri; filter bulan/tahun/minggu + pencarian kanan)
     + tabel + paginasi dalam satu kartu.

     Hook JS (resources/js/pages/sdm/attendance/index.js): #selectAll,
     input[name="ids[]"], #delete-button, form#deleteForm, select[name="month"],
     select[name="year"], #filter_week_number (opsi minggu dimuat via AJAX).
     Parameter: $attendances (paginator Attendance)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php
    $indexUrl = route('attendance.index');
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year') || request()->filled('week_number');
    $statuses = [
        'hadir' => ['Hadir', 'success'],
        'izin' => ['Izin', 'primary'],
        'sakit' => ['Sakit', 'warning'],
        'cuti' => ['Cuti', 'gray'],
        'lembur' => ['Lembur', 'purple'],
    ];
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
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form GET: bulan/tahun/minggu langsung submit saat diganti, pencarian saat Enter --}}
        <form method="GET" action="{{ $indexUrl }}"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
            </div>

            {{-- Filter Minggu: opsi diisi JS via route payroll.get-weeks (bergantung bulan + tahun) --}}
            <div class="col-span-2 sm:w-56">
                <x-fi.select name="week_number" id="filter_week_number" aria-label="Filter Minggu" placeholder="Semua Minggu"
                    onchange="this.form.requestSubmit()" data-fi-select />
            </div>

            <x-fi.search-input :value="request('search')" placeholder="Cari absensi..." class="col-span-2 sm:w-64" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark"
                    class="justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    {{-- ─── Form Hapus Massal + Tabel ────────────────────────────────────────── --}}
    <form id="deleteForm" method="POST" action="{{ route('attendance.destroy') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-3 py-3.5 sm:ps-6">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th>Nama Karyawan</x-fi.table.th>
                        <x-fi.table.th align="center">Tanggal</x-fi.table.th>
                        <x-fi.table.th align="center">Status</x-fi.table.th>
                        <x-fi.table.th>Keterangan</x-fi.table.th>
                        <x-fi.table.th align="center" class="w-1">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($attendances as $attendance)
                        @php [$statusLabel, $statusColor] = $statuses[$attendance->status] ?? [ucfirst((string) $attendance->status), 'gray']; @endphp
                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-3 py-4 sm:ps-6">
                                <input type="checkbox" name="ids[]" value="{{ $attendance->id }}" class="fi-checkbox"
                                    title="Pilih absensi {{ $attendance->employee->name ?? '' }}">
                            </td>
                            <x-fi.table.td class="font-medium">{{ $attendance->employee->name ?? '-' }}</x-fi.table.td>
                            <x-fi.table.td align="center" class="text-gray-700">
                                {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('d/m/Y') }}
                            </x-fi.table.td>
                            <x-fi.table.td align="center">
                                @if ($attendance->status)
                                    <x-fi.badge :color="$statusColor">{{ $statusLabel }}</x-fi.badge>
                                @endif
                            </x-fi.table.td>
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal text-gray-700">{{ $attendance->notes ?? '-' }}</x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi absensi {{ $attendance->id }}" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square"
                                            onclick="openModal('editModal-{{ $attendance->id }}')">
                                            Edit
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger" :data-delete-row="$attendance->id"
                                            :data-delete-row-label="($attendance->employee->name ?? '-') . ' · ' . \Carbon\Carbon::parse($attendance->attendance_date)->format('d/m/Y')">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada absensi yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="calendar-days" heading="Data tidak ditemukan" />
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
        <x-fi.pagination :paginator="$attendances" />
    </div>
</x-fi.card>
