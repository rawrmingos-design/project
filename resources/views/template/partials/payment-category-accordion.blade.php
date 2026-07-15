{{-- Accordion display style: renders collapsible section with category label as header --}}
{{-- Receives: $category (PaymentDisplayCategory), $methods (Collection of Method), $loop (parent loop variable) --}}

@if($methods->isNotEmpty())
    @php
        $accordionId = 'cat_' . $category->id;
        $containerId = 'container_cat_' . $category->id;
        $logoId = 'logo_cat_' . $category->id;
    @endphp

    <div x-bind:class="{ 'border-yellow-400': selected == '{{ $accordionId }}', 'border-murky-600': selected != '{{ $accordionId }}' }"
        class="accordion-header w-full overflow-hidden rounded-xl border bg-murky-800 text-left text-sm font-medium text-white shadow-2xl duration-300 focus:outline-none"
        data-state="">
        <dt>
            {{-- Accordion toggle button --}}
            <button class="w-full disabled:opacity-75" type="button"
                @click="selected !== '{{ $accordionId }}' ? selected = '{{ $accordionId }}' : selected = null"
                aria-expanded="false"
                aria-controls="disclosure-panel-{{ $category->id }}">
                <div class="flex w-full items-center justify-between border-b border-murky-600">
                    <div class="flex flex-row items-center gap-1 rounded-br-md bg-[#ffc007] text-darkColor">
                        <div class="items-center justify-center flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16"
                            style="border-top-left-radius: 12px;">
                            @if($category->icon)
                                <i class="{{ $category->icon }} text-white"></i>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-4 w-4 text-white">
                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                        </div>
                        <span class="px-2 py-2 text-sm font-semibold leading-6 text-white sm:px-4" id="label-category-{{ $category->id }}">
                            {{ $category->label }}
                        </span>
                    </div>
                    <span class="mr-4 flex h-7 items-center text-yellow-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                            class="h-6 w-6 transform duration-300"
                            x-bind:class="selected == '{{ $accordionId }}' ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                        </svg>
                    </span>
                </div>
            </button>

            {{-- Collapsible content --}}
            <div class="relative overflow-hidden transition-all max-h-0 duration-700"
                x-ref="{{ $containerId }}"
                x-bind:style="selected == '{{ $accordionId }}' ? 'max-height: ' + $refs.{{ $containerId }}.scrollHeight + 'px' : 'max-height: 0'">
                <div class="p-4 text-sm text-murky-300 sm:p-6"
                    id="disclosure-panel-{{ $category->id }}">
                    <div role="radiogroup" aria-labelledby="label-category-{{ $category->id }}">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3"
                            role="none">
                            @foreach($methods as $p)
                                @include('template.partials.payment-method-item', ['method' => $p])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Logo preview strip (visible when collapsed) --}}
            <div class="relative overflow-hidden transition-all max-h-0 w-full border-t border-murky-600 bg-murky-700"
                x-ref="{{ $logoId }}"
                x-bind:style="selected == '{{ $accordionId }}' ? 'max-height: 0' : 'max-height: 36px'"
                x-bind:class="selected == '{{ $accordionId }}' ? 'px-0 py-0' : 'px-4 py-2'">
                <div class="flex justify-end gap-x-2">
                    @foreach($methods as $p)
                        <div class="relative aspect-[6/2] w-10 rounded bg-white px-1 py-0.5">
                            <x-optimized-image :src="$p->image_url" profile="payment_logo"
                                alt="{{ $p->name }}" sizes="160px"
                                class="object-scale-down"
                                style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                        </div>
                    @endforeach
                </div>
            </div>
        </dt>
    </div>
@endif
