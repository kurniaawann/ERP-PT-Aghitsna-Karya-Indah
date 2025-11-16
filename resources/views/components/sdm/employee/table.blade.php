{{-- Employee Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('employee.destroy') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-gray-300 rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">Kode Karyawan</th>
                            <th class="p-2 text-left">Nama</th>
                            <th class="p-2 text-left">Jabatan</th>
                            <th class="p-2 text-left">No. Telepon</th>
                            <th class="p-2 text-left">Email</th>
                            <th class="p-2 text-center">Gaji Pokok</th>
                            <th class="p-2 text-center">Tanggal Masuk</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $employee->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 font-medium text-primary">{{ $employee->employee_code }}</td>
                                <td class="p-2">{{ $employee->name }}</td>
                                <td class="p-2">{{ $employee->position }}</td>
                                <td class="p-2">{{ $employee->phone ?? '-' }}</td>
                                <td class="p-2">{{ $employee->email ?? '-' }}</td>

                                {{-- Gaji Pokok --}}
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($employee->base_salary, 0, ',', '.') }}
                                </td>

                                {{-- Tanggal Masuk --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($employee->join_date)->format('d/m/Y') }}
                                </td>

                                {{-- Status --}}
                                <td class="p-2 text-center">
                                    @if ($employee->status === 'aktif')
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Non-Aktif
                                        </span>
                                    @endif
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button" onclick="openModal('editModal-{{ $employee->id }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Karyawan">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center p-4 text-gray-500">
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
