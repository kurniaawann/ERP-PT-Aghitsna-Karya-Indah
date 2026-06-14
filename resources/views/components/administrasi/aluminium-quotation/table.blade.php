{{-- Project Quotation Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('aluminium-quotation.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
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
                    <tbody>
                        @forelse($quotations as $quotation)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $quotation->quotation_number }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 font-medium text-primary">{{ $quotation->quotation_number }}</td>
                                <td class="p-2">{{ $quotation->recipient }}</td>
                                <td class="p-2 text-gray-600 text-sm">{{ $quotation->subject }}</td>

                                <td class="p-2 text-right font-semibold text-green-600">
                                    Rp {{ number_format($quotation->total_amount, 0, ',', '.') }}
                                </td>

                                <td class="p-2 text-center text-sm">
                                    {{ \Carbon\Carbon::parse($quotation->date)->isoFormat('DD MMM YYYY') }}
                                </td>

                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-1 flex-wrap">
                                        <x-buttons.detail onclick="openModal('detailModal-{{ $quotation->quotation_number }}')" />
                                        <x-buttons.edit onclick="openModal('editModal-{{ $quotation->quotation_number }}')" />
                                        <x-buttons.pdf url="{{ route('aluminium-quotation.print.pdf', $quotation->quotation_number) }}" />
                                        <x-buttons.excel url="{{ route('aluminium-quotation.print.excel', $quotation->quotation_number) }}" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center p-4 text-text-secondary">
                                    {{ auth()->user()?->isSuperAdmin() ? 'Belum ada data penawaran.' : 'Belum ada data penawaran proyek.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>