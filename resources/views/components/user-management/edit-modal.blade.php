<x-modal id="editModal-{{ $user->id }}" title="Edit User: {{ $user->name }}"
    action="{{ route('user-management.update', $user->id) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Lengkap <span class="text-error">*</span></label>
        <input type="text" name="name" value="{{ $user->name }}" class="w-full border rounded p-2"
            placeholder="Masukkan nama lengkap" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Email <span class="text-error">*</span></label>
        <input type="email" name="email" value="{{ $user->email }}" class="w-full border rounded p-2"
            placeholder="Masukkan email" required maxlength="255"
            oninvalid="this.setCustomValidity('Email tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Role <span class="text-error">*</span></label>
        <select name="role" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Role tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Role</option>
            @foreach (\App\Models\User::ROLES as $value => $label)
                <option value="{{ $value }}" {{ $user->role === $value ? 'selected' : '' }}>{{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</x-modal>
