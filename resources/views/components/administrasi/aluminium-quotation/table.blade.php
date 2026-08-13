{{-- =====================================================================
     Komponen Tabel Penawaran Aluminium (Aluminium Quotation)

     Menampilkan daftar penawaran dalam bentuk tabel dengan:
     - Checkbox untuk pemilihan massal
     - Kolom: No. Penawaran, Kepada, Perihal, Total, Tanggal, Aksi
     - Form hapus massal (#deleteForm) TIDAK membungkus tabel agar tidak
       bertabrakan dengan form "Buat Invoice" per baris (form tidak boleh
       bersarang). Checkbox memakai atribut form="deleteForm" untuk tetap
       ter-submit oleh form hapus.
     ===================================================================== --}}
<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="inline-block min-w-full align-middle">
        <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                {{-- Header Tabel --}}
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                        <th class="p-2 text-left">No. Penawaran</th>
                        <th class="p-2 text-left">Kepada</th>
                        <th class="p-2 text-left">Perihal</th>
                        <th class="p-2 text-right">Total</th>
                        <th class="p-2 text-center">Tanggal</th>
                        <th class="p-2 text-center">Aksi</th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody>
                    @forelse($quotations as $quotation)
                        <tr class="border-t hover:bg-surface-secondary">
                            {{-- Checkbox Pilihan --}}
                            <td class="p-2 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $quotation->quotation_number }}"
                                    form="deleteForm" class="row-checkbox w-4 h-4 accent-primary cursor-pointer">
                            </td>

                            {{-- No. Penawaran --}}
                            <td class="p-2 font-medium text-primary">{{ $quotation->quotation_number }}</td>

                            {{-- Kepada --}}
                            <td class="p-2">{{ $quotation->recipient }}</td>

                            {{-- Perihal --}}
                            <td class="p-2 text-gray-600 text-sm">{{ $quotation->subject }}</td>

                            {{-- Total --}}
                            <td class="p-2 text-right font-semibold text-green-600">
                                Rp {{ number_format($quotation->total_amount, 0, ',', '.') }}
                            </td>

                            {{-- Tanggal --}}
                            <td class="p-2 text-center text-sm">
                                {{ \Carbon\Carbon::parse($quotation->date)->isoFormat('DD MMM YYYY') }}
                            </td>

                            {{-- Tombol Aksi: Buat Invoice, Detail, Edit, PDF, Excel --}}
                            <td class="p-2 text-center">
                                <div class="flex justify-center gap-1 flex-wrap">
                                    {{-- Buat Invoice dari Penawaran --}}
                                    @if ($quotation->invoices->isEmpty())
                                        <form method="POST"
                                            action="{{ route('aluminium-quotation.invoice.create', $quotation->quotation_number) }}"
                                            class="inline"
                                            onsubmit="this.querySelector('button').disabled = true;">
                                            @csrf
                                            <button type="submit"
                                                class="flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                title="Buat Invoice dari Penawaran ini">
                                                <i class="fa-solid fa-file-invoice w-3 h-3"></i>
                                                Buat Invoice
                                            </button>
                                        </form>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 bg-green-100 text-green-700 border border-green-200 px-2 py-1 rounded-lg text-xs"
                                            title="Invoice sudah dibuat dari penawaran ini">
                                            <i class="fa-solid fa-check w-3 h-3"></i>
                                            Sudah dibuat
                                        </span>
                                    @endif

                                    {{-- Tombol Detail --}}
                                    <button type="button"
                                        onclick="openModal('detailModal-{{ $quotation->quotation_number }}')"
                                        class="flex items-center gap-1 bg-primary hover:bg-primary-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Detail">
                                        <i class="fa-solid fa-eye w-3 h-3"></i>
                                        Detail
                                    </button>

                                    {{-- Tombol Edit --}}
                                    <button type="button"
                                        onclick="openModal('editModal-{{ $quotation->quotation_number }}')"
                                        class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Edit">
                                        <i class="fa-solid fa-pen w-3 h-3"></i>
                                        Edit
                                    </button>

                                    {{-- Tombol PDF --}}
                                    <a href="{{ route('aluminium-quotation.print.pdf', $quotation->quotation_number) }}"
                                        class="flex items-center gap-1 bg-error hover:bg-error/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Print PDF">
                                        <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                        PDF
                                    </a>

                                    {{-- Tombol Excel --}}
                                    <a href="{{ route('aluminium-quotation.print.excel', $quotation->quotation_number) }}"
                                        class="flex items-center gap-1 bg-success hover:bg-success/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Print Excel">
                                        <i class="fa-solid fa-file-excel w-3 h-3"></i>
                                        Excel
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- Tampilan saat data kosong --}}
                        <tr>
                            <td colspan="7" class="text-center p-4 text-text-secondary">
                                <i class="fa-solid fa-inbox text-2xl mb-2 block opacity-50"></i>
                                Belum ada data penawaran aluminium.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Form hapus massal — di luar tabel agar form "Buat Invoice" per baris
     tidak bersarang. Checkbox name="ids[]" terhubung via form="deleteForm". --}}
<form id="deleteForm" method="POST" action="{{ route('aluminium-quotation.destroySelected') }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
