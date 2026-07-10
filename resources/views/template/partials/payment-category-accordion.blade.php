{{-- Accordion display style: renders collapsible section with category label as header --}}
{{-- Receives: $category (PaymentDisplayCategory), $methods (Collection of Method), $loop (parent loop variable) --}}

@if($methods->isNotEmpty())
    @php
        $accordionId = 'cat_' . $category->id;
        $containerId = 'container_cat_' . $category->id;
        $logoId = 'logo_cat_' . $category->id;
    @endphp

    <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header"
        data-state="">
        <dt>
            {{-- Accordion toggle button --}}
            <button class="w-full disabled:opacity-75" type="button"
                @click="selected !== '{{ $accordionId }}' ? selected = '{{ $accordionId }}' : selected = null"
                aria-expanded="false"
                aria-controls="disclosure-panel-{{ $category->id }}">
                <div class="flex w-full justify-between px-4 py-2">
                    <span class="transform text-base font-medium leading-7 duration-300">
                        <div class="flex items-center gap-2">
                            @if($category->icon)
                                <i class="{{ $category->icon }}"></i>
                            @endif
                            {{ $category->label }}
                        </div>
                    </span>
                    <span class="ml-6 flex h-7 items-center">
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
                <div class="px-4 pt-2 pb-4 text-sm text-murky-300"
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
            <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300"
                x-ref="{{ $logoId }}"
                x-bind:style="selected == '{{ $accordionId }}' ? 'max-height: 0' : 'max-height: 30px'"
                x-bind:class="selected == '{{ $accordionId }}' ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                <div class="flex justify-end gap-x-2">
                    @foreach($methods as $p)
                        <div class="relative aspect-[6/2] w-10">
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
