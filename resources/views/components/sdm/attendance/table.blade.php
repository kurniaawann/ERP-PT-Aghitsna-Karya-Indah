{{-- Attendance Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('attendance.destroy') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">Nama Karyawan</th>
                            <th class="p-2 text-center">Tanggal</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-left">Keterangan</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $attendance->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2">{{ $attendance->employee->name }}</td>

                                {{-- Tanggal --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('d/m/Y') }}
                                </td>

                                {{-- Status --}}
                                <td class="p-2 text-center">
                                    @if ($attendance->status === 'hadir')
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Hadir
                                        </span>
                                    @elseif ($attendance->status === 'izin')
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Izin
                                        </span>
                                    @elseif ($attendance->status === 'sakit')
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Sakit
                                        </span>
                                    @elseif ($attendance->status === 'cuti')
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Cuti
                                        </span>
                                    @endif
                                </td>

                                <td class="p-2">{{ $attendance->notes ?? '-' }}</td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button" onclick="openModal('editModal-{{ $attendance->id }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Absensi">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-4 text-text-secondary">
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
