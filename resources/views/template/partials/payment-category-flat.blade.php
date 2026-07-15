{{-- Flat display style: renders category label and methods as an always-open section --}}
{{-- Receives: $category (PaymentDisplayCategory), $methods (Collection of Method) --}}

@if($methods->isNotEmpty())
    <div class="w-full overflow-hidden rounded-xl border border-murky-600 bg-murky-800 shadow-2xl">
        <div class="flex border-b border-murky-600">
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
                <h4 class="px-2 py-2 text-sm font-semibold leading-6 text-white sm:px-4" id="label-category-{{ $category->id }}">
                    {{ $category->label }}
                </h4>
            </div>
        </div>

        <div class="p-4 text-sm text-murky-300 sm:p-6">
            <div role="radiogroup" aria-labelledby="label-category-{{ $category->id }}">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none">
                    @foreach($methods as $p)
                        @include('template.partials.payment-method-item', ['method' => $p])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
