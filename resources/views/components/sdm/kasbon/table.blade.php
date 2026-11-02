{{-- ═══════════════════════════════════════════════════════════════════════
     Komponen Tabel Data Kasbon (gaya Filament)
     Toolbar (hapus massal kiri; filter + pencarian kanan) + tabel + paginasi
     dalam satu kartu. Kasbon yang sudah dipotong / terhubung payroll tidak
     bisa dipilih maupun dihapus (checkbox & item Hapus dinonaktifkan).

     Hook JS (resources/js/pages/sdm/kasbon/index.js): #select-all,
     .row-checkbox (name selected_kasbons[]), #delete-button, form#deleteForm,
     form#filterForm, openPayModal(...), openModal('editModal{kode}').
     Parameter: $kasbons (paginator Kasbon)
     ═══════════════════════════════════════════════════════════════════════ --}}
@php
    $indexUrl = route('kasbon.index');
    $isFiltered = request()->hasAny(['search', 'month', 'year', 'status', 'type', 'payment_status', 'project_name']);
    $paymentColors = ['paid' => 'success', 'partial' => 'primary'];
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('.row-checkbox:checked').length"
    x-on:change="selected = $el.querySelectorAll('.row-checkbox:checked').length">

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

        {{-- Form filter: semua filter langsung submit saat diganti, pencarian saat Enter.
             Bulan/tahun berdasar periode payroll (period_start_date), bukan tanggal kasbon. --}}
        <form method="GET" action="{{ $indexUrl }}" id="filterForm"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="document.getElementById('filterForm').submit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="document.getElementById('filterForm').submit()" />
            </div>
            <div class="sm:w-40">
                <x-fi.select name="status" id="status-select" aria-label="Status" :value="request('status')" data-fi-select
                    :options="['' => 'Semua Status', 'pending' => 'Belum Dipotong', 'deducted' => 'Sudah Dipotong']"
                    onchange="document.getElementById('filterForm').submit()" />
            </div>
            <div class="sm:w-48">
                <x-fi.select name="payment_status" id="payment_status-select" aria-label="Status Pembayaran"
                    :value="request('payment_status')" data-fi-select
                    :options="['' => 'Semua Pembayaran', 'unpaid' => 'Belum Dibayar', 'partial' => 'Cicilan Berjalan', 'paid' => 'Lunas']"
                    onchange="document.getElementById('filterForm').submit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.select name="type" id="type-select" aria-label="Jenis" :value="request('type')" data-fi-select
                    :options="['' => 'Semua Jenis', 'personal' => 'Per Orang', 'team' => 'Per Tim']"
                    onchange="document.getElementById('filterForm').submit()" />
            </div>

            {{-- Filter Proyek: searchable (10 item per load) dari Rekap Proyek --}}
            <x-fi.project-dropdown class="sm:w-48" :value="request('project_name')"
                dropdown-id="filter_project_name" all-option="Semua Proyek" placeholder="Semua Proyek" :auto-submit="true" />

            <x-fi.search-input :value="request('search')" placeholder="Cari kasbon..." class="sm:w-56" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark"
                    class="justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    {{-- ─── Form Hapus Massal + Tabel ────────────────────────────────────────── --}}
    <form id="deleteForm" method="POST" action="{{ route('kasbon.destroySelected') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-2 py-3 sm:ps-4">
                            <input type="checkbox" id="select-all" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th dense>Kode</x-fi.table.th>
                        <x-fi.table.th dense>Karyawan/Divisi</x-fi.table.th>
                        <x-fi.table.th dense align="center">Jenis</x-fi.table.th>
                        <x-fi.table.th dense align="center">Tanggal</x-fi.table.th>
                        <x-fi.table.th dense>Proyek</x-fi.table.th>
                        <x-fi.table.th dense align="end">Jumlah</x-fi.table.th>
                        <x-fi.table.th dense align="end">Terbayar</x-fi.table.th>
                        <x-fi.table.th dense align="end">Sisa</x-fi.table.th>
                        <x-fi.table.th dense align="center">Progress</x-fi.table.th>
                        <x-fi.table.th dense align="center">Status Pembayaran</x-fi.table.th>
                        <x-fi.table.th dense align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($kasbons as $kasbon)
                        @php
                            $isTeam = $kasbon->kasbon_type === 'team';
                            $isBulananKasbon = $kasbon->kasbon_type === 'personal'
                                && $kasbon->employee
                                && $kasbon->employee->employment_type === 'bulanan';
                            $locked = $kasbon->status === 'deducted' || $kasbon->live_payroll_payments_count > 0;
                            $canPay = $kasbon->payment_status !== 'paid'
                                && ! ($isTeam && ! empty($kasbon->project_names))
                                && ! $isBulananKasbon;
                            $canEdit = $kasbon->status === 'pending' && $kasbon->payment_status === 'unpaid';
                            $subjectLabel = $isTeam ? 'Divisi ' . $kasbon->division : ($kasbon->employee->name ?? '-');
                        @endphp
                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-2 py-4 sm:ps-4">
                                <input type="checkbox" name="selected_kasbons[]" value="{{ $kasbon->kasbon_code }}"
                                    class="row-checkbox fi-checkbox disabled:cursor-not-allowed disabled:opacity-40"
                                    title="{{ $locked ? 'Kasbon terhubung payroll / sudah dipotong' : 'Pilih ' . $kasbon->kasbon_code }}"
                                    @disabled($locked)>
                            </td>

                            <x-fi.table.td dense class="font-medium text-primary">{{ $kasbon->kasbon_code }}</x-fi.table.td>

                            {{-- Karyawan / Divisi + jenis karyawan --}}
                            <x-fi.table.td dense>
                                @if ($kasbon->kasbon_type === 'personal' && $kasbon->employee)
                                    <div class="font-medium">{{ $kasbon->employee->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $kasbon->employee->employee_code }}</div>
                                    <x-fi.badge :color="$isBulananKasbon ? 'primary' : 'warning'" class="mt-1 !px-1.5 !py-0.5">
                                        {{ $isBulananKasbon ? 'KARYAWAN KANTOR' : 'TUKANG PROYEK' }}
                                    </x-fi.badge>
                                @elseif ($isTeam && $kasbon->division)
                                    <div class="font-medium text-purple-700">Divisi {{ $kasbon->division }}</div>
                                    <div class="text-xs text-gray-500">Kasbon Tim</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td dense align="center">
                                <x-fi.badge :color="$isTeam ? 'purple' : 'primary'">{{ $kasbon->kasbon_type_label }}</x-fi.badge>
                            </x-fi.table.td>

                            <x-fi.table.td dense align="center" class="text-gray-700">
                                {{ $kasbon->kasbon_date ? $kasbon->kasbon_date->format('d/m/Y') : '-' }}
                            </x-fi.table.td>

                            {{-- Proyek (kasbon divisi ber-proyek) --}}
                            <x-fi.table.td dense class="max-w-[11rem] whitespace-normal">
                                @if ($isTeam && ! empty($kasbon->project_names))
                                    <div class="font-medium">{{ implode(', ', $kasbon->project_names) }}</div>
                                    <div class="text-xs text-gray-500">Otomatis lunas saat payroll dibayar</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td dense align="end" class="font-medium tabular-nums">{{ $kasbon->formatted_amount }}</x-fi.table.td>

                            {{-- Terbayar (akumulasi cicilan; karyawan kantor dicicil lewat slip gaji) --}}
                            <x-fi.table.td dense align="end" class="tabular-nums">
                                @if ($isTeam)
                                    <span class="text-gray-400">-</span>
                                @else
                                    {{ $kasbon->formatted_paid_amount }}
                                @endif
                            </x-fi.table.td>

                            {{-- Sisa Pembayaran --}}
                            <x-fi.table.td dense align="end" class="tabular-nums">
                                @if ($isTeam)
                                    <span class="text-gray-400">-</span>
                                @elseif ($kasbon->payment_status === 'paid')
                                    <span class="font-medium text-emerald-700">Rp 0</span>
                                @else
                                    <span class="font-medium {{ $kasbon->remaining_amount > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                        {{ $kasbon->formatted_remaining_amount }}
                                    </span>
                                @endif
                            </x-fi.table.td>

                            {{-- Progress --}}
                            <x-fi.table.td dense align="center">
                                @if ($isTeam)
                                    <span class="text-gray-400">-</span>
                                @else
                                    <div class="mx-auto w-20">
                                        <div class="mb-1 text-xs text-gray-500">{{ $kasbon->progress_percentage }}%</div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-200">
                                            <div class="h-1.5 rounded-full {{ $kasbon->payment_status === 'paid' ? 'bg-emerald-500' : ($kasbon->payment_status === 'partial' ? 'bg-primary' : 'bg-amber-500') }}"
                                                style="width: {{ $kasbon->progress_percentage }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td dense align="center">
                                <x-fi.badge :color="$paymentColors[$kasbon->payment_status] ?? 'warning'">{{ $kasbon->payment_status_label }}</x-fi.badge>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td dense align="center">
                                <x-fi.dropdown label="Aksi {{ $kasbon->kasbon_code }}" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        @if ($canPay)
                                            <x-fi.dropdown.item icon="banknotes"
                                                onclick="openPayModal('{{ $kasbon->kasbon_code }}', '{{ $kasbon->formatted_amount }}', '{{ $kasbon->formatted_remaining_amount }}', {{ $kasbon->remaining_amount }})">
                                                Bayar Cicilan
                                            </x-fi.dropdown.item>
                                        @elseif ($isBulananKasbon && $kasbon->payment_status !== 'paid')
                                            <x-fi.dropdown.item icon="document-text" disabled
                                                hint="Dicicil lewat potongan slip gaji (diatur pada Generate/Edit slip gaji)">
                                                Potong Slip Gaji
                                            </x-fi.dropdown.item>
                                        @endif
                                        <x-fi.dropdown.item icon="pencil-square"
                                            :onclick="$canEdit ? 'openModal(\'editModal' . $kasbon->kasbon_code . '\')' : null"
                                            :disabled="! $canEdit" :hint="$canEdit ? null : 'Hanya kasbon yang belum dibayar & belum dipotong'">
                                            Edit
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            :data-delete-row="$locked ? null : $kasbon->kasbon_code"
                                            :data-delete-row-label="$kasbon->kasbon_code . ' · ' . $subjectLabel"
                                            :disabled="$locked" :hint="$locked ? 'Sudah dipotong / terhubung payroll' : null">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Tidak ada data kasbon"
                                        description="Tidak ada kasbon yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="inbox" heading="Tidak ada data kasbon" />
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
        <x-fi.pagination :paginator="$kasbons" />
    </div>
</x-fi.card>
