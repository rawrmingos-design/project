@extends('template.template')

@section('custom_style')

<style>
    .bg-green-500 {
        background-color: #34D399; 
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


    <div class="relative min-h-screen grid grid-cols-1 md:grid-cols-2 items-center justify-center ">
                <div class="relative col-span-1 hidden md:block"><img alt="{{ $config->judul_web }}" src="{{ URL::asset('assets/image/register.jpg')}}" loading="lazy" decoding="async" class="object-cover object-center w-full h-screen" />
                    <div class="absolute inset-0"></div>
                </div>
                <div class="z-20 w-full col-span-1 px-4">
                    <div class="absolute top-4 left-4 z-20"><a class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 !bg-murky-700 !p-2 hover:!bg-murky-800" href="/" style="outline: none;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg></a></div>
                    <div
                        class="mx-auto w-full max-w-md space-y-8 mt-5">
                        <div>
                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">Masuk</h2>
                            <p class="mt-2 text-sm text-white">Masuk dengan akun yang telah Anda daftarkan.</p>
                        </div>
                     @if(session('error'))
                        <div class="flex items-center justify-between rounded-md bg-rose-500 px-4 py-3 text-sm">
                            <div>{{ session('error') }}</div>
                          
                        </div>
                    @endif
                    
                    @if(session('success'))
                        <div class="flex items-center justify-between rounded-md bg-green-500 px-4 py-3 text-sm">
                            <div>{{ session('success') }}</div>
                           
                        </div>
                    @endif
                    
                    @if ($errors->any())
                        <div class="flex items-center justify-between rounded-md bg-rose-500 px-4 py-3 text-sm">
                            <div>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                           
                        </div>
                    @endif
                    <form action="{{ route('login') }}" method="POST" class="mt-8 space-y-6">
                        @csrf
                        <input type="hidden" name="remember" value="true" />
                        <div class="space-y-3 rounded-md shadow-sm">
                            <div><label for="username" class="block text-xs font-medium text-white pb-2">Username</label>
                                    <div class="flex flex-col items-start"><input class="relative block w-full appearance-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                            type="text" id="username" autocomplete="username" placeholder="Username" name="username" required /></div><span class="text-xs text-rose-500"></span></div>
                            
                            <div>
                               
                                 <div x-data="{ isPassword: true }"><label for="password" class="block text-xs font-medium text-white pb-2">Kata sandi</label>
                                    <div class="relative">
                                        <div class="flex flex-col items-start"><input class="relative block w-full appearance-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                                :type="isPassword ? 'password' : 'text'" id="password" autocomplete="current-password" placeholder="Kata sandi" name="password" /></div><button type="button" class="absolute top-0 right-4 z-20 h-full text-white"
                                            @click="isPassword = !isPassword"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4" :class="{'hidden': !isPassword, 'block': isPassword}" ><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"></path></svg><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4" :class="{'hidden': isPassword, 'block': !isPassword}" ><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></button></div>
                  <span class="text-xs text-rose-500"></span>
                </div>
                   </div>
                                <div class="relative flex justify-end text-sm"><a class="text-decoration-none text-danger text-xs font-medium" style="color:#ff7c7c;" href="{{ route('forgot') }}">Forgot Password?</span></div><br>
                        </div>
                        <div class="flex items-center justify-between">
                        </div>
                        <div>
              </div>
                        <div>
                            <button
                                class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50 group relative flex w-full"
                                id="btnMasuk"
                                disabled
                                type="submit" name="tombol" value="submit">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3" id="iconMasuk">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                        class="h-5 w-5 text-white transition-colors group-hover:text-primary-500"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                        ></path>
                                    </svg>
                                </span>
                                <span id="textMasuk">Masuk</span>
                            </button>
                        </div>
                       <div class="relative flex justify-center text-sm"><span class="px-2 text-foreground">Belum memiliki akun?</span></div>
                       <a class="items-center justify-center rounded-md px-4 py-2 text-sm font-medium duration-300 group relative flex w-full bg-primary-500 text-muted-foreground hover:bg-muted/75"
                    href="{{route('register')}}" style="outline: none;"><span class="absolute inset-y-0 left-0 flex items-center pl-3"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-background transition-colors group-hover:text-muted-foreground"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" ></path></svg></span> Daftar </a>
                    <div
                    class="mt-3"></div>
            </form>
            <div class="flex items-center justify-center gap-4"><button class="items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-primary-foreground duration-300 hover:bg-primary/75 disabled:cursor-not-allowed disabled:opacity-75 hover:!bg-murky-100 group relative flex !bg-foreground !px-2 !text-background"
                    type="button" disabled=""><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="100" height="100" viewBox="0 0 48 48" class="h-5 w-5" aria-hidden="true"><path fill="#fbc02d" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12 s5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24s8.955,20,20,20 s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" ></path><path fill="#e53935" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039 l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"></path><path fill="#4caf50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36 c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"></path><path fill="#1565c0" d="M43.611,20.083L43.595,20L42,20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571 c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"></path></svg></button>
                <button
                    class="items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-primary-foreground duration-300 hover:bg-primary/75 disabled:cursor-not-allowed disabled:opacity-75 hover:!bg-murky-100 group relative flex !bg-foreground !px-2 !text-background"
                    type="button" disabled=""><svg xmlns="http://www.w3.org/2000/svg" width="1365.12" height="1365.12" viewBox="0 0 14222 14222" class="h-5 w-5" aria-hidden="true"><circle cx="7111" cy="7112" r="7111" fill="#1977f3"></circle><path d="M9879 9168l315-2056H8222V5778c0-562 275-1111 1159-1111h897V2917s-814-139-1592-139c-1624 0-2686 984-2686 2767v1567H4194v2056h1806v4969c362 57 733 86 1111 86s749-30 1111-86V9168z" fill="#fff"></path></svg></button>
                    <button
                        class="items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-primary-foreground duration-300 hover:bg-primary/75 disabled:cursor-not-allowed disabled:opacity-75 hover:!bg-murky-100 group relative flex !bg-foreground !px-2 !text-background"
                        type="button" disabled=""><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 fill-black" aria-hidden="true"><path d="M11.6734 7.2221C10.7974 7.2221 9.44138 6.2261 8.01338 6.2621C6.12938 6.2861 4.40138 7.3541 3.42938 9.0461C1.47338 12.4421 2.92538 17.4581 4.83338 20.2181C5.76938 21.5621 6.87338 23.0741 8.33738 23.0261C9.74138 22.9661 10.2694 22.1141 11.9734 22.1141C13.6654 22.1141 14.1454 23.0261 15.6334 22.9901C17.1454 22.9661 18.1054 21.6221 19.0294 20.2661C20.0974 18.7061 20.5414 17.1941 20.5654 17.1101C20.5294 17.0981 17.6254 15.9821 17.5894 12.6221C17.5654 9.8141 19.8814 8.4701 19.9894 8.4101C18.6694 6.4781 16.6414 6.2621 15.9334 6.2141C14.0854 6.0701 12.5374 7.2221 11.6734 7.2221ZM14.7934 4.3901C15.5734 3.4541 16.0894 2.1461 15.9454 0.850098C14.8294 0.898098 13.4854 1.5941 12.6814 2.5301C11.9614 3.3581 11.3374 4.6901 11.5054 5.9621C12.7414 6.0581 14.0134 5.3261 14.7934 4.3901Z" ></path></svg></button>
            </div>

                </div>
        </div>

    </div>


        



@push('custom_script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form');
        const btnMasuk = document.getElementById('btnMasuk');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        function checkValidity() {
            if (usernameInput.value.trim() !== '' && passwordInput.value.trim() !== '') {
                btnMasuk.removeAttribute('disabled');
            } else {
                btnMasuk.setAttribute('disabled', 'true');
            }
        }

        usernameInput.addEventListener('input', checkValidity);
        passwordInput.addEventListener('input', checkValidity);

        // Initial check in case browser auto-fills
        checkValidity();

        form.addEventListener('submit', function() {
            // Ubah tombol menjadi state loading
            btnMasuk.classList.add('btn-loading');
            const iconMasuk = document.getElementById('iconMasuk');
            if(iconMasuk) iconMasuk.style.display = 'none';
        });
    });
</script>
@endpush




@endsection
