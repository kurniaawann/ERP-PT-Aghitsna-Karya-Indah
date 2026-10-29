{{--
    Textarea bergaya Filament. Atribut (name, rows, placeholder, ...)
    diteruskan ke <textarea>; isi awal lewat slot.
--}}
<div class="flex w-full rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus-within:ring-2 focus-within:ring-primary has-[:disabled]:bg-gray-50">
    <textarea {{ $attributes->merge(['rows' => 3])->class('block w-full resize-y border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none placeholder:text-gray-400 disabled:cursor-not-allowed disabled:text-gray-500 sm:text-sm sm:leading-6') }}>{{ $slot }}</textarea>
</div>
