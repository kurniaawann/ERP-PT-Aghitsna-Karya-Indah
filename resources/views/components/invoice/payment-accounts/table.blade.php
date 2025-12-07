{{-- Table Rekening Pembayaran --}}
<form id="deleteForm" method="POST" action="{{ route('payment-accounts.destroy', ['paymentAccount' => 'temp']) }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-gray-300 rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-center">No</th>
                            <th class="p-2 text-left">Nama Bank</th>
                            <th class="p-2 text-left">Nomor Rekening</th>
                            <th class="p-2 text-left">Nama Pemilik</th>
                            <th class="p-2 text-center">Urutan</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($accounts as $index => $account)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                {{-- Checkbox --}}
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_accounts[]" value="{{ $account->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer account-checkbox">
                                </td>

                                {{-- No --}}
                                <td class="p-2 text-center font-medium text-primary">
                                    {{ $accounts->firstItem() + $index }}
                                </td>

                                {{-- Nama Bank --}}
                                <td class="p-2 text-gray-700 font-medium">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-building-columns text-primary"></i>
                                        {{ $account->bank_name }}
                                    </div>
                                </td>

                                {{-- Nomor Rekening --}}
                                <td class="p-2">
                                    <span
                                        class="inline-block px-2 py-1 text-sm font-mono bg-gray-100 text-gray-700 rounded">
                                        {{ $account->account_number }}
                                    </span>
                                </td>

                                {{-- Nama Pemilik --}}
                                <td class="p-2 text-gray-700">
                                    {{ $account->account_holder }}
                                </td>

                                {{-- Urutan --}}
                                <td class="p-2 text-center text-gray-600">
                                    {{ $account->order }}
                                </td>

                                {{-- Status --}}
                                <td class="p-2 text-center">
                                    <button type="button" onclick="toggleActive({{ $account->id }})"
                                        class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-lg gap-1 transition-colors duration-200
                                    {{ $account->is_active ? 'bg-success-light text-success hover:bg-success hover:text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-400 hover:text-white' }}">
                                        <i
                                            class="fa-solid {{ $account->is_active ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                        {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <button type="button" onclick="openModal('editModal-{{ $account->id }}')"
                                        class="flex items-center justify-center gap-2 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 mx-auto">
                                        <i class="fa-solid fa-pen w-4 h-4"></i>
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center p-4 text-gray-500">Data tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
