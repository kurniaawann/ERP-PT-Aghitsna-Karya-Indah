<x-modal id="addModal" title="Tambah User" action="{{ route('user-management.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Lengkap <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" placeholder="Masukkan nama lengkap"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Email <span class="text-error">*</span></label>
        <input type="email" name="email" class="w-full border rounded p-2" placeholder="Masukkan email" required
            maxlength="255" oninvalid="this.setCustomValidity('Email tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Password <span class="text-error">*</span></label>
        <input type="password" name="password" class="w-full border rounded p-2" placeholder="Minimal 8 karakter"
            required minlength="8" oninvalid="this.setCustomValidity('Password minimal 8 karakter')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Role <span class="text-error">*</span></label>
        <select name="role" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Role tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Role</option>
            @foreach (\App\Models\User::ROLES as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
</x-modal>
