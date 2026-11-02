{{--
    Payroll Table Component (gaya Filament)

    Toolbar (aksi massal kiri; filter bulan/tahun/minggu/proyek + pencarian
    kanan) + tabel + paginasi dalam satu kartu.

    Data dikelompokkan per proyek + periode. Setiap kelompok (satu lembar
    payroll = satu proyek + satu minggu) berupa satu baris header berisi
    ringkasan agregat (jumlah karyawan, upah bersih, kasbon, biaya lain-lain,
    total dibayarkan, status draft/paid) dan bisa di-collapse/expand. Detail
    per karyawan tampil pada baris-baris di bawah header grup.

    Data yang diterima:
    - $payrollGroups : paginator grup hasil PayrollService::groupPayrollsForView

    Hook JS (resources/js/pages/sdm/payroll/index.js): #selectAll,
    .group-select-all (data-group-index), input[name="ids[]"].payroll-checkbox
    (data-group-index, data-status), #delete-button, #bulk-pay-button,
    togglePayrollGroup(i) + .payroll-group-rows-{i} / .group-chevron-{i},
    showAdditionalCostDetail(btn), select[name="month"], select[name="year"],
    #filter_week_number, #filter_project_name, form#deleteForm, form#bulkPayForm,
    #additionalCostDetailModal (+ isi #additional-cost-detail-*).
--}}
@php
    $indexUrl = route('payroll.index');
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year')
        || request()->filled('week_number') || request()->filled('project_name');
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

            {{-- Bayar Terpilih: aktif bila ada pilihan berstatus draft (JS) --}}
            <x-fi.button id="bulk-pay-button" color="success" size="sm" icon="banknotes" disabled
                onclick="openModal('bulkPayModal')">
                Bayar Terpilih
            </x-fi.button>

            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form GET: filter langsung submit saat diganti, pencarian saat Enter --}}
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

            {{-- Filter Proyek: searchable (10 item per load) dari Rekap Proyek --}}
            <x-fi.project-dropdown class="col-span-2 sm:w-56" :value="request('project_name')"
                dropdown-id="filter_project_name" all-option="Semua Proyek" placeholder="Semua Proyek" :auto-submit="true" />

            <x-fi.search-input :value="request('search')" placeholder="Cari karyawan..." class="col-span-2 sm:w-56" />

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
                    <x-fi.table.th dense>Proyek</x-fi.table.th>
                    <x-fi.table.th dense align="center">Periode</x-fi.table.th>
                    <x-fi.table.th dense align="end">Upah/Hari</x-fi.table.th>
                    <x-fi.table.th dense align="center">Hari Masuk</x-fi.table.th>
                    <x-fi.table.th dense align="end">Potongan Kasbon</x-fi.table.th>
                    <x-fi.table.th dense align="end">Lembur</x-fi.table.th>
                    <x-fi.table.th dense align="end">Upah Bersih</x-fi.table.th>
                    <x-fi.table.th dense align="center">Status</x-fi.table.th>
                    <x-fi.table.th dense align="center">Aksi</x-fi.table.th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                @forelse ($payrollGroups as $groupIndex => $group)
                    @php
                        $gStart = \Carbon\Carbon::parse($group['period_start_date']);
                        $gEnd = \Carbon\Carbon::parse($group['period_end_date']);
                    @endphp

                    {{-- ── BARIS HEADER GRUP (proyek + periode) ── --}}
                    <tr class="bg-gray-50/70">
                        <td colspan="11" class="p-0">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-2 py-2.5 sm:ps-4 sm:pe-4">
                                <input type="checkbox" class="group-select-all fi-checkbox"
                                    data-group-index="{{ $groupIndex }}" title="Pilih semua payroll dalam grup ini">

                                <div class="flex min-w-0 items-center gap-2">
                                    <x-fi.icon name="folder-open" class="h-5 w-5 shrink-0 text-primary" />
                                    <span class="whitespace-normal font-semibold">{{ $group['project_name'] }}</span>
                                </div>

                                <span class="text-sm text-gray-500">
                                    @if ($gStart->month === $gEnd->month && $gStart->year === $gEnd->year)
                                        {{ $gStart->format('d') }}-{{ $gEnd->format('d M Y') }}
                                    @elseif ($gStart->year === $gEnd->year)
                                        {{ $gStart->format('d M') }} - {{ $gEnd->format('d M Y') }}
                                    @else
                                        {{ $gStart->format('d M Y') }} - {{ $gEnd->format('d M Y') }}
                                    @endif
                                    @if ($group['week_number'])
                                        · Minggu {{ $group['week_number'] }}
                                    @endif
                                    · {{ $group['count'] }} karyawan
                                </span>

                                <div class="ms-auto flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                    <span class="text-gray-500">Upah Bersih
                                        <strong class="ms-1 font-semibold text-gray-950 tabular-nums">{{ $rupiah($group['total_net']) }}</strong></span>

                                    @if (($group['total_kasbon'] ?? 0) > 0)
                                        <span class="text-gray-500">Kasbon
                                            <strong class="ms-1 font-semibold text-red-600 tabular-nums">{{ $rupiah($group['total_kasbon']) }}</strong></span>
                                    @endif

                                    @if (($group['additional_cost_count'] ?? 0) > 0)
                                        <button type="button" onclick="showAdditionalCostDetail(this)"
                                            class="additional-cost-badge inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 transition hover:brightness-95"
                                            data-title="{{ $group['project_name'] }}"
                                            data-period="{{ $gStart->format('d M Y') }} - {{ $gEnd->format('d M Y') }}"
                                            data-items='{{ json_encode($group['additional_costs']->values(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}'
                                            title="Lihat rincian biaya lain-lain">
                                            <x-fi.icon name="receipt-percent" class="h-4 w-4" />
                                            {{ $group['additional_cost_count'] }} biaya lain-lain
                                        </button>
                                    @endif

                                    <span class="text-gray-500">Total Dibayarkan
                                        <strong class="ms-1 font-semibold text-emerald-700 tabular-nums">{{ $rupiah($group['total_dibayarkan']) }}</strong></span>

                                    {{-- Status ringkas grup --}}
                                    @if ($group['paid_count'] === 0)
                                        <x-fi.badge color="warning">Draft</x-fi.badge>
                                    @elseif ($group['draft_count'] === 0)
                                        <x-fi.badge color="success">Paid</x-fi.badge>
                                    @else
                                        <x-fi.badge color="primary">{{ $group['draft_count'] }} Draft · {{ $group['paid_count'] }} Paid</x-fi.badge>
                                    @endif

                                    {{-- Tombol collapse/expand grup --}}
                                    <button type="button" onclick="togglePayrollGroup({{ $groupIndex }})"
                                        class="group-toggle flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-500"
                                        title="Tampilkan / sembunyikan karyawan">
                                        <x-fi.icon name="chevron-down" class="group-chevron-{{ $groupIndex }} h-5 w-5 transition-transform" />
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- ── BARIS PER KARYAWAN dalam grup ── --}}
                    @foreach ($group['payrolls'] as $payroll)
                        @php
                            $isDraft = $payroll->status === 'draft';
                            $payrollProject = $payroll->project_name ?: ($payroll->employee->project_name ?? null);
                        @endphp
                        <tr class="payroll-group-rows-{{ $groupIndex }} bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-2 py-3 sm:ps-4">
                                <input type="checkbox" name="ids[]" value="{{ $payroll->id }}"
                                    data-group-index="{{ $groupIndex }}" data-status="{{ $payroll->status }}"
                                    class="payroll-checkbox fi-checkbox" title="Pilih payroll {{ $payroll->employee->name }}">
                            </td>

                            <x-fi.table.td dense class="font-medium">{{ $payroll->employee->name }}</x-fi.table.td>

                            {{-- Proyek --}}
                            <x-fi.table.td dense class="max-w-[12rem] whitespace-normal">
                                @if ($payrollProject)
                                    <x-fi.badge color="primary" class="whitespace-normal text-start">{{ $payrollProject }}</x-fi.badge>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </x-fi.table.td>

                            {{-- Periode --}}
                            <x-fi.table.td dense align="center" class="text-gray-700">
                                @if ($payroll->period_start_date && $payroll->period_end_date)
                                    @php
                                        $start = \Carbon\Carbon::parse($payroll->period_start_date);
                                        $end = \Carbon\Carbon::parse($payroll->period_end_date);
                                    @endphp
                                    @if ($start->month === $end->month)
                                        {{ $start->format('d') }}-{{ $end->format('d M Y') }}
                                    @else
                                        {{ $start->format('d M') }} - {{ $end->format('d M Y') }}
                                    @endif
                                @else
                                    {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month, 1)->format('M Y') }}
                                    @if ($payroll->week_number)
                                        <div class="text-xs text-gray-500">Minggu {{ $payroll->week_number }}</div>
                                    @endif
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td dense align="end" class="tabular-nums">{{ $rupiah($payroll->base_salary) }}</x-fi.table.td>
                            <x-fi.table.td dense align="center" class="font-medium">{{ $payroll->present_days }} hari</x-fi.table.td>
                            <x-fi.table.td dense align="end" class="tabular-nums text-red-600">
                                @if ($payroll->kasbon_deduction)
                                    {{ $rupiah($payroll->kasbon_deduction) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </x-fi.table.td>
                            <x-fi.table.td dense align="end" class="tabular-nums text-emerald-700">{{ $rupiah($payroll->overtime_total) }}</x-fi.table.td>
                            <x-fi.table.td dense align="end" class="font-semibold tabular-nums">{{ $rupiah($payroll->net_salary) }}</x-fi.table.td>
                            <x-fi.table.td dense align="center">
                                <x-fi.badge :color="$isDraft ? 'warning' : 'success'">{{ $isDraft ? 'Draft' : 'Paid' }}</x-fi.badge>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td dense align="center">
                                <x-fi.dropdown label="Aksi payroll {{ $payroll->employee->name }}" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="eye" onclick="openModal('detailModal-{{ $payroll->id }}')">
                                            Lihat Detail
                                        </x-fi.dropdown.item>
                                        <x-fi.dropdown.item icon="pencil-square"
                                            :onclick="$isDraft ? 'openModal(\'editModal-' . $payroll->id . '\')' : null"
                                            :disabled="! $isDraft" :hint="$isDraft ? null : 'Sudah dibayar'">
                                            Edit
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger" :data-delete-row="$payroll->id"
                                            :data-delete-row-label="$payroll->employee->name . ' · ' . $group['project_name']">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="11">
                            @if ($isFiltered)
                                <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                    description="Tidak ada payroll yang cocok dengan filter/pencarian.">
                                    <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                </x-fi.empty-state>
                            @else
                                <x-fi.empty-state icon="credit-card" heading="Data tidak ditemukan" />
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ─── Paginasi ─────────────────────────────────────────────────────────── --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$payrollGroups" />
    </div>
</x-fi.card>

{{-- Form untuk Bulk Delete (ids[] disalin JS dari checkbox terpilih) --}}
<form id="deleteForm" method="POST" action="{{ route('payroll.destroy') }}" class="hidden">
    @csrf
    @method('DELETE')
</form>

{{-- Form untuk Bulk Pay (ids[] + payment_date disalin JS) --}}
<form id="bulkPayForm" method="POST" action="{{ route('payroll.bulk-pay') }}" class="hidden">
    @csrf
    @method('PATCH')
</form>

{{-- Modal Detail Biaya Lain-lain (shared, diisi via JS showAdditionalCostDetail) --}}
<x-fi.modal id="additionalCostDetailModal" heading="Biaya Lain-lain" icon="receipt-percent" width="md">
    <div class="grid gap-y-4">
        <div>
            <p class="font-semibold text-gray-950" id="additional-cost-detail-title"></p>
            <p class="text-sm text-gray-500" id="additional-cost-detail-period"></p>
        </div>

        <div class="overflow-hidden rounded-lg ring-1 ring-gray-950/5">
            <table class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-start font-semibold text-gray-950">No</th>
                        <th class="px-3 py-2 text-start font-semibold text-gray-950">Nama Biaya</th>
                        <th class="px-3 py-2 text-end font-semibold text-gray-950">Jumlah</th>
                    </tr>
                </thead>
                <tbody id="additional-cost-detail-body" class="divide-y divide-gray-200 [&_td]:px-3 [&_td]:py-2"></tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="2" class="px-3 py-2 text-end font-semibold text-gray-950">Total</td>
                        <td class="px-3 py-2 text-end font-semibold text-primary tabular-nums" id="additional-cost-detail-total"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-fi.modal>
