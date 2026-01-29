{{-- Invoice Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('invoice.administrasi.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">No. Invoice</th>
                            <th class="p-2 text-left">Kepada</th>
                            <th class="p-2 text-left">Faktur No</th>
                            <th class="p-2 text-left">SJ No</th>
                            <th class="p-2 text-right">Jumlah Total</th>
                            <th class="p-2 text-center">Tanggal</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $invoice->id_invoice }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 font-medium text-primary">{{ $invoice->id_invoice }}</td>
                                <td class="p-2">{{ $invoice->kepada }}</td>
                                <td class="p-2">{{ $invoice->faktur_no }}</td>
                                <td class="p-2">{{ $invoice->sj_no }}</td>

                                {{-- Jumlah Total --}}
                                <td class="p-2 text-right font-semibold text-green-600">
                                    Rp {{ number_format($invoice->jumlah_total, 0, ',', '.') }}
                                </td>

                                {{-- Tanggal --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        {{-- Button Edit --}}
                                        <button type="button"
                                            onclick="openModal('editModal-{{ $invoice->id_invoice }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Invoice">
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
