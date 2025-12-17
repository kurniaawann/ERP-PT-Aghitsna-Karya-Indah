{{-- Reimburse Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('reimburse.destroy') }}">
    @csrf
    @method('DELETE')

    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">Kode</th>
                            <th class="p-2 text-left">Tanggal</th>
                            <th class="p-2 text-left">Nama Proyek</th>
                            <th class="p-2 text-left">Keterangan Belanja</th>
                            <th class="p-2 text-right">Total</th>
                            <th class="p-2 text-center">Tgl Jatuh Tempo</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-center">Tgl Perubahan</th>
                            <th class="p-2 text-left">Catatan</th>
                            @if (Auth::user()->role === 'admin')
                                <th class="p-2 text-center">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reimburses as $reimburse)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    @if (Auth::user()->role === 'superadmin' && $reimburse->status === 'draft')
                                        {{-- Super admin can select draft items --}}
                                        <input type="checkbox" name="ids[]" value="{{ $reimburse->reimburse_code }}"
                                            class="reimburse-checkbox w-4 h-4 accent-primary cursor-pointer"
                                            data-amount="{{ $reimburse->total_amount }}">
                                    @elseif (Auth::user()->role === 'admin')
                                        {{-- Admin can select all items --}}
                                        <input type="checkbox" name="ids[]" value="{{ $reimburse->reimburse_code }}"
                                            class="w-4 h-4 accent-primary cursor-pointer">
                                    @endif
                                </td>

                                <td class="p-2 font-medium text-primary">{{ $reimburse->reimburse_code }}</td>
                                <td class="p-2">{{ $reimburse->formatted_date }}</td>
                                <td class="p-2">{{ $reimburse->project_name }}</td>
                                <td class="p-2 text-sm">{{ Str::limit($reimburse->expense_description, 50) }}</td>
                                <td class="p-2 text-right font-semibold">{{ $reimburse->formatted_total_amount }}</td>
                                <td class="p-2 text-center">{{ $reimburse->formatted_due_date }}</td>

                                {{-- Status Badge --}}
                                <td class="p-2 text-center">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-semibold {{ $reimburse->status_badge_class }}">
                                        {{ $reimburse->status_label }}
                                    </span>
                                </td>

                                {{-- Tanggal Perubahan Status --}}
                                <td class="p-2 text-center text-xs text-text-label">
                                    {{ $reimburse->formatted_status_changed_at }}
                                </td>

                                <td class="p-2 text-sm">{{ Str::limit($reimburse->notes ?? '-', 30) }}</td>

                                {{-- Aksi (Admin only, only for draft) --}}
                                @if (Auth::user()->role === 'admin')
                                    <td class="p-2 text-center">
                                        @if ($reimburse->status === 'draft')
                                            <div class="flex justify-center gap-2">
                                                <button type="button"
                                                    onclick="openModal('editModal-{{ $reimburse->reimburse_code }}')"
                                                    class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                    title="Edit Reimburse">
                                                    <i class="fa-solid fa-pen w-3 h-3"></i>
                                                    Edit
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-text-tertiary text-xs">-</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ Auth::user()->role === 'admin' ? '11' : '10' }}"
                                    class="text-center p-4 text-text-secondary">
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
