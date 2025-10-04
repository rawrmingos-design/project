@extends('template.template')

@section('custom_style')

<style>
    .bg-green-500 {
    background-color: #34D399; 
}
</style>


@endsection


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
            
            <div class="absolute inset-0 z-10 bg-gradient-to-tr from-murky-800 via-murky-800/75 "></div>
            <div class="z-20 w-full">
                <div class="mx-auto w-full max-w-md space-y-8 lg:mx-0 mt-5">
                    <div>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">Lupa Katasandi</h2>
                        <p class="mt-2 text-sm text-white">Masukan Username untuk melakukan reset katasandi.</p>
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
                    <form action="{{ route('post.forgot') }}" method="POST" class="mt-8 space-y-6">
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
                                        placeholder="Enter Username"
                                        name="username"
                                        required
                                    />
                                </div>
                                <span class="text-xs text-rose-500"></span>
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
                                Submit
                            </button>
                        </div>
                        
                        </div>

                </div>
        </div>

    </div>


        



@push('custom_script')



@endpush




@endsection
