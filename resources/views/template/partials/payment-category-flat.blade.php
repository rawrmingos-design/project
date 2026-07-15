{{-- Flat display style: renders category label and methods as an always-open section --}}
{{-- Receives: $category (PaymentDisplayCategory), $methods (Collection of Method) --}}

@if($methods->isNotEmpty())
    <div class="flex w-full transform flex-col justify-between overflow-hidden rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none">
        <div class="flex w-full justify-between px-4 py-2">
            <span class="transform text-base font-medium leading-7 duration-300">
                <div class="flex items-center gap-2">
                    @if($category->icon)
                        <i class="{{ $category->icon }}"></i>
                    @endif
                    {{ $category->label }}
                </div>
            </span>
        </div>

        <div class="px-4 pt-2 pb-4 text-sm text-murky-300">
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
