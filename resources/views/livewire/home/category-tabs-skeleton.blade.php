
<div class="container mx-auto mt-4">
    <div class="flex items-center gap-2 mb-8">
        <div class="tabs-container flex overflow-x-auto scroll-smooth gap-2">
            @for($i = 0; $i < 5; $i++)
            <div class="h-10 w-32 bg-gray-700 animate-pulse rounded-xl"></div>
            @endfor
        </div>
    </div>
    
    <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-8 lg:grid-cols-5 xl:grid-cols-6">
        @for($i = 0; $i < 18; $i++)
        <div class="bg-muted rounded-xl overflow-hidden animate-pulse">
            <div class="w-full aspect-square bg-gray-700"></div>
            <div class="p-3 space-y-2">
                <div class="h-4 bg-gray-700 rounded w-3/4"></div>
                <div class="h-3 bg-gray-700 rounded w-1/2"></div>
            </div>
        </div>
        @endfor
    </div>
</div>
