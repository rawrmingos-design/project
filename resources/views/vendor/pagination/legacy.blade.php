{{-- Pagination untuk theme LEGACY (`public_theme = default`).

     Kenapa view ini ada: theme legacy TIDAK memuat utility Tailwind lengkap.
     View pagination bawaan Laravel (`pagination::tailwind`) memakai ~20 kelas
     yang tidak ada di stylesheet legacy (text-gray-700, border-gray-300,
     bg-gray-200, rounded-l-md, dst). Akibatnya tombol halaman tampil sebagai
     kotak putih kosong: `bg-white` kebetulan terdefinisi, sedangkan warnanya
     tidak — teks mewarisi `text-white` dari <body> sehingga putih di atas
     putih. Hanya terlihat setelah di-hover karena ada rule global
     `a:hover { background-color: var(--warna_3); }`.

     View ini hanya memakai kelas yang didefinisikan di
     `public/assets/css/legacy-pagination.css`, jadi tampilannya tidak lagi
     bergantung pada utility Tailwind yang bisa ter-purge.

     Permintaan client: background transparan, border tetap, teks terbaca. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="legacy-pagination">
        {{-- Info ringkas: hanya tampil di layar lebar --}}
        <p class="legacy-pagination__summary">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span>{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span>{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            {!! __('of') !!}
            <span>{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </p>

        <ul class="legacy-pagination__list">
            {{-- Previous --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="legacy-pagination__item is-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="legacy-pagination__icon">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="legacy-pagination__label">@lang('pagination.previous')</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="legacy-pagination__item" aria-label="@lang('pagination.previous')">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="legacy-pagination__icon">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="legacy-pagination__label">@lang('pagination.previous')</span>
                    </a>
                @endif
            </li>

            {{-- Nomor halaman (jendela + pemisah "..." sudah dihitung paginator) --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="legacy-pagination__item is-gap" aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="legacy-pagination__item is-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="legacy-pagination__item" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="legacy-pagination__item" aria-label="@lang('pagination.next')">
                        <span class="legacy-pagination__label">@lang('pagination.next')</span>
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="legacy-pagination__icon">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span class="legacy-pagination__item is-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                        <span class="legacy-pagination__label">@lang('pagination.next')</span>
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="legacy-pagination__icon">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
