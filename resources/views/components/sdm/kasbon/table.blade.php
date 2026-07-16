{{-- Table Kasbon --}}
<form id="deleteForm" method="POST" action="{{ route('kasbon.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-border-light">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="p-2 text-center">
                                <input type="checkbox" id="select-all"
                                    class="rounded border-border text-primary focus:ring-primary">
                            </th>
                            <th class="p-2 text-left text-xs font-medium text-text-label uppercase tracking-wider">Kode
                            </th>
                            <th class="p-2 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                                Karyawan</th>
                            <th class="p-2 text-center text-xs font-medium text-text-label uppercase tracking-wider">
                                Jenis</th>
                            <th class="p-2 text-right text-xs font-medium text-text-label uppercase tracking-wider">
                                Jumlah</th>
                            <th class="p-2 text-center text-xs font-medium text-text-label uppercase tracking-wider">
                                Tanggal</th>
                            <th class="p-2 text-center text-xs font-medium text-text-label uppercase tracking-wider">
                                Periode</th>
                            <th class="p-2 text-center text-xs font-medium text-text-label uppercase tracking-wider">
                                Status</th>
                            <th class="p-2 text-center text-xs font-medium text-text-label uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-base">
                        @forelse($kasbons as $kasbon)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_kasbons[]" value="{{ $kasbon->kasbon_code }}"
                                        class="row-checkbox w-4 h-4 accent-primary cursor-pointer"
                                        {{ $kasbon->status === 'deducted' ? 'disabled' : '' }}>
                                </td>
                                <td class="p-2 text-sm font-medium text-text-primary">{{ $kasbon->kasbon_code }}</td>
                                <td class="p-2 text-sm text-text-label">
                                    @if ($kasbon->kasbon_type === 'personal' && $kasbon->employee)
                                        <div>
                                            <div class="font-medium text-text-primary">{{ $kasbon->employee->name }}
                                            </div>
                                            <div class="text-xs text-text-label">{{ $kasbon->employee->employee_code }}
                                            </div>
                                        </div>
                                    @elseif ($kasbon->kasbon_type === 'team' && $kasbon->division)
                                        <div>
                                            <div class="font-medium text-secondary">Divisi {{ $kasbon->division }}
                                            </div>
                                            <div class="text-xs text-text-label">Kasbon Tim</div>
                                        </div>
                                    @else
                                        <span class="text-text-label italic">-</span>
                                    @endif
                                </td>
                                <td class="p-2 text-center">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $kasbon->kasbon_type === 'personal' ? 'bg-primary-light text-primary' : 'bg-secondary-light text-secondary' }}">{{ $kasbon->kasbon_type_label }}</span>
                                </td>
                                <td class="p-2 text-right text-sm font-medium text-text-primary">
                                    {{ $kasbon->formatted_amount }}</td>
                                <td class="p-2 text-center text-sm text-text-label">
                                    {{ $kasbon->kasbon_date->format('d M Y') }}</td>
                                <td class="p-2 text-center text-sm text-text-label">
                                    @if ($kasbon->period_start_date && $kasbon->period_end_date)
                                        @php
                                            $start = \Carbon\Carbon::parse($kasbon->period_start_date);
                                            $end = \Carbon\Carbon::parse($kasbon->period_end_date);
                                        @endphp
                                        @if ($start->month === $end->month)
                                            {{ $start->format('d') }}-{{ $end->format('d M Y') }}
                                        @else
                                            {{ $start->format('d M') }} - {{ $end->format('d M Y') }}
                                        @endif
                                    @else
                                        {{ $kasbon->period_month }}/{{ $kasbon->period_year }}@if ($kasbon->week_number)
                                            <span class="text-xs text-text-label"> - Minggu
                                                {{ $kasbon->week_number }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="p-2 text-center">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $kasbon->status === 'pending' ? 'bg-warning-light text-warning' : 'bg-success-light text-success' }}">{{ $kasbon->status_label }}</span>
                                </td>
                                <td class="p-2 text-center text-sm">
                                    @if ($kasbon->status === 'pending')
                                        <div class="flex justify-center gap-2">
                                            <button type="button"
                                                onclick="openModal('editModal{{ $kasbon->kasbon_code }}')"
                                                class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                                title="Edit Kasbon">
                                                <i class="fa-solid fa-pen w-3 h-3"></i>
                                                Edit
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-text-label italic text-xs">Sudah Dipotong</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-text-label">
                                        <i class="fa-solid fa-inbox text-4xl mb-2 text-border"></i>
                                        <p>Tidak ada data kasbon</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
    </form>
