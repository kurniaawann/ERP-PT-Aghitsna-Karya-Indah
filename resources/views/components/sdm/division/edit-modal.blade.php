{{-- Modal Edit Divisi --}}
<x-modal id="editModal-{{ $division->id }}" title="Edit Divisi" action="{{ route('division.update', $division->id) }}"
    method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Divisi <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" value="{{ $division->name }}" required
            maxlength="100">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Deskripsi</label>
        <textarea name="description" class="w-full border rounded p-2" rows="3" maxlength="500">{{ $division->description }}</textarea>
    </div>
</x-modal>
