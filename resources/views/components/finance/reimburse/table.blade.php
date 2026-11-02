{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN TABEL REIMBURSEMENT (gaya Filament)
     Kartu berisi toolbar (aksi massal di kiri saat ada baris dicentang +
     filter status/bulan/tahun & pencarian di kanan), tabel, dan paginasi.

     Checkbox `ids[]` tersedia di semua baris untuk kedua role (dipakai
     Export Dipilih PDF/Excel di tombol Export header). Untuk admin, aksi
     Setujui/Tolak/Hapus hanya berlaku pada baris berstatus draft (data-status).
     Pengajuan otomatis dari Nota ditandai badge "Dari Nota" + link nota
     (pratinjau PDF di dalam halaman via data-preview).

     Parameter: $reimburses (Paginator Reimburse)
     Hook JS (finance/reimburse/index.js): form#deleteForm, #selectAll,
     input[name="ids[]"] (data-amount, data-status), #delete-button,
     #approval-dropdown-button / #approval-dropdown-menu (admin),
     #selected-info / #selected-count / #selected-total / #selected-draft-info (admin).
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php
    $role = Auth::user()->role;
    $isSuperAdmin = $role === 'superadmin';
    $isAdmin = $role === 'admin';
    $indexUrl = route('reimburse.index');
    $isFiltered = request()->filled('search') || request()->filled('status') || request()->filled('month') || request()->filled('year');
    $statusColors = ['draft' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=ids]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=ids]:checked').length">

    {{-- ─── Toolbar: aksi massal (kiri) + filter & pencarian (kanan) ─────────── --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
        <div x-cloak x-show="selected > 0" class="flex flex-wrap items-center gap-3">

            {{-- Ringkasan pilihan: admin → jumlah + total (diisi JS), super admin → jumlah --}}
            @if ($isAdmin)
                <p id="selected-info" class="hidden text-sm text-gray-500">
                    <span id="selected-count" class="font-semibold text-gray-950">0</span> reimburse terpilih ·
                    Total <strong id="selected-total" class="font-semibold text-gray-950">Rp 0</strong>
                    <span id="selected-draft-info"></span>
                </p>
            @else
                <span class="whitespace-nowrap text-sm text-gray-500">
                    <span class="font-semibold text-gray-950" x-text="selected"></span> data dipilih
                </span>
            @endif

            {{-- Dropdown Persetujuan (Admin): aktif bila ada pilihan berstatus draft --}}
            @if ($isAdmin)
                <div class="relative inline-block text-left">
                    <x-fi.button id="approval-dropdown-button" size="sm" icon="check-circle" disabled aria-haspopup="menu">
                        Aksi Persetujuan
                        <x-fi.icon name="chevron-down" class="-me-0.5 ms-0.5 inline-block h-4 w-4 align-[-2px]" />
                    </x-fi.button>

                    <div id="approval-dropdown-menu" role="menu"
                        class="absolute left-0 z-20 mt-2 hidden w-44 rounded-lg bg-white p-1 shadow-lg ring-1 ring-gray-950/5">
                        <button type="button" role="menuitem" onclick="openModal('approveModal')"
                            class="flex w-full items-center gap-2 rounded-md p-2 text-sm text-gray-700 transition-colors duration-75 hover:bg-gray-50">
                            <x-fi.icon name="check-circle" class="h-5 w-5 text-emerald-600" />
                            Setujui
                        </button>
                        <button type="button" role="menuitem" onclick="openModal('rejectModal')"
                            class="flex w-full items-center gap-2 rounded-md p-2 text-sm text-gray-700 transition-colors duration-75 hover:bg-gray-50">
                            <x-fi.icon name="x-mark" class="h-5 w-5 text-red-600" />
                            Tolak
                        </button>
                    </div>
                </div>
            @endif

            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form GET: status/bulan/tahun langsung submit saat diganti, pencarian saat Enter --}}
        <form method="GET" action="{{ $indexUrl }}"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            <div class="col-span-2 sm:w-40">
                <x-fi.select name="status" id="status-select" aria-label="Filter Status" :value="request('status')"
                    :options="['' => 'Semua Status', 'draft' => 'Draft', 'approved' => 'Disetujui', 'rejected' => 'Ditolak']"
                    onchange="this.form.requestSubmit()" data-fi-select />
            </div>
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari proyek, kode reimburse, atau nota..." class="col-span-2 sm:w-72" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark"
                    class="justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    {{-- ─── Form Hapus Bulk + Tabel ──────────────────────────────────────────── --}}
    <form id="deleteForm" method="POST" action="{{ route('reimburse.destroy') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">

                {{-- ─── Header Tabel ───────────────────────────────────────── --}}
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-2 py-3 sm:ps-4">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th dense>Kode</x-fi.table.th>
                        <x-fi.table.th dense>Tanggal</x-fi.table.th>
                        <x-fi.table.th dense>Nama Proyek</x-fi.table.th>
                        <x-fi.table.th dense>Keterangan Belanja</x-fi.table.th>
                        <x-fi.table.th dense align="end">Total</x-fi.table.th>
                        <x-fi.table.th dense>Status</x-fi.table.th>
                        <x-fi.table.th dense>Tgl Perubahan</x-fi.table.th>
                        <x-fi.table.th dense>Catatan</x-fi.table.th>
                        <x-fi.table.th dense align="center">Bukti</x-fi.table.th>
                        <x-fi.table.th dense align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- ─── Body Tabel ──────────────────────────────────────────── --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($reimburses as $reimburse)
                        @php $isDraft = $reimburse->status === 'draft'; @endphp
                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">

                            {{-- Checkbox: semua baris bisa dipilih (export terpilih);
                                 persetujuan admin hanya memproses yang berstatus draft --}}
                            <td class="w-1 px-2 py-4 sm:ps-4">
                                <input type="checkbox" name="ids[]" value="{{ $reimburse->reimburse_code }}"
                                    class="reimburse-checkbox fi-checkbox" title="Pilih {{ $reimburse->reimburse_code }}"
                                    data-amount="{{ $reimburse->total_amount }}"
                                    data-status="{{ $reimburse->status }}">
                            </td>

                            {{-- Kode + penanda pengajuan otomatis dari Nota --}}
                            <x-fi.table.td dense>
                                <div class="font-medium text-primary">{{ $reimburse->reimburse_code }}</div>
                                @if ($reimburse->is_from_nota)
                                    <div class="mt-1 flex flex-col items-start gap-1">
                                        <x-fi.badge color="primary">Dari Nota</x-fi.badge>
                                        @include('components.finance.reimburse.nota-link', ['reimburse' => $reimburse])
                                    </div>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td dense class="text-gray-700">{{ $reimburse->formatted_date }}</x-fi.table.td>
                            <x-fi.table.td dense class="min-w-[8rem] max-w-[14rem] whitespace-normal font-medium">
                                <div class="line-clamp-2" title="{{ $reimburse->project_name }}">{{ $reimburse->project_name }}</div>
                            </x-fi.table.td>
                            <x-fi.table.td dense class="min-w-[9rem] max-w-xs whitespace-normal text-gray-700" title="{{ $reimburse->expense_description }}">
                                {{ Str::limit($reimburse->expense_description, 50) }}
                            </x-fi.table.td>
                            <x-fi.table.td dense align="end" class="font-semibold tabular-nums">{{ $reimburse->formatted_total_amount }}</x-fi.table.td>

                            {{-- Badge Status --}}
                            <x-fi.table.td dense>
                                <x-fi.badge :color="$statusColors[$reimburse->status] ?? 'gray'">{{ $reimburse->status_label }}</x-fi.badge>
                            </x-fi.table.td>

                            {{-- Tanggal Perubahan Status --}}
                            <x-fi.table.td dense class="text-xs text-gray-500">{{ $reimburse->formatted_status_changed_at }}</x-fi.table.td>

                            {{-- Catatan --}}
                            <x-fi.table.td dense class="min-w-[7rem] max-w-[12rem] whitespace-normal text-gray-700">
                                {{ Str::limit($reimburse->notes ?? '-', 30) }}
                            </x-fi.table.td>

                            {{-- Bukti: pratinjau di dalam halaman (tanpa tab baru) --}}
                            <x-fi.table.td dense align="center">
                                @if ($reimburse->proof_file)
                                    <a href="{{ $reimburse->proof_url }}"
                                        onclick="event.preventDefault(); window.openFilePreview(this.href, { title: @js('Bukti Reimburse ' . $reimburse->reimburse_code), downloadName: @js($reimburse->proof_file_name ?: basename($reimburse->proof_file)) })"
                                        class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                                        title="Lihat bukti">
                                        <x-fi.icon name="paper-clip" class="h-4 w-4" />
                                        Lihat
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td dense align="center">
                                <x-fi.dropdown label="Aksi {{ $reimburse->reimburse_code }}" class="-my-1.5">
                                    {{-- Edit: Super Admin, hanya status Draft --}}
                                    @if ($isSuperAdmin)
                                        <x-fi.dropdown.list>
                                            <x-fi.dropdown.item icon="pencil-square"
                                                :onclick="$isDraft ? 'openModal(\'editModal-' . $reimburse->reimburse_code . '\')' : null"
                                                :disabled="! $isDraft" :hint="$isDraft ? null : 'Sudah ' . $reimburse->status_label">
                                                Edit
                                            </x-fi.dropdown.item>
                                        </x-fi.dropdown.list>
                                    @endif

                                    {{-- Hapus: admin hanya boleh menghapus Draft --}}
                                    @php $canDelete = ! $isAdmin || $isDraft; @endphp
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            :data-delete-row="$canDelete ? $reimburse->reimburse_code : null"
                                            :data-delete-row-label="$reimburse->reimburse_code . ' · ' . $reimburse->project_name"
                                            :disabled="! $canDelete" :hint="$canDelete ? null : 'Hanya Draft yang bisa dihapus'">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty

                        {{-- ─── Empty State ─────────────────────────────────── --}}
                        <tr>
                            <td colspan="11">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada reimburse yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="receipt-refund" heading="Data tidak ditemukan" />
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
        <x-fi.pagination :paginator="$reimburses" />
    </div>
</x-fi.card>
