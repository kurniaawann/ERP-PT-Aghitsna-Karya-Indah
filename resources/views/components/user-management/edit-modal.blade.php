<x-modal id="editModal-{{ $user->id }}" title="Edit User: {{ $user->name }}"
    action="{{ route('user-management.update', $user->id) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Lengkap <span class="text-error">*</span></label>
        <input type="text" name="name" value="{{ $user->name }}" class="w-full border rounded p-2"
            placeholder="Masukkan nama lengkap" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Email <span class="text-error">*</span></label>
        <input type="email" name="email" value="{{ $user->email }}" class="w-full border rounded p-2"
            placeholder="Masukkan email" required maxlength="255"
            oninvalid="this.setCustomValidity('Email tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Role <span class="text-error">*</span></label>
        <select name="role" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Role tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Role</option>
            @foreach (\App\Models\User::ROLES as $value => $label)
                <option value="{{ $value }}" {{ $user->role === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        @if ($user->id === auth()->id())
            <input type="hidden" name="is_active" value="{{ $user->is_active ? '1' : '0' }}">
            <label class="flex items-center gap-2">
                <input type="checkbox" class="w-4 h-4" disabled {{ $user->is_active ? 'checked' : '' }}>
                <span class="text-text-primary">Aktif</span>
                <span class="text-xs text-text-label">(Tidak dapat mengubah status akun sendiri)</span>
            </label>
        @else
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }}
                    class="w-4 h-4 accent-primary">
                <span class="text-text-primary">Aktif</span>
            </label>
        @endif
    </div>
</x-modal>
