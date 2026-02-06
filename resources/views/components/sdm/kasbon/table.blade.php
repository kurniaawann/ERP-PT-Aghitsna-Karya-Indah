{{-- Table Kasbon --}}
<form id="deleteForm" method="POST" action="{{ route('kasbon.destroy', 'bulk') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto">
        <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                            <input type="checkbox" id="select-all"
                                class="rounded border-border text-primary focus:ring-primary">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                            Kode
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                            Karyawan
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                            Jenis
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                            Jumlah
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                            Tanggal
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                            Periode
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-label uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-border">
                    @forelse($kasbons as $kasbon)
                        <tr class="hover:bg-surface-secondary transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="ids[]" value="{{ $kasbon->kasbon_code }}"
                                    class="row-checkbox rounded border-border text-primary focus:ring-primary"
                                    {{ $kasbon->status === 'deducted' ? 'disabled' : '' }}>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                                {{ $kasbon->kasbon_code }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-label">
                                @if ($kasbon->kasbon_type === 'personal' && $kasbon->employee)
                                    <div>
                                        <div class="font-medium text-text-primary">{{ $kasbon->employee->name }}</div>
                                        <div class="text-xs text-text-label">{{ $kasbon->employee->employee_code }}
                                        </div>
                                    </div>
                                @elseif ($kasbon->kasbon_type === 'team' && $kasbon->division)
                                    <div>
                                        <div class="font-medium text-purple-600">Divisi {{ $kasbon->division }}</div>
                                        <div class="text-xs text-text-label">Kasbon Tim</div>
                                    </div>
                                @else
                                    <span class="text-text-label italic">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $kasbon->kasbon_type === 'personal' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $kasbon->kasbon_type_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                                {{ $kasbon->formatted_amount }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-label">
                                {{ $kasbon->kasbon_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-label">
                                {{ $kasbon->period_month }}/{{ $kasbon->period_year }}
                                @if ($kasbon->week_number)
                                    <span class="text-xs text-text-label"> - Minggu {{ $kasbon->week_number }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $kasbon->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $kasbon->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if ($kasbon->status === 'pending')
                                    <button type="button" onclick="openModal('editModal{{ $kasbon->kasbon_code }}')"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
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
