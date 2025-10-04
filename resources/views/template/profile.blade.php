@extends('template.template')

@section('custom_style')

@endsection

@section('content')

@include('../navbar')
<div class="container grid grid-cols-8 gap-8 pt-8 sm:pt-16">
    <div class="col-span-1 hidden sm:block md:col-span-2">
        <aside class="sticky top-20 print:hidden">
            <nav class="h-full content-start lg:grid lg:content-between">
                <div class="space-y-4">
                      <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700"
                            style="outline:none" href="/id/dashboard">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25">
                                </path>
                            </svg>
                            <span class="hidden truncate md:block">Dashboard</span>
                        </a>
                        <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700"
                            style="outline:none" href="{{ route('riwayat') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="hidden truncate md:block">Transaksi</span>
                        </a>
                    <!--<a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700" style="outline: none;" href="/id/dashboard/deposit/topup">-->
                    <!--    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">-->
                    <!--        <path-->
                    <!--            stroke-linecap="round"-->
                    <!--            stroke-linejoin="round"-->
                    <!--            d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"-->
                    <!--        ></path>-->
                    <!--    </svg>-->
                    <!--    <span class="hidden truncate md:block">Riwayat Deposit</span>-->
                    <!--</a>-->
                    <!--<a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700" style="outline: none;" href="/id/dashboard/mutation">-->
                    <!--    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">-->
                    <!--        <path-->
                    <!--            stroke-linecap="round"-->
                    <!--            stroke-linejoin="round"-->
                    <!--            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"-->
                    <!--        ></path>-->
                    <!--    </svg>-->
                    <!--    <span class="hidden truncate md:block">Riwayat Mutasi</span>-->
                    <!--</a>-->
                    <!--<a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700" style="outline: none;" href="/id/dashboard/report">-->
                    <!--    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">-->
                    <!--        <path-->
                    <!--            stroke-linecap="round"-->
                    <!--            stroke-linejoin="round"-->
                    <!--            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"-->
                    <!--        ></path>-->
                    <!--    </svg>-->
                    <!--    <span class="hidden truncate md:block">Laporan</span>-->
                    <!--</a>-->
                </div>
                <div class="w-full pt-4">
                   <form action="{{ route('logout') }}" method="POST" id="logout">
                            @csrf                        
                            <button type="submit" class="flex w-full items-center gap-3 rounded-md bg-gradient-to-r px-3 py-2 text-sm font-medium text-rose-500 hover:from-murky-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                                    ></path>
                                </svg>
                                <span class="hidden md:block">Keluar</span>
                            </button>
                        </form>
                </div>
            </nav>
        </aside>
    </div>
    <div class="col-span-8 sm:col-span-7 sm:col-start-2 md:col-span-7 md:col-start-3">
        <div>
            <div>
                <a class="inline-flex items-center space-x-2 outline-none" href="/id/dashboard">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="pb-16 pt-8 sm:pb-24 sm:pt-12">
                <div class="max-w-3xl space-y-8 divide-y divide-murky-600">
                    <div class="space-y-8 divide-y divide-murky-600">
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-base font-semibold leading-6 text-white">Profil</h3>
                                <p class="mt-1 max-w-2xl text-sm text-white">Informasi ini bersifat rahasia, jadi berhati-hatilah dengan apa yang Anda bagikan.</p>
                            </div>
                            	@if ($errors->any())
                    <div class="alert alert-danger mt-2">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if(session('success'))
			    
			    <div class="alert alert-success mt-2">
			       <ul>
			           <li>{{session('success')}}</li>
			       </ul>
			    </div>
			    
			    @endif
                            <form action="{{ route('saveEditProfile')}}" method="POST" class="grid grid-cols-2 gap-4">
					          @csrf 
                                <div class="col-span-2"></div>
                                <div>
                                    <label for="name" class="block text-xs font-medium text-white pb-2">Nama Anda</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="text"
                                            id="nama"
                                            autocomplete="name"
                                            placeholder="Nama Anda"
                                            value="{{Auth()->user()->name}}" name="name" required
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label for="username" class="block text-xs font-medium text-white pb-2">Username</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="text"
                                            autocomplete="username"
                                            placeholder="Username"
                                            value="{{Auth()->user()->username}}" name="username" required
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <label for="apikey" class="block text-xs font-medium text-white pb-2">Api Key</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="apikey"
                                            id="apikey"
                                            autocomplete="apikey"
                                            placeholder="Api Key"
                                            name="apikey"
                                            disabled=""
                                            value="{{Auth()->user()->api_key}}"
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <label for="email" class="block text-xs font-medium text-white pb-2">Alamat Email</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="email"
                                            id="email"
                                            autocomplete="email"
                                            placeholder="Alamat Email"
                                            name="email"
                                            disabled=""
                                            value="{{Auth()->user()->email}}"
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <label for="no" class="block text-xs font-medium text-white pb-2">No. Handphone</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="text"
                                            id="no"
                                            autocomplete="no"
                                            name="no_wa"
                                            placeholder="No. Handphone"
                                            value="{{Auth()->user()->no_wa}}"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <label for="no" class="block text-xs font-medium text-white pb-2">Masukan Password Untuk Merubah</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="text"
                                            name="password" autocomplete="off" placeholder="(Enter if want to changed)"
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2"></div>
                                <div class="pt-4">
                                    <button
                                        class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75"
                                        type="submit"
                                    >
                                        Ubah Profil
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Semua tampilan riwayat deposit di profile disembunyikan sementara --}}
                        <!--<div class="space-y-6">-->
                        <!--    <div class="pt-8">-->
                        <!--        <h3 class="text-base font-semibold leading-6 text-white">Ubah Kata Sandi</h3>-->
                        <!--        <p class="mt-1 max-w-2xl text-sm text-murky-200">Pastikan Anda mengingat kata sandi baru Anda sebelum mengubahnya.</p>-->
                        <!--    </div>-->
                        <!--    <form class="grid grid-cols-2 gap-4" x-data="{ isCurrentPassword: true, isNewPassword: true, isConfirmNewPassword: true }" method="POST" action="/id/settings/change-password">-->
                        <!--        <input type="hidden" name="_token" value="vUHKTN3oWsPl8jZ3CEhNEVofdIK94BabLgf2wDil" />-->
                        <!--        <div class="col-span-2">-->
                        <!--            <label for="current-password" class="block text-xs font-medium text-white pb-2">Kata Sandi Saat Ini</label>-->
                        <!--            <div class="flex flex-col items-start">-->
                        <!--                <input-->
                        <!--                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"-->
                        <!--                    type="password"-->
                        <!--                    type="password"-->
                        <!--                    id="password"-->
                        <!--                    name="password"-->
                        <!--                    autocomplete="current-password"-->
                        <!--                    placeholder="Kata Sandi Saat Ini"-->
                        <!--                />-->
                        <!--                <button type="button" class="absolute top-0 right-4 z-20 h-full text-murky-700" @click="isPassword = !isPassword"></button>-->
                        <!--            </div>-->
                        <!--            <span class="text-xs text-rose-500"></span>-->
                        <!--        </div>-->
                        <!--        <div>-->
                        <!--            <label for="new-password" class="block text-xs font-medium text-white pb-2">Kata Sandi Baru</label>-->
                        <!--            <div class="flex flex-col items-start">-->
                        <!--                <input-->
                        <!--                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"-->
                        <!--                    type="new_password"-->
                        <!--                    id="new_password"-->
                        <!--                    name="new_password"-->
                        <!--                    placeholder="Kata Sandi Baru"-->
                        <!--                />-->
                        <!--            </div>-->
                        <!--            <span class="text-xs text-rose-500"></span>-->
                        <!--        </div>-->
                        <!--        <div>-->
                        <!--            <label for="confirm-new-password" class="block text-xs font-medium text-white pb-2">Konfirmasi Kata Sandi Baru</label>-->
                        <!--            <div class="flex flex-col items-start">-->
                        <!--                <input-->
                        <!--                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"-->
                        <!--                    type="new_password2"-->
                        <!--                    id="new_password2"-->
                        <!--                    name="new_password2"-->
                        <!--                    placeholder="Konfirmasi Kata Sandi Baru"-->
                        <!--                />-->
                        <!--            </div>-->
                        <!--            <span class="text-xs text-rose-500"></span>-->
                        <!--        </div>-->
                        <!--        <div class="pt-4">-->
                        <!--            <button-->
                        <!--                class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75"-->
                        <!--                type="submit"-->
                        <!--            >-->
                        <!--                Ubah Kata Sandi-->
                        <!--            </button>-->
                        <!--        </div>-->
                        <!--    </form>-->
                        <!--</div>-->
                        

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

      







@include('../footer')
@push('custom_script')



@endpush




@endsection