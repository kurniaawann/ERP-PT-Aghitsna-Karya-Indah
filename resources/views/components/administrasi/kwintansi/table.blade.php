{{-- Kwintansi Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('kwintansi.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">No</th>
                            <th class="p-2 text-left">Kwitansi No.</th>
                            <th class="p-2 text-left">Sudah Terima Dari</th>
                            <th class="p-2 text-left">Uang Pembayaran</th>
                            <th class="p-2 text-right">Jumlah</th>
                            <th class="p-2 text-center">Tanggal</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kwintansis as $kwintansi)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $kwintansi->id_kwintansi }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 font-medium text-primary">
                                    {{ $kwintansi->payment_sequence ? str_pad((string) $kwintansi->payment_sequence, 3, '0', STR_PAD_LEFT) : '-' }}
                                </td>
                                <td class="p-2">
                                    {{ $kwintansi->invoice_number ?? '-' }}
                                    @if ($kwintansi->payment_proof_id)
                                        <span
                                            class="ml-1 inline-flex items-center rounded-md bg-info-light px-1.5 py-0.5 text-[10px] font-semibold text-info"
                                            title="Dibuat otomatis dari bukti pembayaran">Auto</span>
                                    @endif
                                </td>
                                <td class="p-2">{{ $kwintansi->received_from }}</td>
                                <td class="p-2">{{ Str::limit($kwintansi->payment_for, 50) }}</td>

                                {{-- Jumlah --}}
                                <td class="p-2 text-right font-semibold">
                                    Rp {{ number_format($kwintansi->amount, 0, ',', '.') }}
                                </td>

                                {{-- Tanggal --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->format('d/m/Y') }}
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        {{-- Button Edit --}}
                                        <button type="button"
                                            onclick="openModal('editModal-{{ $kwintansi->id_kwintansi }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Kwintansi">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center p-4 text-text-secondary">
                                    Data tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
