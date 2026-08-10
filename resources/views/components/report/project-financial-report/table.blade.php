@props(['items', 'totals', 'recap'])

{{-- ==================== Tabel Laporan Keuangan Proyek ==================== --}}
<form id="deleteForm" method="POST" action="{{ route('project-financial-report.destroySelected', $recap) }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-border-light">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-center">No Bon</th>
                            <th class="p-2 text-left">Kategori</th>
                            <th class="p-2 text-center">Tanggal</th>
                            <th class="p-2 text-left">Keterangan</th>
                            <th class="p-2 text-right">Uang Masuk</th>
                            <th class="p-2 text-right">Uang Keluar</th>
                            <th class="p-2 text-left">Keterangan Bon</th>
                            <th class="p-2 text-center">Bukti</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-base divide-y divide-border-light">
                        @forelse ($items->groupBy('transaction_category_id') as $categoryId => $categoryItems)
                            @php
                                $category = $categoryItems->first()->category;
                                $catIndex = $loop->iteration;
                                $categoryIncome = (int) $categoryItems->sum('income_amount');
                                $categoryExpense = (int) $categoryItems->sum('expense_amount');
                            @endphp

                            {{-- Baris Header Kategori --}}
                            <tr class="bg-surface-hover">
                                <td colspan="10" class="p-2 text-center">
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-lg
                                        {{ $category && $category->type == 'INCOME' ? 'bg-success-light text-success' : 'bg-error-light text-error' }}">
                                        {{ strtoupper($category->name ?? 'LAIN-LAIN') }}
                                    </span>
                                </td>
                            </tr>

                            {{-- Item Bon dalam Kategori --}}
                            @foreach ($categoryItems as $bonIndex => $item)
                                <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                    <td class="p-2 text-center">
                                        <input type="checkbox" name="selected_items[]" value="{{ $item->id }}"
                                            class="w-4 h-4 accent-primary cursor-pointer item-checkbox">
                                    </td>
                                    <td class="p-2 text-center font-medium text-primary">
                                        {{ $catIndex }} Bon {{ $bonIndex + 1 }}
                                    </td>
                                    <td class="p-2 text-text-label">
                                        {{ $item->category->name ?? '-' }}
                                    </td>
                                    <td class="p-2 text-center">
                                        {{ $item->transaction_date ? \Carbon\Carbon::parse($item->transaction_date)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="p-2 text-text-primary">
                                        {{ $item->description ?? '-' }}
                                    </td>
                                    <td class="p-2 text-right font-medium {{ $item->income_amount ? 'text-success' : 'text-text-tertiary' }}">
                                        {{ $item->income_amount ? 'Rp ' . number_format($item->income_amount, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="p-2 text-right font-medium {{ $item->expense_amount ? 'text-error' : 'text-text-tertiary' }}">
                                        {{ $item->expense_amount ? 'Rp ' . number_format($item->expense_amount, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="p-2 text-text-label">
                                        {{ $item->keterangan_bon ?? '-' }}
                                    </td>
                                    <td class="p-2 text-center">
                                        @if ($item->hasProof())
                                            <a href="{{ asset('storage/' . $item->proof_file) }}" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-primary hover:underline text-sm" title="Lihat bukti">
                                                <i class="fa-solid fa-file-invoice text-xs"></i>
                                                Lihat
                                            </a>
                                        @else
                                            <span class="text-text-tertiary text-sm">-</span>
                                        @endif
                                    </td>
                                    <td class="p-2 text-center">
                                        <button type="button" onclick="openModal('editModal-{{ $item->id }}')"
                                            class="flex items-center justify-center gap-2 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 mx-auto">
                                            <i class="fa-solid fa-pen w-4 h-4"></i>
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Subtotal Kategori --}}
                            <tr class="bg-primary/10 font-semibold text-sm">
                                <td colspan="5" class="p-2 text-right text-text-heading">Subtotal {{ strtoupper($category->name ?? 'LAIN-LAIN') }}</td>
                                <td class="p-2 text-right text-success">Rp {{ number_format($categoryIncome, 0, ',', '.') }}</td>
                                <td class="p-2 text-right text-error">Rp {{ number_format($categoryExpense, 0, ',', '.') }}</td>
                                <td colspan="3" class="p-2"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center p-4 text-text-secondary">
                                    Belum ada transaksi. Klik <strong>Tambah Transaksi</strong> untuk mencatat uang masuk / uang keluar.
                                </td>
                            </tr>
                        @endforelse

                        {{-- Baris Total Keseluruhan --}}
                        @if ($items->isNotEmpty())
                            <tr class="bg-gradient-to-r from-primary/20 to-primary/10 border-t-4 border-primary font-bold text-base">
                                <td colspan="5" class="p-3 text-right text-text-heading">
                                    TOTAL KESELURUHAN
                                </td>
                                <td class="p-3 text-right text-success font-bold text-lg">
                                    Rp {{ number_format($totals->total_income ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-right text-error font-bold text-lg">
                                    Rp {{ number_format($totals->total_expense ?? 0, 0, ',', '.') }}
                                </td>
                                <td colspan="3" class="p-3">
                                    <div class="text-right">
                                        <span class="text-text-primary">Saldo: </span>
                                        <span class="font-bold text-lg {{ ($totals->balance ?? 0) >= 0 ? 'text-success' : 'text-error' }}">
                                            Rp {{ number_format($totals->balance ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
