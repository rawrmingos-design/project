<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    @for ($i = 0; $i < 4; $i++)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-start justify-between gap-4">
                <div class="w-full space-y-3">
                    <div class="h-3 w-28 animate-pulse rounded-full bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-7 w-36 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-3 w-44 animate-pulse rounded-full bg-gray-100 dark:bg-gray-800"></div>
                </div>
                <div class="h-9 w-9 shrink-0 animate-pulse rounded-full bg-gray-100 dark:bg-gray-800"></div>
            </div>
        </div>
    @endfor
</div>
