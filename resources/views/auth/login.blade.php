{{-- =====================================================================
     Halaman: Login
     Layout split-screen:
     - Kiri (lg ke atas): panel brand biru bergaris "blueprint" + ilustrasi
       Design and Build (warna aksen oranye & hijau dari logo) + tagline.
     - Kanan: logo, sapaan, form login (email & kata sandi), footer.
     Form tetap POST ke route('login.post') (AuthController@login) dengan field
     email & password; tombol memakai partials.loading-submit-button.
     Pesan: session('status') (mis. setelah reset password) & error login.
     ===================================================================== --}}
@extends('layouts.guest')

@section('title', 'Login | ERP PT Aghitsna Karya Indah')

@section('main-class', 'min-h-screen w-full')

@push('styles')
    <style>
        @keyframes login-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes login-wave {
            0%, 100% { transform: skewY(0deg); }
            50% { transform: skewY(-4deg); }
        }

        @media (prefers-reduced-motion: no-preference) {
            .login-float { animation: login-float 6s ease-in-out infinite; }
            .login-float-delay { animation: login-float 7s ease-in-out 1.5s infinite; }
            .login-wave { animation: login-wave 3s ease-in-out infinite; transform-box: fill-box; transform-origin: left center; }
        }
    </style>
@endpush

