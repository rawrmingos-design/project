@extends('template.template')

@section('content')


    <div class="jsx-1305739042 __className_a8b5ca font-sans">
        <div class="relative flex min-h-screen items-center justify-center px-4 sm:px-32">
            <div class="absolute top-4 left-4 z-20">
                <a
                    class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 !bg-murky-700 !p-2 hover:!bg-murky-800"
                    href="{{url('/')}}"
                    style="outline: none;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>
            <!--<img-->

            <!--    class="object-cover object-center"-->
            <!--    sizes="100vw"-->
            <!--    src=""-->
            <!--    style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;"-->
            <!--/>-->
            <div class="absolute inset-0 z-10 bg-gradient-to-tr from-murky-800 via-murky-800/75 "></div>
            <main class="z-20 w-full">
                <div class="mx-auto w-full max-w-md space-y-8 lg:mx-0 mt-5">
                    <div>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">Masuk Dashboard</h2>
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
                        <form method="POST" action="{{ route('post.adminlogin') }}" class="mt-8 space-y-6">
                            @csrf
                        <input type="hidden" name="remember" value="true" />
                        <div class="space-y-3 rounded-md shadow-sm">
                            <div>
                                <label for="username" class="block text-xs font-medium text-white pb-2">Username</label>
                                <div class="flex flex-col items-start">
                                    <input
                                        class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                        type="text"
                                        id="username"
                                        autocomplete="username"
                                        placeholder="Username"
                                        name="username"
                                        required
                                    />
                                </div>
                                <span class="text-xs text-rose-500"></span>
                            </div>
                            <div>
                                <label for="password" class="block text-xs font-medium text-white pb-2">Kata sandi</label>
                                <div class="relative">
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="password"
                                            id="password"
                                            autocomplete="current-password"
                                            placeholder="Kata sandi"
                                            name="password"
                                            required
                                        />
                                    </div>
                                    <button type="button" class="toggle-password absolute top-0 right-4 z-20 h-full text-murky-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
                                        ></path>
                                    </svg>
                                </button>

                                </div>
                                <span class="text-xs text-rose-500"></span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                        </div>
                        <div>
                           
                        </div>
                        <div>
                            <button
                                class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-75 group relative flex w-full"
                                type="submit" name="tombol" value="submit">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
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
                                  {{ __('Login') }}
                            </button>
                        </div>
                     

                </div>
            </main>
        </div>

    </div>


        


@endsection
