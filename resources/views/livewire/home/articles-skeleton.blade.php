{{-- Articles Skeleton Placeholder --}}
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    {{-- Section Header Skeleton --}}
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <div class="h-8 w-64 bg-gray-700 animate-pulse rounded mb-2"></div>
            <div class="h-4 w-96 bg-gray-700 animate-pulse rounded"></div>
        </div>
        <div class="h-10 w-32 bg-gray-700 animate-pulse rounded-full"></div>
    </div>

    {{-- Grid Skeleton --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @for($i = 0; $i < 3; $i++)
        <div class="rounded-3xl bg-secondary-900 border border-white/5 overflow-hidden animate-pulse">
            {{-- Image --}}
            <div class="aspect-[16/9] w-full bg-gray-700"></div>
            
            {{-- Content --}}
            <div class="p-6 space-y-3">
                <div class="h-6 bg-gray-700 rounded w-3/4"></div>
                <div class="h-4 bg-gray-700 rounded w-full"></div>
                <div class="h-4 bg-gray-700 rounded w-5/6"></div>
                <div class="h-4 bg-gray-700 rounded w-1/3 mt-4"></div>
            </div>
        </div>
        @endfor
    </div>
</div>