@section('content')
    <div class="flex min-h-screen w-full bg-white">

        {{-- SECTION: Panel Brand (kiri, desktop) --}}
        <aside class="relative hidden overflow-hidden bg-gradient-to-br from-blue-700 via-primary to-blue-900 lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-1/2 xl:w-7/12">
            {{-- Pola grid blueprint --}}
            <div class="absolute inset-0 opacity-60"
                style="background-image: linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px); background-size: 36px 36px;"
                aria-hidden="true"></div>

            {{-- Cahaya aksen warna logo --}}
            <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-sky-300/30 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-[#0FAE52]/25 blur-3xl" aria-hidden="true"></div>

            <div class="relative z-10 flex w-full flex-col gap-6 p-10 xl:p-14">

                {{-- Nama perusahaan --}}
                <div class="flex shrink-0 items-center gap-3 text-white">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20 backdrop-blur">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M3 20h18M5 20V10l7-6 7 6v10" stroke-linejoin="round" />
                            <path d="M10 20v-5h4v5" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div>
                        <p class="font-semibold leading-tight">PT Aghitsna Karya Indah</p>
                        <p class="text-sm text-blue-100">Design and Build</p>
                    </div>
                </div>

                {{-- Ilustrasi: bangunan beratap segitiga (ala logo), crane, kartu dashboard ERP.
                     Tingginya mengikuti sisa ruang layar agar tagline tidak terpotong. --}}
                <div class="flex min-h-0 flex-1 items-center justify-center">
                    <svg viewBox="0 0 520 440" class="h-full max-h-[460px] w-full max-w-xl" fill="none" aria-hidden="true">
                        {{-- Bayangan tanah --}}
                        <ellipse cx="260" cy="386" rx="240" ry="20" fill="#fff" fill-opacity=".08" />

                        {{-- Lembar blueprint di belakang bangunan --}}
                        <rect x="140" y="60" width="290" height="230" rx="12" fill="#fff" fill-opacity=".05"
                            stroke="#fff" stroke-opacity=".3" stroke-dasharray="6 6" />

                        {{-- Crane --}}
                        <g stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M78 382V70M98 382V70" />
                            <path d="M78 382l20-26-20-26 20-26-20-26 20-26-20-26 20-26-20-26 20-26-20-26 20-26" stroke-opacity=".7" stroke-width="2" />
                            <path d="M46 70h196M46 84h196" />
                            <path d="M78 70l-14 14M98 70l14 14M130 70l14 14M162 70l14 14M194 70l14 14M226 70l14 14" stroke-opacity=".7" stroke-width="2" />
                            <path d="M88 70V40l-38 30M88 40l150 30" stroke-opacity=".8" stroke-width="2" />
                            <path d="M214 84v42" stroke-width="2" />
                        </g>
                        <rect x="36" y="86" width="30" height="22" rx="3" fill="#fff" fill-opacity=".25" stroke="#fff" stroke-width="2" />
                        <rect x="72" y="88" width="32" height="26" rx="4" fill="#FC6703" />
                        <rect x="176" y="126" width="76" height="12" rx="3" fill="#0FAE52" stroke="#fff" stroke-width="2" />

                        {{-- Bangunan --}}
                        <rect x="170" y="200" width="200" height="182" fill="#fff" fill-opacity=".12" stroke="#fff" stroke-width="3" />
                        <g fill="#fff" fill-opacity=".22" stroke="#fff" stroke-width="2">
                            <rect x="190" y="226" width="46" height="38" rx="3" />
                            <rect x="304" y="226" width="46" height="38" rx="3" />
                            <rect x="190" y="290" width="46" height="38" rx="3" />
                            <rect x="304" y="290" width="46" height="38" rx="3" />
                        </g>
                        <path d="M213 226v38M190 245h46M327 226v38M304 245h46M213 290v38M190 309h46M327 290v38M304 309h46"
                            stroke="#fff" stroke-opacity=".6" stroke-width="1.5" />
                        <rect x="246" y="298" width="48" height="84" rx="4" fill="#FC6703" stroke="#fff" stroke-width="2" />
                        <circle cx="284" cy="342" r="3" fill="#fff" />

                        {{-- Atap segitiga oranye + bendera hijau (dari logo) --}}
                        <path d="M152 206 270 96l118 110" stroke="#FC6703" stroke-width="12" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M270 96V56" stroke="#fff" stroke-width="3" stroke-linecap="round" />
                        <path class="login-wave" d="M271 56c10-7 20-7 30 0s20 7 30 0v20c-10 7-20 7-30 0s-20-7-30 0z" fill="#0FAE52" />

                        {{-- Pagar (ala garis pada logo) --}}
                        <g fill="#FC6703">
                            <rect x="122" y="352" width="8" height="30" rx="2" />
                            <rect x="136" y="352" width="8" height="30" rx="2" />
                            <rect x="150" y="352" width="8" height="30" rx="2" />
                        </g>

                        {{-- Garis ukur di bawah bangunan --}}
                        <path d="M170 414h200M170 407v14M370 407v14" stroke="#fff" stroke-opacity=".5" stroke-width="2" />
                        <text x="270" y="434" fill="#fff" fill-opacity=".7" font-size="13" text-anchor="middle"
                            font-family="ui-sans-serif, system-ui">12.000 mm</text>

                        {{-- Pohon --}}
                        <path d="M462 382v-36" stroke="#fff" stroke-width="3" stroke-linecap="round" />
                        <circle cx="462" cy="334" r="22" fill="#0FAE52" />
                        <circle cx="478" cy="346" r="14" fill="#0FAE52" fill-opacity=".8" />

                        {{-- Roda gigi --}}
                        <g stroke="#fff" stroke-opacity=".5">
                            <circle cx="470" cy="66" r="20" stroke-width="8" stroke-dasharray="5.5 5" />
                            <circle cx="470" cy="66" r="8" stroke-width="3" />
                        </g>

                        {{-- Kartu dashboard ERP (melayang) --}}
                        <g class="login-float">
                            <rect x="368" y="146" width="140" height="112" rx="14" fill="#0b1f5c" fill-opacity=".25" transform="translate(4 6)" />
                            <rect x="368" y="146" width="140" height="112" rx="14" fill="#fff" />
                            <rect x="382" y="160" width="54" height="7" rx="3.5" fill="#CBD5E1" />
                            <rect x="382" y="173" width="34" height="6" rx="3" fill="#E2E8F0" />
                            <path d="M382 242h112" stroke="#E2E8F0" stroke-width="2" />
                            <rect x="388" y="214" width="14" height="28" rx="3" fill="#2563EB" fill-opacity=".35" />
                            <rect x="410" y="200" width="14" height="42" rx="3" fill="#2563EB" />
                            <rect x="432" y="208" width="14" height="34" rx="3" fill="#FC6703" />
                            <rect x="454" y="192" width="14" height="50" rx="3" fill="#0FAE52" />
                            <circle cx="494" cy="150" r="15" fill="#0FAE52" stroke="#fff" stroke-width="3" />
                            <path d="m487 150 5 5 9-10" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        </g>

                        {{-- Kartu stok (melayang) --}}
                        <g class="login-float-delay">
                            <rect x="18" y="246" width="124" height="64" rx="12" fill="#0b1f5c" fill-opacity=".25" transform="translate(4 6)" />
                            <rect x="18" y="246" width="124" height="64" rx="12" fill="#fff" />
                            <rect x="30" y="260" width="36" height="36" rx="9" fill="#FC6703" fill-opacity=".15" />
                            <path d="M39 272l9-5 9 5v11l-9 5-9-5zM39 272l9 5 9-5M48 277v11" stroke="#FC6703" stroke-width="2" stroke-linejoin="round" />
                            <rect x="76" y="264" width="52" height="7" rx="3.5" fill="#CBD5E1" />
                            <rect x="76" y="279" width="34" height="9" rx="4.5" fill="#0FAE52" fill-opacity=".9" />
                        </g>

                        {{-- Titik dekorasi --}}
                        <g fill="#fff" fill-opacity=".35">
                            <circle cx="30" cy="40" r="3" />
                            <circle cx="500" cy="300" r="3" />
                            <circle cx="420" cy="30" r="2.5" />
                            <circle cx="130" cy="180" r="2.5" />
                        </g>
                    </svg>
                </div>

                {{-- Tagline --}}
                <div class="max-w-xl shrink-0 text-white">
                    <h2 class="text-3xl font-bold leading-tight">
                        Dari rancangan hingga serah terima, semua tercatat rapi.
                    </h2>
                    <p class="mt-3 text-blue-100">
                        Kelola inventory, keuangan, administrasi, dan laporan proyek dalam satu sistem terpadu.
                    </p>
                    <ul class="mt-6 flex flex-wrap gap-2 text-sm">
                        @foreach (['Inventory', 'Keuangan', 'Administrasi', 'Laporan'] as $module)
                            <li class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/20 backdrop-blur">
                                <span @class([
                                    'h-2 w-2 rounded-full',
                                    'bg-[#FC6703]' => $loop->even,
                                    'bg-[#0FAE52]' => $loop->odd,
                                ])></span>
                                {{ $module }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </aside>

        {{-- SECTION: Form Login (kanan) --}}
        <section class="flex min-h-screen flex-1 flex-col">
            {{-- Garis aksen warna brand (mobile) --}}
            <div class="h-1.5 bg-gradient-to-r from-primary via-[#0FAE52] to-[#FC6703] lg:hidden" aria-hidden="true"></div>

            <div class="flex flex-1 items-center justify-center px-6 py-10 sm:px-12">
                <div class="w-full max-w-md">

                    {{-- Logo --}}
                    <img src="{{ asset('images/logo.jpeg') }}" alt="Logo PT Aghitsna Karya Indah"
                        class="h-20 w-auto object-contain sm:h-28">

                    {{-- Sapaan --}}
                    <h1 class="mt-8 text-4xl font-bold tracking-tight text-gray-950">Selamat Datang</h1>
                    <p class="mt-2 text-gray-500">Masuk untuk mengakses akun ERP Anda.</p>

                    {{-- Pesan sukses (mis. setelah reset password) --}}
                    @if (session('status'))
                        <div class="mt-6 flex items-start gap-3 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-600/20" role="status">
                            <svg viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    {{-- Pesan error login / validasi --}}
                    @if ($errors->any())
                        <div class="mt-6 flex items-start gap-3 rounded-xl bg-red-50 p-4 text-sm text-red-700 ring-1 ring-inset ring-red-600/20" role="alert">
                            <svg viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 h-5 w-5 shrink-0 text-red-600" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    {{-- FORM LOGIN --}}
                    <form action="{{ route('login.post') }}" method="POST" class="mt-8 space-y-6" autocomplete="on">
                        @csrf

                        {{-- EMAIL (label di garis tepi) --}}
                        <div class="group relative">
                            <label for="email"
                                class="absolute -top-2.5 start-3 z-10 bg-white px-1 text-[0.8125rem] font-medium leading-5 text-gray-600 transition group-focus-within:text-primary">
                                Email<span class="text-red-600">*</span>
                            </label>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400 transition group-focus-within:text-primary"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                                autocomplete="username" placeholder="nama@email.com"
                                @class([
                                    'block h-12 w-full rounded-xl border bg-white pe-4 ps-12 text-base text-gray-950 outline-none transition placeholder:text-gray-400 focus:ring-4',
                                    'border-red-500 focus:border-red-500 focus:ring-red-500/15' => $errors->has('email'),
                                    'border-gray-300 focus:border-primary focus:ring-primary/15' => ! $errors->has('email'),
                                ])>
                        </div>

                        {{-- PASSWORD (label di garis tepi + tombol lihat sandi) --}}
                        <div>
                            <div class="group relative">
                                <label for="password"
                                    class="absolute -top-2.5 start-3 z-10 bg-white px-1 text-[0.8125rem] font-medium leading-5 text-gray-600 transition group-focus-within:text-primary">
                                    Kata Sandi<span class="text-red-600">*</span>
                                </label>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                    class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400 transition group-focus-within:text-primary"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <input type="password" name="password" id="password" required
                                    autocomplete="current-password" placeholder="Masukkan kata sandi"
                                    @class([
                                        'block h-12 w-full rounded-xl border bg-white pe-12 ps-12 text-base text-gray-950 outline-none transition placeholder:text-gray-400 focus:ring-4',
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/15' => $errors->has('password'),
                                        'border-gray-300 focus:border-primary focus:ring-primary/15' => ! $errors->has('password'),
                                    ])>
                                @include('partials.password-visibility-toggle', ['targetId' => 'password'])
                            </div>

                            <div class="mt-3 text-end">
                                <a href="{{ route('password.request') }}"
                                    class="text-sm font-medium text-primary hover:text-primary-hover hover:underline">
                                    Lupa kata sandi?
                                </a>
                            </div>
                        </div>

                        {{-- TOMBOL LOGIN (loading reusable) --}}
                        @include('partials.loading-submit-button', [
                            'id' => 'loginBtn',
                            'textId' => 'loginBtnText',
                            'spinnerId' => 'loginBtnSpinner',
                            'buttonText' => 'Masuk',
                            'buttonType' => 'submit',
                            'buttonClass' =>
                                'h-12 w-full rounded-xl bg-primary text-base font-semibold text-white shadow-lg shadow-primary/30 transition hover:bg-primary-hover focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/30 disabled:cursor-wait disabled:opacity-80',
                        ])
                    </form>

                    <p class="mt-8 text-center text-sm text-gray-500">
                        Belum punya akun atau tidak bisa masuk? Hubungi administrator.
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <footer class="px-6 pb-6 text-center text-sm text-gray-400">
                © {{ date('Y') }} PT Aghitsna Karya Indah · Design and Build
            </footer>
        </section>
    </div>
@endsection
