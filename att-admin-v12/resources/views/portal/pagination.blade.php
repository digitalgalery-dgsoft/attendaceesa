@if ($paginator->hasPages())
    <div class="portal-pagination-wrapper">
        <div class="portal-pagination-info">
            Menampilkan <span>{{ $paginator->firstItem() ?? 0 }}</span> &ndash; <span>{{ $paginator->lastItem() ?? 0 }}</span> dari <span>{{ number_format($paginator->total()) }}</span> total data
        </div>

        <div class="portal-pagination-controls">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-btn disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <i class="fa-solid fa-chevron-left"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn" aria-label="@lang('pagination.previous')">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="pagination-btn dots" aria-disabled="true">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-btn active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pagination-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn" aria-label="@lang('pagination.next')">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            @else
                <span class="pagination-btn disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <i class="fa-solid fa-chevron-right"></i>
                </span>
            @endif
        </div>
    </div>
@else
    <div class="portal-pagination-wrapper">
        <div class="portal-pagination-info">
            Menampilkan <span>{{ $paginator->firstItem() ?? 0 }}</span> &ndash; <span>{{ $paginator->lastItem() ?? 0 }}</span> dari <span>{{ number_format($paginator->total()) }}</span> total data
        </div>
    </div>
@endif
