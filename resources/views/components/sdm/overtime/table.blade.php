{{-- Overtime Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('overtime.destroy') }}">
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
                            <th class="p-2 text-center">Jam Lembur</th>
                            <th class="p-2 text-center">Tarif/Jam</th>
                            <th class="p-2 text-center">Total Lembur</th>
                            <th class="p-2 text-left">Keterangan</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overtimes as $overtime)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $overtime->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2">{{ $overtime->employee->name }}</td>

                                {{-- Tanggal --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($overtime->attendance_date)->format('d/m/Y') }}
                                </td>

                                {{-- Jam Lembur --}}
                                <td class="p-2 text-center">
                                    {{ number_format($overtime->overtime_hours, 2) }} jam
                                </td>

                                {{-- Tarif/Jam --}}
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($overtime->overtime_rate, 0, ',', '.') }}
                                </td>

                                {{-- Total Lembur --}}
                                <td class="p-2 text-right font-semibold text-green-600">
                                    {{ 'Rp ' . number_format($overtime->overtime_total, 0, ',', '.') }}
                                </td>

                                <td class="p-2">{{ $overtime->notes ?? '-' }}</td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <x-buttons.edit onclick="openModal('editModal-{{ $overtime->id }}')" />
                                    </div>
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