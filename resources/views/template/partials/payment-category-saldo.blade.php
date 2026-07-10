{{-- SALDO category partial --}}
{{-- Receives: $category (PaymentDisplayCategory), $methods (collection of Method) --}}
@php
    $saldoMethod = $methods->first();
@endphp

@if($saldoMethod)
    @if(Auth::check())
        <div x-bind:class="{ 'bg-white bj-shadow': paymentSelected === '{{ $saldoMethod->code }}', 'bg-murky-200': paymentSelected !== '{{ $saldoMethod->code }}' }"
            class="relative flex cursor-pointer method-list rounded-xl border border-transparent bg-murky-200 p-3 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out"
            role="radio" aria-checked="false" method-id="{{ $saldoMethod->code }}" name="paymentMethod"
            @click="paymentSelected = '{{ $saldoMethod->code }}'">
            <div class="flex items-center gap-2 max-w-xs">
                <input type="radio" id="method_{{ $saldoMethod->id }}" name="paymentMethod" value="{{ $saldoMethod->code }}"
                    class="peer hidden" />
                <label for="method_{{ $saldoMethod->id }}"></label>
                <img src="{{ ENV('COIN_STORE') }}" class="object-cover rounded-full" alt="Coin" width="45"
                    height="40" />
                <div>
                    <span class="block font-bjcredits text-xs font-semibold text-murky-800 sm:text-sm"
                        id="headlessui-label-:riu:">{{ $config->judul_web }} COIN</span>
                    <p class="block text-xxs text-murky-800 sm:text-xs hargapembayaran" id="{{ $saldoMethod->code }}">Rp 0
                    </p>
                </div>
            </div>
            <div class="max-w-xs">
                <div class="relative text-sm font-semibold text-murky-800 sm:text-base">
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
    @else
        <div
            class="relative flex cursor-pointer rounded-xl border border-transparent bg-murky-200 p-3 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out">
            <div class="flex items-center gap-2 max-w-xs">
                <img src="{{ ENV('COIN_STORE') }}" class="rounded-full" alt="Coin" width="45" height="40" />
                <div>
                    <span class="block text-xs font-semibold text-murky-800 sm:text-sm"
                        id="headlessui-label-:riu:">{{ $config->judul_web }} COIN</span>
                    <p class="block text-xxs text-murky-800 sm:text-xs" id="{{ $saldoMethod->code }}">Rp 0</p>
                </div>
            </div>
            <div class="max-w-xs">
                <div class="relative text-sm font-semibold text-murky-800 sm:text-base">
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
    @endif
@endif
