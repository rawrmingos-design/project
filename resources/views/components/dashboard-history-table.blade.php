@props([
    'rows' => [],
    'emptyMessage' => 'Belum ada data.',
    'sectionClass' => 'public-dashboard-table public-dashboard-table--history',
    'tableClass' => 'public-dashboard-table__table',
])

@php
    $hasRows = false;

    if ($rows instanceof \Illuminate\Contracts\Pagination\Paginator || $rows instanceof \Illuminate\Support\Collection || is_array($rows)) {
        $hasRows = count($rows) > 0;
    }
@endphp

<section class="{{ trim((string) $sectionClass) }}">
    <div class="public-dashboard-table__shell">
        @if($hasRows)
            <table class="{{ trim((string) $tableClass) }}">
                @isset($head)
                    <thead>
                        {{ $head }}
                    </thead>
                @endisset
                <tbody>
                    {{ $slot }}
                </tbody>
            </table>
        @else
            <div class="public-dashboard-table__empty">{{ $emptyMessage }}</div>
        @endif
    </div>

    @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator && method_exists($rows, 'hasPages') && $rows->hasPages())
        <div class="public-affiliate-pagination">
            <span>Halaman {{ $rows->currentPage() }} dari {{ $rows->lastPage() }}</span>
            <div class="flex items-center gap-2">
                @if($rows->onFirstPage())
                    <span class="is-disabled">Sebelumnya</span>
                @else
                    <a href="{{ $rows->previousPageUrl() }}">Sebelumnya</a>
                @endif

                @if($rows->hasMorePages())
                    <a href="{{ $rows->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="is-disabled">Berikutnya</span>
                @endif
            </div>
        </div>
    @endif
</section>
