{{--
    Payroll Table Component

    Menampilkan data payroll yang dikelompokkan per proyek + periode. Setiap
    kelompok (satu lembar payroll = satu proyek + satu minggu) berupa satu
    baris header berisi ringkasan agregat (jumlah karyawan, subtotal upah
    bersih, status draft/paid) dan bisa di-collapse/expand. Detail per
    karyawan (nama, upah/hari, hari masuk, kasbon, lembur, upah bersih,
    status, aksi) tampil pada baris-baris di bawah header grup.

    Kolom:
    - Checkbox (bulk select, semua status — draft maupun paid)
    - Nama Karyawan
    - Periode (rentang tanggal + minggu)
    - Upah/Hari
    - Hari Masuk
    - Potongan Kasbon
    - Lembur
    - Upah Bersih
    - Status
    - Aksi

    Data yang diterima:
    - $payrollGroups : Collection grup hasil PayrollService::groupPayrollsForView
                      (sudah dipaginasi per-grup di controller)

    Setiap baris karyawan punya modal unik berdasarkan ID payroll (edit-modal
    untuk draft, detail-modal untuk semua). Dipakai dari pages/sdm/payroll.blade.php.
--}}

<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="inline-block min-w-full align-middle">
        <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-border-light">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                        <th class="p-2 text-left">Nama Karyawan</th>
                        <th class="p-2 text-left">Proyek</th>
                        <th class="p-2 text-center">Periode</th>
                        <th class="p-2 text-center">Upah/Hari</th>
                        <th class="p-2 text-center">Hari Masuk</th>
                        <th class="p-2 text-center">Potongan Kasbon</th>
                        <th class="p-2 text-center">Lembur</th>
                        <th class="p-2 text-center">Upah Bersih</th>
                        <th class="p-2 text-center">Status</th>
                        <th class="p-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrollGroups as $groupIndex => $group)
                        {{-- ============================================
                             BARIS HEADER GRUP (proyek + periode)
                             ============================================ --}}
                        <tr class="bg-primary-light/20">
                            <td colspan="11" class="p-0">
                                <div class="flex items-center gap-3 px-4 py-2">
                                    <input type="checkbox" class="group-select-all w-4 h-4 accent-primary cursor-pointer"
                                        data-group-index="{{ $groupIndex }}"
                                        title="Pilih semua payroll dalam grup ini">

                                    <i class="fa-solid fa-folder-open text-primary"></i>
                                    <span class="font-semibold text-text-primary">{{ $group['project_name'] }}</span>

                                    <span class="text-xs text-text-label">
                                        @php
                                            $gStart = \Carbon\Carbon::parse($group['period_start_date']);
                                            $gEnd = \Carbon\Carbon::parse($group['period_end_date']);
                                        @endphp
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

                                    <span class="ml-auto flex items-center gap-3">
                                        <span class="text-xs text-text-label">Upah Bersih:</span>
                                        <span class="font-semibold text-primary text-sm">
                                            {{ 'Rp ' . number_format($group['total_net'], 0, ',', '.') }}
                                        </span>

                                        @if (($group['total_kasbon'] ?? 0) > 0)
                                            <span class="text-xs text-text-label">Kasbon:</span>
                                            <span class="font-semibold text-error text-sm">
                                                {{ 'Rp ' . number_format($group['total_kasbon'], 0, ',', '.') }}
                                            </span>
                                        @endif

                                        {{-- Status ringkas grup --}}
                                        @if ($group['paid_count'] === 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-warning-light text-warning">
                                                Draft
                                            </span>
                                        @elseif ($group['draft_count'] === 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-success-light text-success">
                                                Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-light text-primary">
                                                {{ $group['draft_count'] }} Draft · {{ $group['paid_count'] }} Paid
                                            </span>
                                        @endif

                                        {{-- Tombol collapse/expand grup --}}
                                        <button type="button"
                                            class="group-toggle flex items-center justify-center w-7 h-7 rounded-lg bg-surface-secondary hover:bg-border-light text-text-secondary transition-colors duration-200"
                                            onclick="togglePayrollGroup({{ $groupIndex }})"
                                            title="Tampilkan / sembunyikan karyawan">
                                            <i class="fa-solid fa-chevron-down group-chevron-{{ $groupIndex }}"></i>
                                        </button>
                                    </span>
                                </div>
                            </td>
                        </tr>

                        {{-- ============================================
                             BARIS PER KARYAWAN dalam grup
                             ============================================ --}}
                        @foreach ($group['payrolls'] as $payroll)
                            <tr class="payroll-group-rows-{{ $groupIndex }} border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $payroll->id }}"
                                        data-group-index="{{ $groupIndex }}"
                                        data-status="{{ $payroll->status }}"
                                        class="payroll-checkbox w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 pl-4">{{ $payroll->employee->name }}</td>

                                {{-- Proyek --}}
                                <td class="p-2 text-sm">
                                    @php
                                        $payrollProject = $payroll->project_name ?: ($payroll->employee->project_name ?? null);
                                    @endphp
                                    @if ($payrollProject)
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-light text-primary">{{ $payrollProject }}</span>
                                    @else
                                        <span class="text-text-label">-</span>
                                    @endif
                                </td>

                                {{-- Periode --}}
                                <td class="p-2 text-center text-sm">
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
                                            <br><span class="text-xs text-text-label">Minggu {{ $payroll->week_number }}</span>
                                        @endif
                                    @endif
                                </td>

                                {{-- Upah Per Hari --}}
                                <td class="p-2 text-right text-sm">
                                    {{ 'Rp ' . number_format($payroll->base_salary, 0, ',', '.') }}
                                </td>

                                {{-- Hari Masuk --}}
                                <td class="p-2 text-center font-medium">
                                    {{ $payroll->present_days }} hari
                                </td>

                                {{-- Kasbon --}}
                                <td class="p-2 text-right text-error text-sm">
                                    @if ($payroll->kasbon_deduction)
                                        {{ 'Rp ' . number_format($payroll->kasbon_deduction, 0, ',', '.') }}
                                    @else
                                        <span class="text-text-label">-</span>
                                    @endif
                                </td>

                                {{-- Lembur --}}
                                <td class="p-2 text-right text-success text-sm">
                                    {{ 'Rp ' . number_format($payroll->overtime_total, 0, ',', '.') }}
                                </td>

                                {{-- Upah Bersih --}}
                                <td class="p-2 text-right font-semibold text-primary">
                                    {{ 'Rp ' . number_format($payroll->net_salary, 0, ',', '.') }}
                                </td>

                                {{-- Status --}}
                                <td class="p-2 text-center">
                                    @if ($payroll->status === 'draft')
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-warning-light text-warning">
                                            Draft
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-success-light text-success">
                                            Paid
                                        </span>
                                    @endif
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        @if ($payroll->status === 'draft')
                                            <button type="button" onclick="openModal('editModal-{{ $payroll->id }}')"
                                                class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                title="Edit Payroll Draft">
                                                <i class="fa-solid fa-pen w-3 h-3"></i>
                                                Edit
                                            </button>
                                        @endif

                                        <button type="button" onclick="openModal('detailModal-{{ $payroll->id }}')"
                                            class="flex items-center gap-1 bg-primary hover:bg-primary-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Detail Payroll">
                                            <i class="fa-solid fa-eye w-3 h-3"></i>
                                            Detail
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="11" class="text-center p-4 text-text-secondary">
                                Data tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Form untuk Bulk Delete --}}
<form id="deleteForm" method="POST" action="{{ route('payroll.destroy') }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>

{{-- Form untuk Bulk Pay --}}
<form id="bulkPayForm" method="POST" action="{{ route('payroll.bulk-pay') }}" style="display: none;">
    @csrf
    @method('PATCH')
</form>
