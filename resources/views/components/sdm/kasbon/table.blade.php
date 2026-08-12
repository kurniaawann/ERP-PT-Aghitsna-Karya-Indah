{{-- ═══════════════════════════════════════════════════════════════════════
     Komponen Tabel Data Kasbon
     Menampilkan daftar kasbon dengan kotak centang, lencana status,
     informasi karyawan, dan aksi edit untuk data yang tertunda.
     ═══════════════════════════════════════════════════════════════════════ --}}
<form id="deleteForm" method="POST" action="{{ route('kasbon.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-border-light">
                    {{-- Tabel Header --}}
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="p-2 text-center">
                                <input type="checkbox" id="select-all"
                                    class="rounded border-border text-primary focus:ring-primary">
                            </th>
                            <th class="p-2 text-left text-xs font-medium ">Kode</th>
                            <th class="p-2 text-left text-xs font-medium ">Karyawan/Divisi</th>
                            <th class="p-2 text-center text-xs font-medium ">Jenis Karyawan</th>
                            <th class="p-2 text-center text-xs font-medium ">Jenis</th>
                            <th class="p-2 text-center text-xs font-medium ">Tanggal</th>
                            <th class="p-2 text-left text-xs font-medium ">Proyek</th>
                            <th class="p-2 text-right text-xs font-medium ">Jumlah</th>
                            <th class="p-2 text-right text-xs font-medium ">Terbayar</th>
                            <th class="p-2 text-right text-xs font-medium ">Sisa Pembayaran</th>
                            <th class="p-2 text-center text-xs font-medium ">Progress</th>
                            <th class="p-2 text-center text-xs font-medium ">Status Pembayaran</th>
                            <th class="p-2 text-center text-xs font-medium ">Aksi</th>
                        </tr>
                    </thead>

                    {{-- Badan Tabel --}}
                    <tbody class="bg-surface-base">
                        @forelse($kasbons as $kasbon)
                            @php
                                $isBulananKasbon = $kasbon->kasbon_type === 'personal'
                                    && $kasbon->employee
                                    && $kasbon->employee->employment_type === 'bulanan';
                            @endphp
                            <tr class="border-t hover:bg-surface-secondary">
                                {{-- Kotak Centang --}}
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_kasbons[]" value="{{ $kasbon->kasbon_code }}"
                                        class="row-checkbox w-4 h-4 accent-primary cursor-pointer"
                                        {{ $kasbon->status === 'deducted' || $kasbon->live_payroll_payments_count > 0 ? 'disabled' : '' }}>
                                </td>

                                {{-- Kode Kasbon --}}
                                <td class="p-2 text-sm font-medium text-text-primary">{{ $kasbon->kasbon_code }}</td>

                                {{-- Informasi Karyawan / Divisi --}}
                                <td class="p-2 text-sm text-text-label">
                                    @if ($kasbon->kasbon_type === 'personal' && $kasbon->employee)
                                        <div>
                                            <div class="font-medium text-text-primary">{{ $kasbon->employee->name }}</div>
                                            <div class="text-xs text-text-label">{{ $kasbon->employee->employee_code }}</div>
                                        </div>
                                    @elseif ($kasbon->kasbon_type === 'team' && $kasbon->division)
                                        <div>
                                            <div class="font-medium text-secondary">Divisi {{ $kasbon->division }}</div>
                                            <div class="text-xs text-text-label">Kasbon Tim</div>
                                        </div>
                                    @else
                                        <span class="text-text-label italic">-</span>
                                    @endif
                                </td>

                                {{-- Jenis Karyawan (Harian/Bulanan) --}}
                                <td class="p-2 text-center">
                                    @if ($kasbon->kasbon_type === 'personal' && $kasbon->employee)
                                        <span class="px-2 py-1 bg-primary-light text-primary text-xs font-semibold rounded-md">
                                            {{ $kasbon->employee->employment_type === 'bulanan' ? 'Bulanan' : 'Harian' }}
                                        </span>
                                    @else
                                        <span class="text-text-label">-</span>
                                    @endif
                                </td>

                                {{-- Lencana Jenis Kasbon --}}
                                <td class="p-2 text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-md {{ $kasbon->kasbon_type === 'personal' ? 'bg-primary-light text-primary' : 'bg-secondary-light text-secondary' }}">
                                        {{ $kasbon->kasbon_type_label }}
                                    </span>
                                </td>

                                {{-- Tanggal Kasbon --}}
                                <td class="p-2 text-center text-sm text-text-primary">
                                    {{ $kasbon->kasbon_date ? $kasbon->kasbon_date->format('d/m/Y') : '-' }}
                                </td>

                                {{-- Proyek (kasbon divisi ber-proyek) --}}
                                <td class="p-2 text-sm text-text-primary">
                                    @if ($kasbon->kasbon_type === 'team' && ! empty($kasbon->project_names))
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ implode(', ', $kasbon->project_names) }}</span>
                                            <span class="text-xs text-text-label">Otomatis lunas saat payroll dibayar</span>
                                        </div>
                                    @else
                                        <span class="text-text-label italic">-</span>
                                    @endif
                                </td>

                                {{-- Jumlah --}}
                                <td class="p-2 text-right text-sm font-medium text-text-primary">
                                    {{ $kasbon->formatted_amount }}
                                </td>

                                {{-- Terbayar (akumulasi cicilan kasbon) --}}
                                <td class="p-2 text-center text-sm text-text-primary">
                                    @if ($kasbon->kasbon_type === 'team' || $isBulananKasbon)
                                        <span class="text-text-label">-</span>
                                    @else
                                        {{ $kasbon->formatted_paid_amount }}
                                    @endif
                                </td>

                                {{-- Sisa Pembayaran --}}
                                <td class="p-2 text-center text-sm text-text-primary">
                                    @if ($kasbon->kasbon_type === 'team' || $isBulananKasbon)
                                        <span class="text-text-label">-</span>
                                    @elseif ($kasbon->payment_status === 'paid')
                                        <span class="text-success font-medium">Rp 0</span>
                                    @else
                                        <span class="font-medium {{ $kasbon->remaining_amount > 0 ? 'text-error' : 'text-success' }}">
                                            {{ $kasbon->formatted_remaining_amount }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Progress --}}
                                <td class="p-2 text-center">
                                    @if ($kasbon->kasbon_type === 'team' || $isBulananKasbon)
                                        <span class="text-text-label">-</span>
                                    @else
                                        <div class="w-full max-w-[80px] mx-auto">
                                            <div class="flex justify-between text-xs mb-1">
                                                <span class="text-text-label">{{ $kasbon->progress_percentage }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all duration-300
                                                    {{ $kasbon->payment_status === 'paid' ? 'bg-success' : ($kasbon->payment_status === 'partial' ? 'bg-primary' : 'bg-warning') }}"
                                                    style="width: {{ $kasbon->progress_percentage }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </td>

                                {{-- Lencana Status Pembayaran --}}
                                <td class="p-2 text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ match($kasbon->payment_status) {
                                            'paid' => 'bg-success-light text-success',
                                            'partial' => 'bg-primary-light text-primary',
                                            default => 'bg-warning-light text-warning',
                                        } }}">
                                        {{ $kasbon->payment_status_label }}
                                    </span>
                                </td>

                                {{-- Tombol Aksi --}}
                                <td class="p-2 text-center text-sm">
                                    <div class="flex justify-center gap-2">
                                        @if ($kasbon->payment_status !== 'paid'
                                            && ! ($kasbon->kasbon_type === 'team' && ! empty($kasbon->project_names))
                                            && ! $isBulananKasbon)
                                            <button type="button"
                                                onclick="openPayModal('{{ $kasbon->kasbon_code }}', '{{ $kasbon->formatted_amount }}', '{{ $kasbon->formatted_remaining_amount }}', {{ $kasbon->remaining_amount }})"
                                                class="flex items-center gap-1 bg-success hover:bg-success-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                title="Bayar Cicilan">
                                                <i class="fa-solid fa-money-bill-wave w-3 h-3"></i>
                                                Bayar
                                            </button>
                                        @elseif ($isBulananKasbon && $kasbon->payment_status !== 'paid')
                                            <span class="flex items-center gap-1 text-xs text-text-label"
                                                title="Kasbon karyawan bulanan tidak bisa dicicil. Otomatis dipotong penuh dari slip gaji.">
                                                <i class="fa-solid fa-file-invoice w-3 h-3"></i>
                                                Potong Slip Gaji
                                            </span>
                                        @endif
                                        @if ($kasbon->status === 'pending' && $kasbon->payment_status === 'unpaid')
                                            <button type="button"
                                                onclick="openModal('editModal{{ $kasbon->kasbon_code }}')"
                                                class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                title="Edit Kasbon">
                                                <i class="fa-solid fa-pen w-3 h-3"></i>
                                                Edit
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-6 py-8 text-center text-text-label">
                                    <i class="fa-solid fa-inbox text-4xl mb-2 text-border"></i>
                                    <p>Tidak ada data kasbon</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
