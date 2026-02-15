@if ($paginator->hasPages())
    <ul class="pagination pagination-tabs">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled">
                <span class="page-link fs-3">«</span>
            </li>
        @else
            <li class="page-item">
                <a href="{{ $paginator->previousPageUrl() }}" class="page-link fs-3" rel="prev">«</a>
            </li>
        @endif

        {{-- Pagination Elements --}}
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $startPage = max(1, $currentPage - 2);
            $endPage = min($lastPage, $currentPage + 2);
            
            // Adjust if we're near the beginning
            if ($currentPage <= 3) {
                $endPage = min($lastPage, 5);
            }
            
            // Adjust if we're near the end
            if ($currentPage >= $lastPage - 2) {
                $startPage = max(1, $lastPage - 4);
            }
        @endphp

        @if ($startPage > 1)
            <li class="page-item"><a href="{{ $paginator->url(1) }}" class="page-link fs-3">1</a></li>
            @if ($startPage > 2)
                <li class="page-item disabled"><span class="page-link fs-3">...</span></li>
            @endif
        @endif

        @for ($page = $startPage; $page <= $endPage; $page++)
            @if ($page == $currentPage)
                <li class="page-item active"><span class="page-link fs-3">{{ $page }}</span></li>
            @else
                <li class="page-item"><a href="{{ $paginator->url($page) }}" class="page-link fs-3">{{ $page }}</a></li>
            @endif
        @endfor

        @if ($endPage < $lastPage)
            @if ($endPage < $lastPage - 1)
                <li class="page-item disabled"><span class="page-link fs-3">...</span></li>
            @endif
            <li class="page-item"><a href="{{ $paginator->url($lastPage) }}" class="page-link fs-3">{{ $lastPage }}</a></li>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a href="{{ $paginator->nextPageUrl() }}" class="page-link fs-3" rel="next">»</a>
            </li>
        @else
            <li class="page-item disabled">
                <span class="page-link fs-3">»</span>
            </li>
        @endif
    </ul>
@endif