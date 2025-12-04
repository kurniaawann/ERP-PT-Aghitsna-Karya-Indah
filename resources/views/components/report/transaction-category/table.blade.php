{{-- Table Kategori Transaksi --}}
<form id="deleteForm" method="POST" action="{{ route('transaction-category.destroySelected') }}">
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
                            <th class="p-2 text-left">Nama Kategori</th>
                            <th class="p-2 text-left">Kode</th>
                            <th class="p-2 text-center">Tipe</th>
                            <th class="p-2 text-center">Urutan</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($categories as $index => $category)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                {{-- Checkbox --}}
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_categories[]" value="{{ $category->id }}"
                                        data-is-used="{{ in_array($category->id, $usedCategoryIds ?? []) ? 'true' : 'false' }}"
                                        data-category-name="{{ $category->name }}"
                                        class="w-4 h-4 accent-primary cursor-pointer category-checkbox">
                                </td>

                                {{-- No --}}
                                <td class="p-2 text-center font-medium text-primary">
                                    {{ $categories->firstItem() + $index }}
                                </td>

                                {{-- Nama Kategori --}}
                                <td class="p-2 text-gray-700 font-medium">
                                    {{ $category->name }}
                                </td>

                                {{-- Kode --}}
                                <td class="p-2">
                                    <span
                                        class="inline-block px-2 py-1 text-xs font-mono bg-gray-100 text-gray-700 rounded">
                                        {{ $category->code }}
                                    </span>
                                </td>

                                {{-- Tipe --}}
                                <td class="p-2 text-center">
                                    @if ($category->type == 'INCOME')
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-lg bg-success-light text-success gap-1">
                                            <i class="fa-solid fa-arrow-down"></i>
                                            Pemasukan
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-lg bg-error-light text-error gap-1">
                                            <i class="fa-solid fa-arrow-up"></i>
                                            Pengeluaran
                                        </span>
                                    @endif
                                </td>

                                {{-- Urutan --}}
                                <td class="p-2 text-center text-gray-600">
                                    {{ $category->sort_order }}
                                </td>

                                {{-- Status --}}
                                <td class="p-2 text-center">
                                    <button type="button" onclick="toggleStatus({{ $category->id }})"
                                        class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-lg gap-1 transition-colors duration-200
                                    {{ $category->is_active ? 'bg-success-light text-success hover:bg-success hover:text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-400 hover:text-white' }}">
                                        <i
                                            class="fa-solid {{ $category->is_active ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <button type="button" onclick="openModal('editModal-{{ $category->id }}')"
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
