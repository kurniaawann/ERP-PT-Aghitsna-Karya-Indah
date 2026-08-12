{{--
    Slip Gaji Table Component

    Menampilkan daftar slip gaji karyawan bulanan. Satu baris = satu slip
    (satu karyawan + satu periode). Semua status (draft maupun paid) bisa
    dipilih untuk aksi massal; PDF per slip selalu tersedia.

    Kolom:
    - Checkbox (bulk select)
    - Nama Karyawan + kode
    - Periode
    - Gaji Pokok
    - Rekap Absensi (Hadir / Izin / Sakit / Cuti / Alpha / Libur)
    - Total Potongan
    - THP
    - Status
    - Aksi (Edit draft, Detail, Print PDF)

    Form #deleteForm & #bulkPayForm ada di sini (dipakai JS modul halaman).
--}}

<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="inline-block min-w-full align-middle">
        <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-border-light">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                        <th class="p-2 text-left">Nama Karyawan</th>
                        <th class="p-2 text-center">Periode</th>
                        <th class="p-2 text-right">Gaji Pokok</th>
                        <th class="p-2 text-center">Rekap Absensi</th>
                        <th class="p-2 text-right">Total Potongan</th>
                        <th class="p-2 text-right">THP</th>
                        <th class="p-2 text-center">Status</th>
                        <th class="p-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slips as $slip)
                        <tr class="border-t hover:bg-surface-secondary">
                            <td class="p-2 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $slip->id }}"
                                    data-status="{{ $slip->status }}"
                                    class="slip-checkbox w-4 h-4 accent-primary cursor-pointer">
                            </td>

                            <td class="p-2 pl-4">
                                <div class="font-medium text-text-primary">{{ $slip->employee->name ?? '-' }}</div>
                                <div class="text-xs text-text-label">{{ $slip->employee_code }}</div>
                            </td>

                            {{-- Periode --}}
                            <td class="p-2 text-center text-sm">
                                {{ $slip->formatted_period }}
                                @if ($slip->payment_date)
                                    <br><span class="text-xs text-text-label">Dibayar {{ $slip->payment_date->format('d M Y') }}</span>
                                @endif
                            </td>

                            {{-- Gaji Pokok --}}
                            <td class="p-2 text-right text-sm">
                                {{ 'Rp ' . number_format($slip->base_salary, 0, ',', '.') }}
                            </td>

                            {{-- Rekap Absensi --}}
                            <td class="p-2 text-center text-xs">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-4 h-4 inline-flex items-center justify-center rounded bg-surface-hover text-text-primary font-semibold">H</span>
                                        {{ $slip->present_days }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-4 h-4 inline-flex items-center justify-center rounded bg-surface-hover text-text-primary font-semibold">I</span>
                                        {{ $slip->permission_days }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-4 h-4 inline-flex items-center justify-center rounded bg-surface-hover text-text-primary font-semibold">S</span>
                                        {{ $slip->sick_days }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-4 h-4 inline-flex items-center justify-center rounded bg-surface-hover text-text-primary font-semibold">C</span>
                                        {{ $slip->leave_days }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-4 h-4 inline-flex items-center justify-center rounded bg-surface-hover text-text-primary font-semibold">A</span>
                                        {{ $slip->absent_days }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-4 h-4 inline-flex items-center justify-center rounded bg-surface-hover text-text-primary font-semibold">L</span>
                                        {{ $slip->libur_days }}
                                    </span>
                                </div>
                            </td>

                            {{-- Total Potongan --}}
                            <td class="p-2 text-right text-error text-sm">
                                @if ($slip->total_deduction > 0)
                                    {{ 'Rp ' . number_format($slip->total_deduction, 0, ',', '.') }}
                                @else
                                    <span class="text-text-label">-</span>
                                @endif
                            </td>

                            {{-- THP --}}
                            <td class="p-2 text-right font-semibold text-primary">
                                {{ 'Rp ' . number_format($slip->net_salary, 0, ',', '.') }}
                            </td>

                            {{-- Status --}}
                            <td class="p-2 text-center">
                                @if ($slip->status === 'draft')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-warning-light text-warning">
                                        Draft
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-success-light text-success">
                                        Paid
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="p-2 text-center">
                                <div class="flex justify-center gap-2">
                                    @if ($slip->status === 'draft')
                                        <button type="button" onclick="openModal('editModal-{{ $slip->id }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Rekap Absensi Slip">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    @endif

                                    <button type="button" onclick="openModal('detailModal-{{ $slip->id }}')"
                                        class="flex items-center gap-1 bg-primary hover:bg-primary-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Detail Slip Gaji">
                                        <i class="fa-solid fa-eye w-3 h-3"></i>
                                        Detail
                                    </button>

                                    <a href="{{ route('salary-slips.print.pdf', $slip->id) }}" target="_blank"
                                        class="flex items-center gap-1 bg-btn-print hover:bg-btn-print-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Cetak PDF Slip Gaji">
                                        <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                        PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center p-4 text-text-secondary">
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
<form id="deleteForm" method="POST" action="{{ route('salary-slips.destroy') }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>

{{-- Form untuk Bulk Pay --}}
<form id="bulkPayForm" method="POST" action="{{ route('salary-slips.bulk-pay') }}" style="display: none;">
    @csrf
    @method('PATCH')
</form>
