{{-- SALDO category partial --}}
{{-- Receives: $category (PaymentDisplayCategory), $methods (collection of Method) --}}
@php
    $saldoMethod = $methods->first();
@endphp

@if($saldoMethod)
    @if(Auth::check())
        <div x-bind:class="{ 'border-yellow-400 bg-murky-800 ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800': paymentSelected === '{{ $saldoMethod->code }}', 'border-murky-500 bg-murky-700': paymentSelected !== '{{ $saldoMethod->code }}' }"
            class="method-list payment-method relative flex cursor-pointer overflow-hidden rounded-xl border p-3 text-left shadow-sm outline-none duration-300 ease-in-out hover:border-yellow-400 hover:bg-murky-800 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 md:p-4"
            role="radio" aria-checked="false" method-id="{{ $saldoMethod->code }}" name="paymentMethod"
            @click="paymentSelected = '{{ $saldoMethod->code }}'">
            <input type="radio" id="method_{{ $saldoMethod->id }}" name="paymentMethod" value="{{ $saldoMethod->code }}"
                class="peer hidden" />
            <label for="method_{{ $saldoMethod->id }}" class="absolute inset-0 z-20 cursor-pointer">
                <span class="sr-only">Pilih {{ $config->judul_web }} COIN</span>
            </label>

            <div class="relative z-10 flex w-full items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#ffc007] p-1 shadow-sm">
                        <img src="{{ ENV('COIN_STORE') }}" class="h-full w-full rounded-full object-cover" alt="Coin" width="45"
                            height="40" />
                    </div>
                    <div class="min-w-0">
                        <span class="block truncate font-bjcredits text-xs font-bold text-white sm:text-sm"
                            id="headlessui-label-:riu:">{{ $config->judul_web }} COIN</span>
                        <span class="mt-0.5 block text-xxs font-semibold uppercase text-yellow-400">Saldo Member</span>
                        <p class="hargapembayaran mt-1 block text-xs font-bold text-white sm:text-sm" id="{{ $saldoMethod->code }}">Rp 0
                        </p>
                    </div>
                </div>
                <div class="flex aspect-square w-8 items-center">
                    <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                        <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
                        <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
                        <div
                            class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">
                            BEST PRICE</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div
            class="relative flex overflow-hidden rounded-xl border border-murky-500 bg-murky-700 p-3 text-left shadow-sm outline-none md:p-4">
            <div class="flex w-full items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#ffc007] p-1 shadow-sm">
                        <img src="{{ ENV('COIN_STORE') }}" class="h-full w-full rounded-full object-cover" alt="Coin" width="45" height="40" />
                    </div>
                    <div class="min-w-0">
                        <span class="block truncate text-xs font-bold text-white sm:text-sm"
                            id="headlessui-label-:riu:">{{ $config->judul_web }} COIN</span>
                        <span class="mt-0.5 block text-xxs font-semibold uppercase text-yellow-400">Login untuk memakai saldo</span>
                        <p class="mt-1 block text-xs font-bold text-white sm:text-sm" id="{{ $saldoMethod->code }}">Rp 0</p>
                    </div>
                </div>
                <div class="flex aspect-square w-8 items-center">
                    <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                        <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
                        <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
                        <div
                            class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">
                            BEST PRICE</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
