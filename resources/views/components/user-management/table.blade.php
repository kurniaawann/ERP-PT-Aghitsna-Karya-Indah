{{-- Delete form standalone (tidak boleh nested di dalam form lain) --}}
<form id="deleteForm" method="POST" action="{{ route('user-management.destroy') }}">
    @csrf
    @method('DELETE')
</form>

{{-- Table tanpa nested form --}}
<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="inline-block min-w-full align-middle">
        <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                        <th class="p-2 text-left">Nama</th>
                        <th class="p-2 text-left">Email</th>
                        <th class="p-2 text-center">Role</th>
                        <th class="p-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-t hover:bg-surface-secondary">
                            <td class="p-2 text-center">
                                @if ($user->id !== auth()->id())
                                    {{-- form="deleteForm" mengaitkan checkbox ke deleteForm meski berada di luar form --}}
                                    <input type="checkbox" name="ids[]" value="{{ $user->id }}" form="deleteForm"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                @endif
                            </td>
                            <td class="p-2 font-medium">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                    <span class="ml-1 text-xs text-primary font-normal">(Anda)</span>
                                @endif
                            </td>
                            <td class="p-2 text-text-label">{{ $user->email }}</td>
                            <td class="p-2 text-center">
                                @php
                                    $roleColors = [
                                        'superadmin' => 'bg-secondary-light text-secondary',
                                        'admin' => 'bg-primary-light text-primary',
                                        'keuangan' => 'bg-success-light text-success',
                                        'sdm' => 'bg-warning-light text-warning',
                                        'administrasi' => 'bg-info-light text-info',
                                    ];
                                    $color = $roleColors[$user->role] ?? 'bg-button-cancel text-button-inactive';
                                @endphp
                                <span class="px-2 py-1 {{ $color }} text-xs rounded-full font-medium">
                                    {{ $user->role_label }}
                                </span>
                            </td>
                            <td class="p-2 text-center">
                                <x-buttons.edit onclick="openModal('editModal-{{ $user->id }}')" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-4 text-text-secondary">
                                Data tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>