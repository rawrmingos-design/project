<div>
<section id="mobile-game" class="relative mt-4 w-full overflow-hidden pb-6 md:pb-8 lg:pb-10 bg-secondary-950 ">
    <div class="container mx-auto">
        <div class="flex items-center gap-2">
            <div class="block lg:hidden"><button id="scrollLeft" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-card text-primary-foreground hover:bg-primary/90 h-9 w-9"
                type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left h-4 w-4"><path d="m15 18-6-6 6-6"></path></svg></button></div>
            <div class="tabs-container flex overflow-x-auto scroll-smooth">
                @foreach ($categoryTypes as $type)
                <button
                    class="tab-button whitespace-nowrap melpazoom rounded-xl border border-secondary-600 px-4 py-2 text-sm text-text-color shadow-xl outline-none duration-300 hover:bg-secondary-500 focus:bg-secondary-500 focus-visible:bg-secondary-500 {{ $loop->first ? 'active' : 'bg-transparent' }}"
                    id="headlessui-tabs-tab-:{{ $type->slug }}:" role="tab" type="button" aria-selected="false"
                    tabindex="0" data-headlessui-state="false"
                    aria-controls="headlessui-tabs-panel-:{{ $type->slug }}:"
                    data-tabs-toggle="#headlessui-tabs-panel-{{ $type->slug }}"
                    @if($loop->first) style="background-color: var(--warna_3);" @endif>
                    {{ $type->name }}
                </button>
                @endforeach
            </div>
            <div class="block lg:hidden"><button id="scrollRight"  class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-card text-primary-foreground hover:bg-primary/90 h-9 w-9"
                type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right h-4 w-4"><path d="m9 18 6-6-6-6"></path></svg></button></div>
        </div>
        
        <div class="my-8">
            @include('template.id.components.dynamic_tabs')
        </div>
    </div>
</section>
</div>
