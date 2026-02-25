@extends('template.template')

@section('custom_style')
    <style>
        .accordion-button {
            box-shadow: none !important;
        }

        .product .box {
            margin-bottom: 40px;
        }
        /* Style khusus untuk input referral yang terkunci */
        input#kode_referral[readonly] {
            background-color: #2d2d30 !important; /* Warna lebih gelap */
            color: #9ca3af !important;           /* Warna teks agak pudar */
            cursor: not-allowed;                 /* Kursor tanda dilarang */
            border-color: #3f3f46;               /* Warna border lebih redup */
            pointer-events: none;                /* Mencegah klik/fokus sama sekali */
        }
        /* Style untuk tombol loading */
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-top: -10px;
            margin-left: -10px;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-right-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
@endsection


@section('content')
    <main class="relative">
        <div id="app">
            <div class="relative min-h-screen grid grid-cols-1 md:grid-cols-2 items-center justify-center ">
                <div class="relative col-span-1 hidden md:block"><img alt="{{ $config->judul_web }}" src="{{ URL::asset('assets/image/register.jpg')}}" loading="lazy" decoding="async" class="object-cover object-center w-full h-screen" />
                    <div class="absolute inset-0 "></div>
                </div>
                <div class="z-20 w-full col-span-1 px-4">
                    <div class="absolute top-4 left-4 z-20"><a
                            class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 !bg-murky-700 !p-2 hover:!bg-murky-800"
                            href="/" style="outline: none;"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg></a></div>
                    <div class="mx-auto w-full max-w-md space-y-8 mt-5">
                        <div>
                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">Daftar</h2>
                            <p class="mt-2 text-sm text-white">Masukkan informasi pendaftaran yang valid.</p>
                        </div>
                        @if ($errors->any())
                            <div class="flex items-center justify-between rounded-md bg-rose-500 px-4 py-3 text-sm">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form id="myForm" action="{{ route('register') }}" method="POST" class="mt-8 space-y-6">
                            @csrf
                            <div class="space-y-3 rounded-md shadow-sm">
                                <div class="flex space-x-4">
                                    <div class="w-1/2"><label for="nama"
                                            class="block text-xs font-medium text-white pb-2">Nama lengkap</label>
                                        <div class="flex flex-col items-start"><input
                                                class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                                type="text" autocomplete="name" id="nama" placeholder="Nama lengkap"
                                                name="nama" required=""></div><span
                                            class="text-xs text-rose-500"></span>
                                    </div>
                                    <div class="w-1/2"><label for="username"
                                            class="block text-xs font-medium text-white pb-2">Username</label>
                                        <div class="flex flex-col items-start"><input
                                                class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                                type="text" id="username" autocomplete="username" placeholder="Username"
                                                name="username" required=""></div><span
                                            class="text-xs text-rose-500"></span>
                                    </div>
                                </div>
                                <div><label for="email" class="block text-xs font-medium text-white pb-2">Email</label>
                                    <div class="flex flex-col items-start"><input
                                            class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                            type="email" id="email" placeholder="Email" name="email" required="">
                                    </div><span class="text-xs text-rose-500"></span>
                                </div>
                                <div><label for="wa" class="block text-xs font-medium text-white pb-2">No.
                                        WhatsApp</label>
                                    <div class="flex flex-col items-start"><input
                                            class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                            type="number" id="wa" placeholder="No. WhatsApp" name="no_wa" required=""></div><span class="text-xs text-rose-500"></span>
                                </div>
                                <div><label for="kode_referral" class="block text-xs font-medium text-white pb-2">Kode
                                        Referral (Opsional)</label>
                                    <div class="flex flex-col items-start"><input
                                            class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                            type="text" id="kode_referral" placeholder="Kode Referral (Opsional)"
                                            {{ request('ref') || \Illuminate\Support\Facades\Cookie::get('referral_code') ? 'readonly' : '' }}
                                            name="kode_referral" value="{{ request('kode_referral') ?? request('ref') ?? \Illuminate\Support\Facades\Cookie::get('referral_code') }}"></div><span class="text-xs text-rose-500"></span>
                                </div>
                                @if(request('ref') || \Illuminate\Support\Facades\Cookie::get('referral_code'))
                                    <span class="text-[10px] text-green-400 mt-1 italic">Kode referral otomatis diterapkan.</span>
                                @endif
                                <div class="flex space-x-4">
                                    <div class="w-1/2">
                                        <div x-data="{ isPassword: true }"><label for="password"
                                                class="block text-xs font-medium text-white pb-2">Kata sandi</label>
                                            <div class="relative">
                                                <div class="flex flex-col items-start"><input
                                                        class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                                        :type="isPassword ? 'password' : 'text'" type="password"
                                                        id="password" placeholder="Kata sandi" name="password"
                                                        required=""></div><button type="button"
                                                    class="absolute top-0 right-4 z-20 h-full text-white"
                                                    @click="isPassword = !isPassword"><svg
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        aria-hidden="true" class="h-4 w-4 block"
                                                        :class="{ 'hidden': !isPassword, 'block': isPassword }">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88">
                                                        </path>
                                                    </svg><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        aria-hidden="true" class="h-4 w-4 hidden"
                                                        :class="{ 'hidden': isPassword, 'block': !isPassword }">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z">
                                                        </path>
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg></button>
                                            </div><span class="text-xs text-rose-500"></span>
                                        </div>
                                    </div>
                                    <div class="w-1/2">
                                        <div x-data="{ isPassword: true }"><label for="passwordd"
                                                class="block text-xs font-medium text-white pb-2">Konfirmasi kata
                                                sandi</label>
                                            <div class="relative">
                                                <div class="flex flex-col items-start"><input
                                                        class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                                        :type="isPassword ? 'password' : 'text'" type="password"
                                                        id="passwordd" placeholder="Konfirmasi Kata sandi"
                                                        name="passwordd" required=""></div><button type="button"
                                                    class="absolute top-0 right-4 z-20 h-full text-white"
                                                    @click="isPassword = !isPassword"><svg
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        aria-hidden="true" class="h-4 w-4 block"
                                                        :class="{ 'hidden': !isPassword, 'block': isPassword }">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88">
                                                        </path>
                                                    </svg><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        aria-hidden="true" class="h-4 w-4 hidden"
                                                        :class="{ 'hidden': isPassword, 'block': !isPassword }">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z">
                                                        </path>
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg></button>
                                            </div><span class="text-xs text-rose-500"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="checkbox"
                                        class="h-4 w-4 cursor-pointer rounded border-murky-600 bg-murky-700 text-primary-500 "
                                        id="tac" name="tac" />
                                    <label for="tac"
                                        class="block text-xs font-medium text-white ml-3 block select-none text-sm text-white">
                                        Saya setuju dengan <a class="text-primary-500" style="outline: none;"
                                            href="/id/privacy-policy">Kebijakan Pribadi</a> dan <a
                                            class="text-primary-500" style="outline: none;"
                                            href="/id/terms-and-condition">Syarat dan Ketentuan</a>.
                                    </label>
                                </div>

                            </div>
                            <div><button
                                    class="items-center justify-center rounded-md px-4 py-2 text-sm font-medium duration-300 group relative flex w-full bg-primary-500 text-muted-foreground hover:bg-muted/75 disabled:opacity-50 disabled:cursor-not-allowed"
                                    id="btnRegister"
                                    disabled
                                    type="submit"><span class="absolute inset-y-0 left-0 flex items-center pl-3" id="iconRegister"><svg
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                            class="h-5 w-5 text-white ">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z">
                                            </path>
                                        </svg></span> <span id="textRegister">Daftar</span> </button></div>
                            <div class="relative flex justify-center text-sm"><span class="px-2 text-foreground">Sudah
                                    memiliki akun?</span></div><a
                                class="items-center justify-center rounded-md px-4 py-2 text-sm font-medium duration-300 group relative flex w-full bg-primary-500 text-muted-foreground hover:bg-muted/75"
                                href="{{ route('login') }}" style="outline: none;"><span
                                    class="absolute inset-y-0 left-0 flex items-center pl-3"><svg
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                        class="h-5 w-5 text-background transition-colors group-hover:text-muted-foreground">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z">
                                        </path>
                                    </svg></span> Masuk </a>
                            <div class="mt-3"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@push('custom_script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('myForm');
        const btnRegister = document.getElementById('btnRegister');
        const textRegister = document.getElementById('textRegister');
        const iconRegister = document.getElementById('iconRegister');

        function checkValidity() {
            // Kita ambil nilai terbaru langsung di dalam fungsi ini
            const nama = document.getElementsByName('nama')[0]?.value.trim() || '';
            const username = document.getElementsByName('username')[0]?.value.trim() || '';
            const email = document.getElementsByName('email')[0]?.value.trim() || '';
            const wa = document.getElementsByName('no_wa')[0]?.value.trim() || '';
            const password = document.getElementsByName('password')[0]?.value || '';
            const passwordd = document.getElementsByName('passwordd')[0]?.value || '';
            const agreement = document.getElementById('tac')?.checked || false;

            // Syarat tombol menyala:
            const isValid = 
                nama !== '' && 
                username.length >= 6 && 
                email.includes('@') && 
                wa.length >= 10 && 
                password.length >= 6 && 
                password === passwordd && 
                agreement === true;

            // Update status tombol
            btnRegister.disabled = !isValid;

            // Update visual agar user tahu tombol sudah aktif
            if (isValid) {
                btnRegister.classList.remove('opacity-50', 'cursor-not-allowed');
                btnRegister.classList.add('hover:bg-primary-400');
            } else {
                btnRegister.classList.add('opacity-50', 'cursor-not-allowed');
                btnRegister.classList.remove('hover:bg-primary-400');
            }
        }

        // Pantau input dan perubahan (termasuk checkbox)
        form.addEventListener('input', checkValidity);
        form.addEventListener('change', checkValidity);

        // Jalankan sekali saat halaman dimuat (untuk handle autofill browser)
        setTimeout(checkValidity, 500);

        // State Loading saat form dikirim
        form.addEventListener('submit', function (e) {
            // Mencegah klik ganda
            btnRegister.disabled = true;
            btnRegister.classList.add('btn-loading'); // Gunakan CSS spinner yang Anda buat
            
            if (textRegister) textRegister.textContent = 'Memproses...';
            if (iconRegister) iconRegister.style.display = 'none';
        });
        });
    </script>
@endpush

@endsection
