{{--
    Ringkasan error validasi form ($errors) bergaya Filament.
    Tidak menampilkan apa pun bila tidak ada error.

    Props:
    - heading : judul kotak error
--}}
@props(['heading' => 'Data belum bisa disimpan'])

@if ($errors->any())
    <div {{ $attributes->class('flex gap-3 rounded-xl bg-red-50 p-4 ring-1 ring-inset ring-red-600/20') }} role="alert">
        <x-fi.icon name="exclamation-circle" class="h-5 w-5 shrink-0 text-red-600" />
        <div class="text-sm text-red-700">
            <p class="font-semibold">{{ $heading }}</p>
            <ul class="mt-1 list-disc space-y-0.5 ps-5">
                @foreach (array_unique($errors->all()) as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
