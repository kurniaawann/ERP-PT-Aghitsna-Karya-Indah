{{-- Employee Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('employee.destroy') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-border-light">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">Kode</th>
                            <th class="p-2 text-left">Nama</th>
                            <th class="p-2 text-center">Upah Per Hari</th>
                            <th class="p-2 text-left">Divisi</th>
                            <th class="p-2 text-left">No. Telp</th>
                            <th class="p-2 text-left">Alamat</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-base">
                        @forelse($employees as $employee)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $employee->employee_code }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 font-medium text-primary">{{ $employee->employee_code }}</td>
                                <td class="p-2">{{ $employee->name }}</td>

                                {{-- Upah Per Hari --}}
                                <td class="p-2 text-center">
                                    {{ 'Rp ' . number_format($employee->daily_wage ?? 0, 0, ',', '.') }}
                                </td>

                                <td class="p-2">
                                    <span class="px-2 py-1 bg-primary-light text-primary text-xs rounded-full">
                                        {{ $employee->division ?? '-' }}
                                    </span>
                                </td>

                                <td class="p-2">{{ $employee->phone ?? '-' }}</td>
                                <td class="p-2">{{ $employee->address ?? '-' }}</td>

                                <td class="p-2 text-center">
                                    <x-buttons.edit onclick="openModal('editModal-{{ $employee->employee_code }}')" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center p-4 text-text-secondary">
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