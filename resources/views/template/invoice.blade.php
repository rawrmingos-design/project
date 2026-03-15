@extends('template.template')

@section('custom_style')
    <style>
        .fa-star,
        .fa-star-o {
            color: #FFD700;
            cursor: pointer;
            font-size: 24px;
            margin-right: 5px;
        }

        .fa-star-o:hover {
            color: #FFA500;
        }

        .bg-green-500 {
            --tw-bg-opacity: 1;
            background-color: rgb(34 197 94 / var(--tw-bg-opacity));
        }

        .w-0\.5 {
            width: .125rem;
        }

        .h-full {
            height: 100%;
        }

        .mt-0\.5 {
            margin-top: .125rem;
        }

        .-ml-px {
            margin-left: -1px;
        }

        .top-4 {
            top: 1rem;
        }

        .left-4 {
            left: 1rem;
        }

        .absolute {
            position: absolute;
        }

        textarea {
            --tw-shadow: 0 0 #0000;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #636161;
            border-color: #6b7280;
            border-radius: 0;
            border-width: 1px;
            font-size: 1rem;
            line-height: 1.5rem;
            padding: 0.5rem 0.75rem;
        }
    </style>
@endsection


@section('content')

    @include('../navbar')
    <div class=" print:!text-slate-800">
        <div class="container py-12 print:py-8 md:py-8">
            <div
                class="flex flex-col-reverse items-end justify-between gap-8 print:mt-0 print:flex-row print:items-start print:gap-0 md:mt-0 md:flex-row md:items-start md:gap-0">
                <div class="max-w-3xl">
                    <h1 class="text-base font-medium text-white">Terima Kasih!</h1>
                    <p class="mt-2 text-4xl font-bold tracking-tight">Harap lengkapi pembayaran.</p>
                    <p class="mt-2 text-base text-white">Pesanan kamu <span
                            class="font-semibold text-white">{{ $data->id_pembelian }}</span> menunggu pembayaran sebelum
                        dikirim.</p>
                </div>
            </div>
            <div class="mt-8 flex flex-col items-end justify-between gap-8 print:flex-row md:flex-row">
                <dl class="w-full text-left text-sm font-medium md:w-auto">
                    <dt class="text-white print:text-slate-800">Pesanan ini akan kedaluwarsa pada</dt>
                    <dd class="mt-2 text-primary-500">
                        <div
                            class="rounded-md bg-rose-500 px-4 py-2 text-center text-white print:p-0 print:text-left print:text-slate-800">
                            {{ $expired }}</div>
                    </dd>
                </dl>
            </div>

            <div class="my-8 border-y border-murky-600 py-8">
                <div class="grid grid-cols-2 gap-8">
                    <div class="col-span-2 flex gap-8 lg:col-span-1">
                        <div
                            class="relative mt-2 aspect-[4/6] h-32 flex-none overflow-hidden rounded-lg bg-murky-600 object-cover object-center print:hidden sm:h-56 md:mt-0 md:block">
                            <img alt="{{ $namas }}" fetchpriority="high" decoding="async" data-nimg="fill"
                                class="object-cover object-center" sizes="100vw" src="{{ asset($thumbnails) }}"
                                style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-white print:text-sm print:text-slate-800">
                                <span href="" style="outline: none;">{{ $namas }}</span>
                            </h3>
                            <p class="text-sm">{{ $data->layanan }}</p>
                            <div>

                                @if ($data->tipe_transaksi == 'joki')
                                    <div class="mt-8 text-sm font-medium text-murky-200 print:text-slate-800">
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Email :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">Censored</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Password :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">Censored</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Login Via :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->loginvia_joki }}</p>
                                            </div>
                                        </div>
                                        @if($data->nickname_joki)
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">NIckname :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->nickname_joki }}</p>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Request :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->request_joki }}</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Catatan :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->catatan_joki }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($data->tipe_transaksi == 'jokigendong')
                                    <div class="mt-8 text-sm font-medium text-murky-200 print:text-slate-800">
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Catatan :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->catatan_joki }}</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Role :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->loginvia_joki }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-8 text-sm font-medium text-murky-200 print:text-slate-800">
                                        @if($data->nickname)
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Nickname</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->nickname }}</p>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">ID</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->user_id }}
                                                    {{ $data->zone != null ? '(' . $data->zone . ')' : '' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2 row-span-3 lg:col-span-1">
                        <div class="w-full flex-1 print:pt-0 md:flex-auto md:pt-0">
                            <dl class="gap-x-8 text-sm">
                                <div class="w-full">
                                    <dt class="text-lg font-medium text-white print:text-sm print:text-slate-800">Metode
                                        Pembayaran</dt>
                                    <dd class="text-murky-200">
                                        <div class="flex items-start space-x-4 print:text-slate-800">
                                            <div class="text-sm text-white">{{ $metode_name }}</div>
                                        </div>
                                        @if (Str::upper($data->metode_pembayaran) == 'ALFAMRT' ||
                                                Str::upper($data->metode_pembayaran) == 'INDOMARET' ||
                                                Str::upper($data->metode_pembayaran) == 'PERMATAVAA' ||
                                                Str::upper($data->metode_pembayaran) == 'BNCVA' ||
                                                Str::upper($data->metode_pembayaran) == 'BSIVA' ||
                                                Str::upper($data->metode_pembayaran) == 'DANAMONVA' ||
                                                Str::upper($data->metode_pembayaran) == 'CIMBVA' ||
                                                Str::upper($data->metode_pembayaran) == 'PERMATAVA' ||
                                                Str::upper($data->metode_pembayaran) == 'MANDIRIVA' ||
                                                Str::upper($data->metode_pembayaran) == 'BNIVA' ||
                                                Str::upper($data->metode_pembayaran) == 'BCAVA' ||
                                                Str::upper($data->metode_pembayaran) == 'BC' ||
                                                Str::upper($data->metode_pembayaran) == 'M2' ||
                                                Str::upper($data->metode_pembayaran) == 'VA' ||
                                                Str::upper($data->metode_pembayaran) == 'I1' ||
                                                Str::upper($data->metode_pembayaran) == 'B1' ||
                                                Str::upper($data->metode_pembayaran) == 'BT' ||
                                                Str::upper($data->metode_pembayaran) == 'A1' ||
                                                Str::upper($data->metode_pembayaran) == 'NC' ||
                                                Str::upper($data->metode_pembayaran) == 'BR' ||
                                                Str::upper($data->metode_pembayaran) == 'S1' ||
                                                Str::upper($data->metode_pembayaran) == 'DM' ||
                                                Str::upper($data->metode_pembayaran) == 'BV' ||
                                                Str::upper($data->metode_pembayaran) == 'IR' ||
                                                Str::upper($data->metode_pembayaran) == 'FT' ||
                                                Str::upper($data->metode_pembayaran) == 'BRIVA' ||
                                                Str::upper($data->metode_pembayaran) == 'BRIVA' ||
                                                (Str::upper($data->metode_pembayaran) == 'DUITKU' && !Str::startsWith($data->no_pembayaran, ['http', 'https'])))
                                            <div
                                                class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4 mt-3 mb-2">
                                                No Pembayaran</div>
                                            <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                                <button type="button"
                                                    class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden"
                                                    onclick="copyNoPembayaranToClipboard()">
                                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none"
                                                        id="noPembayaran">{{ $data->no_pembayaran }}</div>
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        aria-hidden="true" class="h-5 w-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184">
                                                        </path>
                                                    </svg>
                                                </button>
                                                <span class="hidden print:block"></span>
                                            </div>
                                        @endif
                                    </dd>
                                    <div
                                        class="mt-8 grid w-full grid-cols-8 gap-4 border-t border-murky-600 pt-8 text-left text-murky-200 print:border-slate-200 print:text-slate-800 md:gap-x-2">
                                        <div
                                            class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4">
                                            Nomor Invoice</div>
                                        <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                            <button type="button"
                                                class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden"
                                                onclick="copyToClipboard()">
                                                <div class="max-w-[172px] truncate md:w-auto md:max-w-none"
                                                    id="invoicePembelian">{{ $data->id_pembelian }}</div>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    aria-hidden="true" class="h-5 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184">
                                                    </path>
                                                </svg>
                                            </button>
                                            <span class="hidden print:block"></span>
                                        </div>
                                        <!--conditional status pembelian & pembayaran-->
                                        @php
                                            $statuscolor = '';

                                            if ($data->status_pembelian == 'Pending') {
                                                $statuscolor = 'yellow';
                                            } elseif (
                                                $data->status_pembelian == 'Sukses' ||
                                                $data->status_pembelian == 'Success'
                                            ) {
                                                $statuscolor = 'green';
                                            } elseif ($data->status_pembelian == 'Proses' || $data->status_pembelian == 'Processing') {
                                                $statuscolor = 'cyan';
                                            } else {
                                                $statuscolor = 'rose';
                                            }
                                        @endphp
                                        <div class="col-span-3 text-white print:text-slate-800 md:col-span-4">Status Transaksi</div>
                                        <div class="col-span-5 md:col-span-4">
                                            <span
                                                class="inline-flex rounded-sm  text-xs font-semibold leading-5 print:p-0 bg-{{ $statuscolor }}-300 text-{{ $statuscolor }}-800">
                                                @if ($data->status_pembelian == 'Pending')
                                                    <div class="whitespace-nowrap"> <span
                                                            class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-yellow-300 text-emerald-900">Pending</span>
                                                    </div>
                                                @elseif($data->status_pembelian == 'Proses' || $data->status_pembelian == 'Processing')
                                                    <td
                                                        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                                        <div class="whitespace-nowrap">
                                                            <span
                                                                class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-sky-600 text-white">Process</span>
                                                        </div>
                                                    </td>
                                                @elseif($data->status_pembelian == 'Batal' || $data->status_pembelian == 'Gagal')
                                                    <div class="whitespace-nowrap"> <span
                                                            class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-rose-300 text-rose-800">Cancelled</span>
                                                    </div>
                                                @elseif($data->status_pembelian == 'Sukses' || $data->status_pembelian == 'Success')
                                                    <td
                                                        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                                        <div class="whitespace-nowrap">
                                                            <span
                                                                class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-emerald-200 text-emerald-900">Success</span>
                                                        </div>
                                                    </td>
                                                @endif
                                            </span>
                                        </div>
                                        @php
                                            $pembayarancolor = '';

                                            if ($data->status_pembayaran == 'Belum Lunas') {
                                                $pembayarancolor = 'rose';
                                            } elseif (
                                                $data->status_pembayaran == 'PAID' ||
                                                $data->status_pembayaran == 'Lunas'
                                            ) {
                                                $pembayarancolor = 'cyan';
                                            } else {
                                                $pembayarancolor = 'rose';
                                            }
                                        @endphp
                                        <div class="col-span-3 text-white print:text-slate-800 md:col-span-4">Status
                                            Pembayaran</div>
                                        <div class="col-span-5 md:col-span-4"><span id="badge-unpaid"
                                                class="inline-flex rounded-sm text-xs font-semibold leading-5 print:p-0 bg-{{ $pembayarancolor }}-300 text-{{ $pembayarancolor }}-800">
                                                @if ($data->status_pembayaran == 'Belum Lunas')
                                                    <div class="whitespace-nowrap"> <span
                                                            class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-rose-300 text-emerald-900">Unpaid</span>
                                                    </div>
                                                @elseif($data->status_pembayaran == 'PAID' || $data->status_pembayaran == 'Lunas')
                                                    <td
                                                        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                                        <div class="whitespace-nowrap">
                                                            <span
                                                                class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-emerald-200 text-emerald-900">Paid</span>
                                                        </div>
                                                    </td>
                                                @else
                                                    <div class="whitespace-nowrap"> <span
                                                            class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-rose-300 text-emerald-900">Expired</span>
                                                    </div>
                                                @endif
                                            </span></div>
                                        @php
                                            $snValue = $data->voucher ?: $data->keterangan_sn;
                                        @endphp
                                        @if ($snValue)
                                            <div
                                                class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4">
                                                Keterangan / SN</div>
                                            <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                                <button onclick="copyToClipboardsn()" type="button"
                                                    class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden">
                                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none"
                                                        id="sn">{{ $snValue }}</div>
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        aria-hidden="true" class="h-5 w-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </dl>
                            @if ($data->status_pembayaran == 'Belum Lunas')
                                @if (Str::upper($data->metode_pembayaran) == 'QRIS' ||
                                        Str::upper($data->metode_pembayaran) == '11' ||
                                        Str::upper($data->metode_pembayaran) == '17' ||
                                        Str::upper($data->metode_pembayaran) == '23' ||
                                        Str::upper($data->metode_pembayaran) == 'QRISREALTIME' ||
                                        Str::upper($data->metode_pembayaran) == 'SP' ||
                                        Str::upper($data->metode_pembayaran) == 'QRIS_CUSTOM' ||
                                        Str::upper($data->metode_pembayaran) == 'QRIS2' ||
                                        Str::upper($data->metode_pembayaran) == 'QRIS2_OFFLINE' ||
                                        Str::upper($data->metode_pembayaran) == 'QRIS2_RECURRING')
                                    <div
                                        class="relative mt-8 flex h-64 w-64 items-center justify-center overflow-hidden rounded-lg bg-white sm:h-56 sm:w-56">
                                        <div id="qris-payment">
                                            <center><img src="{{ $data->no_pembayaran }}" width="200"></center>
                                        </div>
                                    </div>
                                @elseif(Str::upper($data->metode_pembayaran) == '1' ||
                                        Str::upper($data->metode_pembayaran) == '1' ||
                                        Str::upper($data->metode_pembayaran) == '2' ||
                                        Str::upper($data->metode_pembayaran) == '3' ||
                                        Str::upper($data->metode_pembayaran) == '4' ||
                                        Str::upper($data->metode_pembayaran) == '5' ||
                                        Str::upper($data->metode_pembayaran) == '6' ||
                                        Str::upper($data->metode_pembayaran) == '7' ||
                                        Str::upper($data->metode_pembayaran) == '8' ||
                                        Str::upper($data->metode_pembayaran) == '9' ||
                                        Str::upper($data->metode_pembayaran) == '10' ||
                                        Str::upper($data->metode_pembayaran) == '18' ||
                                        Str::upper($data->metode_pembayaran) == '19' ||
                                        Str::upper($data->metode_pembayaran) == '21' ||
                                        Str::upper($data->metode_pembayaran) == '22' ||
                                        Str::upper($data->metode_pembayaran) == '12' ||
                                        Str::upper($data->metode_pembayaran) == '13' ||
                                        Str::upper($data->metode_pembayaran) == '14' ||
                                        Str::upper($data->metode_pembayaran) == 'SHOPEEPAY' ||
                                        Str::upper($data->metode_pembayaran) == 'GOPAY' ||
                                        Str::upper($data->metode_pembayaran) == 'LINKAJA' ||
                                        Str::upper($data->metode_pembayaran) == 'VIRGO' ||
                                        Str::upper($data->metode_pembayaran) == 'DANA_REALTIME' ||
                                        Str::upper($data->metode_pembayaran) == 'SHOPEEPAY_REALTIME' ||
                                        Str::upper($data->metode_pembayaran) == 'ASTRAPAY' ||
                                        Str::upper($data->metode_pembayaran) == 'OVOPUSH' ||
                                        Str::upper($data->metode_pembayaran) == 'DANA' ||
                                        Str::upper($data->metode_pembayaran) == 'SPs' ||
                                        Str::upper($data->metode_pembayaran) == 'AXIS' ||
                                        Str::upper($data->metode_pembayaran) == 'XL' ||
                                        Str::upper($data->metode_pembayaran) == 'DA' ||
                                        Str::upper($data->metode_pembayaran) == 'SL' ||
                                        Str::upper($data->metode_pembayaran) == 'OL' ||
                                        Str::upper($data->metode_pembayaran) == 'JP' ||
                                        Str::upper($data->metode_pembayaran) == 'LQ' ||
                                        Str::upper($data->metode_pembayaran) == 'NQ' ||
                                        Str::upper($data->metode_pembayaran) == 'DQ' ||
                                        Str::upper($data->metode_pembayaran) == 'GQ' ||
                                        Str::upper($data->metode_pembayaran) == 'SQ' ||
                                        Str::upper($data->metode_pembayaran) == 'SMARTFREN' || 
                                        Str::upper($data->metode_pembayaran) == 'OVO' ||
                                        Str::upper($data->metode_pembayaran) == 'OVO' ||
                                        (Str::upper($data->metode_pembayaran) == 'DUITKU' && Str::startsWith($data->no_pembayaran, ['http', 'https'])))
                                    <a target="_blank" href="{{ $data->no_pembayaran }}"><button
                                            class="mt-8 inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 w-full space-x-2 pr-3 sm:w-auto"
                                            type="button"><span>Bayar Sekarang</span><svg
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                                class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25">
                                                </path>
                                            </svg></button></a>
                                @else
                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none">
                                        <span id="hargaPembayaran"></span>
                                    </div>
                                @endif


                                @if (Str::upper($data->metode_pembayaran) == 'QRIS' ||
                                        Str::upper($data->metode_pembayaran) == '11' ||
                                        Str::upper($data->metode_pembayaran) == '17' ||
                                        Str::upper($data->metode_pembayaran) == '23' ||
                                        Str::upper($data->metode_pembayaran) == 'QRISREALTIME' ||
                                        Str::upper($data->metode_pembayaran) == 'QRISC' ||
                                        Str::upper($data->metode_pembayaran) == '11' ||
                                        Str::upper($data->metode_pembayaran) == 'QRISOP' ||
                                        Str::upper($data->metode_pembayaran) == 'SP' ||
                                        Str::upper($data->metode_pembayaran) == 'QRIS_CUSTOM')
                                    <button
                                        class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 mt-2 w-64 py-1 !text-xs print:hidden sm:w-56"
                                        type="button" onclick="downloadQRCode()">
                                        Unduh Kode QR / Screenshoot
                                    </button>
                                @endif
                            @endif
                            @if (
                                $data->status_pembelian == 'Sukses' ||
                                    $data->status_pembelian == 'Success' ||
                                    $data->status_pembelian == 'Processing' ||
                                    $data->status_pembelian == 'Proses')
                                <div class="pt-8 print:hidden">
                                    <form id="myForm"
                                        action="{{ route('rating.pembelian', ['order' => $data->id_pembelian]) }}"
                                        method="POST">
                                        @csrf
                                        <div class="font-semibold">Tinggalkan ulasan untuk transaksi ini.</div>
                                        <div class="flex items-center star-rating">
                                            <span class="fa fa-star-o" data-rating="1"></span>
                                            <span class="fa fa-star-o" data-rating="2"></span>
                                            <span class="fa fa-star-o" data-rating="3"></span>
                                            <span class="fa fa-star-o" data-rating="4"></span>
                                            <span class="fa fa-star-o" data-rating="5"></span>
                                            <input type="hidden" name="bintang" class="rating-value" value="0" />
                                        </div>
                                        <input type="hidden" name="kategori_nama" value="{{ $namas }}">
                                        <div>
                                            <label for="pesanTextArea"
                                                class="flex items-center justify-between text-sm font-medium leading-6 text-white">
                                                <div>Tambahkan ulasan Kamu</div>
                                            </label>
                                            <div class="my-2 flex flex-wrap gap-1">
                                                <!-- Tambahkan elemen di sini jika diperlukan -->
                                            </div>
                                            <div class="mt-2">
                                                <textarea rows="4" id="pesanTextArea" placeholder="Tulis review kamu disini ..."
                                                    class="block w-full rounded-md border-0 text-black py-1.5 text-sm leading-6 shadow-sm  focus:ring-2 focus:ring-inset focus:ring-primary-500"
                                                    name="comment"></textarea>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 mt-2">
                                            <button id="melpa"
                                                class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-text-color-foreground transition-colors duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75"
                                                type="submit">Kirim</button>
                                        </div>
                                    </form>

                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-span-2 col-start-1 row-start-2 lg:col-span-1">

                        <button
                            class="flex w-full justify-between rounded-lg bg-murky-800 px-4 py-2 text-left text-sm font-medium text-white duration-200 ease-in-out hover:bg-murky-800 focus:outline-none"
                            id="toggleButton" type="button" aria-expanded="true" data-headlessui-state="open"
                            aria-controls="headlessui-disclosure-panel-:r6r:">
                            <span>Rincian Pembayaran</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                aria-hidden="true" class="rotate-180 transform h-5 w-5 text-white">
                                <path fill-rule="evenodd"
                                    d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        <div id="dropdownContent" class="pt-4 text-sm text-murky-200 hidden">
                            <div class="rounded-lg bg-murky-800 p-4">
                                <dl class="space-y-4 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">Harga</dt>
                                        <dd class="flex flex-col text-murky-200 print:text-slate-800"><span>Rp&nbsp;
                                                {{ number_format($data->harga_pembayaran, 0, ',', '.') }},-</span></dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">Jumlah</dt>
                                        <dd class="text-murky-200 print:text-slate-800">1x</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">Metode Pembayaran</dt>
                                        <dd class="text-murky-200 print:text-slate-800">{{ $metode_name }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">No Invoice</dt>
                                        <dd class="text-murky-200 print:text-slate-800">{{ $data->id_pembelian }}</dd>
                                    </div>
                                    <div class="h-px w-full bg-murky-400"></div>
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">Subtotal</dt>
                                        <dd class="text-murky-200 print:text-slate-800">Rp&nbsp;
                                            {{ number_format($data->harga_pembayaran, 0, ',', '.') }},-</dd>
                                    </div>

                                </dl>
                            </div>
                        </div>

                        <div class="mb-8 mt-4 flex items-center justify-between text-primary-500">
                            <dt class="text-xl font-bold text-white print:text-sm md:text-2xl">Total Harga</dt>
                            <dd class="font-semibold text-white print:text-slate-800">
                                <button type="button"
                                    class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 text-xl text-primary-500 print:hidden md:text-2xl"
                                    id="copyButton">
                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none">
                                        Rp.
                                        <span
                                            id="hargaPembayaran">{{ number_format($data->harga_pembayaran, 0, ',', '.') }},-</span>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184">
                                        </path>
                                    </svg>
                                </button>
                            </dd>
                        </div>
                        <div class="flex flex-col items-start gap-4 pt-4">
                            <h3 class="text-sm font-semibold">Progress Transaksi</h3>
                            <nav aria-label="Progress">
                                <ol role="list" class="overflow-hidden">

                                    <li class="pb-5 relative">
                                        <div class="absolute left-4 top-4 -ml-px mt-0.5 h-full w-0.5 bg-green-500"
                                            aria-hidden="true"></div>
                                        <div class="group relative flex items-start">
                                            <span class="flex h-9 items-center">
                                                <span
                                                    class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 group-hover:bg-green-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        aria-hidden="true" class="h-5 w-5 text-white">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M4.5 12.75l6 6 9-13.5"></path>
                                                    </svg>
                                                </span>
                                            </span>
                                            <span class="ml-4 flex min-w-0 flex-col">
                                                <span class="text-base font-medium text-gray-400">Transaksi Dibuat</span>
                                                <span class="text-murky-200 text-xs">Transaksi berhasil dibuat.</span>
                                            </span>
                                        </div>
                                    </li>
                                    @if ($data->status_pembayaran == 'PAID' || $data->status_pembayaran == 'Lunas')
                                        <li class="pb-5 relative">
                                            <div class="absolute left-4 top-4 -ml-px mt-0.5 h-full w-0.5 bg-green-500"
                                                aria-hidden="true"></div>
                                            <div class="group relative flex items-start">
                                                <span class="flex h-9 items-center">
                                                    <span
                                                        class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 group-hover:bg-green-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                            aria-hidden="true" class="h-5 w-5 text-white">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M4.5 12.75l6 6 9-13.5"></path>
                                                        </svg>
                                                    </span>
                                                </span>
                                                <span class="ml-4 flex min-w-0 flex-col">
                                                    <span class="text-base font-medium text-green-500">Pembayaran</span>
                                                    <span class="text-murky-200 text-xs">Pembayaran sudah kami terima,
                                                        transaksi akan segera diproses.</span>
                                                </span>
                                            </div>
                                        </li>
                                        <li class="pb-5 relative">
                                            <div class="group relative flex items-start">
                                                <span class="flex h-9 items-center">
                                                    <span
                                                        class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 group-hover:bg-green-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                            aria-hidden="true" class="h-5 w-5 text-white">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M4.5 12.75l6 6 9-13.5"></path>
                                                        </svg>
                                                    </span>
                                                </span>
                                                <span class="ml-4 flex min-w-0 flex-col">
                                                    <span class="text-base font-medium text-green-500">Selesai</span>
                                                    <span class="text-murky-200 text-xs">Transaksi selesai.</span>
                                                </span>
                                            </div>
                                        </li>
                                    @else
                                        <!-- Unpaid / Pending Steps -->
                                        <li class="pb-5 relative">
                                            <div class="absolute left-4 top-4 -ml-px mt-0.5 h-full w-0.5 bg-gray-300"
                                                aria-hidden="true"></div>
                                            <div class="group relative flex items-start">
                                                <span class="flex h-9 items-center">
                                                    <span
                                                        class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-yellow-500 group-hover:bg-yellow-600 animate-pulse">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-white">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </span>
                                                </span>
                                                <span class="ml-4 flex min-w-0 flex-col">
                                                    <span class="text-base font-medium text-yellow-500">Menunggu Pembayaran</span>
                                                    <span class="text-murky-200 text-xs">Silakan selesaikan pembayaran Anda.</span>
                                                </span>
                                            </div>
                                        </li>
                                        <li class="pb-5 relative">
                                            <div class="group relative flex items-start">
                                                <span class="flex h-9 items-center">
                                                    <span
                                                        class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 group-hover:bg-gray-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                            aria-hidden="true" class="h-5 w-5 text-white">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </span>
                                                </span>
                                                <span class="ml-4 flex min-w-0 flex-col">
                                                    <span class="text-base font-medium text-gray-500">Selesai</span>
                                                    <span class="text-murky-200 text-xs">Menunggu pembayaran dikonfirmasi.</span>
                                                </span>
                                            </div>
                                        </li>
                                    @endif
                                </ol>
                            </nav>
                        </div>
                        @if ($data->status_pembayaran == 'Belum Lunas')
                            <div class="border-l-4 border-yellow-300 bg-yellow-100 p-4 print:hidden">
                                <div>
                                    <div class="text-yellow-800 print:hidden">
                                        <p>Gunakan <strong>Ewallet </strong>atau <strong>aplikasi mobile banking</strong>
                                            yang tersedia scan QRIS</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        const toggleButton = document.getElementById('toggleButton');
        const dropdownContent = document.getElementById('dropdownContent');

        toggleButton.addEventListener('click', function() {
            const expanded = toggleButton.getAttribute('aria-expanded') === 'true' || false;
            toggleButton.setAttribute('aria-expanded', !expanded);
            dropdownContent.classList.toggle('hidden');
        });
    </script>
    <script>
        function downloadQRCode() {
            var qrCodeUrl = "{{ $data->no_pembayaran }}";

            var downloadLink = document.createElement("a");
            downloadLink.href = qrCodeUrl;
            downloadLink.download = ;

            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
    <script>
        const copyButton = document.getElementById("copyButton");
        const hargaPembayaran = document.getElementById("hargaPembayaran");

        copyButton.addEventListener("click", function() {
            const inputElement = document.createElement("input");
            inputElement.value = hargaPembayaran.textContent;

            document.body.appendChild(inputElement);
            inputElement.select();
            inputElement.setSelectionRange(0, 99999);

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
            toastr.success('{{ $data->harga_pembayaran }}</br>successfully copied to the clipboard!');
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



    <script>
        function copyNoPembayaranToClipboard() {
            const noPembayaranValue = document.getElementById('noPembayaran').innerText;
            navigator.clipboard.writeText(noPembayaranValue);
            toastr.success('successfully copied to the clipboard!');
        }

        function copyToClipboard() {
            const invoiceValue = document.getElementById('invoicePembelian').innerText;
            navigator.clipboard.writeText(invoiceValue);

            toastr.success('successfully copied to the clipboard!');
        }


        function copyToClipboardsn() {
            const invoiceValue = document.getElementById('sn').innerText;
            navigator.clipboard.writeText(invoiceValue);

            toastr.success('successfully copied to the clipboard!');
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        $(document).ready(function() {
            var $star_rating = $('.star-rating .fa');

            var SetRatingStar = function() {
                return $star_rating.each(function() {
                    if (parseInt($star_rating.siblings('input.rating-value').val()) >= parseInt($(this)
                            .data('rating'))) {
                        $(this).removeClass('fa-star-o').addClass('fa-star');
                    } else {
                        $(this).removeClass('fa-star').addClass('fa-star-o');
                    }
                });
            };

            $star_rating.on('click', function() {
                $star_rating.siblings('input.rating-value').val($(this).data('rating'));
                SetRatingStar();
            });

            const pesanTextArea = document.getElementById('pesanTextArea');
            pesanTextArea.value = "Proses topup nya cepat dan harga nya murah banget!";

            pesanTextArea.addEventListener('focus', function() {
                if (pesanTextArea.value === "Proses topup nya cepat dan harga nya murah banget!") {
                    pesanTextArea.value = "";
                }
            });

            pesanTextArea.addEventListener('blur', function() {
                if (pesanTextArea.value === "") {
                    pesanTextArea.value = "Proses topup nya cepat & harga nya murah banget!";
                }
            });

            const myForm = document.getElementById('myForm');
            const buttonKirim = document.getElementById('melpa');

            function handleSubmit(e) {
                e.preventDefault();
                const formData = new FormData(myForm);
                fetch(myForm.action, {
                    method: 'POST',
                    body: formData
                }).then(function(response) {
                    if (response.ok) {
                        Swal.fire({
                            icon: 'success',
                            text: 'Terima kasih telah memberikan testimoni!',
                        }).then(function() {
                            buttonKirim.removeEventListener('click', handleSubmit);
                            buttonKirim.disabled = true;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            text: 'Gagal menyimpan testimoni',
                        });
                    }
                }).catch(function(error) {
                    Swal.fire({
                        icon: 'error',
                        text: 'Gagal menyimpan testimoni',
                    });
                });
            }

            buttonKirim.addEventListener('click', handleSubmit);
        });
    </script>






    @include('../footer')

    @push('custom_script')
    <script>
        setInterval(function() {
            let orderId = "{{ $data->id_pembelian }}";
            let url = "{{ route('ajax.status', ':order') }}".replace(':order', orderId);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let currentStatusPembelian = "{{ $data->status_pembelian }}";
                        let currentStatusPembayaran = "{{ $data->status_pembayaran }}";
                        
                        // Check if status has changed
                        if (data.status_pembelian !== currentStatusPembelian || data.status_pembayaran !== currentStatusPembayaran) {
                           console.log('Status changed! Reloading...');
                           location.reload();
                        }
                    }
                })
                .catch(error => console.error('Error polling status:', error));
        }, 3000); // Check every 3 seconds
    </script>
    @endpush




@endsection
