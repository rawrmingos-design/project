@extends('template.template')

@section('custom_style')


<style>
    .btn:disabled{background:#8ba4b1;border-color:#8ba4b1}
    
    .container-image {
    width: 150px;
    height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: left;
    margin-bottom: 10px;
  }

  .container-image b {
    width: 100%;
  }

  .container-image img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 10px;
  }
</style>


@endsection



@if(session('success'))
    <script>
        $(document).ready(function () {
            toastr.success('{{ session('success') }}');
        });
    </script>
@endif

@section('content')

@include('../navbar')

<main class="relative mt-5 p-2" id="invoice">
    <div class=" print:!text-slate-800">
        <div class="container py-12 print:py-8 md:py-8">
            <div class="flex flex-col-reverse items-end justify-between gap-8 print:mt-0 print:flex-row print:items-start print:gap-0 md:mt-0 md:flex-row md:items-start md:gap-0">
                 @if($data->status_pembayaran == "Belum Lunas")
                <div class="max-w-3xl">
                    <h1 class="text-base font-medium text-primary-500">Terima Kasih!</h1>
                    <p class="mt-2 text-4xl font-bold tracking-tight">Harap lengkapi pembayaran.</p>
                    <p class="mt-2 text-base text-murky-200">Pesanan kamu <span class="font-semibold text-primary-500">{{ $data->id_pembelian }}</span> menunggu pembayaran sebelum dikirim.</p>
                </div>
                 @elseif($data->status_pembayaran == "PAID" || $data->status_pembayaran == "Lunas")
                <div class="max-w-3xl">
                    <h1 class="text-base font-medium text-primary-500">Terima Kasih!</h1>
                    <p class="mt-2 text-4xl font-bold tracking-tight">Deposit Sudah Selesai.</p>
                    <p class="mt-2 text-base text-murky-200">Deposit kamu <span class="font-semibold text-primary-500">{{ $data->id_pembelian }}</span> telah dikirim dan akan segera tiba.</p>
                </div>
                @endif
            </div>
            <div class="mt-8 flex flex-col items-end justify-between gap-8 print:flex-row md:flex-row">
                <dl class="w-full text-left text-sm font-medium md:w-auto">
                    <dt class="text-white print:text-slate-800">Pesanan ini akan kedaluwarsa pada</dt>
                    <dd class="mt-2 text-primary-500"><div class="rounded-md bg-rose-500 px-4 py-2 text-center text-white print:p-0 print:text-left print:text-slate-800">{{ $expired }}</div></dd>
                </dl>
                <div class="absolute top-4 right-4 print:hidden md:static mt-3">
                    <button
                        class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 flex items-center space-x-2"
                        onclick="print_invoice()"
                        id="invoice"
                        type="button"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"
                            ></path>
                        </svg>
                        <span>Unduh Invoice</span>
                    </button>
                </div>
            </div>
            <div class="my-8 border-y border-murky-600 py-8">
                <div class="grid grid-cols-2 gap-8">
                    <div class="col-span-2 flex gap-8 lg:col-span-1">
                   
                        <div>
                            <h3 class="text-lg font-medium text-white print:text-sm print:text-slate-800"><a href="" style="outline: none;"> </a></h3>
                            <p class="text-sm">{{ $data->layanan }}</p>
                            <div>
                                <div class="mt-8 text-sm font-medium text-murky-200 print:text-slate-800">
                                    <div class="grid grid-cols-3 gap-4 pb-2">
                                        <div class="text-white print:text-slate-800">Deposit Dengan No Invoice {{ $data->id_pembelian }} </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2 row-span-3 lg:col-span-1">
                        <div class="w-full flex-1 print:pt-0 md:flex-auto md:pt-0">
                            <dl class="gap-x-8 text-sm">
                                <div class="w-full">
                                    <dt class="text-lg font-medium text-white print:text-sm print:text-slate-800">Metode Pembayaran</dt>
                                    <dd class="text-murky-200">
                                        <div class="flex items-start space-x-4 print:text-slate-800">
                                            <div class="text-sm text-white">QRIS DEPOSIT</div></div>
                                    </dd>
                                    <div class="mt-8 grid w-full grid-cols-8 gap-4 border-t border-murky-600 pt-8 text-left text-murky-200 print:border-slate-200 print:text-slate-800 md:gap-x-2">
                                    <div class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4">Nomor Invoice</div>
                                    <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                        <button type="button" id="copyButton1" class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden" onclick="copyToClipboard('copyButton1')">
                                            <div class="max-w-[172px] truncate md:w-auto md:max-w-none">{{ $data->id_pembelian }}</div>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"
                                                ></path>
                                            </svg>
                                        </button>
                                        <span class="hidden print:block"></span>
                                    </div>
                                        <!--conditional status pembelian & pembayaran-->
                                        @php
                                            $statuscolor = '';
                                            
                                            if($data->status_pembelian == "Pending"){
                                                $statuscolor = 'yellow';
                                            } elseif($data->status_pembelian == "Sukses" || $data->status_pembelian == "Success"){
                                                $statuscolor = 'green';
                                            } elseif($data->status_pembelian == "Proses"){
                                                $statuscolor = 'cyan';
                                            } else {
                                                $statuscolor = 'rose';
                                            }
                                        @endphp
                                        <!--<div class="col-span-3 text-white print:text-slate-800 md:col-span-4">Status Transaksi</div>-->
                                        <!--<div class="col-span-5 md:col-span-4"><span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-{{$statuscolor}}-300 text-{{$statuscolor}}-800">-->
                                        <!--    @if($data->status_pembelian == "Pending")-->
                                        <!--        Pending-->
                                        <!--    @elseif($data->status_pembelian == "Proses")-->
                                        <!--        Processing-->
                                        <!--    @elseif($data->status_pembelian == "Sukses" || $data->status_pembelian == "Success")-->
                                        <!--        Sukses-->
                                        <!--    @endif-->
                                        <!--</span></div>-->
                                         @php
                                            $pembayarancolor = '';
                                            
                                            if($data->status_pembayaran == "Belum Lunas"){
                                                $pembayarancolor = 'rose';
                                            } elseif($data->status_pembayaran == "Success" || $data->status_pembayaran == "Lunas"){
                                                $pembayarancolor = 'green';
                                            }else {
                                                $pembayarancolor = 'rose';
                                            }
                                        @endphp
                                        <div class="col-span-3 text-white print:text-slate-800 md:col-span-4">Status Pembayaran</div>
                                        <div class="col-span-5 md:col-span-4"><span id="badge-unpaid" class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-{{$pembayarancolor}}-300 text-{{$pembayarancolor}}-800">
                                            @if($data->status_pembayaran == "Belum Lunas")
                                               <div class="whitespace-nowrap"> <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-rose-300 text-emerald-900">Unpaid</span> </div>
                                            @elseif($data->status_pembayaran == "PAID" || $data->status_pembayaran == "Lunas")
                                                <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                                   <div class="whitespace-nowrap">
                                                        <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-emerald-200 text-emerald-900">Paid</span>
                                                    </div>
                                                </td>
                                            @else
                                                <div class="whitespace-nowrap"> <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-rose-300 text-emerald-900">Expired</span> </div>
                                            @endif
                                        </span></div>
                                        <div class="col-span-3 text-white print:text-slate-800 md:col-span-4">Pesan</div>
                                        <div class="col-span-5 md:col-span-4">
                                            @if($data->status_pembayaran == "Belum Lunas")
                                            Menunggu pembayaran deposit saldo
                                            @elseif($data->status_pembayaran == "PAID" || $data->status_pembayaran == "Lunas")
                                            Saldo berhasil ditambahkan pada {{ $data->updated_at }}. Diproses oleh sistem.
                                            @else
                                                Expired
                                            @endif
                                        </div>
                                        @if($data->voucher !== null)
                                        <div class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4">Kode Voucher / SN</div>
                                        <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                            <button type="button" class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden">
                                                <div class="max-w-[172px] truncate md:w-auto md:max-w-none">{{ $data->voucher }}</div>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"
                                                    ></path>
                                                </svg>
                                            </button>
                                            <span class="hidden print:block"></span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </dl>
                            @if(Str::upper($data->metode_pembayaran) == "QRIS" || Str::upper($data->metode_pembayaran) == "QRISC" || Str::upper($data->metode_pembayaran) == "QRIS2" || Str::upper($data->metode_pembayaran) == "QRISOP" || Str::upper($data->metode_pembayaran) == "SP" )
                            <div class="relative mt-8 flex h-64 w-64 items-center justify-center overflow-hidden rounded-lg bg-white sm:h-56 sm:w-56">
                              <div id="qris-payment">
                                  <center><img src="{{$data->no_pembayaran}}" width="200"></center>
                              </div>
                            </div>
                            @elseif(Str::upper($data->metode_pembayaran) == "SHOPEEPAY" || Str::upper($data->metode_pembayaran) == "OVOPUSH" || Str::upper($data->metode_pembayaran) == "DANA" || Str::upper($data->metode_pembayaran) == "LINKAJA" || Str::upper($data->metode_pembayaran) == "11" || Str::upper($data->metode_pembayaran) == "17" || Str::upper($data->metode_pembayaran) == "23")
                            <a href="{{$data->no_pembayaran}}"><button class="mt-8 inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 w-full space-x-2 pr-3 sm:w-auto" type="button"><span>Klik di sini untuk melakukan pembayaran</span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"></path></svg></button></a>
                            @endif
                            
                            
                            @if(Str::upper($data->metode_pembayaran) == "QRIS" || Str::upper($data->metode_pembayaran) == "QRISC" || Str::upper($data->metode_pembayaran) == "QRIS2" || Str::upper($data->metode_pembayaran) == "QRISOP" || Str::upper($data->metode_pembayaran) == "SP" )
                            <!--<button-->
                            <!--    class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 mt-2 w-64 py-1 !text-xs print:hidden sm:w-56"-->
                            <!--    type="button"-->
                            <!-->
                            <!--    Unduh Kode QR-->
                            <!--</button>-->
                            @endif
                        </div>
                    </div>
                    <div class="col-span-2 col-start-1 row-start-2 lg:col-span-1">
                    
                       <div class="mb-8 mt-4 flex items-center justify-between text-primary-500">
                            <dt class="text-xl font-bold text-white print:text-sm md:text-2xl">Total Pembayaran</dt>
                            <dd class="font-semibold text-white print:text-slate-800">
                                <button type="button" id="copyButton" class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 text-xl text-primary-500 print:hidden md:text-2xl" >
                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none">
                                        Rp.
                                        <span id="hargaPembayaran">{{ number_format($data->harga_pembayaran, 0, ',','.') }},-</span>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"
                                        ></path>
                                    </svg>
                                </button>
                            </dd>
                        </div>
                        <div class="border-l-4 border-yellow-300 bg-yellow-100 p-4 print:hidden">
                            <div>
                                
                             @if(Str::upper($data->metode_pembayaran) == "QRIS" || Str::upper($data->metode_pembayaran) == "QRISC" || Str::upper($data->metode_pembayaran) == "QRIS2" || Str::upper($data->metode_pembayaran) == "QRISOP" || Str::upper($data->metode_pembayaran) == "SP" )
                                <div class="text-yellow-800 print:hidden">
                                    <p>Gunakan <strong>Ewallet </strong>atau <strong>aplikasi mobile banking</strong> yang tersedia scan QRIS</p>
                                </div>
                                @elseif(Str::upper($data->metode_pembayaran) == "BRIVA" || Str::upper($data->metode_pembayaran) == "BCAVA" || Str::upper($data->metode_pembayaran) == "BNIVA" || Str::upper($data->metode_pembayaran) == "MANDIRIVA" || Str::upper($data->metode_pembayaran) == "PERMATAVA" || Str::upper($data->metode_pembayaran) == "CIMBVA" || Str::upper($data->metode_pembayaran) == "DANAMONVA" || Str::upper($data->metode_pembayaran) == "BSIVA")
                                 <div class="text-yellow-800 print:hidden">
                                    <p>Gunakan <strong>aplikasi mobile banking</strong> untuk melakukan pembayaran</p>
                                </div>
                                @elseif(Str::upper($data->metode_pembayaran) == "INDOMARET")
                                <div class="text-yellow-800 print:hidden">
                                    <p>Silahkan tunjukkan <strong>nomor pembayaran </strong> ke kasir indomaret agar pesanan dapat diproses</p>
                                </div>
                                @elseif(Str::upper($data->metode_pembayaran) == "ALFAMART")
                                <div class="text-yellow-800 print:hidden">
                                    <p>Silahkan tunjukkan <strong>nomor pembayaran </strong> ke kasir alfamart agar pesanan dapat diproses</p>
                                </div>
                                @else
                                <div class="text-yellow-800 print:hidden">
                                    <p>Gunakan Aplikasi <strong>Ewallet </strong> untuk melakukan pembayaran</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
                           

<script>
    const copyButton = document.getElementById("copyButton");
    const hargaPembayaran = document.getElementById("hargaPembayaran");

    copyButton.addEventListener("click", function() {
        const inputElement = document.createElement("input");
        inputElement.value = hargaPembayaran.textContent;

        document.body.appendChild(inputElement);
        inputElement.select();
        // inputElement.setSelectionRange(0, 99999); 

        document.execCommand("copy");
        document.body.removeChild(inputElement);

        toastr.options = {
          "closeButton": false,
          "debug": false,
          "newestOnTop": true,
          "progressBar": false,
          "positionClass": "toast-top-right",
          "preventDuplicates": false,
          "onclick": null,
          "showDuration": "50",
          "hideDuration": "1000",
          "timeOut": "5000",
          "extendedTimeOut": "1000",
          "showEasing": "swing",
          "hideEasing": "linear",
          "showMethod": "show",
          "hideMethod": "hide"
        }
        toastr.success('No pembayaran berhasil disalin!');
    });
    
    function print_invoice() {
            var printContents = document.getElementById('invoice').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            window.onafterprint = function() {
                location.reload()
            }
        }
</script>






@include('../footer')

@push('custom_script')



@endpush




@endsection