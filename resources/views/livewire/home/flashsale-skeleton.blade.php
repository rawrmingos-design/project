{{-- Flashsale Skeleton Placeholder --}}
<div class="container">
    <div class="rounded-2xl bg-muted/50">
        <div class="px-4 pb-3 pt-4">
            <h3 class="flex items-center space-x-4 text-foreground">
                <div class="text-lg font-semibold uppercase leading-relaxed tracking-wider flex items-center">
                    <div class="w-6 h-6 bg-gray-700 animate-pulse rounded mr-2"></div>
                    <div class="h-6 w-32 bg-gray-700 animate-pulse rounded"></div>
                </div>
                <div class="flex items-center gap-1 text-sm capitalize ml-auto">
                    <div class="h-8 w-24 bg-gray-700 animate-pulse rounded"></div>
                </div>
            </h3>
            <p class="pl-6 text-xs text-foreground">
                <div class="h-4 w-48 bg-gray-700 animate-pulse rounded mt-1"></div>
            </p>
        </div>
        <div class="relative flex h-full w-full flex-col items-center justify-center overflow-hidden pb-2 pt-1">
            <div class="group flex overflow-hidden p-2 gap-4 container">
                @for($i = 0; $i < 6; $i++)
                <div class="flex-shrink-0 w-48">
                    <div class="item relative bg-gray-800 rounded-lg animate-pulse">
                        <div class="w-full h-48 bg-gray-700 rounded-t-lg"></div>
                        <div class="p-3 space-y-2">
                            <div class="h-4 bg-gray-700 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-700 rounded w-full"></div>
                            <div class="h-3 bg-gray-700 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
                @endfor
            </div>
        </div>
    </div>
</div>
