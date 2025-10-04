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
                        <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700" style="outline: none;" href="/id/dashboard">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"
                                ></path>
                            </svg>
                            <span class="hidden truncate md:block">Dashboard</span>
                        </a>
                        <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700" style="outline: none;" href="{{ route('riwayat') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="hidden truncate md:block">Transaksi</span>
                        </a>
                    
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
            <div class="grid grid-cols-3 gap-4 md:gap-8">
                <div class="col-span-3 space-y-8 xl:col-span-2">
                    <div>
                        <a class="inline-flex items-center space-x-2 outline-none" href="{{ route('reload') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path
                                    fill-rule="evenodd"
                                    d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            <span>Riwayat Deposit</span>
                        </a>
                    </div>
                    
                    <div class="block space-y-8 xl:hidden">
                        <div class="rounded-lg border border-murky-600 bg-murky-700 p-6">
                            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row">
                                <div>
                                    <p class="text-sm font-medium">Saldo Anda</p>
                                    <h3 class="mt-1 text-[24px] font-bold text-primary-500 lg:text-[26px]">Rp&nbsp;{{ number_format(Auth::user()->balance, 0, ',', '.') }}</h3>
                                </div>
                                <div class="flex items-center justify-center space-x-2">
                                    <a class="rounded-md bg-murky-600 p-2 outline-none" href="{{ route('reload') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="w-full border-t border-murky-600"></div>
                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5 text-blue-400">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1 md:flex md:justify-start">
                                    <p class="text-sm text-blue-700">
                                        QRIS : 24 Jam
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
               



<div  class="rounded-md">
    
     <form action="{{ route('deposit.store') }}" method="POST" class="my-form px-3 mt-2" id="topup-form">

    @csrf

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            <ul>
                <li>{{ session('success') }}</li>
            </ul>
        </div>
    @endif

    				
                    </div>
                    <input type="hidden" id="selected_method" name="metode">
                    <div id="payment-method-section" class="rounded-xl bg-murky-800 shadow-2xl">
          
            
           
            <div class="rounded-xl bg-murky-800 shadow-2xl" >
              <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-4 py-2 text-xl font-semibold"> 1 </div>
                <h3 class="flex w-full items-center justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Masukkan Data Deposit </h3>
              </div>
              <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
    <div>
        <label for="id" class="block text-xs font-medium text-white pb-2">Jumlah Deposit</label>
        <div class="flex flex-col items-start">
            <input
                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                type="number"
                name="jumlah"
                placeholder="Ketikan Nominal Topup"
                required
            />
        </div>
    </div>
    <div>
        <label for="server" class="block text-xs font-medium text-white pb-2">No. WhatsApp</label>
        <div class="flex flex-col items-start">
            <input
                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                type="number" name="no_telfon" placeholder="No WhatsApp" required
            />
        </div>
    </div>
    </div>
    </div>

            
            
            <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-payment-channel">
              <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-4 py-2 text-xl font-semibold"> 2 </div>
                <h3 class="flex w-full items-center justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Pilih Metode Pembayaran </h3>
              </div>
              <dl id="paymentList" class="flex w-full flex-col space-y-4 p-4 sm:p-6" x-data="{ selected: null, paymentSelected: '' }">
                  
               
                
                <!--QRIS-->
              <div class="flex w-full transform flex-col justify-between rounded-md bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>

        <button class="w-full disabled:opacity-75" id="disclosure-button-:rbb:" type="button" @click="selected !== 7 ? selected = 7 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-:rc8:">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>QRIS</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 7 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container1" x-bind:style="selected == 7 ? 'max-height: ' + $refs.container1.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-1">
                        <div id="radiogroup-1" role="radiogroup" aria-labelledby="label-1">
                          <label class="sr-only" id="label-1" role="none">Select a payment list</label>
                          <div id="eWalletList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->tipe == 'qris') <div x-bind:class="{ 'bg-white bj-shadow': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" data-method="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out " id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}"   tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                          
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->images}}" x-bind:class="{ 'grayscale-0': paymentSelected === '{{$p->code}}', 'grayscale': paymentSelected !== '{{$p->code}}' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo3" x-bind:style="selected == 7 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 7 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->tipe == 'qris') <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->images}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                

              </dl>
            </div>
       
                    <div class="w-full border-t border-murky-600"></div>
                    <button  type="submit" name="tombol" value="submit"
                        class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 flex w-full items-center justify-center space-x-2"
                        
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"
                            ></path>
                        </svg>
                        <span>Topup Sekarang!</span>
                    </button>
                    </form>
                </div>
                <div class="hidden xl:block">
                    <div class="sticky top-20 space-y-8">
                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5 text-blue-400">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1 md:flex md:justify-start">
                                    <p class="text-sm text-blue-700">
                                        QRIS : 24 jam 
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-murky-600 bg-murky-700 p-6">
                            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row">
                                <div>
                                    <p class="text-sm font-medium">Saldo Anda</p>
                                    <h3 class="mt-1 text-[24px] font-bold text-primary-500 lg:text-[26px]">Rp&nbsp;{{ number_format(Auth::user()->balance, 0, ',', '.') }}</h3>
                                </div>
                                <div class="flex items-center justify-center space-x-2">
                                    <a class="rounded-md bg-murky-600 p-2" href="{{ route('reload') }}" style="outline: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    
                    </div>
                    </div>
                    </div>
                    </div>
                    
                    
 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    
    <script>
     document.addEventListener("DOMContentLoaded", function () {
        const paymentMethods = document.querySelectorAll('.payment-method');
        const accordionBody = document.querySelector('.accordion-body.bg-payment');

        paymentMethods.forEach(function (method) {
            method.addEventListener('click', function () {
                paymentMethods.forEach(function (elem) {
                    elem.classList.remove('selected');
                });

                this.classList.add('selected');
                document.getElementById('selected_method').value = this.getAttribute('data-method');

                accordionBody.classList.add('selected');
            });
        });
    });
    </script>
		
    <script>
    const jumlahInput = document.querySelector('input[name="jumlah"]');

