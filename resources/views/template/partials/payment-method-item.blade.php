{{-- Payment method item partial --}}
{{-- Receives: $method (Method model instance) --}}
<div x-bind:class="{ 'border-yellow-400 bg-murky-800 ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800': paymentSelected === '{{ $method->code }}', 'border-murky-500 bg-murky-700': paymentSelected !== '{{ $method->code }}' }"
    method-id="{{ $method->code }}"
    class="method-list payment-method group relative flex cursor-pointer overflow-hidden rounded-xl border p-3 text-left shadow-sm outline-none duration-300 ease-in-out hover:border-yellow-400 hover:bg-murky-800 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 md:p-4"
    id="radio-group-{{ $method->code }}" role="radio" aria-checked="false"
    name="paymentMethod" value="{{ $method->code }}" tabindex="0"
    aria-labelledby="label-{{ $method->code }}"
    aria-describedby="description-{{ $method->code }}"
    @click="paymentSelected = '{{ $method->code }}'">
    <input type="radio" id="method_{{ $method->id }}" name="paymentMethod"
        value="{{ $method->code }}" class="peer hidden" />
    <label for="method_{{ $method->id }}" class="absolute inset-0 z-20 cursor-pointer">
        <span class="sr-only">Pilih {{ $method->name }}</span>
    </label>

    <span class="relative z-10 flex w-full flex-col gap-3">
        <span class="flex items-start justify-between gap-3">
            <span class="min-w-0">
                <span class="block truncate text-xs font-bold text-white sm:text-sm" id="label-{{ $method->code }}">
                    {{ $method->name }}
                </span>
                <span class="mt-1 block text-xxs text-murky-200" id="description-{{ $method->code }}">
                    {{ $method->keterangan }}
                </span>
            </span>
            <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full border border-yellow-400 text-yellow-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-3 w-3">
                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                </svg>
            </span>
        </span>

        <span class="flex w-full items-end justify-between gap-3 border-t border-dashed border-murky-500 pt-3">
            <span>
                <span class="block text-xxs font-semibold uppercase text-yellow-400">Total Bayar</span>
                <span class="hargapembayaran mt-0.5 block text-xs font-bold leading-4 text-white sm:text-sm" id="{{ $method->code }}"></span>
            </span>
            <span class="relative aspect-[6/2] w-14 flex-shrink-0 rounded-md bg-white px-2 py-1 shadow-sm sm:w-16">
                <x-optimized-image :src="$method->image_url"
                    profile="payment_logo" alt="{{ $method->name }}"
                    sizes="160px"
                    x-bind:class="{ 'grayscale-0': paymentSelected === '{{ $method->code }}', 'grayscale': paymentSelected !== '{{ $method->code }}' }"
                    class="object-scale-down grayscale-0"
                    style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
            </span>
        </span>
    </span>
</div>
