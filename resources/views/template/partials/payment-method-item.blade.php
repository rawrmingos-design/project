{{-- Payment method item partial --}}
{{-- Receives: $method (Method model instance) --}}
<div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{ $method->code }}', 'bg-murky-200': paymentSelected !== '{{ $method->code }}' }"
    method-id="{{ $method->code }}"
    class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out"
    id="radio-group-{{ $method->code }}" role="radio" aria-checked="false"
    name="paymentMethod" value="{{ $method->code }}" tabindex="0"
    aria-labelledby="label-{{ $method->code }}"
    aria-describedby="description-{{ $method->code }}"
    @click="paymentSelected = '{{ $method->code }}'">
    <input type="radio" id="method_{{ $method->id }}" name="paymentMethod"
        value="{{ $method->code }}" class="peer hidden" />
    <label for="method_{{ $method->id }}"></label>
    <span class="flex w-full">
        <span class="flex w-full flex-col justify-between">
            <div>
                <span class="block text-xs font-semibold text-murky-800">
                    {{ $method->name }}
                </span>
                <span class="mt-0 flex items-center text-xxs text-murky-600">{{ $method->keterangan }}</span>
                <hr>
            </div>
            <div class="flex w-full items-center justify-between">
                <div class="mt-1">
                    <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800 text-dark.meltihhh">
                        <h6 class="hargapembayaran" id="{{ $method->code }}"></h6>
                    </div>
                </div>
                <div class="relative aspect-[6/2] w-10">
                    <x-optimized-image :src="$method->image_url"
                        profile="payment_logo" alt="{{ $method->name }}"
                        sizes="160px"
                        x-bind:class="{ 'grayscale-0': paymentSelected === '{{ $method->code }}', 'grayscale': paymentSelected !== '{{ $method->code }}' }"
                        class="object-scale-down grayscale-0"
                        style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                </div>
            </div>
        </span>
    </span>
</div>