jumlahInput.addEventListener('input', function(event) {
    const nilaiJumlah = event.target.value;
    const flexEndElements = document.querySelectorAll('.showHarga');

    if (nilaiJumlah === '') {
        flexEndElements.forEach(function(element) {
            element.textContent = '';
        });
    } else {
        flexEndElements.forEach(function(element) {
            const paymentCode = element.id;
            console.log(paymentCode);

            if (paymentCode === 'DANA' || paymentCode === 'OVOPUSH' || paymentCode === 'SHOPEEPAY' || paymentCode === 'LINKAJA' || paymentCode === 'VIRGO' || paymentCode === 'ASTRAPAY') {
                const nilaiDana = parseFloat(nilaiJumlah) + (parseFloat(nilaiJumlah) * 0.03);
                element.textContent = formatRupiah(nilaiDana);
            } else if(paymentCode === 'QRIS'){
                const nilaiQris = parseFloat(nilaiJumlah) + (parseFloat(nilaiJumlah) * 0.01) + 100;
                element.textContent = formatRupiah(nilaiQris);
            }else if(paymentCode === 'TRI' || paymentCode === 'AXIS' || paymentCode === 'XL'){
                const nilaiPulsa = parseFloat(nilaiJumlah) + (parseFloat(nilaiJumlah) * 0.25);
                element.textContent = formatRupiah(nilaiPulsa);
            }else if(paymentCode === 'TELKOMSEL'){
                const nilaiPulsa = parseFloat(nilaiJumlah) + (parseFloat(nilaiJumlah) * 0.32);
                element.textContent = formatRupiah(nilaiPulsa);
            }else {
                const nilaiVa = parseFloat(nilaiJumlah) + 5000;
                element.textContent = formatRupiah(nilaiVa);
            }
        });
    }
});

function formatRupiah(angka) {
    var reverse = angka.toString().split('').reverse().join(''),
        ribuan = reverse.match(/\d{1,3}/g);
    ribuan = ribuan.join('.').split('').reverse().join('');
    return 'Rp ' + ribuan;
}

    </script>





@include('../footer')

@push('custom_script')

@endpush

@endsection