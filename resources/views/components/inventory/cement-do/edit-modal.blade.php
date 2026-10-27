{{-- Modal Edit DO Semen (gaya Filament): info DO + repeater baris Data Semen --}}
<x-fi.modal id="editModal-{{ $cementDeliveryOrder->no }}" heading="Edit DO Semen" :description="'No. DO: ' . $cementDeliveryOrder->no"
    icon="pencil-square" :action="route('cement-do.update', $cementDeliveryOrder->no)" method="PUT" submitLabel="Update"
    width="6xl">

    {{-- Info DO (header) --}}
    <x-fi.fieldset label="Informasi DO">
        <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">

            {{-- Field: No (Readonly) --}}
            <x-fi.field label="No" hint="No tidak dapat diubah" class="sm:col-span-2">
                <x-fi.input :value="$cementDeliveryOrder->no" prefix-icon="lock-closed" readonly />
            </x-fi.field>

            {{-- Field: Tanggal DO --}}
            <x-fi.field label="Tanggal DO" for="edit-tanggal-{{ $cementDeliveryOrder->no }}" required>
                <x-fi.input type="date" id="edit-tanggal-{{ $cementDeliveryOrder->no }}" name="tanggal"
                    :value="$cementDeliveryOrder->tanggal?->format('Y-m-d')" required
                    oninvalid="this.setCustomValidity('Tanggal DO tidak boleh kosong')" oninput="this.setCustomValidity('')" />
            </x-fi.field>

            {{-- Field: Tanggal Datang --}}
            <x-fi.field label="Tanggal Datang" for="edit-tanggal-datang-{{ $cementDeliveryOrder->no }}">
                <x-fi.input type="date" id="edit-tanggal-datang-{{ $cementDeliveryOrder->no }}" name="tanggal_datang"
                    :value="$cementDeliveryOrder->tanggal_datang?->format('Y-m-d')" />
            </x-fi.field>

            {{-- Field: Tanggal Bayar --}}
            <x-fi.field label="Tanggal Bayar" for="edit-tanggal-bayar-{{ $cementDeliveryOrder->no }}">
                <x-fi.input type="date" id="edit-tanggal-bayar-{{ $cementDeliveryOrder->no }}" name="tanggal_bayar"
                    :value="$cementDeliveryOrder->tanggal_bayar?->format('Y-m-d')" />
            </x-fi.field>

            {{-- Field: Harga Modal (per DO) --}}
            <x-fi.field label="Harga Modal" for="edit-harga-modal-{{ $cementDeliveryOrder->no }}" required
                hint="Total modal untuk seluruh baris semen dalam DO ini.">
                <x-fi.input name="harga_modal"
                    value="Rp {{ number_format($cementDeliveryOrder->harga_modal ?? 0, 0, ',', '.') }}"
                    prefix-icon="banknotes" required inputmode="numeric"
                    id="edit-harga-modal-{{ $cementDeliveryOrder->no }}"
                    oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
            </x-fi.field>
        </div>
    </x-fi.fieldset>

    {{-- Baris Data Semen (detail) --}}
    <x-inventory.cement-do.cement-rows-table mode="edit">
        @forelse ($cementDeliveryOrder->cements as $index => $cement)
            @include('components.inventory.cement-do.cement-row', ['index' => $index, 'cement' => $cement])
        @empty
            @include('components.inventory.cement-do.cement-row', ['index' => 0, 'cement' => null])
        @endforelse
    </x-inventory.cement-do.cement-rows-table>
</x-fi.modal>
