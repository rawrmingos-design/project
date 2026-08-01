@extends('template.template')

@section('content')
    <div class="jsx-1305739042 __className_a8b5ca font-sans">
        <div class="relative flex min-h-screen items-center justify-center px-4 sm:px-32">
            <div class="absolute top-4 left-4 z-20">
                <a class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 !bg-murky-700 !p-2 hover:!bg-murky-800" href="{{ url('/') }}" aria-label="Kembali ke beranda">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>

            <div class="absolute inset-0 z-10 bg-gradient-to-tr from-murky-800 via-murky-800/75"></div>
            <div class="z-20 w-full">
                <div class="mx-auto mt-5 w-full max-w-md space-y-8 lg:mx-0">
                    <div>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">Lupa Kata Sandi</h2>
                        <p class="mt-2 text-sm text-white">Masukkan username. Jika akun dan metode pemulihan tersedia, instruksi reset akan dikirim.</p>
                    </div>

                    @if(session('success'))
                        <div class="flex items-center justify-between rounded-md bg-green-500 px-4 py-3 text-sm">
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="flex items-center justify-between rounded-md bg-rose-500 px-4 py-3 text-sm">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('post.forgot') }}" method="POST" class="mt-8 space-y-6">
                        @csrf
                        <div class="space-y-3 rounded-md shadow-sm">
                            <label for="username" class="block text-xs font-medium text-white pb-2">Username</label>
                            <input
                                class="relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:bg-white focus:outline-none"
                                type="text"
                                id="username"
                                autocomplete="username"
                                placeholder="Masukkan username"
                                name="username"
                                maxlength="255"
                                required
                            />
                        </div>
                        <button class="inline-flex w-full items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75" type="submit">
                            Kirim Instruksi Reset
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
