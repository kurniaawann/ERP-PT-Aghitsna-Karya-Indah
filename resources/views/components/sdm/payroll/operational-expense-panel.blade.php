{{--
    Pengeluaran Operasional Proyek - Panel (Index Page)

    Menampilkan ringkasan pengeluaran tambahan / operasional proyek
    yang tercatat saat generate payroll. Satu record = satu periode.

    Variabel:
    - $operationalExpenses: Collection of ProjectOperationalExpense
    - $expense->period_locked: bool, true jika periode sudah dibayar (tidak bisa diedit)

    Aksi:
    - Edit (buka modal expenseModal-{id})
    - Hapus (form DELETE dengan konfirmasi)
--}}

@if ($operationalExpenses->isNotEmpty())
    <div class="mb-4 p-4 bg-secondary-light border border-border-strong rounded-lg">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="text-lg font-semibold text-text-primary flex items-center gap-2">
                <i class="fa-solid fa-cash-register text-primary"></i>
                Pengeluaran Operasional Proyek
                <span class="text-xs font-normal text-text-secondary">(sekali per periode, tidak per karyawan)</span>
            </h2>
            <span class="text-sm font-bold text-primary">
                Total: Rp {{ number_format($operationalExpenses->sum('total_amount'), 0, ',', '.') }}
            </span>
        </div>

        {{-- Daftar dibatasi ~3 kartu agar halaman tidak terlalu turun ke bawah;
             jika lebih, sisanya bisa di-scroll di dalam panel (lihat initExpensePanelScroll di index.js). --}}
        <div class="expense-scroll relative max-h-[320px] overflow-y-auto pr-1">
        @foreach ($operationalExpenses as $expense)
            <div class="expense-card border border-border-strong rounded-lg p-3 bg-surface-base mb-3 last:mb-0">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-semibold text-text-primary">{{ $expense->formatted_period }}</span>
                        @if ($expense->project_name)
                            <span
                                class="text-xs bg-primary-light text-primary px-2 py-0.5 rounded-full">{{ $expense->project_name }}</span>
                        @endif
                        @if ($expense->period_locked)
                            <span class="text-xs bg-success-light text-success px-2 py-0.5 rounded-full">Sudah dibayar</span>
                        @else
                            <span class="text-xs bg-warning-light text-warning px-2 py-0.5 rounded-full">Draft</span>
                        @endif
                    </div>

                    @if (!$expense->period_locked)
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openModal('expenseModal-{{ $expense->id }}')"
                                class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                title="Edit Pengeluaran Operasional">
                                <i class="fa-solid fa-pen w-3 h-3"></i>
                                Edit
                            </button>
                            <form method="POST" action="{{ route('payroll.operational-expense.destroy', $expense->id) }}"
                                onsubmit="return confirm('Hapus pengeluaran operasional periode ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="flex items-center gap-1 bg-btn-delete hover:bg-btn-delete-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                    title="Hapus Pengeluaran Operasional">
                                    <i class="fa-solid fa-trash w-3 h-3"></i>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @forelse (($expense->expense_items ?? []) as $item)
                        <span
                            class="text-xs bg-surface-secondary border border-border-light px-2 py-1 rounded">
                            {{ $item['name'] ?? 'Item' }}:
                            <strong>Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}</strong>
                        </span>
                    @empty
                        <span class="text-xs text-text-secondary">- Tidak ada rincian -</span>
                    @endforelse
                    <span class="text-sm font-bold text-primary ml-auto">
                        Total: Rp {{ number_format($expense->total_amount, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        @endforeach
        </div>
    </div>
@endif
