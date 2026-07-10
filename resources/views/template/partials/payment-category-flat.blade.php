{{-- Flat display style: renders category label and methods without collapsible wrapper --}}
{{-- Receives: $category (PaymentDisplayCategory), $methods (Collection of Method) --}}

@if($methods->isNotEmpty())
    {{-- Category header --}}
    <div class="flex items-center gap-2 px-1 pb-2">
        @if($category->icon)
            <i class="{{ $category->icon }} text-murky-700 text-base"></i>
        @endif
        <span class="text-sm font-semibold text-murky-700">{{ $category->label }}</span>
    </div>

    {{-- Method items rendered directly (no collapsible wrapper) --}}
    @foreach($methods as $p)
        <div x-bind:class="{ 'bg-white bj-shadow': paymentSelected === '{{ $p->code }}', 'bg-murky-200': paymentSelected !== '{{ $p->code }}' }"
            class="relative flex cursor-pointer method-list rounded-xl border border-transparent bg-murky-200 p-4 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out"
            role="radio" aria-checked="false" method-id="{{ $p->code }}" name="paymentMethod"
            @click="paymentSelected = '{{ $p->code }}'">
            <div class="flex items-center gap-2 max-w-xs">
                <input type="radio" id="method_{{ $p->id }}" name="paymentMethod" value="{{ $p->code }}"
                    class="peer hidden" />
                <label for="method_{{ $p->id }}"></label>
                <x-optimized-image :src="$p->image_url" profile="payment_logo" :alt="$p->name" sizes="55px"
                    width="55" height="40" />
                <div>
                    <span class="block font-bjcredits text-xs font-semibold text-murky-800 sm:text-sm">{{ $p->name }}</span>
                    <p class="block text-xxs text-murky-800 sm:text-xs hargapembayaran" id="{{ $p->code }}">Rp 0</p>
                </div>
            </div>
        </div>
    @endforeach
@endif
