{{-- Document Receipt Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('document-receipt.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">ID Dokumen</th>
                            <th class="p-2 text-left">Terima Dari</th>
                            <th class="p-2 text-left">Perihal</th>
                            <th class="p-2 text-left">Berupa</th>
                            <th class="p-2 text-center">Tanggal</th>
                            <th class="p-2 text-center">Jam</th>
                            <th class="p-2 text-center">Lokasi</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $document)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $document->id_document }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 font-medium text-primary">{{ $document->id_document }}</td>
                                <td class="p-2">{{ $document->received_from }}</td>
                                <td class="p-2">{{ $document->regarding }}</td>
                                <td class="p-2">{{ $document->form_of }}</td>

                                {{-- Tanggal --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($document->receipt_date)->format('d/m/Y') }}
                                </td>

                                {{-- Jam --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($document->receipt_time)->format('H:i') }}
                                </td>

                                {{-- Lokasi --}}
                                <td class="p-2 text-center">{{ $document->location }}</td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        {{-- Button Edit --}}
                                        <x-buttons.edit onclick="openModal('editModal-{{ $document->id_document }}')" />
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
</form>